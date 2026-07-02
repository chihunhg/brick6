<?php
declare(strict_types=1);
/**
 * knowledge 表單送出（結構同 class1/addin.php；主檔為單一標題欄位）
 */

$manage_csp_editor = true;
require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

$tables     = manage_detail_tables();
$table_name = $tables['master'];
$table_msg  = (string)($tables['msg'] ?? '');
$table_img  = ($tables['img'] ?? '') !== '' ? (string)$tables['img'] : ($table_name . '_img');
$FKName     = $tables['fk'];
$moduleCol  = (string)($tables['module_pk_col'] ?? 'Module_PKey');

$csrfKey = (string)($detailConfig['csrf'] ?? 'knowledge_addin');
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

/* ── 驗證 ───────────────────────────────────────────── */
$MSG = '';
if (safe_int($filter_array['Sort'] ?? 0) <= 0) {
    $MSG .= "【順序】空白或非數字格式\n";
}
if (trim((string)($filter_array['strName'] ?? '')) === '') {
    $MSG .= "【標題】為空白\n";
}
$MSG .= crud_addin_validate_layer_classes($filter_array, $Layer);

$b64 = crud_decode_b64_content_fields($filter_array, 5 * 1024 * 1024);
$MSG .= $b64['error'];
$DecodedContents = $b64['decoded'];

/* ── 上傳 ───────────────────────────────────────────── */
$uploadDirInfo = crud_upload_dir();
$upload_foder  = $uploadDirInfo['dir'];
$MSG          .= $uploadDirInfo['error'];

$photoSlots = crud_resolve_photo_upload_slots($filter_array, $file_array, (int)($detailConfig['img_slot_max'] ?? 1));
$ForderName    = $ForderName ?? 'knowledge_';
$config_total  = $photoSlots['config_total'];
$size_bytes    = (int)($size_bytes ?? 6000 * 1024);
$indices       = $photoSlots['indices'];
$maxSlots      = $photoSlots['max_slots'];

$allowed_exts = [
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico',
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
    'zip', 'rar',
];
$allowed_mimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/vnd.microsoft.icon',
    'application/pdf',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'application/zip', 'application/x-zip-compressed',
    'application/x-rar-compressed', 'application/vnd.rar',
];

$Photo  = [];
$PhotoW = [];
$PhotoH = [];
$PhotoM = [];

for ($i = 1; $i <= $maxSlots; $i++) {
    if (isset($filter_array['PhotoM' . $i])) {
        $PhotoM[$i] = (string)$filter_array['PhotoM' . $i];
    }
}

$uploadResult = crud_upload_file_slots($file_array, $upload_foder, $indices, [
    'forder_prefix' => $ForderName,
    'size_bytes'    => $size_bytes,
    'allowed_exts'  => $allowed_exts,
    'allowed_mimes' => $allowed_mimes,
    'field_prefix'  => 'Photo',
    'resize_thumb'  => true,
]);

foreach ((array)($uploadResult['photos'] ?? []) as $idx => $filename) {
    $Photo[(int)$idx] = (string)$filename;
}
foreach ((array)($uploadResult['photoW'] ?? []) as $idx => $w) {
    $PhotoW[(int)$idx] = (int)$w;
}
foreach ((array)($uploadResult['photoH'] ?? []) as $idx => $h) {
    $PhotoH[(int)$idx] = (int)$h;
}

$MSG      .= (string)($uploadResult['messages'] ?? '');
$forderVal = rtrim((string)($uploadResult['monthdir'] ?? date('Ym')), "\\/");

if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

if ($formPKey > 0 && $modulePKey > 0 && crud_is_safe_sql_identifier($moduleCol)) {
    $row = crud_fetch_one(
        'SELECT `' . $moduleCol . '` FROM `' . $table_name . '` WHERE `PKey` = :pk LIMIT 1',
        ['pk' => $formPKey]
    );
    $rowModule = $row !== null && function_exists('crud_row_int')
        ? crud_row_int($row, $moduleCol)
        : (int)($row[$moduleCol] ?? 0);
    if ($row === null || $rowModule !== $modulePKey) {
        crud_form_error_redirect('查無要修改資料或無權限', $returnUrl);
    }
}

/* ── 主檔欄位 ───────────────────────────────────────── */
$data_array = [
    $moduleCol  => SqlFilter($modulePKey, 'int'),
    'strName'   => SqlFilter((string)($filter_array['strName'] ?? ''), 'tab'),
    'Sort'      => SqlFilter($filter_array['Sort'] ?? 0, 'int'),
    'Upload'    => SqlFilter($filter_array['Upload'] ?? 'Yes', 'tab'),
    'dtUDate'   => date('Y-m-d H:i:s'),
    'UserID'    => SqlFilter($Login_ID, 'tab'),
];
$data_array = crud_addin_append_publish_master_fields($data_array, $filter_array, $Layer);

/* ── 寫入 ───────────────────────────────────────────── */
try {
    $upsert     = crud_upsert_master($table_name, $formPKey, $data_array);
    $parentPKey = $upsert['pkey'];
    $show       = $upsert['action'];

    if ($table_msg !== '') {
        crud_save_msg_blocks($table_msg, $FKName, $parentPKey, $DecodedContents, $filter_array, 6);
    }
    if ($table_img !== '') {
        crud_save_img_slots(
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

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['error_msg'] = '資料寫入失敗';

    header('Location: ' . crud_addin_return_url($formPKey));
    exit;
}
