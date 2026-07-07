<?php
declare(strict_types=1);

/**
 * 批次補建 Pinecone 向量（CLI / Plesk 排程）
 *
 * CLI：php scripts/vector_reindex.php --type=knowledge --limit=200
 * HTTP：scripts/vector_reindex.php?token=...（驗證見 vector_reindex_auth.php）
 *
 * 依賴：vendor/、vector_sync_helpers.php、VectorSearchService.php
 */

if (!function_exists('vector_reindex_bootstrap')) {
    /**
     * 載入 reindex 所需 autoload、.env、PDO、CRUD、向量服務
     */
    function vector_reindex_bootstrap(string $projectRoot): void
    {
        $autoload = $projectRoot . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }

        require_once $projectRoot . '/include/host.php';
        require_once $projectRoot . '/include/Conn.php';
        require_once $projectRoot . '/include/Function.php';
        require_once $projectRoot . '/include/common.php';
        require_once $projectRoot . '/include/crud_helpers.php';
        require_once $projectRoot . '/include/vector_sync_helpers.php';
        require_once $projectRoot . '/include/VectorSearchService.php';
    }
}

if (!function_exists('vector_reindex_sync_types')) {
    /**
     * 取得 sync=true 的類型清單
     *
     * @param string|null $onlyType 指定單一 type 時只回傳該項；不存在回 []
     * @return list<string>
     */
    function vector_reindex_sync_types(?string $onlyType = null): array
    {
        $types = [];
        foreach (vector_sync_type_config() as $type => $config) {
            if (!is_array($config) || empty($config['sync'])) {
                continue;
            }
            $types[] = (string)$type;
        }

        if ($onlyType !== null && $onlyType !== '') {
            $onlyType = strtolower(trim($onlyType));
            if (!in_array($onlyType, $types, true)) {
                return [];
            }

            return [$onlyType];
        }

        sort($types);

        return $types;
    }
}

if (!function_exists('vector_reindex_list_pkeys')) {
    /**
     * 列出某 type 待補建的主鍵（可篩選已上架、分頁）
     *
     * @return list<int>
     */
    function vector_reindex_list_pkeys(string $type, bool $onlyPublished = true, int $offset = 0, int $limit = 0): array
    {
        $config = vector_sync_type_config()[$type] ?? null;
        if (!is_array($config)) {
            return [];
        }

        $table = trim((string)($config['table'] ?? ''));
        $pk = trim((string)($config['pk'] ?? 'PKey'));
        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)
            || !preg_match('/^[a-zA-Z0-9_]+$/', $pk)) {
            return [];
        }

        $sql = "SELECT `{$pk}` AS pk FROM `{$table}`";
        $params = [];
        vector_sync_append_published_sql($sql, $params, $table, $config, $onlyPublished);
        $sql .= " ORDER BY `{$pk}` ASC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset);
        }

        if (!function_exists('crud_fetch_all')) {
            return [];
        }

        $rows = crud_fetch_all($sql, $params);
        $pkeys = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['pk'] ?? 0);
            if ($id > 0) {
                $pkeys[] = $id;
            }
        }

        return $pkeys;
    }
}

