<?php
declare(strict_types=1);

require_once __DIR__ . '/_inc.php';
require_once __DIR__ . '/include/json_response.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed'], 405);
}

$pkey = filter_input(INPUT_POST, 'PKey', FILTER_VALIDATE_INT);
if ($pkey === false || $pkey === null || $pkey <= 0) {
    json_out(['success' => false, 'error' => 'Invalid PKey'], 400);
}

$modulePKey = frontend_module_pkey_for_link('paper.htm');
if ($modulePKey <= 0) {
    $modulePKey = frontend_module_pkey_for_link('paper');
}
if ($modulePKey <= 0) {
    json_out(['success' => false, 'error' => 'Module not found'], 500);
}

frontend_module_set_config(array_merge(
    require __DIR__ . '/manage/paper/_config.php',
    [
        'view'           => 'view_paper',
        'class_link'     => 'paper',
        'detail_link'    => 'paper-detail',
        'publish_window' => false,
    ]
));

if (frontend_fetch_detail($modulePKey, $pkey) === null) {
    json_out(['success' => false, 'error' => 'Not found'], 404);
}

$sql = 'UPDATE paper SET Pageview = Pageview + 1'
    . ' WHERE PKey = :PKey AND Module_PKey = :Module_PKey LIMIT 1';

crud_pdo_query($sql, [
    'PKey'         => $pkey,
    'Module_PKey'  => $modulePKey,
]);

json_out(['success' => true]);
