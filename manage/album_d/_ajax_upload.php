<?php
declare(strict_types=1);

require_once __DIR__ . '/../_inc.php';
require_once __DIR__ . '/../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/_helpers.php';

$csrfKey = (string)($detailConfig['csrf'] ?? 'album_d_addin');
album_d_ajax_csrf_verify($csrfKey);

$albumPKey = safe_int($_POST['Album_PKey'] ?? 0);
$maxSlots = max(1, (int)($detailConfig['img_slot_max'] ?? $detailConfig['add_photo_slots'] ?? 10));

if ($albumPKey <= 0) {
    crud_json_response(false, '相簿參數錯誤');
}

$parent = album_d_load_parent($albumPKey);
if (!($parent['ok'] ?? false)) {
    crud_json_response(false, '查無相簿資料');
}

if (album_d_staging_count($albumPKey) >= $maxSlots) {
    crud_json_response(false, '已達最多 ' . $maxSlots . ' 張圖片');
}

$file = $_FILES['photo'] ?? null;
if (!is_array($file)) {
    crud_json_response(false, '請選擇圖片檔案');
}

try {
    $result = album_d_upload_staged_file($albumPKey, $file, $detailConfig);
    $result['staging_count'] = album_d_staging_count($albumPKey);
    crud_json_response(true, 'OK', $result);
} catch (Throwable $e) {
    crud_json_response(false, $e->getMessage());
}
