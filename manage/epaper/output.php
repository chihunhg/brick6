<?php
declare(strict_types=1);

/** Excel 二進位下載 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';
require_once '../_module.php';
require_once __DIR__ . '/_form_data.php';

[$PDO_Cond, $Cond_Array] = crud_module_where();

$filter = is_array($filter_array ?? null) ? $filter_array : [];
$kwPlaceholder = '請輸入 E-Mail 搜尋';
epaper_list_apply_keyword_search($PDO_Cond, $Cond_Array, $filter, $kwPlaceholder);
crud_list_apply_opendate_range($PDO_Cond, $Cond_Array, $filter, 'dtDate');

$sql = 'SELECT * FROM epaper ' . $PDO_Cond . ' ORDER BY PKey DESC';
$rows = crud_fetch_all($sql, $Cond_Array);

$exportDate = static function (array $row, string $col, int $mode): string {
    $raw = crud_row_val($row, $col);
    if (!is_scalar($raw) || trim((string)$raw) === '') {
        return '';
    }
    return function_exists('Date_EN') ? (string)Date_EN($raw, $mode) : trim((string)$raw);
};

$tableData = [['E-Mail', '建檔日期']];
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $tableData[] = [
        (string)($row['EMail'] ?? ''),
        $exportDate($row, 'dtUDate', 0),
    ];
}

xlsx_download($tableData, 'epaper_' . date('Ymd_His') . '.xlsx', 'epaper');
