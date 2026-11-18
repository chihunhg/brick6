<?php
declare(strict_types=1);

if (!function_exists('frontend_visit_log_archive_normalize_env_token')) {
    /** 去除 .env 值外層引號與空白 */
    function frontend_visit_log_archive_normalize_env_token(string $value): string
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

if (!function_exists('frontend_visit_log_archive_token_is_placeholder')) {
    function frontend_visit_log_archive_token_is_placeholder(string $token): bool
    {
        if (function_exists('host_env_is_placeholder')) {
            return host_env_is_placeholder($token);
        }
        $v = strtolower(trim($token));

        return $v === '' || str_starts_with($v, 'change_me');
    }
}

if (!function_exists('frontend_visit_log_archive_token')) {
    /** 讀取 .env FRONTEND_VISIT_LOG_ARCHIVE_TOKEN（占位符視為未設定） */
    function frontend_visit_log_archive_token(): string
    {
        $raw = $_ENV['FRONTEND_VISIT_LOG_ARCHIVE_TOKEN'] ?? null;
        if ($raw === null || trim((string)$raw) === '') {
            $fromGetenv = getenv('FRONTEND_VISIT_LOG_ARCHIVE_TOKEN');
            $raw = ($fromGetenv !== false) ? $fromGetenv : '';
        }

        $token = frontend_visit_log_archive_normalize_env_token((string)$raw);
        if ($token === '' || frontend_visit_log_archive_token_is_placeholder($token)) {
            return '';
        }

        return $token;
    }
}

if (!function_exists('frontend_visit_log_archive_token_fingerprint')) {
    /**
     * @return array{len:int, sha256_8:string, plus:int, space:int, pct:int}
     */
    function frontend_visit_log_archive_token_fingerprint(string $token): array
    {
        $token = trim($token);

        return [
            'len'      => strlen($token),
            'sha256_8' => $token === '' ? '-' : substr(hash('sha256', $token), 0, 8),
            'plus'     => str_contains($token, '+') ? 1 : 0,
            'space'    => str_contains($token, ' ') ? 1 : 0,
            'pct'      => str_contains($token, '%') ? 1 : 0,
        ];
    }
}

if (!function_exists('frontend_visit_log_archive_request_token_candidates')) {
    /**
     * GET query 會把 + 解成空白，需還原比對
     *
     * @return list<string>
     */
    function frontend_visit_log_archive_request_token_candidates(string $token): array
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

if (!function_exists('frontend_visit_log_archive_collect_request_tokens')) {
    /**
     * 收集 HTTP 請求中的 token（GET／POST／QUERY_STRING 原始值／Bearer）
     *
     * @return list<string>
     */
    function frontend_visit_log_archive_collect_request_tokens(): array
    {
        $tokens = [];
        $fromParam = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
        if ($fromParam !== '') {
            $tokens[] = $fromParam;
        }

        $qs = (string)($_SERVER['QUERY_STRING'] ?? '');
        if ($qs !== '' && preg_match('/(?:^|&)token=([^&]*)/', $qs, $m) === 1) {
            $raw = (string)$m[1];
            if ($raw !== '') {
                $tokens[] = $raw;
                $tokens[] = str_replace('+', '%2B', $raw);
                $tokens[] = rawurldecode($raw);
            }
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = trim((string)$_SERVER['HTTP_AUTHORIZATION']);
            if (preg_match('/^Bearer\s+(\S+)/i', $auth, $m) === 1) {
                $tokens[] = trim($m[1]);
            }
        }

        $unique = [];
        foreach ($tokens as $item) {
            $item = trim((string)$item);
            if ($item !== '' && !in_array($item, $unique, true)) {
                $unique[] = $item;
            }
        }

        return $unique;
    }
}

if (!function_exists('frontend_visit_log_archive_verify_token')) {
    /** 以 hash_equals 驗證排程 token（支援 URL 解碼與 + 還原） */
    function frontend_visit_log_archive_verify_token(string $token): bool
    {
        $expected = frontend_visit_log_archive_token();
        if ($expected === '') {
            return false;
        }

        $expectedList = [$expected];
        $decodedExpected = rawurldecode($expected);
        if (is_string($decodedExpected) && $decodedExpected !== '' && $decodedExpected !== $expected) {
            $expectedList[] = $decodedExpected;
        }

        $seen = [];
        $sources = $token !== '' ? [$token] : [];
        foreach (frontend_visit_log_archive_collect_request_tokens() as $extra) {
            $sources[] = $extra;
        }

        foreach ($sources as $source) {
            foreach (frontend_visit_log_archive_request_token_candidates($source) as $candidate) {
                if (isset($seen[$candidate])) {
                    continue;
                }
                $seen[$candidate] = true;
                foreach ($expectedList as $want) {
                    if (hash_equals($want, $candidate)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

if (!function_exists('frontend_visit_log_archive_invalid_token_message')) {
    function frontend_visit_log_archive_invalid_token_message(?string $received = null): string
    {
        $received = trim((string)($received ?? ($_GET['token'] ?? $_POST['token'] ?? '')));
        $plusFixed = str_replace(' ', '+', $received);
        if ($plusFixed !== '') {
            $received = $plusFixed;
        }

        $rawEnv = frontend_visit_log_archive_normalize_env_token((string)(
            $_ENV['FRONTEND_VISIT_LOG_ARCHIVE_TOKEN'] ?? (getenv('FRONTEND_VISIT_LOG_ARCHIVE_TOKEN') ?: '')
        ));
        $expected = frontend_visit_log_archive_token();
        $expFp = frontend_visit_log_archive_token_fingerprint($expected !== '' ? $expected : $rawEnv);
        $gotFp = frontend_visit_log_archive_token_fingerprint($received);

        $lines = [
            '[ERROR] invalid token',
            sprintf(
                'diag: expected len=%d sha256_8=%s plus=%d placeholder=%d',
                $expFp['len'],
                $expFp['sha256_8'],
                $expFp['plus'],
                frontend_visit_log_archive_token_is_placeholder($rawEnv) ? 1 : 0
            ),
            sprintf(
                'diag: received len=%d sha256_8=%s plus=%d space=%d',
                $gotFp['len'],
                $gotFp['sha256_8'],
                $gotFp['plus'],
                $gotFp['space']
            ),
        ];
        if (frontend_visit_log_archive_token_is_placeholder($rawEnv) || $expected === '') {
            $lines[] = 'hint: brick6/.env 仍是 change_me 占位符時，private/.env 的 token 不會生效；請把真實 token 寫進實際載入的 .env，或刪除占位符。';
        } else {
            $lines[] = 'hint: 網址 token 與伺服器 .env 的 FRONTEND_VISIT_LOG_ARCHIVE_TOKEN 不一致（比對 sha256_8）。';
        }

        return implode("\n", $lines) . "\n";
    }
}

if (!function_exists('frontend_visit_log_archive_bootstrap')) {
    /** 載入 archive 腳本所需 include */
    function frontend_visit_log_archive_bootstrap(string $projectRoot): void
    {
        require_once $projectRoot . '/include/host.php';
        require_once $projectRoot . '/include/Conn.php';
        require_once $projectRoot . '/include/dbclass.php';
        require_once $projectRoot . '/include/Function.php';
        require_once $projectRoot . '/include/crud_helpers.php';
        require_once $projectRoot . '/include/frontend_visit_log.php';
    }
}

if (!function_exists('frontend_visit_log_archive_execute')) {
    /**
     * @param array{status?:bool,month?:?string,dry_run?:bool} $options
     * @return array{success:bool, exit_code:int, lines:list<string>, error?:string}
     */
    function frontend_visit_log_archive_execute(array $options = []): array
    {
        $statusOnly = !empty($options['status']);
        $dryRun = !empty($options['dry_run']);
        $monthArg = isset($options['month']) ? trim((string)$options['month']) : null;
        if ($monthArg === '') {
            $monthArg = null;
        }

        $lines = [];

        if ($statusOnly) {
            $status = frontend_visit_log_archive_status();
            if (!$status['success']) {
                return [
                    'success'   => false,
                    'exit_code' => 1,
                    'lines'     => [],
                    'error'     => (string)($status['error'] ?? 'status_failed'),
                ];
            }

            $lines[] = 'Current month (kept in hot table): ' . (string)($status['current_month'] ?? '');
            $lines[] = 'Pending months in main table:';
            $pendingMonths = $status['pending_months'] ?? [];
            if ($pendingMonths === []) {
                $lines[] = '  (none)';
            } else {
                foreach ($pendingMonths as $item) {
                    $lines[] = sprintf(
                        '  %s  rows=%d  -> %s',
                        (string)($item['label'] ?? ''),
                        (int)($item['count'] ?? 0),
                        (string)($item['archive_table'] ?? '')
                    );
                }
            }
            $lines[] = 'Pending total rows: ' . (int)($status['pending_total'] ?? 0);
            $lines[] = 'Existing archive tables: ' . implode(', ', $status['archived_tables'] ?? []);

            return ['success' => true, 'exit_code' => 0, 'lines' => $lines];
        }

        if ($monthArg !== null) {
            try {
                $month = frontend_visit_log_archive_resolve_month($monthArg);
            } catch (InvalidArgumentException $e) {
                return [
                    'success'   => false,
                    'exit_code' => 1,
                    'lines'     => [],
                    'error'     => $e->getMessage(),
                ];
            }

            $result = frontend_visit_log_archive_run($month, $dryRun);
            if (!$result['success']) {
                return [
                    'success'   => false,
                    'exit_code' => 1,
                    'lines'     => [],
                    'error'     => (string)($result['error'] ?? 'archive_failed'),
                ];
            }

            $mode = !empty($result['dry_run']) ? 'DRY-RUN' : 'OK';
            $lines[] = sprintf(
                '[%s] month=%s table=%s pending=%d inserted=%d deleted=%d',
                $mode,
                (string)($result['month'] ?? ''),
                (string)($result['archive_table'] ?? ''),
                (int)($result['pending'] ?? 0),
                (int)($result['inserted'] ?? 0),
                (int)($result['deleted'] ?? 0)
            );

            return ['success' => true, 'exit_code' => 0, 'lines' => $lines];
        }

        $catchUp = frontend_visit_log_archive_catch_up($dryRun);
        if (!$catchUp['success']) {
            return [
                'success'   => false,
                'exit_code' => 1,
                'lines'     => [],
                'error'     => (string)($catchUp['error'] ?? 'catch_up_failed'),
            ];
        }

        $mode = !empty($catchUp['dry_run']) ? 'DRY-RUN' : 'OK';
        $lines[] = sprintf(
            '[%s] catch-up processed=%d pending_total=%d inserted_total=%d deleted_total=%d',
            $mode,
            (int)($catchUp['processed'] ?? 0),
            (int)($catchUp['pending_total'] ?? 0),
            (int)($catchUp['inserted_total'] ?? 0),
            (int)($catchUp['deleted_total'] ?? 0)
        );

        foreach ($catchUp['results'] ?? [] as $result) {
            $lines[] = sprintf(
                '  - month=%s table=%s pending=%d inserted=%d deleted=%d',
                (string)($result['month'] ?? ''),
                (string)($result['archive_table'] ?? ''),
                (int)($result['pending'] ?? 0),
                (int)($result['inserted'] ?? 0),
                (int)($result['deleted'] ?? 0)
            );
        }

        return ['success' => true, 'exit_code' => 0, 'lines' => $lines];
    }
}

if (!function_exists('frontend_visit_log_archive_handle_http')) {
    /** HTTP 排程入口（Plesk「執行 URL」） */
    function frontend_visit_log_archive_handle_http(string $projectRoot): never
    {
        frontend_visit_log_archive_bootstrap($projectRoot);

        $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
        if (frontend_visit_log_archive_token() === '') {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(503);
            echo "[ERROR] FRONTEND_VISIT_LOG_ARCHIVE_TOKEN is not configured in .env\n"
                . "hint: brick6/.env 若仍是 change_me 占位符，請改成真實 token，或刪除該行讓 private/.env 補上。\n";
            exit(1);
        }

        if (!frontend_visit_log_archive_verify_token($token)) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(403);
            echo frontend_visit_log_archive_invalid_token_message($token);
            exit(1);
        }

        $monthArg = trim((string)($_GET['month'] ?? $_POST['month'] ?? ''));
        $result = frontend_visit_log_archive_execute([
            'status'  => isset($_GET['status']) || isset($_POST['status']),
            'dry_run' => isset($_GET['dry_run']) || isset($_POST['dry_run']),
            'month'   => $monthArg !== '' ? $monthArg : null,
        ]);

        header('Content-Type: text/plain; charset=utf-8');
        if (!$result['success']) {
            http_response_code(500);
            echo '[ERROR] ' . (string)($result['error'] ?? 'failed') . "\n";
            exit(1);
        }

        echo implode("\n", $result['lines']) . "\n";
        exit(0);
    }
}
