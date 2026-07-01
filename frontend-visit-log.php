<?php
declare(strict_types=1);

require_once __DIR__ . '/include/host.php';
require_once __DIR__ . '/include/Conn.php';
require_once __DIR__ . '/include/dbclass.php';
require_once __DIR__ . '/include/Function.php';
require_once __DIR__ . '/include/crud_helpers.php';
require_once __DIR__ . '/include/frontend_visit_log.php';
require_once __DIR__ . '/include/json_response.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed'], 405);
}

$modulePKey = filter_input(INPUT_POST, 'Module_PKey', FILTER_VALIDATE_INT);
if ($modulePKey === false || $modulePKey === null) {
    $modulePKey = 0;
}

$pageLink = trim((string)($_POST['strLink'] ?? ''));
$result = frontend_visit_log_insert((int)$modulePKey, $pageLink);

if (!empty($result['skipped'])) {
    json_out(['success' => true, 'skipped' => true]);
}

if (!$result['success']) {
    $error = (string)($result['error'] ?? 'failed');
    $code = match ($error) {
        'disabled', 'table_missing' => 503,
        'invalid_row', 'insert_failed', 'db_unavailable' => 500,
        default => 400,
    };
    json_out(['success' => false, 'error' => $error], $code);
}

json_out([
    'success' => true,
    'PKey'    => (int)($result['pkey'] ?? 0),
]);
