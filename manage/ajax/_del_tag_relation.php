<?php
declare(strict_types=1);

require_once '../_inc.php';

$pkey = safe_int($filter_array['PKey'] ?? 0);
if ($pkey <= 0) {
    crud_json_response(false, '參數錯誤');
}

$table = 'tag_d';
if (!function_exists('chkTable') || !chkTable($table)) {
    crud_json_response(false, '資料表不存在');
}

$pdo = new dbPDO();
if (!$pdo->delete($table, 'PKey', SqlFilter($pkey, 'int'))) {
    $err = method_exists($pdo, 'getErrorMessage') ? trim((string)$pdo->getErrorMessage()) : '';
    crud_json_response(false, $err !== '' ? $err : '刪除失敗');
}
$pdo->close();

crud_json_response(true, 'OK');
