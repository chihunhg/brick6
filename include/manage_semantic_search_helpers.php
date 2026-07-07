<?php
declare(strict_types=1);

/**
 * 後台列表智慧語意搜尋（Gemini 擴詞 + 可選向量相似度）
 *
 * 環境變數：
 *   MANAGE_SEMANTIC_SEARCH=1        總開關
 *   MANAGE_SEMANTIC_USE_EMBEDDING=1 列表向量相似度（預設 0，以擴詞+字面為主）
 *   GEMINI_API_KEY
 *   config/manage_search_synonyms.php 同義詞
 *
 * 使用方式（list.php）：
 *   require_once '../_list_semantic_search.php';
 *   crud_list_apply_keyword_search(..., semantic: true);
 */

if (!function_exists('manage_semantic_search_label')) {
    /** 後台搜尋欄位標籤文字 */
    function manage_semantic_search_label(): string
    {
        return '智慧語意搜尋';
    }
}

if (!function_exists('manage_semantic_resolve_api_key')) {
    /** 解析 GEMINI_API_KEY 或 GOOGLE_API_KEY */
    function manage_semantic_resolve_api_key(): string
    {
        if (function_exists('gemini_resolve_api_key')) {
            return gemini_resolve_api_key();
        }

        $key = trim((string)($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: ''));
        if ($key !== '') {
            return $key;
        }

        return trim((string)($_ENV['GOOGLE_API_KEY'] ?? getenv('GOOGLE_API_KEY') ?: ''));
    }
}

if (!function_exists('manage_semantic_search_enabled')) {
    /** MANAGE_SEMANTIC_SEARCH=1 且已有 Gemini 金鑰 */
    function manage_semantic_search_enabled(): bool
    {
        $raw = strtolower(trim((string)($_ENV['MANAGE_SEMANTIC_SEARCH'] ?? getenv('MANAGE_SEMANTIC_SEARCH') ?: '1')));
        if (in_array($raw, ['0', 'false', 'no', 'off', 'disable', 'disabled'], true)) {
            return false;
        }

        return manage_semantic_resolve_api_key() !== '';
    }
}

if (!function_exists('manage_semantic_embedding_model')) {
    /** Gemini Embedding 模型（text-embedding-004 已淘汰，改用 gemini-embedding-001） */
    function manage_semantic_embedding_model(): string
    {
        $raw = trim((string)($_ENV['MANAGE_SEMANTIC_EMBEDDING_MODEL'] ?? getenv('MANAGE_SEMANTIC_EMBEDDING_MODEL') ?: ''));

        return $raw !== '' ? $raw : 'gemini-embedding-001';
    }
}

if (!function_exists('manage_semantic_embedding_enabled')) {
    /** 是否啟用列表內 Gemini Embedding 相似度（MANAGE_SEMANTIC_USE_EMBEDDING） */
    function manage_semantic_embedding_enabled(): bool
    {
        if (!manage_semantic_search_enabled()) {
            return false;
        }

        $raw = strtolower(trim((string)($_ENV['MANAGE_SEMANTIC_USE_EMBEDDING'] ?? getenv('MANAGE_SEMANTIC_USE_EMBEDDING') ?: '0')));
        if (in_array($raw, ['0', 'false', 'no', 'off', 'disable', 'disabled'], true)) {
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['manage_semantic_embed_disabled'])) {
            return false;
        }

        return true;
    }
}

if (!function_exists('manage_semantic_disable_embedding_runtime')) {
    /** API 失敗時關閉本 session 的 embedding，避免每列重試 */
    function manage_semantic_disable_embedding_runtime(string $reason): void
    {
        error_log('[manage_semantic_embedding] ' . $reason);
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['manage_semantic_embed_disabled'] = 1;
        }
    }
}

if (!function_exists('manage_semantic_safe_sql_column')) {
    /** 白名單 SQL 欄位名（非法則 fallback strName） */
    function manage_semantic_safe_sql_column(string $column): string
    {
        $column = trim($column);

        return preg_match('/^[a-zA-Z0-9_.]+$/', $column) === 1 ? $column : 'strName';
    }
}

