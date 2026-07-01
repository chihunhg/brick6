<?php
declare(strict_types=1);
/**
 * product 表單送出
 */

$manage_csp_editor = true;
require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);
require_once __DIR__ . '/_form_data.php';

$tables     = manage_detail_tables();
$table_name = $tables['master'];
$table_lang = (string)($tables['lang'] ?? '');
$table_msg  = (string)($tables['msg'] ?? '');
$table_img  = ($tables['img'] ?? '') !== '' ? (string)$tables['img'] : ($table_name . '_img');
$FKName     = $tables['fk'];
$moduleCol  = (string)($tables['module_pk_col'] ?? 'Module_PKey');
$hasSort    = (bool)($detailConfig['has_sort'] ?? true);
$maxSlots   = (int)($detailConfig['img_slot_max'] ?? 8);

$csrfKey = (string)($detailConfig['csrf'] ?? 'product_addin');
crud_csrf_verify_form($csrfKey);

global $filter_array, $file_array, $Layer, $Class_Name;
$file_array = $file_array ?? [];
$Layer      = (int)($Layer ?? 1);

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
    $modulePKey = safe_int($filter_array['manNo'] ?? 0);
}

$returnUrl = crud_addin_return_url($formPKey);

$MSG = '';
if ($hasSort && safe_int($filter_array['Sort'] ?? 0) < 0) {
    $MSG .= "【順序】空白或非數字格式\n";
}
$MSG .= crud_validate_lang_show_strname($filter_array);
$MSG .= crud_addin_validate_layer_classes($filter_array, $Layer);

$photoSlots = crud_resolve_photo_upload_slots($filter_array, $file_array, $maxSlots);
$MAX_CONTENT_BYTES = $photoSlots['slot_max'] * 1024 * 1024;
$b64 = crud_decode_b64_content_multilang($filter_array, $MAX_CONTENT_BYTES, 6);
$MSG .= $b64['error'];
$DecodedContents = $b64['contents'];

$uploadDirInfo = crud_upload_dir();
$upload_foder  = $uploadDirInfo['dir'];
$MSG          .= $uploadDirInfo['error'];

$ForderName   = (string)($detailConfig['forder_prefix'] ?? 'product_');
$size_bytes   = (int)($size_bytes ?? 2000 * 1024);
$file_size    = (int)($file_size ?? 6000 * 1024);

$Photo  = [];
$PhotoW = [];
$PhotoH = [];
$PhotoM = [];

for ($i = 1; $i <= $maxSlots; $i++) {
    if (isset($filter_array['PhotoM' . $i])) {
        $PhotoM[$i] = (string)$filter_array['PhotoM' . $i];
    }
}

$imageIndices = range(1, min(6, $maxSlots));
$fileIndices  = range(7, $maxSlots);

$uploadResultImg = crud_upload_file_slots($file_array, $upload_foder, $imageIndices, [
    'forder_prefix' => $ForderName,
    'size_bytes'    => $size_bytes,
    'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'webp'],
    'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
    'field_prefix'  => 'Photo',
    'resize_thumb'  => true,
]);

$uploadResultFile = crud_upload_file_slots($file_array, $upload_foder, $fileIndices, [
    'forder_prefix' => $ForderName,
    'size_bytes'    => $file_size,
    'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt'],
    'allowed_mimes' => [],
    'field_prefix'  => 'Photo',
    'resize_thumb'  => false,
]);

foreach ([$uploadResultImg, $uploadResultFile] as $uploadResult) {
    foreach ((array)($uploadResult['photos'] ?? []) as $idx => $filename) {
        $Photo[(int)$idx] = (string)$filename;
    }
    foreach ((array)($uploadResult['photoW'] ?? []) as $idx => $w) {
        $PhotoW[(int)$idx] = (int)$w;
    }
    foreach ((array)($uploadResult['photoH'] ?? []) as $idx => $h) {
        $PhotoH[(int)$idx] = (int)$h;
    }
    $MSG .= (string)($uploadResult['messages'] ?? '');
}

$forderVal = rtrim((string)($uploadResultFile['monthdir'] ?? $uploadResultImg['monthdir'] ?? date('Ym')), "\\/");

if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

if (function_exists('crud_addin_verify_master_module')) {
    $moduleErr = crud_addin_verify_master_module($table_name, $formPKey, $modulePKey, $moduleCol);
    if ($moduleErr !== null && $formPKey > 0) {
        crud_form_error_redirect($moduleErr, $returnUrl);
    }
}

$data_array = [
    $moduleCol  => SqlFilter($modulePKey, 'int'),
    'strName'   => SqlFilter((string)($filter_array['strName1'] ?? ''), 'tab'),
    'strNo'     => SqlFilter((string)($filter_array['strNo'] ?? ''), 'tab'),
    'dtUDate'   => date('Y-m-d H:i:s'),
    'UserID'    => SqlFilter($Login_ID, 'tab'),
];
if ($hasSort) {
    $data_array['Sort'] = SqlFilter($filter_array['Sort'] ?? 0, 'int');
}
if (isset($filter_array['Upload'])) {
    $data_array['Upload'] = SqlFilter($filter_array['Upload'], 'tab');
}
if (isset($filter_array['Home'])) {
    $data_array['Home'] = SqlFilter($filter_array['Home'] ?? 'No', 'tab');
}
if ($Layer >= 1 && isset($filter_array['Class1'])) {
    $data_array['Class1_PKey'] = SqlFilter($filter_array['Class1'], 'int');
}
if ($Layer >= 2 && isset($filter_array['Class2'])) {
    $data_array['Class2_PKey'] = SqlFilter($filter_array['Class2'], 'int');
}
if ($Layer >= 3 && isset($filter_array['Class3'])) {
    $data_array['Class3_PKey'] = SqlFilter($filter_array['Class3'], 'int');
}

if (crud_table_has_column($table_name, 'Keywords')) {
    $data_array['Keywords'] = SqlFilter(
        crud_collect_lang_keywords_from_filter($filter_array, 1),
        'tab'
    );
}
if (crud_table_has_column($table_name, 'Description') && isset($filter_array['Description1'])) {
    $data_array['Description'] = SqlFilter((string)$filter_array['Description1'], 'tab');
}

$data_array = crud_filter_row_for_table($table_name, $data_array);

try {
    $upsert     = crud_upsert_master($table_name, $formPKey, $data_array);
    $parentPKey = $upsert['pkey'];
    $show       = $upsert['action'];

    if ($table_lang !== '') {
        crud_save_lang_slots($table_lang, $FKName, $parentPKey, $filter_array);
    }
    if ($table_msg !== '') {
        crud_save_msg_blocks_multilang(
            $table_msg,
            $FKName,
            $parentPKey,
            $DecodedContents,
            $filter_array,
            6
        );
    }
    if ($table_img !== '' && function_exists('product_save_img_slots')) {
        product_save_img_slots(
            $table_img,
            $FKName,
            $parentPKey,
            $forderVal,
            $Photo,
            $PhotoW,
            $PhotoH,
            $PhotoM,
            $filter_array,
            $maxSlots,
            $upload_foder
        );
    }
    if (function_exists('product_save_relations') && chkTable('product_relation')) {
        product_save_relations($parentPKey, $filter_array);
    }

    $actionShow = $show;
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

    $failMsg = '資料寫入失敗';
    if ($e->getMessage() !== '') {
        $failMsg .= '：' . $e->getMessage();
    }
    crud_form_error_redirect($failMsg, $returnUrl);
}
