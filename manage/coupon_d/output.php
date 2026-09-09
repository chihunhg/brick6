<?php
declare(strict_types=1);

/** Excel 二進位下載 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';
require_once '../_module.php';
require_once __DIR__ . '/_form_data.php';

$couponPKey = coupon_d_resolve_coupon_pkey(is_array($filter_array ?? null) ? $filter_array : []);
if ($couponPKey <= 0) {
    $couponPKey = safe_int($_GET['Coupon_PKey'] ?? $_GET['PKey'] ?? 0);
}
if ($couponPKey <= 0) {
    xlsx_fail_html('參數錯誤');
}

$rows = coupon_d_fetch_export_rows($couponPKey);

$tableData = [[
    '優惠券開始日', '優惠券結束日', '會員帳號', '會員姓名',
    '優惠券使用日期', '折抵訂單', '訂單總金額', '訂單折抵金額',
]];

foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $orderNo = trim((string)($row['OrderNo'] ?? ''));
    $line = [
        coupon_date_for_list($row['OpenDate'] ?? ''),
        coupon_date_for_list($row['EndDate'] ?? ''),
        (string)($row['EMail'] ?? $row['Email'] ?? ''),
        (string)($row['Member_Name'] ?? ''),
        '未使用',
        '',
        '',
        '',
    ];
    if ($orderNo !== '') {
        $line[4] = coupon_date_for_list($row['Order_Date'] ?? '');
        $line[5] = $orderNo;
        $line[6] = (string)($row['TotalPrice'] ?? '');
        $line[7] = (string)($row['Coupon_Price'] ?? '');
    }
    $tableData[] = $line;
}

xlsx_download($tableData, 'coupon_' . date('Ymd_His') . '.xlsx', '活動與優惠');