if (!function_exists('vector_reindex_one')) {
    /**
     * 補建單筆：upsert、delete（無內容）或 dry-run 預覽
     *
     * @return array{status: string, message: string} status 為 upsert|delete|skip|error
     */
    function vector_reindex_one(VectorSearchService $service, string $type, int $pkey, bool $dryRun = false): array
    {
        if ($pkey <= 0) {
            return ['status' => 'skip', 'message' => 'invalid pkey'];
        }

        $text = vector_sync_build_document_text($type, $pkey);
        if ($text === '') {
            if ($dryRun) {
                return ['status' => 'delete', 'message' => 'empty or unpublished'];
            }
            try {
                $service->deleteVector($type, $pkey);

                return ['status' => 'delete', 'message' => 'removed from index'];
            } catch (Throwable $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        if ($dryRun) {
            return ['status' => 'upsert', 'message' => 'chars=' . mb_strlen($text)];
        }

        try {
            $service->upsertVector($type, $pkey, $text, [
                'table' => (string)(vector_sync_type_config()[$type]['table'] ?? $type),
            ]);

            return ['status' => 'upsert', 'message' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

if (!function_exists('vector_reindex_execute')) {
    /**
     * 執行批次補建（所有 sync 類型或指定 type）
     *
     * @param array{
     *     type?: string|null,
     *     dry_run?: bool,
     *     only_published?: bool,
     *     offset?: int,
     *     limit?: int,
     *     sleep_ms?: int
     * } $options
     * @return array{success: bool, lines: list<string>, stats: array<string, int>, error?: string}
     */
    function vector_reindex_execute(array $options = []): array
    {
        $onlyType = isset($options['type']) ? trim((string)$options['type']) : null;
        $dryRun = !empty($options['dry_run']);
        $onlyPublished = !array_key_exists('only_published', $options) || !empty($options['only_published']);
        $offset = max(0, (int)($options['offset'] ?? 0));
        $limit = max(0, (int)($options['limit'] ?? 0));
        $sleepMs = max(0, (int)($options['sleep_ms'] ?? 200));

        $pinecone = vector_pinecone_api_key();
        $host = vector_pinecone_host();
        if (!vector_embedding_api_ready() || $pinecone === '' || $host === '') {
            $provider = vector_embedding_provider();
            $keyHint = $provider === 'gemini'
                ? 'GEMINI_API_KEY'
                : 'OPENAI_API_KEY';

            return [
                'success' => false,
                'lines'   => [],
                'stats'   => [],
                'error'   => '請在 .env 設定 ' . $keyHint . '、PINECONE_API_KEY、PINECONE_HOST'
                    . '（正式機通常為 private/.env，見 config/env.path.php）',
            ];
        }

        $types = vector_reindex_sync_types($onlyType !== '' ? $onlyType : null);
        if ($types === []) {
            return [
                'success' => false,
                'lines'   => [],
                'stats'   => [],
                'error'   => $onlyType !== null && $onlyType !== ''
                    ? "類型「{$onlyType}」不存在或未啟用 sync"
                    : '沒有可同步的類型，請檢查 config/vector_search_types.php',
            ];
        }

        try {
            $service = VectorSearchService::fromEnv();
        } catch (Throwable $e) {
            return [
                'success' => false,
                'lines'   => [],
                'stats'   => [],
                'error'   => 'VectorSearchService 初始化失敗：' . $e->getMessage(),
            ];
        }

        $lines = [];
        $stats = ['upsert' => 0, 'delete' => 0, 'skip' => 0, 'error' => 0, 'total' => 0];
        $mode = $dryRun ? '[DRY-RUN] ' : '';
        $lines[] = $mode . '開始批次補建向量';

        foreach ($types as $type) {
            $pkeys = vector_reindex_list_pkeys($type, $onlyPublished, $offset, $limit);
            $lines[] = sprintf('%s類型 %s：%d 筆', $mode, $type, count($pkeys));

            foreach ($pkeys as $pkey) {
                $stats['total']++;
                $result = vector_reindex_one($service, $type, $pkey, $dryRun);
                $status = (string)($result['status'] ?? 'error');
                if (isset($stats[$status])) {
                    $stats[$status]++;
                } else {
                    $stats['error']++;
                }

                if ($status === 'error') {
                    $lines[] = sprintf('  [ERROR] %s#%d — %s', $type, $pkey, (string)($result['message'] ?? ''));
                }

                if ($sleepMs > 0 && !$dryRun) {
                    usleep($sleepMs * 1000);
                }
            }
        }

        $lines[] = sprintf(
            '%s完成：upsert=%d, delete=%d, skip=%d, error=%d, total=%d',
            $mode,
            $stats['upsert'],
            $stats['delete'],
            $stats['skip'],
            $stats['error'],
            $stats['total']
        );

        return [
            'success' => $stats['error'] === 0,
            'lines'   => $lines,
            'stats'   => $stats,
        ];
    }
}

if (!function_exists('vector_reindex_parse_cli_args')) {
    /**
     * 解析 CLI 參數（--type=、--dry-run、--limit= 等）
     *
     * @param list<string> $argv 通常傳 $argv
     * @return array{type: ?string, dry_run: bool, only_published: bool, offset: int, limit: int, sleep_ms: int}
     */
    function vector_reindex_parse_cli_args(array $argv): array
    {
        $options = [
            'type'            => null,
            'dry_run'         => false,
            'only_published'  => true,
            'offset'          => 0,
            'limit'           => 0,
            'sleep_ms'        => 200,
        ];

        foreach (array_slice($argv, 1) as $arg) {
            if ($arg === '--dry-run') {
                $options['dry_run'] = true;
                continue;
            }
            if ($arg === '--all-published') {
                $options['only_published'] = true;
                continue;
            }
            if ($arg === '--include-unpublished') {
                $options['only_published'] = false;
                continue;
            }
            if (str_starts_with($arg, '--type=')) {
                $options['type'] = trim(substr($arg, 7));
                continue;
            }
            if (str_starts_with($arg, '--offset=')) {
                $options['offset'] = max(0, (int)substr($arg, 9));
                continue;
            }
            if (str_starts_with($arg, '--limit=')) {
                $options['limit'] = max(0, (int)substr($arg, 8));
                continue;
            }
            if (str_starts_with($arg, '--sleep-ms=')) {
                $options['sleep_ms'] = max(0, (int)substr($arg, 11));
            }
        }

        return $options;
    }
}
