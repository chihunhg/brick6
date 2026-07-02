<?php
declare(strict_types=1);
/**
 * faq 表單送出（列表圖 + faq_msg 內文，結構參照 paper/addin.php）
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
$table_img  = ($tables['img'] ?? '') !== '' ? (string)$tables['img'] : ($table_name . '_img');
$FKName     = $tables['fk'];
$moduleCol  = (string)($tables['module_pk_col'] ?? 'Module_PKey');
$hasSort       = (bool)($detailConfig['has_sort'] ?? true);
$contentBlocks = max(1, (int)($detailConfig['content_blocks'] ?? 1));
$photoFallback = max(1, (int)($detailConfig['img_slot_max'] ?? $detailConfig['photo_slots'] ?? 1));

$csrfKey = (string)($detailConfig['csrf'] ?? 'faq_addin');
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
if ($hasSort && safe_int($filter_array['Sort'] ?? 0) < 0) {
    $MSG .= "【順序】空白或非數字格式\n";
}
$MSG .= crud_validate_lang_show_strname($filter_array);
$MSG .= crud_addin_validate_strdate($filter_array);
$MSG .= crud_addin_validate_layer_classes($filter_array, $Layer);

$photoSlots = crud_resolve_photo_upload_slots($filter_array, $file_array, $photoFallback);
$MAX_CONTENT_BYTES = 2 * 1024 * 1024;
$b64 = crud_decode_b64_content_multilang($filter_array, $MAX_CONTENT_BYTES, $contentBlocks);
$MSG .= $b64['error'];
$DecodedContents = $b64['contents'];

/* ── 上傳 ───────────────────────────────────────────── */
$uploadDirInfo = crud_upload_dir();
$upload_foder  = $uploadDirInfo['dir'];
$MSG          .= $uploadDirInfo['error'];

$ForderName    = (string)($detailConfig['forder_prefix'] ?? 'faq_');
$config_total  = $photoSlots['config_total'];
$size_bytes    = (int)($size_bytes ?? 2000 * 1024);
$indices       = $photoSlots['indices'];
$maxSlots      = min($photoSlots['max_slots'], $photoFallback);

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

$MSG      .= (string)($uploadResult['messages'] ?? '');
$forderVal = rtrim((string)($uploadResult['monthdir'] ?? date('Ym')), "\\/");

if ($MSG !== '') {
    crud_form_error_redirect($MSG, $returnUrl);
}

crud_addin_resolve_strdate($filter_array);

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
    'strName'   => SqlFilter((string)($filter_array['strName1'] ?? ''), 'tab'),
    'dtUDate'   => date('Y-m-d H:i:s'),
    'UserID'    => SqlFilter($Login_ID, 'tab'),
];
if ($hasSort) {
    $data_array['Sort'] = SqlFilter($filter_array['Sort'] ?? 0, 'int');
}
if (isset($filter_array['Upload'])) {
    $data_array['Upload'] = SqlFilter($filter_array['Upload'], 'tab');
}
$data_array = crud_addin_append_publish_master_fields($data_array, $filter_array, $Layer, $table_name);

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

/* ── 寫入 ───────────────────────────────────────────── */
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
            $contentBlocks
        );
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
        if (crud_table_has_column($table_img, 'Home')) {
            for ($imgSlot = 1; $imgSlot <= $maxSlots; $imgSlot++) {
                $pdo = new dbPDO();
                $sqlHome = "UPDATE {$table_img} SET Home = :home WHERE {$FKName} = :fk AND Sort = :sort";
                $bindHome = [
                    'home' => SqlFilter('Yes', 'tab'),
                    'fk'   => SqlFilter($parentPKey, 'int'),
                    'sort' => SqlFilter($imgSlot, 'int'),
                ];
                $pdo->execute($sqlHome, $bindHome);
                $errHome = $pdo->getErrorMessage();
                $pdo->close();
                if ($errHome !== '') {
                    crud_fail_db($sqlHome . PHP_EOL . (function_exists('array_to_string') ? array_to_string($bindHome) : ''), $errHome, $bindHome, true);
                }
            }
        }
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
