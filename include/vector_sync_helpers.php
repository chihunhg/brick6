<?php
declare(strict_types=1);

/**
 * 後台 CRUD → Pinecone 向量同步
 *
 * 設定：config/vector_search_types.php（type → MySQL 表對照，sync=true 才自動同步）
 * 環境：VECTOR_SYNC_ENABLED=1、GEMINI_API_KEY、PINECONE_API_KEY、PINECONE_HOST
 *
 * 掛點（include/crud_helpers.php）：
 *   vector_sync_after_master_save($table, $pkey)   — 主檔新增/修改
 *   vector_sync_after_msg_save($msgTable, $pkey)   — 內文子表儲存
 *   vector_sync_after_master_delete($table, $pkeys) — 主檔刪除
 *
 * 手動補建：scripts/vector_reindex.php（CLI 或 Plesk HTTP）
 */

require_once __DIR__ . '/vector_embedding_helpers.php';

if (!function_exists('vector_sync_enabled')) {
    /**
     * 是否啟用 CRUD 自動同步向量
     *
     * 需 VECTOR_SYNC_ENABLED=1 且 Embedding + Pinecone 金鑰齊全
     */
    function vector_sync_enabled(): bool
    {
        $raw = strtolower(trim((string)($_ENV['VECTOR_SYNC_ENABLED'] ?? getenv('VECTOR_SYNC_ENABLED') ?: '0')));
        if (in_array($raw, ['0', 'false', 'no', 'off', 'disable', 'disabled'], true)) {
            return false;
        }

        $pinecone = vector_pinecone_api_key();
        $host = vector_pinecone_host();

        return vector_embedding_api_ready() && $pinecone !== '' && $host !== '';
    }
}

if (!function_exists('vector_sync_type_config')) {
    /**
     * 載入 config/vector_search_types.php（靜態快取）
     *
     * @return array<string, array<string, mixed>>
     */
    function vector_sync_type_config(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $path = dirname(__DIR__) . '/config/vector_search_types.php';
        if (!is_file($path)) {
            $cache = [];

            return $cache;
        }

        $loaded = require $path;
        $cache = is_array($loaded) ? $loaded : [];

        return $cache;
    }
}

if (!function_exists('vector_sync_resolve_type_by_table')) {
    /**
     * 由 MySQL 主檔表名反查 Pinecone metadata.type（例：news → news）
     *
     * @return string|null 無 sync 類型時 null
     */
    function vector_sync_resolve_type_by_table(string $table): ?string
    {
        $table = trim($table);
        if ($table === '') {
            return null;
        }

        $candidates = [];
        foreach (vector_sync_type_config() as $type => $config) {
            if (!is_array($config)) {
                continue;
            }
            if (empty($config['sync'])) {
                continue;
            }
            if ((string)($config['table'] ?? '') === $table) {
                $candidates[] = (string)$type;
            }
        }

        if ($candidates === []) {
            return null;
        }

        foreach ($candidates as $type) {
            if ($type === $table) {
                return $type;
            }
        }

        return $candidates[0];
    }
}

if (!function_exists('vector_sync_resolve_type_by_msg_table')) {
    /**
     * 由內文子表名反查 type（例：news_msg → news）
     */
    function vector_sync_resolve_type_by_msg_table(string $msgTable): ?string
    {
        $msgTable = trim($msgTable);
        if ($msgTable === '') {
            return null;
        }

        foreach (vector_sync_type_config() as $type => $config) {
            if (!is_array($config) || empty($config['sync'])) {
                continue;
            }
            if ((string)($config['msg_table'] ?? '') === $msgTable) {
                return (string)$type;
            }
        }

        return null;
    }
}

if (!function_exists('vector_sync_service')) {
    /**
     * 取得 VectorSearchService 單例（未啟用或初始化失敗回 null）
     */
    function vector_sync_service(): ?VectorSearchService
    {
        static $service = null;
        static $loaded = false;

        if ($loaded) {
            return $service instanceof VectorSearchService ? $service : null;
        }
        $loaded = true;

        if (!vector_sync_enabled()) {
            return null;
        }

        require_once __DIR__ . '/VectorSearchService.php';

        try {
            $service = VectorSearchService::fromEnv();
        } catch (Throwable $e) {
            error_log('[vector_sync] 初始化失敗：' . $e->getMessage());
            $service = null;
        }

        return $service;
    }
}

