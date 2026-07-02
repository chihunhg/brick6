<?php
declare(strict_types=1);

/**
 * investor 表單送出（連結／內容／檔案三種顯示模式）
 */

$manage_csp_editor = true;
require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

$tables     = manage_detail_tables();
$table_name = $tables['master'];
$table_lang = (string)($tables['lang'] ?? '');
$table_msg  = (string)($tables['msg'] ?? '');
$table_img  = (string)($tables['img'] ?? '');
$FKName     = $tables['fk'];
$moduleCol  = (string)($tables['module_pk_col'] ?? 'Module_PKey');

$csrfKey = (string)($detailConfig['csrf'] ?? 'investor_addin');
crud_csrf_verify_form($csrfKey);

require_once '_form_data.php';

global $filter_array, $file_array, $Layer;
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
$langCount = investor_lang_count();
$showType  = (int)($filter_array['show_type'] ?? 2);

$MSG = '';
if (safe_int($filter_array['Sort'] ?? 0) < 0) {
    $MSG .= "【順序】空白或非數字格式\n";
}
$MSG .= crud_validate_lang_show_strname($filter_array);
$MSG .= crud_addin_validate_layer_classes($filter_array, $Layer);
$MSG .= investor_validate_year($filter_array);
$MSG .= investor_validate_by_show_type($filter_array, $file_array, $formPKey, $table_img, $FKName);

$uploadDirInfo = crud_upload_dir();
$upload_foder  = $uploadDirInfo['dir'];
$MSG          .= $uploadDirInfo['error'];

$ForderName = (string)($detailConfig['forder_prefix'] ?? 'investor_');
$Photo      = [];
$PhotoW     = [];
$PhotoH     = [];
$PhotoM     = [];
$forderVal  = date('Ym');

for ($i = 1; $i <= 10; $i++) {
    if (isset($filter_array['PhotoM' . $i])) {
        $PhotoM[$i] = (string)$filter_array['PhotoM' . $i];
    }
}

// 內容圖（欄 2–7）
if ($showType === 2) {
    $imgIndices = range(2, 7);
    $imgResult = crud_upload_file_slots($file_array, $upload_foder, $imgIndices, [
        'forder_prefix' => $ForderName,
        'size_bytes'    => 2000 * 1024,
        'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'webp'],
        'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
        'field_prefix'  => 'Photo',
        'resize_thumb'  => true,
    ]);
    foreach ((array)($imgResult['photos'] ?? []) as $idx => $filename) {
        $Photo[(int)$idx] = (string)$filename;
    }
    foreach ((array)($imgResult['photoW'] ?? []) as $idx => $w) {
        $PhotoW[(int)$idx] = (int)$w;
    }
    foreach ((array)($imgResult['photoH'] ?? []) as $idx => $h) {
        $PhotoH[(int)$idx] = (int)$h;
    }
    $MSG .= (string)($imgResult['messages'] ?? '');
    $forderVal = rtrim((string)($imgResult['monthdir'] ?? $forderVal), "\\/");
}

// 檔案模式（欄 8–10）
if ($showType === 3) {
    $fileIndices = [];
    for ($i = 1; $i <= min(3, $langCount); $i++) {
        if ((int)($filter_array['intFileLink' . $i] ?? $filter_array['intLink' . $i] ?? 2) !== 2) {
            continue;
        }
        $slot = investor_file_slot_for_lang($i);
        if (!empty($file_array['Photo' . $slot]['tmp_name'])
            && is_uploaded_file((string)$file_array['Photo' . $slot]['tmp_name'])) {
            $fileIndices[] = $slot;
        }
    }
    if ($fileIndices !== []) {
        $fileResult = crud_upload_file_slots($file_array, $upload_foder, $fileIndices, [
            'forder_prefix' => $ForderName,
            'size_bytes'    => 20000 * 1024,
            'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
            'allowed_mimes' => [],
            'field_prefix'  => 'Photo',
            'resize_thumb'  => false,
        ]);
        foreach ((array)($fileResult['photos'] ?? []) as $idx => $filename) {
            $Photo[(int)$idx] = (string)$filename;
        }
        $MSG .= (string)($fileResult['messages'] ?? '');
        $forderVal = rtrim((string)($fileResult['monthdir'] ?? $forderVal), "\\/");
    }
}

$MAX_CONTENT_BYTES = 20000 * 1024;
$b64 = crud_decode_b64_content_multilang($filter_array, $MAX_CONTENT_BYTES, 6);
$MSG .= $b64['error'];
$DecodedContents = $b64['contents'];

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

$data_array = [
    $moduleCol  => SqlFilter($modulePKey, 'int'),
    'strName'   => SqlFilter((string)($filter_array['strName1'] ?? ''), 'tab'),
    'dtUDate'   => date('Y-m-d H:i:s'),
    'UserID'    => SqlFilter($Login_ID, 'tab'),
    'Sort'      => SqlFilter($filter_array['Sort'] ?? 0, 'int'),
];
if (isset($filter_array['Upload'])) {
    $data_array['Upload'] = SqlFilter($filter_array['Upload'], 'tab');
}
if (isset($filter_array['Home']) && crud_table_has_column($table_name, 'Home')) {
    $data_array['Home'] = SqlFilter((string)$filter_array['Home'], 'tab');
}
if (crud_table_has_column($table_name, 'show_type')) {
    $data_array['show_type'] = SqlFilter($showType, 'int');
}
if (crud_table_has_column($table_name, 'year')) {
    $showYear = (int)($filter_array['show_year'] ?? 0);
    $data_array['year'] = SqlFilter($showYear === 1 ? ($filter_array['year'] ?? 0) : 0, 'int');
}

$data_array = crud_addin_append_publish_master_fields($data_array, $filter_array, $Layer, $table_name);
$data_array = crud_filter_row_for_table($table_name, $data_array);

/** 連結/檔案模式：檔案模式用 intFileLink，送出前合併到 investor_lang.intLink（intLink{n}） */
for ($i = 1; $i <= $langCount; $i++) {
    if ($showType === 3) {
        $filter_array['intLink' . $i] = $filter_array['intFileLink' . $i] ?? $filter_array['intLink' . $i] ?? 2;
    }
}

try {
    $upsert     = crud_upsert_master($table_name, $formPKey, $data_array);
    $parentPKey = $upsert['pkey'];
    $show       = $upsert['action'];

    if ($table_lang !== '') {
        crud_save_lang_slots($table_lang, $FKName, $parentPKey, $filter_array);
    }
    if ($table_msg !== '' && $showType === 2) {
        crud_save_msg_blocks_multilang(
            $table_msg,
            $FKName,
            $parentPKey,
            $DecodedContents,
            $filter_array,
            6
        );
    }
    if ($table_img !== '') {
        $maxSlots = 10;
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
