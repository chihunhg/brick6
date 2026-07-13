<?php
declare(strict_types=1);

/** Excel 二進位下載 */
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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('epaper');
$sheet->setCellValueExplicit([1, 1], 'E-Mail', DataType::TYPE_STRING);
$sheet->setCellValueExplicit([2, 1], '建檔日期', DataType::TYPE_STRING);

$rowIdx = 2;
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $sheet->setCellValueExplicit([1, $rowIdx], (string)($row['EMail'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValueExplicit([2, $rowIdx], $exportDate($row, 'dtUDate', 0), DataType::TYPE_STRING);
    $rowIdx++;
}

$fileName = 'epaper_' . date('Ymd_His') . '.xlsx';
$tmpFile = tempnam(sys_get_temp_dir(), 'epa_xlsx_');
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