if (!function_exists('vector_sync_plain_text')) {
    /**
     * HTML 內文轉純文字（供 Embedding 用）
     */
    function vector_sync_plain_text(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }
}

if (!function_exists('vector_sync_table_has_upload')) {
    /** 資料表是否有 Upload 欄位（上架/下架） */
    function vector_sync_table_has_upload(string $table): bool
    {
        return function_exists('crud_table_has_column') && crud_table_has_column($table, 'Upload');
    }
}

if (!function_exists('vector_sync_uses_publish_window')) {
    /**
     * 是否以 OpenDate/EndDate 判斷上架（news 等無 Upload 欄位的主檔）
     */
    function vector_sync_uses_publish_window(string $table): bool
    {
        if (vector_sync_table_has_upload($table)) {
            return false;
        }

        return function_exists('crud_table_has_column')
            && crud_table_has_column($table, 'OpenDate')
            && crud_table_has_column($table, 'EndDate');
    }
}

if (!function_exists('vector_sync_publish_window_params')) {
    /**
     * 刊登區間 SQL 參數（同前台 view_news 邏輯）
     *
     * @return array{vsPubOpen: string, vsPubEnd: string}
     */
    function vector_sync_publish_window_params(): array
    {
        return [
            'vsPubOpen' => date('Y-m-d H:i'),
            'vsPubEnd'  => date('Y-m-d') . ' 23:59:59',
        ];
    }
}

if (!function_exists('vector_sync_append_published_sql')) {
    /**
     * 為 reindex 列表 SQL 附加「已上架」條件
     *
     * Upload=Yes 或 OpenDate/EndDate 刊登區間
     */
    function vector_sync_append_published_sql(
        string &$sql,
        array &$params,
        string $table,
        array $config,
        bool $onlyPublished
    ): void {
        if (!$onlyPublished || empty($config['require_upload_yes'])) {
            return;
        }

        if (vector_sync_table_has_upload($table)) {
            $sql .= " WHERE `Upload` = 'Yes'";

            return;
        }

        if (vector_sync_uses_publish_window($table)) {
            $params = array_merge($params, vector_sync_publish_window_params());
            $sql .= ' WHERE `OpenDate` <= :vsPubOpen AND `EndDate` >= :vsPubEnd';
        }
    }
}

