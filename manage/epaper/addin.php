<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig, true);

$csrfKey = (string)($detailConfig['csrf'] ?? 'epaper_addin');
crud_csrf_verify_form($csrfKey);

require_once __DIR__ . '/_form_data.php';

global $filter_array;
$WorkFile = (string)($_SERVER['PHP_SELF'] ?? 'addin.php');
$Login_ID = (string)($_SESSION['Login_ID'] ?? '');
$GLOBALS['WorkFile'] = $WorkFile;
$GLOBALS['Login_ID']  = $Login_ID;

$formPKey = safe_int($filter_array['PKey'] ?? 0);
if ($formPKey <= 0) {
    $formPKey = safe_int($GLOBALS['Update_PKey'] ?? 0);
}

$modulePKey = (int)($GLOBALS['Module_PKey'] ?? 0);
if ($modulePKey <= 0) {
    $modulePKey = epaper_detail_resolve_module_pkey();
}

$returnUrl = crud_addin_return_url($formPKey);

$MSG = epaper_validate_form($filter_array, $modulePKey);
if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

if ($formPKey > 0 && $modulePKey > 0) {
    $row = crud_fetch_one('SELECT Module_PKey FROM epaper WHERE PKey = :pk LIMIT 1', ['pk' => $formPKey]);
    $rowModule = (int)($row['Module_PKey'] ?? 0);
    if ($row === null || $rowModule !== $modulePKey) {
        crud_form_error_redirect('查無要修改資料或無權限', $returnUrl);
    }
}

$isNew = $formPKey <= 0;
$data_array = epaper_build_master_data($filter_array, $modulePKey, $Login_ID);
if ($isNew) {
    $data_array['dtDate'] = date('Y-m-d H:i:s');
}

try {
    $upsert = crud_upsert_master('epaper', $formPKey, $data_array);
    $actionShow = $upsert['action'];
    require_once '../_return_list.php';
    exit;
} catch (Throwable $e) {
    if (function_exists('sql_error')) {
        sql_error('', $e->getMessage(), $WorkFile, $Login_ID !== '' ? $Login_ID : 'system', $e->getFile(), $e->getLine());
    }
    crud_form_error_redirect('資料寫入失敗', $returnUrl);
}
