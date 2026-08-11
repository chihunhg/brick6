<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig, true);

require_once '_form_data.php';

$table_name = (string)($detailConfig['master'] ?? 'module_class');
$csrfKey    = (string)($detailConfig['csrf'] ?? 'module_class_addin');
crud_csrf_verify_form($csrfKey);

global $filter_array;

$WorkFile = (string)($_SERVER['PHP_SELF'] ?? 'addin.php');
$Login_ID = (string)($_SESSION['Login_ID'] ?? '');
$GLOBALS['WorkFile'] = $WorkFile;
$GLOBALS['Login_ID']  = $Login_ID;

$formPKey = safe_int($filter_array['PKey'] ?? 0);
if ($formPKey <= 0) {
    $formPKey = safe_int($GLOBALS['Update_PKey'] ?? 0);
}

$returnUrl = crud_addin_return_url($formPKey);

$MSG = module_class_addin_validate($filter_array);
if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

if ($formPKey > 0) {
    $exists = crud_fetch_one(
        'SELECT PKey FROM `' . $table_name . '` WHERE PKey = :pk LIMIT 1',
        ['pk' => $formPKey]
    );
    if ($exists === null) {
        crud_form_error_redirect('查無要修改資料', $returnUrl);
    }
}

$isNew = $formPKey <= 0;
$data_array = module_class_addin_build_master_data($filter_array, $Login_ID, $isNew);

try {
    $upsert = crud_upsert_master($table_name, $formPKey, $data_array, 'PKey', 'dtDate');
    $actionShow = $upsert['action'];
    require_once '../_return_list.php';
    exit;
} catch (Throwable $e) {
    if (function_exists('sql_error')) {
        sql_error(
            '',
            $e->getMessage(),
            $WorkFile,
            $Login_ID !== '' ? $Login_ID : 'system',
            $e->getFile(),
            $e->getLine()
        );
    }
    crud_form_error_redirect('資料寫入失敗', $returnUrl);
}