if (!function_exists('manage_semantic_safe_table_name')) {
    /** 白名單資料表名 */
    function manage_semantic_safe_table_name(string $table): string
    {
        $table = trim($table);

        return preg_match('/^[a-zA-Z0-9_]+$/', $table) === 1 ? $table : '';
    }
}

if (!function_exists('manage_semantic_safe_pk_column')) {
    /** 白名單主鍵欄名（預設 PKey） */
    function manage_semantic_safe_pk_column(string $pk): string
    {
        $pk = trim($pk);

        return preg_match('/^[a-zA-Z0-9_]+$/', $pk) === 1 ? $pk : 'PKey';
    }
}

if (!function_exists('manage_semantic_session_bucket')) {
    /** @return array<string, mixed> */
    function manage_semantic_session_bucket(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }

        if (!isset($_SESSION['manage_semantic_search']) || !is_array($_SESSION['manage_semantic_search'])) {
            $_SESSION['manage_semantic_search'] = [];
        }

        return $_SESSION['manage_semantic_search'];
    }
}

if (!function_exists('manage_semantic_cache_get')) {
    /** @return array<string, mixed>|null */
    function manage_semantic_cache_get(string $cacheKey): ?array
    {
        $bucket = manage_semantic_session_bucket();
        $item = $bucket[$cacheKey] ?? null;
        if (!is_array($item)) {
            return null;
        }

        $ts = (int)($item['ts'] ?? 0);
        if ($ts <= 0 || (time() - $ts) > 1800) {
            unset($_SESSION['manage_semantic_search'][$cacheKey]);

            return null;
        }

        return $item;
    }
}

if (!function_exists('manage_semantic_cache_set')) {
    /** @param array<string, mixed> $data */
    function manage_semantic_cache_set(string $cacheKey, array $data): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        manage_semantic_session_bucket();
        $data['ts'] = time();
        $_SESSION['manage_semantic_search'][$cacheKey] = $data;
    }
}

if (!function_exists('manage_semantic_normalize_terms')) {
    /**
     * @param list<string> $terms
     * @return list<string>
     */
    function manage_semantic_normalize_terms(array $terms, string $original): array
    {
        $normalized = [];
        $seen = [];

        $push = static function (string $term) use (&$normalized, &$seen): void {
            $term = trim($term);
            if ($term === '') {
                return;
            }
            $term = mb_substr($term, 0, 30);
            $compact = str_replace(' ', '', mb_strtolower($term, 'UTF-8'));
            if ($compact === '' || isset($seen[$compact])) {
                return;
            }
            $seen[$compact] = true;
            $normalized[] = $term;
        };

        $push($original);
        foreach ($terms as $term) {
            $push((string)$term);
        }

        return array_slice($normalized, 0, 6);
    }
}

if (!function_exists('manage_semantic_is_mostly_latin')) {
    /** 判斷字串是否以拉丁字母為主（跨語系搜尋用） */
    function manage_semantic_is_mostly_latin(string $text): bool
    {
        $compact = str_replace(' ', '', trim($text));
        if ($compact === '') {
            return false;
        }

        return preg_match('/^[a-z0-9_-]+$/i', $compact) === 1;
    }
}

if (!function_exists('manage_semantic_has_cjk')) {
    /** 是否含中日韓字元 */
    function manage_semantic_has_cjk(string $text): bool
    {
        return preg_match('/\p{Script=Han}/u', $text) === 1;
    }
}

if (!function_exists('manage_semantic_is_cross_language_pair')) {
    /** 兩詞是否為跨語系配對（如中文查英文欄位） */
    function manage_semantic_is_cross_language_pair(string $left, string $right): bool
    {
        return (manage_semantic_is_mostly_latin($left) && manage_semantic_has_cjk($right))
            || (manage_semantic_has_cjk($left) && manage_semantic_is_mostly_latin($right));
    }
}

