<?php

declare(strict_types=1);

/**

 * filedown 表單送出（每語系：自訂連結或上傳檔案）

 */



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

manage_detail_set_config($detailConfig);



$tables     = manage_detail_tables();

$table_name = $tables['master'];

$table_lang = (string)($tables['lang'] ?? '');

$table_img  = (string)($tables['img'] ?? '');

$FKName     = $tables['fk'];

$moduleCol  = (string)($tables['module_pk_col'] ?? 'Module_PKey');



$csrfKey = (string)($detailConfig['csrf'] ?? 'filedown_addin');

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

$langCount = filedown_lang_count();



$MSG = '';

if (safe_int($filter_array['Sort'] ?? 0) < 0) {

    $MSG .= "【順序】空白或非數字格式\n";

}

$MSG .= crud_validate_lang_show_strname($filter_array);

$MSG .= crud_addin_validate_layer_classes($filter_array, $Layer);

$MSG .= filedown_validate_lang_link_file($filter_array, $file_array, $formPKey, $table_img, $FKName);



$uploadDirInfo = crud_upload_dir();

$upload_foder  = $uploadDirInfo['dir'];

$MSG          .= $uploadDirInfo['error'];



$ForderName   = (string)($detailConfig['forder_prefix'] ?? 'file_');

$size_bytes   = (int)($size_bytes ?? 6000 * 1024);

$uploadResult = ['monthdir' => date('Ym')];



$uploadIndices = [];

for ($i = 1; $i <= $langCount; $i++) {

    if ((int)($filter_array['intLink' . $i] ?? 2) !== 2) {

        continue;

    }

    if (!empty($file_array['Photo' . $i]['tmp_name'])

        && is_uploaded_file((string)$file_array['Photo' . $i]['tmp_name'])) {

        $uploadIndices[] = $i;

    }

}



$Photo  = [];

$PhotoW = [];

$PhotoH = [];



if ($uploadIndices !== []) {

    $uploadResult = crud_upload_file_slots($file_array, $upload_foder, $uploadIndices, [

        'forder_prefix' => $ForderName,

        'size_bytes'    => $size_bytes,

        'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt'],

        'allowed_mimes' => [],

        'field_prefix'  => 'Photo',

        'resize_thumb'  => false,

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

}



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



try {

    $upsert     = crud_upsert_master($table_name, $formPKey, $data_array);

    $parentPKey = $upsert['pkey'];

    $show       = $upsert['action'];



    if ($table_lang !== '') {

        crud_save_lang_slots($table_lang, $FKName, $parentPKey, $filter_array);

    }



    for ($i = 1; $i <= $langCount; $i++) {

        if (($filter_array['Show' . $i] ?? '') !== 'Y') {

            continue;

        }

        if ((int)($filter_array['intLink' . $i] ?? 2) === 1) {

            filedown_clear_lang_file($parentPKey, $i, $table_img, $table_lang, $FKName, $upload_foder);

        }

    }



    if ($table_img !== '' && $Photo !== []) {

        crud_save_img_slots(

            $table_img,

            $FKName,

            $parentPKey,

            $forderVal,

            $Photo,

            $PhotoW,

            $PhotoH,

            [],

            $filter_array,

            $langCount,

            $upload_foder

        );

        foreach (array_keys($Photo) as $slot) {

            $slot = (int)$slot;

            if ($slot > 0 && (int)($filter_array['intLink' . $slot] ?? 2) === 2) {

                filedown_sync_lang_file_meta($parentPKey, $slot, $table_lang, $table_img, $FKName, $upload_foder);

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

