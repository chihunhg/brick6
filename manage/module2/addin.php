<?php
declare(strict_types=1);

require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

require_once '_form_data.php';
module_require_admin();

$tables     = module_detail_tables();
$table_name = (string)($tables['master'] ?? 'module_p');
$table_lang = (string)($tables['lang'] ?? '');
$listIntType = (int)($detailConfig['list_int_type'] ?? 2);

$csrfKey = (string)($detailConfig['csrf'] ?? 'module2_addin');
crud_csrf_verify_form($csrfKey);

global $filter_array;

$WorkFile = (string)($_SERVER['PHP_SELF'] ?? 'addin.php');
$Login_ID = (string)($_SESSION['Login_ID'] ?? '');
$GLOBALS['WorkFile'] = $WorkFile;
$GLOBALS['Login_ID']  = $Login_ID;

$formPKey = safe_int($filter_array['PKey'] ?? 0);
if ($formPKey <= 0) {
    $formPKey = safe_int($filter_array['Module_PKey'] ?? 0);
}
if ($formPKey <= 0) {
    $formPKey = safe_int($GLOBALS['Update_PKey'] ?? 0);
}

$returnUrl = crud_addin_return_url($formPKey, 'list.php', 'update.php');
$listUrl   = module_addin_list_redirect_url($filter_array);
if ($listUrl === 'list.php' || str_ends_with($listUrl, '/list.php')) {
    $listUrl = 'list.php';
}

$tdkOpts = [
    'tdk_only' => true,
    'int_type' => $listIntType,
];
$filter_array['intType'] = (string)$listIntType;

$MSG = module_addin_validate($filter_array, $tdkOpts);
if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

if ($formPKey <= 0) {
    crud_form_error_redirect('參數錯誤：PKey 無效', $listUrl);
}

$exists = crud_fetch_one(
    'SELECT PKey, intType FROM ' . $table_name . ' WHERE PKey = :pk LIMIT 1',
    ['pk' => $formPKey]
);
if ($exists === null) {
    crud_form_error_redirect('查無要修改資料', $returnUrl);
}
if ((int)($exists['intType'] ?? 0) !== $listIntType) {
    crud_form_error_redirect('此單元非美工頁面類型', $returnUrl);
}

$data_array = module_addin_build_master_data($filter_array, $tdkOpts);
$data_array['intType'] = SqlFilter($listIntType, 'int');

try {
    $upsert     = crud_upsert_master($table_name, $formPKey, $data_array, 'PKey', 'dtDate');
    $modulePKey = $upsert['pkey'];
    $show       = $upsert['action'];

    if ($table_lang !== '') {
        crud_save_module_lang_slots($table_lang, $modulePKey, $filter_array);
    }

    $table_qa = (string)($detailConfig['qa'] ?? 'module_qa');
    if ($table_qa !== '') {
        crud_save_module_qa_slots($table_qa, $modulePKey, $filter_array);
    }

    if (isset($_SESSION['Pkey_98'])) {
        unset($_SESSION['Pkey_98']);
    }

    manage_alert_script($show, $listUrl);
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

    crud_form_error_redirect('資料寫入失敗', $listUrl);
}
