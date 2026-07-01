<?php
declare(strict_types=1);
/**
 * 廣告（dbad / dbad_lang / dbad_img）表單送出
 */

$manage_csp_editor = true;
require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

$tables     = manage_detail_tables();
$table_name = $tables['master'];
$table_lang = (string)($tables['lang'] ?? '');
$table_img  = ($tables['img'] ?? '') !== '' ? (string)$tables['img'] : ($table_name . '_img');
$FKName     = $tables['fk'];
$moduleCol  = (string)($tables['module_pk_col'] ?? 'Module_PKey');

$csrfKey = (string)($detailConfig['csrf'] ?? 'dbad_addin');
crud_csrf_verify_form($csrfKey);

global $filter_array, $file_array;
$file_array = $file_array ?? [];

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
if (safe_int($filter_array['Sort'] ?? -1) < 0) {
    $MSG .= "【順序】空白或非數字格式\n";
}
$MSG .= crud_validate_lang_show_strname($filter_array);

$presentMode = safe_int($filter_array['isShow'] ?? 1);
$presentMode = ($presentMode === 2) ? 2 : 1;

if ($presentMode === 2) {
    if (trim((string)($filter_array['Movielink'] ?? '')) === '') {
        $MSG .= "【影音連結】空白\n";
    }
}

/* ── 上傳（呈現方式為圖檔時）──────────────────────── */
$uploadDirInfo = crud_upload_dir();
$upload_foder  = $uploadDirInfo['dir'];
$MSG          .= $uploadDirInfo['error'];

$photoSlots = crud_resolve_photo_upload_slots($filter_array, $file_array, 2);
$ForderName   = $ForderName ?? 'dbad_';
$config_total = $photoSlots['config_total'];
$size_bytes   = (int)($size_bytes ?? 6000 * 1024);
$indices      = $photoSlots['indices'];
$maxSlots     = $photoSlots['max_slots'];

$Photo  = [];
$PhotoW = [];
$PhotoH = [];
$PhotoM = [];

for ($i = 1; $i <= $maxSlots; $i++) {
    if (isset($filter_array['PhotoM' . $i])) {
        $PhotoM[$i] = (string)$filter_array['PhotoM' . $i];
    }
}

$forderVal = rtrim(date('Ym'), "\\/");

if ($presentMode === 1) {
    $uploadResult = crud_upload_file_slots($file_array, $upload_foder, $indices, [
        'forder_prefix' => $ForderName,
        'size_bytes'    => $size_bytes,
        'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png'],
        'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png'],
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
    $forderVal = rtrim((string)($uploadResult['monthdir'] ?? $forderVal), "\\/");

    $hasDesktopImg = !empty($Photo[1]);
    if (!$hasDesktopImg && $formPKey > 0 && crud_is_safe_sql_identifier($table_img) && crud_is_safe_sql_identifier($FKName)) {
        $existingImg = crud_fetch_one(
            "SELECT PKey FROM {$table_img} WHERE {$FKName} = :fk AND Sort = 1 AND Photo1 <> '' LIMIT 1",
            ['fk' => $formPKey]
        );
        $hasDesktopImg = $existingImg !== null;
    }
    if (!$hasDesktopImg) {
        $MSG .= "【桌機圖片】請上傳\n";
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
    $rowModule = $row !== null && function_exists('crud_row_int')
        ? crud_row_int($row, $moduleCol)
        : (int)($row[$moduleCol] ?? 0);
    if ($row === null || $rowModule !== $modulePKey) {
        crud_form_error_redirect('查無要修改資料或無權限', $returnUrl);
    }
}

/* ── 主檔 dbad（標題/副標取第一個已勾選語系）──────────────── */
$masterName    = '';
$masterSubject = '';
$langCount     = !empty($GLOBALS['array_lang']) ? count($GLOBALS['array_lang']) : 6;
for ($i = 1; $i <= $langCount; $i++) {
    if (($filter_array['Show' . $i] ?? '') !== 'Y') {
        continue;
    }
    $masterName    = trim((string)($filter_array['strName' . $i] ?? ''));
    $masterSubject = trim((string)($filter_array['Subject' . $i] ?? ''));
    if ($masterName !== '') {
        break;
    }
}
if ($masterName === '') {
    $masterName    = trim((string)($filter_array['strName1'] ?? ''));
    $masterSubject = trim((string)($filter_array['Subject1'] ?? ''));
}

$targetVal = trim((string)($filter_array['Target'] ?? ''));
if ($targetVal !== '_self') {
    $targetVal = '_blank';
}

$uploadVal = trim((string)($filter_array['Upload'] ?? ''));
if ($uploadVal !== 'No') {
    $uploadVal = 'Yes';
}

$data_array = [
    $moduleCol  => SqlFilter($modulePKey, 'int'),
    'intLocal'  => SqlFilter(1, 'int'),
    'Sort'      => SqlFilter($filter_array['Sort'] ?? 0, 'int'),
    'strName'   => SqlFilter($masterName, 'tab'),
    'Subject'   => SqlFilter($masterSubject, 'tab'),
    'strLink'   => SqlFilter(trim((string)($filter_array['strLink'] ?? '')), 'tab'),
    'Target'    => SqlFilter($targetVal, 'tab'),
    'isShow'    => SqlFilter($presentMode, 'int'),
    'Movielink' => SqlFilter($presentMode === 2 ? trim((string)($filter_array['Movielink'] ?? '')) : '', 'tab'),
    'Upload'    => SqlFilter($uploadVal, 'tab'),
    'dtUDate'   => date('Y-m-d H:i:s'),
    'UserID'    => SqlFilter($Login_ID, 'tab'),
];

if (isset($filter_array['Color']) && trim((string)$filter_array['Color']) !== '') {
    $data_array['Color'] = SqlFilter((string)$filter_array['Color'], 'tab');
}

/* ── 寫入 ───────────────────────────────────────────── */
try {
    $upsert     = crud_upsert_master($table_name, $formPKey, $data_array);
    $parentPKey = $upsert['pkey'];
    $show       = $upsert['action'];

    if ($table_lang !== '') {
        crud_save_ad_lang_slots($table_lang, $FKName, $parentPKey, $filter_array);
    }
    if ($table_img !== '' && $presentMode === 1) {
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
