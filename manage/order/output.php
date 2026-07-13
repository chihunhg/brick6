<?php
declare(strict_types=1);

/** Excel 二進位下載：略過 _inc.php 的 text/html 標頭 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';
require_once '../_module.php';
require_once __DIR__ . '/_form_data.php';

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

$filter = is_array($filter_array ?? null) ? $filter_array : [];
$rows = order_export_fetch_rows($filter);

$exportCell = static function (array $row, string $col): string {
    $v = crud_row_val($row, $col);
    return is_scalar($v) ? trim((string)$v) : '';
};

$tableData = [[
    '訂單日期', '訂單編號', '訂購人', '電話', '送貨地址', '發票類型', '發票抬頭', '統一編號',
    '付款方式', '處理狀況', '運費', '應付金額', '品名', '顏色', '品號', '商品編號', '國際條碼',
    '金額', '數量', '小計',
]];

foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $price = (int)($row['Price'] ?? 0);
    $qty = (int)($row['Quantity'] ?? 0);
    $addr = $exportCell($row, 'PostCode')
        . $exportCell($row, 'strCounty')
        . $exportCell($row, 'strCity')
        . $exportCell($row, 'Address');
    $dtRaw = crud_row_val($row, 'dtDate');
    $dtStr = is_scalar($dtRaw) && trim((string)$dtRaw) !== '' && function_exists('Date_EN')
        ? (string)Date_EN($dtRaw, 1)
        : $exportCell($row, 'dtDate');

    $tableData[] = [
        $dtStr,
        $exportCell($row, 'OrderNo'),
        $exportCell($row, 'strName'),
        $exportCell($row, 'Mobile'),
        $addr,
        $exportCell($row, 'Invoice'),
        $exportCell($row, 'Title'),
        $exportCell($row, 'InvoiceNo'),
        PayType((int)($row['intPay'] ?? 0)),
        FlowState((int)($row['intState'] ?? 0)),
        $exportCell($row, 'Charge'),
        $exportCell($row, 'TotalPrice'),
        $exportCell($row, 'ProductName') !== '' ? $exportCell($row, 'ProductName') : $exportCell($row, 'strName'),
        $exportCell($row, 'ColorName'),
        $exportCell($row, 'strNo'),
        $exportCell($row, 'ProductNo'),
        $exportCell($row, 'Barcode'),
        (string)$price,
        (string)$qty,
        (string)($price * $qty),
    ];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('訂單');

$rowIdx = 1;
foreach ($tableData as $line) {
    $colIdx = 1;
    foreach ($line as $cell) {
        $sheet->setCellValueExplicit(
            [$colIdx, $rowIdx],
            (string)$cell,
            DataType::TYPE_STRING
        );
        $colIdx++;
    }
    $rowIdx++;
}

$fileName = 'order_' . date('Ymd_His') . '.xlsx';
$tmpFile = tempnam(sys_get_temp_dir(), 'ord_xlsx_');
if ($tmpFile === false) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '無法建立暫存檔，匯出失敗';
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
