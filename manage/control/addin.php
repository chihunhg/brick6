<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig, true);

$csrfKey = (string)($detailConfig['csrf'] ?? 'manage_form');
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
if ($formPKey <= 0 && !empty($filter_array['id'])) {
    $decoded = verify_id((string)$filter_array['id'], 'webcontrol');
    if (ctype_digit((string)$decoded)) {
        $formPKey = (int)$decoded;
    }
}

$modulePKey = (int)($GLOBALS['Module_PKey'] ?? 0);
if ($modulePKey <= 0) {
    $modulePKey = safe_int($filter_array['manNo'] ?? 0);
}

$returnUrl = crud_addin_return_url($formPKey);

$MSG = control_validate_form($filter_array, $formPKey);
if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

if ($formPKey > 0) {
    $row = crud_fetch_one(
        'SELECT PKey, intType FROM webcontrol WHERE PKey = :pk LIMIT 1',
        ['pk' => $formPKey]
    );
    if ($row === null || (int)($row['intType'] ?? -1) !== 0) {
        crud_form_error_redirect('查無要修改資料或無權限', $returnUrl);
    }
}

$isNew = $formPKey <= 0;
$hashPw = $isNew || trim((string)($filter_array['strPW'] ?? '')) !== '';
$data_array = control_build_master_data($filter_array, $modulePKey, $Login_ID, $hashPw);
if ($isNew) {
    $data_array['intType'] = SqlFilter(0, 'int');
}

try {
    $upsert = crud_upsert_master('webcontrol', $formPKey, $data_array);
    $actionShow = $upsert['action'];
    require_once '../_return_list.php';
    exit;
} catch (Throwable $e) {
    if (function_exists('sql_error')) {
        sql_error('', $e->getMessage(), $WorkFile, $Login_ID !== '' ? $Login_ID : 'system', $e->getFile(), $e->getLine());
    }
    crud_form_error_redirect('資料寫入失敗', $returnUrl);
}
