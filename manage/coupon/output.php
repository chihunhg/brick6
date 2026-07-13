<?php
declare(strict_types=1);

/** Excel 二進位下載 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';
require_once '../_module.php';

$couponPKey = safe_int($filter_array['PKey'] ?? $_GET['PKey'] ?? 0);
if ($couponPKey <= 0) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '參數錯誤';
    exit;
}

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '匯出功能尚未安裝相依套件，請在專案根目錄執行：composer install';
    exit;
}
require_once $autoload;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$rows = crud_fetch_all(
    'SELECT Coupon_Code, OrderNo FROM coupon_d WHERE Coupon_PKey = :pk ORDER BY PKey DESC',
    ['pk' => $couponPKey]
);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('優惠券');
$sheet->setCellValueExplicit([1, 1], '優惠代碼', DataType::TYPE_STRING);
$sheet->setCellValueExplicit([2, 1], '訂單編號', DataType::TYPE_STRING);

$rowIdx = 2;
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $sheet->setCellValueExplicit([1, $rowIdx], (string)($row['Coupon_Code'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValueExplicit([2, $rowIdx], (string)($row['OrderNo'] ?? ''), DataType::TYPE_STRING);
    $rowIdx++;
}

$fileName = 'coupon_' . date('Ymd_His') . '.xlsx';
$tmpFile = tempnam(sys_get_temp_dir(), 'cpn_xlsx_');
if ($tmpFile === false) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '無法建立暫存檔';
    exit;
}

try {
    (new Xlsx($spreadsheet))->save($tmpFile);
} catch (Throwable $e) {
    @unlink($tmpFile);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '匯出失敗';
    exit;
}

while (ob_get_level() > 0) {
    ob_end_clean();
}
if (function_exists('header_remove')) {
    header_remove('Content-Type');
}

$safeName = str_replace(['"', "\r", "\n"], '', $fileName);
$fileSize = filesize($tmpFile);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: max-age=0, no-store, no-cache, must-revalidate');
header('Pragma: public');
if ($fileSize !== false) {
    header('Content-Length: ' . (string)$fileSize);
}
readfile($tmpFile);
@unlink($tmpFile);
exit;