if (!function_exists('manage_semantic_is_blocked_search_term')) {
    /** 黑名單詞（config 同義詞白名單除外）是否應略過擴詞 */
    function manage_semantic_is_blocked_search_term(string $term, string $original): bool
    {
        $compact = str_replace(' ', '', mb_strtolower(trim($term), 'UTF-8'));
        $originalCompact = str_replace(' ', '', mb_strtolower(trim($original), 'UTF-8'));
        if ($compact === '' || $compact === $originalCompact) {
            return false;
        }

        foreach (manage_semantic_builtin_bilingual_terms($original) as $synonym) {
            $synCompact = str_replace(' ', '', mb_strtolower(trim($synonym), 'UTF-8'));
            if ($synCompact !== '' && $synCompact === $compact) {
                return false;
            }
        }

        static $blocked = [
            '編輯器', '管理', '系統', '網站', '功能', '其他', '操作',
            'editor', 'manage', 'management', 'system', 'admin', 'website', 'other',
        ];

        return in_array($compact, $blocked, true);
    }
}

if (!function_exists('manage_semantic_synonym_map')) {
    /**
     * @return array<string, list<string>>
     */
    function manage_semantic_synonym_map(): array
    {
        static $map = null;
        if (is_array($map)) {
            return $map;
        }

        $path = dirname(__DIR__) . '/config/manage_search_synonyms.php';
        if (is_file($path)) {
            $loaded = require $path;
            $map = is_array($loaded) ? $loaded : [];
        } else {
            $map = [];
        }

        return $map;
    }
}

if (!function_exists('manage_semantic_builtin_bilingual_terms')) {
    /**
     * 內建中英對照（API 失敗或未回傳對譯時仍可依字面搜尋）
     *
     * @return list<string>
     */
    function manage_semantic_builtin_bilingual_terms(string $query): array
    {
        $compact = str_replace(' ', '', mb_strtolower(trim($query), 'UTF-8'));
        if ($compact === '') {
            return [];
        }

        $map = manage_semantic_synonym_map();

        return $map[$compact] ?? [];
    }
}

if (!function_exists('manage_semantic_collect_terms')) {
    /**
     * @param list<string> $extraTerms
     * @return list<string>
     */
    function manage_semantic_collect_terms(string $query, array $extraTerms = []): array
    {
        $merged = array_merge(
            [$query],
            manage_semantic_builtin_bilingual_terms($query),
            $extraTerms
        );

        return manage_semantic_prune_expanded_terms(
            $query,
            manage_semantic_normalize_terms($merged, $query)
        );
    }
}

if (!function_exists('manage_semantic_prune_expanded_terms')) {
    /**
     * 過濾擴詞：保留同義／變體與中英對譯，剔除過寬的上位分類詞
     *
     * @param list<string> $terms
     * @return list<string>
     */
    function manage_semantic_prune_expanded_terms(string $original, array $terms): array
    {
        $original = trim($original);
        if ($original === '') {
            return [];
        }

        $originalCompact = str_replace(' ', '', mb_strtolower($original, 'UTF-8'));
        $originalChars = array_values(array_unique(preg_split('//u', $originalCompact, -1, PREG_SPLIT_NO_EMPTY) ?: []));
        $kept = [];
        $seen = [];

        $accept = static function (string $term) use (
            &$kept,
            &$seen,
            $original,
            $originalCompact,
            $originalChars
        ): void {
            $term = trim($term);
            if ($term === '' || manage_semantic_is_blocked_search_term($term, $original)) {
                return;
            }
            $compact = str_replace(' ', '', mb_strtolower($term, 'UTF-8'));
            if ($compact === '' || isset($seen[$compact])) {
                return;
            }

            if ($compact === $originalCompact
                || str_contains($originalCompact, $compact)
                || str_contains($compact, $originalCompact)) {
                $seen[$compact] = true;
                $kept[] = $term;

                return;
            }

            if (manage_semantic_is_cross_language_pair($originalCompact, $compact)) {
                $seen[$compact] = true;
                $kept[] = $term;

                return;
            }

            if (mb_strlen($originalCompact, 'UTF-8') >= 2 && !manage_semantic_is_mostly_latin($originalCompact)) {
                foreach ($originalChars as $char) {
                    if ($char !== '' && mb_strpos($compact, $char, 0, 'UTF-8') !== false) {
                        $seen[$compact] = true;
                        $kept[] = $term;

                        return;
                    }
                }
            }
        };

        $accept($original);
        foreach ($terms as $term) {
            $accept((string)$term);
        }

        return $kept;
    }
}

