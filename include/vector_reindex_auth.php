<?php
declare(strict_types=1);

/**
 * 向量補建 HTTP 驗證（獨立小檔，避免 runner 未部署時 500）
 *
 * 環境變數：VECTOR_REINDEX_TOKEN
 * 使用方式：scripts/vector_reindex.php?token=...&type=knowledge&limit=200
 */

if (!function_exists('vector_reindex_token')) {
    /** 讀取 .env 的 VECTOR_REINDEX_TOKEN（去除引號） */
    function vector_reindex_token(): string
    {
        $raw = $_ENV['VECTOR_REINDEX_TOKEN'] ?? null;
        if ($raw === null || trim((string)$raw) === '') {
            $fromGetenv = getenv('VECTOR_REINDEX_TOKEN');
            $raw = ($fromGetenv !== false) ? $fromGetenv : '';
        }

        return vector_reindex_normalize_env_token((string)$raw);
    }
}

if (!function_exists('vector_reindex_normalize_env_token')) {
    /** 去除 .env 值外層引號與空白 */
    function vector_reindex_normalize_env_token(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        return trim($value);
    }
}

if (!function_exists('vector_reindex_request_token_candidates')) {
    /**
     * GET query 會把 + 解成空白，需還原比對
     *
     * @return list<string>
     */
    function vector_reindex_request_token_candidates(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return [];
        }

        $candidates = [$token];

        $plusFixed = str_replace(' ', '+', $token);
        if ($plusFixed !== $token) {
            $candidates[] = $plusFixed;
        }

        foreach ([rawurldecode($token), urldecode($token)] as $decoded) {
            if (!is_string($decoded) || $decoded === '' || $decoded === $token) {
                continue;
            }
            $candidates[] = $decoded;
            $decodedPlus = str_replace(' ', '+', $decoded);
            if ($decodedPlus !== $decoded) {
                $candidates[] = $decodedPlus;
            }
        }

        $unique = [];
        foreach ($candidates as $item) {
            $item = trim($item);
            if ($item !== '' && !in_array($item, $unique, true)) {
                $unique[] = $item;
            }
        }

        return $unique;
    }
}

if (!function_exists('vector_reindex_verify_token')) {
    /**
     * 以 hash_equals 比對請求 token（支援 URL 解碼與 + 還原）
     */
    function vector_reindex_verify_token(string $token): bool
    {
        $expected = vector_reindex_token();
        if ($expected === '') {
            return false;
        }

        foreach (vector_reindex_request_token_candidates($token) as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('vector_reindex_load_env')) {
    /** 載入 include/host.php（.env / Dotenv） */
    function vector_reindex_load_env(string $projectRoot): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $host = $projectRoot . '/include/host.php';
        if (is_file($host)) {
            require_once $host;
        }
        $loaded = true;
    }
}

if (!function_exists('vector_reindex_parse_http_options')) {
    /**
     * @param array<string, mixed> $input
     * @return array{type: ?string, dry_run: bool, only_published: bool, offset: int, limit: int, sleep_ms: int}
     */
    function vector_reindex_parse_http_options(array $input): array
    {
        $options = [
            'type'            => null,
            'dry_run'         => false,
            'only_published'  => true,
            'offset'          => 0,
            'limit'           => 0,
            'sleep_ms'        => 200,
        ];

        $type = trim((string)($input['type'] ?? ''));
        if ($type !== '') {
            $options['type'] = $type;
        }

        if (isset($input['dry_run']) && (string)$input['dry_run'] !== '' && (string)$input['dry_run'] !== '0') {
            $options['dry_run'] = true;
        }

        if (isset($input['include_unpublished']) && (string)$input['include_unpublished'] !== '' && (string)$input['include_unpublished'] !== '0') {
            $options['only_published'] = false;
        }

        if (isset($input['offset']) && (string)$input['offset'] !== '') {
            $options['offset'] = max(0, (int)$input['offset']);
        }

        if (isset($input['limit']) && (string)$input['limit'] !== '') {
            $options['limit'] = max(0, (int)$input['limit']);
        }

        if (isset($input['sleep_ms']) && (string)$input['sleep_ms'] !== '') {
            $options['sleep_ms'] = max(0, (int)$input['sleep_ms']);
        }

        return $options;
    }
}

if (!function_exists('vector_reindex_render_result')) {
    /**
     * 輸出 reindex 結果並回傳 exit code（CLI 0/1）
     *
     * @param array{success: bool, lines: list<string>, stats?: array<string, int>, error?: string} $result
     */
    function vector_reindex_render_result(array $result, bool $isCli = false): int
    {
        foreach ($result['lines'] as $line) {
            echo $line . PHP_EOL;
        }

        if (!$result['success']) {
            $error = trim((string)($result['error'] ?? ''));
            if ($error !== '') {
                if ($isCli) {
                    fwrite(STDERR, '[ERROR] ' . $error . PHP_EOL);
                } else {
                    echo '[ERROR] ' . $error . PHP_EOL;
                }
            }

            return 1;
        }

        return 0;
    }
}