if (!function_exists('vector_sync_row_is_published')) {
    /**
     * 單筆主檔是否視為已上架（reindex / build_document_text 用）
     */
    function vector_sync_row_is_published(array $row, string $table, array $config): bool
    {
        if (empty($config['require_upload_yes'])) {
            return true;
        }

        if (vector_sync_table_has_upload($table)) {
            return strtoupper(trim((string)($row['Upload'] ?? 'YES'))) === 'YES';
        }

        if (vector_sync_uses_publish_window($table)) {
            $params = vector_sync_publish_window_params();
            $openTs = strtotime((string)($row['OpenDate'] ?? ''));
            $endTs = strtotime((string)($row['EndDate'] ?? ''));
            $pubOpenTs = strtotime($params['vsPubOpen']);
            $pubEndTs = strtotime($params['vsPubEnd']);

            if ($openTs !== false && $pubOpenTs !== false && $openTs > $pubOpenTs) {
                return false;
            }
            if ($endTs !== false && $pubEndTs !== false && $endTs < $pubEndTs) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('vector_sync_build_document_text')) {
    /**
     * 組合待 Embedding 的文件文字（主檔欄位 + msg 內文，最多 8000 字）
     *
     * 未上架或無內容回傳空字串
     */
    function vector_sync_build_document_text(string $type, int $pkey): string
    {
        $config = vector_sync_type_config()[$type] ?? null;
        if (!is_array($config) || $pkey <= 0) {
            return '';
        }

        $table = (string)($config['table'] ?? '');
        $pk = (string)($config['pk'] ?? 'PKey');
        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)
            || !preg_match('/^[a-zA-Z0-9_]+$/', $pk)) {
            return '';
        }

        if (!function_exists('crud_fetch_one')) {
            return '';
        }

        $row = crud_fetch_one("SELECT * FROM `{$table}` WHERE `{$pk}` = :pk LIMIT 1", ['pk' => $pkey]);
        if ($row === null) {
            return '';
        }

        if (!vector_sync_row_is_published($row, $table, $config)) {
            return '';
        }

        $parts = [];
        foreach ((array)($config['text_columns'] ?? ['strName']) as $col) {
            $col = trim((string)$col);
            if ($col === '' || !isset($row[$col])) {
                continue;
            }
            $value = trim((string)$row[$col]);
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        $msgTable = trim((string)($config['msg_table'] ?? ''));
        $msgFk = trim((string)($config['msg_fk'] ?? ''));
        if ($msgTable !== '' && $msgFk !== ''
            && preg_match('/^[a-zA-Z0-9_]+$/', $msgTable)
            && preg_match('/^[a-zA-Z0-9_]+$/', $msgFk)
            && function_exists('crud_fetch_all')) {
            $msgRows = crud_fetch_all(
                "SELECT Contents FROM `{$msgTable}` WHERE `{$msgFk}` = :fk ORDER BY Sort ASC",
                ['fk' => $pkey]
            );
            foreach ($msgRows as $msgRow) {
                if (!is_array($msgRow)) {
                    continue;
                }
                $plain = vector_sync_plain_text((string)($msgRow['Contents'] ?? ''));
                if ($plain !== '') {
                    $parts[] = $plain;
                }
            }
        }

        $text = implode("\n", array_unique(array_filter($parts, static fn (string $s): bool => $s !== '')));

        return mb_substr($text, 0, 8000);
    }
}

if (!function_exists('vector_sync_upsert')) {
    /**
     * 同步單筆至 Pinecone（無內容或未上架則 delete）
     *
     * 向量 ID 格式：{type}_{pkey}（例：knowledge_123）
     */
    function vector_sync_upsert(string $type, int $pkey): void
    {
        if (!vector_sync_enabled() || $pkey <= 0) {
            return;
        }

        $service = vector_sync_service();
        if ($service === null) {
            return;
        }

        try {
            $text = vector_sync_build_document_text($type, $pkey);
            if ($text === '') {
                $service->deleteVector($type, $pkey);

                return;
            }

            $service->upsertVector($type, $pkey, $text, [
                'table' => (string)(vector_sync_type_config()[$type]['table'] ?? $type),
            ]);
        } catch (Throwable $e) {
            error_log('[vector_sync] upsert 失敗 (' . $type . '#' . $pkey . ')：' . $e->getMessage());
        }
    }
}

if (!function_exists('vector_sync_delete')) {
    /**
     * 自 Pinecone 刪除多筆向量
     *
     * @param list<int> $pkeys
     */
    function vector_sync_delete(string $type, array $pkeys): void
    {
        if (!vector_sync_enabled() || $pkeys === []) {
            return;
        }

        $service = vector_sync_service();
        if ($service === null) {
            return;
        }

        foreach ($pkeys as $pkey) {
            $pkey = (int)$pkey;
            if ($pkey <= 0) {
                continue;
            }
            try {
                $service->deleteVector($type, $pkey);
            } catch (Throwable $e) {
                error_log('[vector_sync] delete 失敗 (' . $type . '#' . $pkey . ')：' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('vector_sync_after_master_save')) {
    /** CRUD 主檔儲存後呼叫（crud_upsert_master 掛點） */
    function vector_sync_after_master_save(string $table, int $pkey): void
    {
        $type = vector_sync_resolve_type_by_table($table);
        if ($type === null || $pkey <= 0) {
            return;
        }

        vector_sync_upsert($type, $pkey);
    }
}

if (!function_exists('vector_sync_after_msg_save')) {
    /** CRUD 內文子表儲存後呼叫（crud_save_msg_blocks 掛點） */
    function vector_sync_after_msg_save(string $msgTable, int $parentPkey): void
    {
        $type = vector_sync_resolve_type_by_msg_table($msgTable);
        if ($type === null || $parentPkey <= 0) {
            return;
        }

        vector_sync_upsert($type, $parentPkey);
    }
}

if (!function_exists('vector_sync_after_master_delete')) {
    /**
     * CRUD 主檔刪除後呼叫（crud_handle_list_delete 掛點）
     *
     * @param list<int> $pkeys
     */
    function vector_sync_after_master_delete(string $table, array $pkeys): void
    {
        $type = vector_sync_resolve_type_by_table($table);
        if ($type === null || $pkeys === []) {
            return;
        }

        vector_sync_delete($type, $pkeys);
    }
}
