<?php
declare(strict_types=1);

/** Excel 二進位下載：略過 _inc.php 的 text/html 標頭 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';
require_once '../_module.php';
require_once __DIR__ . '/_form_data.php';

[$PDO_Cond, $Cond_Array] = crud_module_where();

$filter = is_array($filter_array ?? null) ? $filter_array : [];
$kwPlaceholder = '請輸入姓名或帳號搜尋';
member_list_apply_keyword_search($PDO_Cond, $Cond_Array, $filter, $kwPlaceholder);
crud_list_apply_opendate_range($PDO_Cond, $Cond_Array, $filter, 'dtDate');

$sql = 'SELECT * FROM member ' . $PDO_Cond . ' ORDER BY dtDate DESC, PKey DESC';
$rows = crud_fetch_all($sql, $Cond_Array);

$memberExportCell = static function (array $row, string $col): string {
    $v = crud_row_val($row, $col);
    return is_scalar($v) ? trim((string)$v) : '';
};

$memberExportBirth = static function (array $row) use ($memberExportCell): string {
    $parts = array_filter([
        $memberExportCell($row, 'Birth_Y'),
        $memberExportCell($row, 'Birth_M'),
        $memberExportCell($row, 'Birth_D'),
    ], static fn(string $v): bool => $v !== '');
    return implode('/', $parts);
};

$memberExportDate = static function (array $row, string $col, int $mode): string {
    $raw = crud_row_val($row, $col);
    if (!is_scalar($raw) || trim((string)$raw) === '') {
        return '';
    }
    return function_exists('Date_EN') ? (string)Date_EN($raw, $mode) : trim((string)$raw);
};

$tableData = [[
    '會員帳號', '姓名', '手機', '電話', '性別', '生日',
    '郵遞區號', '縣市', '鄉鎮市區', '聯絡地址', '修改日期', '加入日期',
]];

foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $tableData[] = [
        $memberExportCell($row, 'EMail'),
        $memberExportCell($row, 'strName'),
        $memberExportCell($row, 'Mobile'),
        $memberExportCell($row, 'Tel'),
        $memberExportCell($row, 'Sex'),
        $memberExportBirth($row),
        $memberExportCell($row, 'PostCode'),
        $memberExportCell($row, 'strCounty'),
        $memberExportCell($row, 'strCity'),
        $memberExportCell($row, 'Address'),
        $memberExportDate($row, 'dtUDate', 0),
        $memberExportDate($row, 'dtDate', 1),
    ];
}

xlsx_download($tableData, 'member_' . date('Ymd_His') . '.xlsx', 'member');
