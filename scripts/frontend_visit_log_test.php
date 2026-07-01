<?php
declare(strict_types=1);

/**
 * 診斷 frontend_visit_log 寫入（CLI，不經 crud_fail_db 中斷）
 *   php scripts/frontend_visit_log_test.php
 *   php scripts/frontend_visit_log_test.php --insert
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/include/host.php';
require $projectRoot . '/include/Conn.php';
require $projectRoot . '/include/dbclass.php';
require $projectRoot . '/include/Function.php';
require $projectRoot . '/include/crud_helpers.php';
require $projectRoot . '/include/frontend_visit_log.php';

$doInsert = in_array('--insert', $argv ?? [], true);
$table = frontend_visit_log_table();

$lines = [];
$lines[] = '=== frontend_visit_log diagnostic ===';
$lines[] = 'FRONTEND_VISIT_LOG_ENABLED: ' . (frontend_visit_log_enabled() ? '1' : '0');
$lines[] = 'FRONTEND_VISIT_LOG_SKIP_BOTS: ' . (frontend_visit_skip_bots() ? '1' : '0');
$lines[] = 'FRONTEND_VISIT_GEO_ENABLED: ' . (frontend_visit_geo_enabled() ? '1' : '0');
$lines[] = 'User-Agent (CLI): ' . (frontend_visit_request_user_agent() === '' ? '(empty → 視為爬蟲)' : frontend_visit_request_user_agent());
$lines[] = 'is_crawler: ' . (frontend_visit_is_crawler() ? 'yes' : 'no');

$pdo = function_exists('sql_conn') ? sql_conn() : null;
if (!$pdo instanceof PDO) {
    $lines[] = 'PDO: unavailable（無法連線資料庫）';
    echo implode(PHP_EOL, $lines) . PHP_EOL;
    exit(1);
}

$lines[] = 'PDO: connected';

try {
    if (function_exists('db_pdo_table_exists')) {
        $tableFound = db_pdo_table_exists($pdo, $table);
    } else {
        $params = [$table];
        $sql = 'SHOW TABLES LIKE ?';
        if (function_exists('db_expand_show_like_sql')) {
            $sql = db_expand_show_like_sql($pdo, $sql, $params);
        }
        if ($params === []) {
            $st = $pdo->query($sql);
        } else {
            $st = $pdo->prepare($sql);
            $st->execute($params);
        }
        $tableFound = (bool)$st->fetchColumn();
    }
    $lines[] = 'SHOW TABLES LIKE ' . $table . ': ' . ($tableFound ? 'found' : 'NOT FOUND（請執行 sql/frontend_visit_log.sql）');
} catch (Throwable $e) {
    $lines[] = 'SHOW TABLES error: ' . $e->getMessage();
    $tableFound = false;
}

if ($tableFound) {
    try {
        $cntSt = $pdo->query('SELECT COUNT(*) FROM `' . $table . '`');
        $lines[] = 'Current row count: ' . (int)$cntSt->fetchColumn();
    } catch (Throwable $e) {
        $lines[] = 'COUNT error: ' . $e->getMessage();
    }
}

$lines[] = 'frontend_visit_log_table_ready(): ' . (frontend_visit_log_table_ready($pdo) ? 'yes' : 'no');
$lines[] = 'crud_table_exists(): ' . (crud_table_exists($table) ? 'yes' : 'no');

if ($doInsert) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; frontend-visit-log-test/1.0)';
    $testLink = '/cli-test-' . date('YmdHis');
    $result = frontend_visit_log_insert(0, $testLink);
    $lines[] = 'insert(' . $testLink . '): ' . json_encode($result, JSON_UNESCAPED_UNICODE);
}

echo implode(PHP_EOL, $lines) . PHP_EOL;
exit($tableFound ? 0 : 2);
