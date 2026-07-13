<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/_form_data.php';

$filter = is_array($filter_array ?? null) ? $filter_array : [];
$couponPKey = coupon_d_resolve_coupon_pkey($filter);
$parent = coupon_d_load_parent($couponPKey);
if ($parent === null) {
    manage_alert_script(
        '查無優惠券資料',
        coupon_d_parent_list_url($detailConfig, $filter)
    );
    exit;
}

$couponPKey = (int)($parent['PKey'] ?? $couponPKey);
$listUrl = coupon_d_list_back_url($couponPKey, $filter);
$csrfKey = (string)($detailConfig['csrf'] ?? 'coupon_d_import');
crud_csrf_verify_form($csrfKey);

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    manage_alert_script(
        '匯入功能尚未安裝相依套件，請在專案根目錄執行：composer install',
        'javascript:history.back()'
    );
    exit;
}
require_once $autoload;

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = $_FILES['Photo1'] ?? null;
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    manage_alert_script('請選擇匯入檔案', 'javascript:history.back()');
    exit;
}

$tmpName = (string)($file['tmp_name'] ?? '');
$origName = (string)($file['name'] ?? '');
$fileSize = (int)($file['size'] ?? 0);
$maxBytes = 6000 * 1024;

if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    manage_alert_script('檔案上傳失敗', 'javascript:history.back()');
    exit;
}
if ($fileSize > $maxBytes) {
    manage_alert_script('檔案過大，請小於 ' . (int)($maxBytes / 1024) . 'KB', 'javascript:history.back()');
    exit;
}

$allowedMimes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'application/octet-stream',
];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detected = $finfo !== false ? (string)finfo_file($finfo, $tmpName) : '';
if ($finfo !== false) {
    finfo_close($finfo);
}
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($detected, $allowedMimes, true) && !in_array($ext, ['xlsx', 'xls'], true)) {
    manage_alert_script('檔案格式有誤，請上傳 Excel（.xlsx / .xls）', 'javascript:history.back()');
    exit;
}

[$uploadInfo] = [crud_upload_dir()];
$uploadDir = rtrim((string)($uploadInfo['dir'] ?? ''), "/\\") . '/coupon/';
if (!is_dir($uploadDir)) {
    makedirs($uploadDir);
}

$saveName = 'coupon_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . ($ext !== '' ? $ext : 'xlsx');
$savePath = $uploadDir . $saveName;
if (!move_uploaded_file($tmpName, $savePath)) {
    manage_alert_script('無法儲存上傳檔案', 'javascript:history.back()');
    exit;
}

try {
    $inputType = IOFactory::identify($savePath);
    $reader = IOFactory::createReader($inputType);
    $spreadsheet = $reader->load($savePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = (int)$worksheet->getHighestRow();
} catch (Throwable $e) {
    @unlink($savePath);
    manage_alert_script('無法讀取 Excel 檔案', 'javascript:history.back()');
    exit;
}

$emails = [];
for ($row = 2; $row <= $highestRow; $row++) {
    $cell = $worksheet->getCell([1, $row]);
    $email = trim((string)$cell->getValue());
    if ($email !== '') {
        $emails[] = $email;
    }
}
@unlink($savePath);

$lines = max(0, $highestRow - 1);
$result = coupon_d_import_emails($couponPKey, $emails);
$msg = '成功匯入共計' . $lines . '筆；成功' . $result['success'] . '筆；失敗' . $result['failed'] . '筆';
manage_alert_script($msg, $listUrl);
exit;
