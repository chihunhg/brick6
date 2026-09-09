<?php
declare(strict_types=1);

/** Excel 二進位下載：略過 _inc.php 的 text/html 標頭 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';
require_once '../_module.php';
require_once __DIR__ . '/_form_data.php';

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

xlsx_download($tableData, 'order_' . date('Ymd_His') . '.xlsx', '訂單');
