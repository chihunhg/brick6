<?php
declare(strict_types=1);

/** Excel 二進位下載 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';
require_once '../_module.php';

$couponPKey = safe_int($filter_array['PKey'] ?? $_GET['PKey'] ?? 0);
if ($couponPKey <= 0) {
    xlsx_fail_html('參數錯誤');
}

$rows = crud_fetch_all(
    'SELECT Coupon_Code, OrderNo FROM coupon_d WHERE Coupon_PKey = :pk ORDER BY PKey DESC',
    ['pk' => $couponPKey]
);

$tableData = [['優惠代碼', '訂單編號']];
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $tableData[] = [
        (string)($row['Coupon_Code'] ?? ''),
        (string)($row['OrderNo'] ?? ''),
    ];
}

xlsx_download($tableData, 'coupon_' . date('Ymd_His') . '.xlsx', '優惠券');
