<?php
declare(strict_types=1);
/**
 * video 表單送出
 */

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

$tables     = manage_detail_tables();
$table_name = $tables['master'];
$table_lang = (string)($tables['lang'] ?? '');
$table_img  = ($tables['img'] ?? '') !== '' ? (string)$tables['img'] : '';
$FKName     = $tables['fk'];
$moduleCol  = (string)($tables['module_pk_col'] ?? 'Module_PKey');

$csrfKey = (string)($detailConfig['csrf'] ?? 'video_addin');
crud_csrf_verify_form($csrfKey);

global $filter_array, $file_array, $array_lang, $Layer;
$file_array = $file_array ?? [];
$Layer = (int)($Layer ?? 1);

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
$showListField = manage_module_show_detail_field('list');
$photoFallback = $showListField
    ? max(1, (int)($detailConfig['img_slot_max'] ?? $detailConfig['photo_slots'] ?? 1))
    : 0;

$MSG = '';
if (safe_int($filter_array['Sort'] ?? 0) < 0) {
    $MSG .= "【順序】空白或非數字格式\n";
}
$MSG .= crud_validate_lang_show_strname($filter_array);
$MSG .= crud_addin_validate_layer_classes($filter_array, $Layer);

$langCount = !empty($array_lang) && is_array($array_lang) ? count($array_lang) : 6;
for ($i = 1; $i <= $langCount; $i++) {
    if (!empty($filter_array['Show' . $i]) && $filter_array['Show' . $i] === 'Y') {
        if (trim((string)($filter_array['Movielink' . $i] ?? '')) === '') {
            $MSG .= '【' . ($array_lang[$i] ?? ('語系' . $i)) . "】影音連結為空白\n";
        }
    }
}

if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

if ($formPKey > 0 && $modulePKey > 0 && crud_is_safe_sql_identifier($moduleCol)) {
    $row = crud_fetch_one(
        'SELECT `' . $moduleCol . '` FROM `' . $table_name . '` WHERE `PKey` = :pk LIMIT 1',
        ['pk' => $formPKey]
    );
    $rowModule = (int)($row[$moduleCol] ?? 0);
    if ($row === null || $rowModule !== $modulePKey) {
        crud_form_error_redirect('查無要修改資料或無權限', $returnUrl);
    }
}

$Photo  = [];
$PhotoW = [];
$PhotoH = [];
$PhotoM = [];
$forderVal = date('Ym');
$upload_foder = crud_upload_base();

if ($showListField && $table_img !== '') {
    $photoSlots = crud_resolve_photo_upload_slots($filter_array, $file_array, $photoFallback);
    $uploadDirInfo = crud_upload_dir();
    $upload_foder  = $uploadDirInfo['dir'];
    $MSG          .= $uploadDirInfo['error'];

    if ($MSG !== '') {
        crud_form_error_redirect($MSG, $returnUrl);
    }

    $ForderName = (string)($detailConfig['forder_prefix'] ?? 'video_');
    $indices    = $photoSlots['indices'];
    $maxSlots   = min($photoSlots['max_slots'], $photoFallback);

    for ($i = 1; $i <= $maxSlots; $i++) {
        if (isset($filter_array['PhotoM' . $i])) {
            $PhotoM[$i] = (string)$filter_array['PhotoM' . $i];
        }
    }

    $uploadResult = crud_upload_file_slots($file_array, $upload_foder, $indices, [
        'forder_prefix' => $ForderName,
        'size_bytes'    => (int)($size_bytes ?? 2000 * 1024),
        'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'webp'],
        'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
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
    $MSG .= (string)($uploadResult['messages'] ?? '');
    $forderVal = rtrim((string)($uploadResult['monthdir'] ?? date('Ym')), "\\/");

    if ($MSG !== '') {
        crud_form_error_redirect($MSG, $returnUrl);
    }
}

$data_array = [
    $moduleCol  => SqlFilter($modulePKey, 'int'),
    'Sort'      => SqlFilter($filter_array['Sort'] ?? 0, 'int'),
    'strName'   => SqlFilter((string)($filter_array['strName1'] ?? ''), 'tab'),
    'dtUDate'   => date('Y-m-d H:i:s'),
    'UserID'    => SqlFilter($Login_ID, 'tab'),
];
if (isset($filter_array['Upload'])) {
    $data_array['Upload'] = SqlFilter($filter_array['Upload'], 'tab');
}
if (crud_table_has_column($table_name, 'Interview')) {
    $data_array['Interview'] = SqlFilter((string)($filter_array['Interview1'] ?? ''), 'tab');
}
if (crud_table_has_column($table_name, 'Movielink')) {
    $data_array['Movielink'] = SqlFilter((string)($filter_array['Movielink1'] ?? ''), 'tab');
}
if (crud_table_has_column($table_name, 'Description')) {
    $data_array['Description'] = SqlFilter((string)($filter_array['Description1'] ?? ''), 'tab');
}
if (crud_table_has_column($table_name, 'Keywords')) {
    $data_array['Keywords'] = SqlFilter(
        crud_collect_lang_keywords_from_filter($filter_array, 1),
        'tab'
    );
}
$data_array = crud_addin_append_publish_master_fields($data_array, $filter_array, $Layer, $table_name);
$data_array = crud_filter_row_for_table($table_name, $data_array);

try {
    $upsert     = crud_upsert_master($table_name, $formPKey, $data_array);
    $parentPKey = $upsert['pkey'];
    $show       = $upsert['action'];

    if ($table_lang !== '') {
        crud_save_lang_slots($table_lang, $FKName, $parentPKey, $filter_array);
    }
    if ($table_img !== '' && $showListField && $photoFallback > 0) {
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
            $photoFallback,
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
