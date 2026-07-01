<?php
declare(strict_types=1);

/**
 * 每月封存 frontend_visit_log：建立 frontend_visit_log_YYYYMM，移入指定月份資料。
 *
 * CLI 用法：
 *   php scripts/frontend_visit_log_archive.php              預設：補齊所有逾期月份（含漏跑）
 *   php scripts/frontend_visit_log_archive.php --status       僅檢視積壓，不寫入
 *   php scripts/frontend_visit_log_archive.php --month=2026-06  只封存指定月
 *   php scripts/frontend_visit_log_archive.php --dry-run      試跑
 *
 * HTTP 排程（Plesk「執行 URL」；需 .env FRONTEND_VISIT_LOG_ARCHIVE_TOKEN）：
 *   https://tsg5.com.tw/brick6/scripts/frontend_visit_log_archive.php?token=YOUR_SECRET
 *   https://tsg5.com.tw/brick6/scripts/frontend-visit-log-archive.php?token=YOUR_SECRET
 *
 * 建議排程（每月 1 日 00:10）：
 *   Linux cron: 10 0 1 * * cd /path/to/brick6 && php scripts/frontend_visit_log_archive.php
 *   Windows 工作排程：php.exe T:\wamp64\www\brick6\scripts\frontend_visit_log_archive.php
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/include/frontend_visit_log_archive_runner.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');

    frontend_visit_log_archive_bootstrap($projectRoot);

    $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
    if (frontend_visit_log_archive_token() === '') {
        http_response_code(503);
        echo "[ERROR] FRONTEND_VISIT_LOG_ARCHIVE_TOKEN is not configured in .env\n";
        exit(1);
    }
    if (!frontend_visit_log_archive_verify_token($token)) {
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

    if (!$result['success']) {
        http_response_code(500);
        echo '[ERROR] ' . (string)($result['error'] ?? 'failed') . "\n";
        exit(1);
    }

    echo implode("\n", $result['lines']) . "\n";
    exit(0);
}

frontend_visit_log_archive_bootstrap($projectRoot);

$monthArg = null;
$dryRun = false;
$statusOnly = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
        continue;
    }
    if ($arg === '--status') {
        $statusOnly = true;
        continue;
    }
    if (str_starts_with($arg, '--month=')) {
        $monthArg = substr($arg, 8);
        continue;
    }
    if ($arg === '--help' || $arg === '-h') {
        echo "Usage: php scripts/frontend_visit_log_archive.php [--status] [--month=YYYY-MM] [--dry-run]\n";
        echo "  (default)  Catch up all overdue months in frontend_visit_log\n";
        echo "  --status     Show pending months only\n";
        echo "  --month=     Archive a single month\n";
        echo "  --dry-run    Simulate without writing\n";
        exit(0);
    }
    fwrite(STDERR, "Unknown argument: {$arg}\n");
    exit(1);
}

$result = frontend_visit_log_archive_execute([
    'status'  => $statusOnly,
    'dry_run' => $dryRun,
    'month'   => ($monthArg !== null && trim($monthArg) !== '') ? trim($monthArg) : null,
]);

if (!$result['success']) {
    fwrite(STDERR, '[frontend_visit_log_archive] ' . (string)($result['error'] ?? 'failed') . PHP_EOL);
    exit((int)($result['exit_code'] ?? 1));
}

echo implode(PHP_EOL, $result['lines']) . PHP_EOL;
exit(0);
