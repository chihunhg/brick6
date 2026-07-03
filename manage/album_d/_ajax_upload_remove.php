<?php
declare(strict_types=1);

require_once __DIR__ . '/../_inc.php';
require_once __DIR__ . '/../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/_helpers.php';

$csrfKey = (string)($detailConfig['csrf'] ?? 'album_d_addin');
album_d_ajax_csrf_verify($csrfKey);

$albumPKey = safe_int($_POST['Album_PKey'] ?? 0);
$uploadId = trim((string)($_POST['upload_id'] ?? ''));

if ($albumPKey <= 0 || $uploadId === '') {
    crud_json_response(false, '參數錯誤');
}

$parent = album_d_load_parent($albumPKey);
if (!($parent['ok'] ?? false)) {
    crud_json_response(false, '查無相簿資料');
}

$ok = album_d_staging_remove($albumPKey, $uploadId, true);
crud_json_response($ok, $ok ? 'OK' : '刪除失敗');
