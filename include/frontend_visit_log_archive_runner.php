<?php
declare(strict_types=1);

if (!function_exists('frontend_visit_log_archive_token')) {
    /** 讀取 .env FRONTEND_VISIT_LOG_ARCHIVE_TOKEN */
    function frontend_visit_log_archive_token(): string
    {
        $raw = $_ENV['FRONTEND_VISIT_LOG_ARCHIVE_TOKEN'] ?? null;
        if ($raw === null || trim((string)$raw) === '') {
            $fromGetenv = getenv('FRONTEND_VISIT_LOG_ARCHIVE_TOKEN');
            $raw = ($fromGetenv !== false) ? $fromGetenv : '';
        }

        return trim((string)$raw);
    }
}

if (!function_exists('frontend_visit_log_archive_verify_token')) {
    /** 以 hash_equals 驗證排程 token */
    function frontend_visit_log_archive_verify_token(string $token): bool
    {
        $expected = frontend_visit_log_archive_token();
        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, trim($token));
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
            echo "[ERROR] FRONTEND_VISIT_LOG_ARCHIVE_TOKEN is not configured in .env\n";
            exit(1);
        }

        if (!frontend_visit_log_archive_verify_token($token)) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(403);
            echo "[ERROR] invalid token\n";
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