if (!function_exists('manage_semantic_expand_terms')) {
    /**
     * 以 Gemini 將搜尋詞擴展為同義／相關語意詞（含原詞）
     *
     * @return list<string>
     */
    function manage_semantic_expand_terms(string $query): array
    {
        $query = trim(mb_substr($query, 0, 50));
        if ($query === '') {
            return [];
        }

        $cacheKey = 'terms7_' . hash('sha256', mb_strtolower($query, 'UTF-8'));
        $cached = manage_semantic_cache_get($cacheKey);
        if ($cached !== null && isset($cached['terms']) && is_array($cached['terms'])) {
            return manage_semantic_collect_terms($query, $cached['terms']);
        }

        if (!manage_semantic_search_enabled()) {
            return manage_semantic_collect_terms($query, []);
        }

        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            return manage_semantic_collect_terms($query, []);
        }

        require_once $autoload;
        if (!function_exists('gemini_create_client')) {
            require_once __DIR__ . '/gemini_client.php';
        }

        try {
            $client = gemini_create_client(manage_semantic_resolve_api_key(), 30);
            $prompt = "你是後台列表標題搜尋助手。使用者輸入：「{$query}」\n"
                . "請輸出 JSON：terms 為搜尋詞陣列（最多 6 個，每詞不超過 20 字）。"
                . "須包含：同義詞、簡稱、繁簡變體；若輸入為英文須含對應繁體中文（例：table→表格）；若輸入為中文可含常見英文（例：表格→table）；中文近義詞須互含（例：資料表→表格）。"
                . "禁止輸出不同主題的上位分類詞（如搜尋「表格」或 table 不可輸出「編輯器」「管理」「editor」「admin」）。";

            $response = $client->generativeModel(model: 'gemini-2.5-flash')
                ->withGenerationConfig(new \Gemini\Data\GenerationConfig(
                    maxOutputTokens: 256,
                    temperature: 0.2,
                    responseMimeType: \Gemini\Enums\ResponseMimeType::APPLICATION_JSON,
                    responseSchema: new \Gemini\Data\Schema(
                        type: \Gemini\Enums\DataType::OBJECT,
                        properties: [
                            'terms' => new \Gemini\Data\Schema(
                                type: \Gemini\Enums\DataType::ARRAY,
                                items: new \Gemini\Data\Schema(type: \Gemini\Enums\DataType::STRING),
                            ),
                        ],
                        required: ['terms'],
                    ),
                ))
                ->generateContent($prompt);

            $jsonText = trim($response->text() ?? '');
            $data = json_decode($jsonText, true);
            $terms = is_array($data['terms'] ?? null) ? $data['terms'] : [];
            $terms = manage_semantic_collect_terms($query, $terms);
            manage_semantic_cache_set($cacheKey, ['terms' => $terms]);

            return $terms;
        } catch (Throwable $e) {
            error_log('[manage_semantic_expand_terms] ' . $e->getMessage());

            return manage_semantic_collect_terms($query, []);
        }
    }
}

if (!function_exists('manage_semantic_cosine_similarity')) {
    /** @param list<float> $a @param list<float> $b */
    function manage_semantic_cosine_similarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}

if (!function_exists('manage_semantic_embedding_values')) {
    /**
     * @param list<string> $texts
     * @return list<list<float>>
     */
    function manage_semantic_embedding_values(array $texts, bool $isQuery): array
    {
        if ($texts === [] || !manage_semantic_embedding_enabled()) {
            return [];
        }

        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            return [];
        }

        require_once $autoload;
        if (!function_exists('gemini_create_client')) {
            require_once __DIR__ . '/gemini_client.php';
        }

        $embeddingModel = manage_semantic_embedding_model();
        $taskType = $isQuery
            ? \Gemini\Enums\TaskType::RETRIEVAL_QUERY
            : \Gemini\Enums\TaskType::RETRIEVAL_DOCUMENT;

        try {
            $client = gemini_create_client(manage_semantic_resolve_api_key(), 45);
            $model = $client->embeddingModel($embeddingModel);
            $vectors = [];

            foreach (array_chunk($texts, 32) as $chunk) {
                $requests = [];
                foreach ($chunk as $text) {
                    $requests[] = new \Gemini\Requests\GenerativeModel\EmbedContentRequest(
                        model: $embeddingModel,
                        part: $text,
                        taskType: $taskType,
                    );
                }

                $response = $model->batchEmbedContents(...$requests);
                foreach ($response->embeddings as $embedding) {
                    $vectors[] = array_map(static fn ($v): float => (float)$v, $embedding->values);
                }
            }

            return $vectors;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (function_exists('gemini_api_error_message')) {
                $message = gemini_api_error_message($e);
            }
            manage_semantic_disable_embedding_runtime($message);

            return [];
        }
    }
}

if (!function_exists('manage_semantic_match_pkeys_by_embedding')) {
    /**
     * @param list<string> $columns
     * @param array<string, mixed> $baseParams
     * @return list<int>
     */
    function manage_semantic_match_pkeys_by_embedding(
        string $query,
        string $table,
        string $pkColumn,
        string $baseWhere,
        array $baseParams,
        array $columns,
        int $maxCandidates = 200,
        float $minScore = 0.75,
    ): array {
        $table = manage_semantic_safe_table_name($table);
        $pkColumn = manage_semantic_safe_pk_column($pkColumn);
        if ($table === '' || $query === '' || !manage_semantic_embedding_enabled()) {
            return [];
        }

        try {
        $cacheKey = 'embed_' . hash('sha256', $query . '|' . $table . '|' . $baseWhere . '|' . json_encode($baseParams, JSON_UNESCAPED_UNICODE));
        $cached = manage_semantic_cache_get($cacheKey);
        if ($cached !== null && isset($cached['pkeys']) && is_array($cached['pkeys'])) {
            return array_values(array_map(static fn ($id): int => (int)$id, $cached['pkeys']));
        }

        $total = (int)crud_fetch_scalar(
            "SELECT COUNT({$pkColumn}) AS Total FROM `{$table}` {$baseWhere}",
            $baseParams,
            'Total'
        );
        if ($total <= 0 || $total > $maxCandidates) {
            return [];
        }

        $selectCols = [$pkColumn];
        foreach ($columns as $column) {
            $selectCols[] = manage_semantic_safe_sql_column((string)$column);
        }
        $selectCols = array_values(array_unique($selectCols));
        $selectSql = implode(', ', array_map(
            static fn (string $col): string => str_contains($col, '.') ? $col : "`{$col}`",
            $selectCols
        ));

        $rows = crud_fetch_all(
            "SELECT {$selectSql} FROM `{$table}` {$baseWhere} ORDER BY {$pkColumn} DESC LIMIT " . (int)$maxCandidates,
            $baseParams
        );
        if ($rows === []) {
            return [];
        }

        $documents = [];
        $pkeys = [];
        foreach ($rows as $row) {
            $pkey = (int)($row[$pkColumn] ?? 0);
            if ($pkey <= 0) {
                continue;
            }

            $parts = [];
            foreach ($columns as $column) {
                $safeCol = manage_semantic_safe_sql_column((string)$column);
                $field = str_contains($safeCol, '.') ? substr($safeCol, strrpos($safeCol, '.') + 1) : $safeCol;
                $value = trim((string)($row[$field] ?? $row[$safeCol] ?? ''));
                if ($value !== '') {
                    $parts[] = $value;
                }
            }

            $text = trim(implode(' ', $parts));
            if ($text === '') {
                continue;
            }

            $pkeys[] = $pkey;
            $documents[] = mb_substr($text, 0, 500);
        }

        if ($documents === []) {
            return [];
        }

        $queryVector = manage_semantic_embedding_values([$query], true);
        $docVectors = manage_semantic_embedding_values($documents, false);
        if ($queryVector === [] || $docVectors === [] || !isset($queryVector[0])) {
            return [];
        }

        $matches = [];
        $bestScore = 0.0;
        foreach ($docVectors as $idx => $vector) {
            $score = manage_semantic_cosine_similarity($queryVector[0], $vector);
            if ($score > $bestScore) {
                $bestScore = $score;
            }
            if ($score >= $minScore && isset($pkeys[$idx])) {
                $matches[] = ['pkey' => $pkeys[$idx], 'score' => $score];
            }
        }

        if ($matches !== [] && $bestScore > 0) {
            $relativeFloor = max($minScore, $bestScore * 0.9);
            $matches = array_values(array_filter(
                $matches,
                static fn (array $item): bool => (float)$item['score'] >= $relativeFloor
            ));
        }

        if ($matches === []) {
            return [];
        }

        usort($matches, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $matchedPkeys = array_values(array_map(static fn (array $item): int => (int)$item['pkey'], $matches));
        manage_semantic_cache_set($cacheKey, ['pkeys' => $matchedPkeys]);

        return $matchedPkeys;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (function_exists('gemini_api_error_message')) {
                $message = gemini_api_error_message($e);
            }
            manage_semantic_disable_embedding_runtime($message);

            return [];
        }
    }
}

if (!function_exists('manage_semantic_apply_keyword_filter')) {
    /**
     * @param string|list<string> $columns
     * @param array<string, mixed> $semanticContext
     */
    function manage_semantic_apply_keyword_filter(
        string &$where,
        array &$params,
        string $query,
        string|array $columns,
        array $semanticContext = [],
    ): void {
        $columns = is_array($columns) ? $columns : [$columns];
        if ($columns === []) {
            $columns = ['strName'];
        }

        $terms = manage_semantic_expand_terms($query);
        if ($terms === []) {
            return;
        }

        $orParts = [];
        $idx = 0;

        foreach ($columns as $column) {
            $safeCol = manage_semantic_safe_sql_column((string)$column);
            foreach ($terms as $term) {
                $paramKey = 'SemKw' . $idx++;
                $needle = str_replace(' ', '', $term);
                if (manage_semantic_is_mostly_latin($term)) {
                    $needle = strtolower($needle);
                    $params[$paramKey] = $needle;
                    $orParts[] = "LOCATE(:{$paramKey}, REPLACE(LOWER({$safeCol}), ' ', '')) > 0";
                } else {
                    $params[$paramKey] = $needle;
                    $orParts[] = "LOCATE(:{$paramKey}, REPLACE({$safeCol}, ' ', '')) > 0";
                }
            }
        }

        $msgTable = trim((string)($semanticContext['msg_table'] ?? ''));
        $msgFk = trim((string)($semanticContext['msg_fk'] ?? ''));
        $mainTable = trim((string)($semanticContext['table'] ?? ''));
        $mainPk = trim((string)($semanticContext['pk'] ?? 'PKey'));
        if ($msgTable !== '' && $msgFk !== '' && $mainTable !== '') {
            $safeMsgTable = manage_semantic_safe_table_name($msgTable);
            $safeMsgFk = manage_semantic_safe_sql_column($msgFk);
            $safeMainTable = manage_semantic_safe_table_name($mainTable);
            $safeMainPk = manage_semantic_safe_pk_column($mainPk);
            $msgCol = manage_semantic_safe_sql_column((string)($semanticContext['msg_column'] ?? 'Contents'));
            foreach ($terms as $term) {
                $paramKey = 'SemKw' . $idx++;
                $needle = str_replace(' ', '', $term);
                if (manage_semantic_is_mostly_latin($term)) {
                    $needle = strtolower($needle);
                    $params[$paramKey] = $needle;
                    $orParts[] = "EXISTS (SELECT 1 FROM `{$safeMsgTable}` _msm"
                        . " WHERE _msm.{$safeMsgFk} = {$safeMainTable}.{$safeMainPk}"
                        . " AND LOCATE(:{$paramKey}, REPLACE(LOWER(_msm.{$msgCol}), ' ', '')) > 0)";
                } else {
                    $params[$paramKey] = $needle;
                    $orParts[] = "EXISTS (SELECT 1 FROM `{$safeMsgTable}` _msm"
                        . " WHERE _msm.{$safeMsgFk} = {$safeMainTable}.{$safeMainPk}"
                        . " AND LOCATE(:{$paramKey}, REPLACE(_msm.{$msgCol}, ' ', '')) > 0)";
                }
            }
        }

        if ($orParts === []) {
            return;
        }

        $where .= ' AND (' . implode(' OR ', $orParts) . ')';
    }
}
