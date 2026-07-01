<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

manage_detail_set_config($detailConfig, true);



$table_name = $detailConfig['master'];

$PKName     = 'PKey';

$FKName     = $detailConfig['fk'];



$__csrf_key = $detailConfig['csrf'];

$csrf_token = crud_csrf_ensure_page($__csrf_key);



require_once '_form_data.php';



$editPKey = manage_request_pkey();

if ($editPKey <= 0) {

    manage_alert_script('參數錯誤：PKey 無效', manage_breadcrumbs_list_href('list.php'));

    exit;

}



class1_detail_init_defaults();



$listUrl = manage_breadcrumbs_list_href('list.php');

$modulePKey = (int)($GLOBALS['Module_PKey'] ?? 0);

if ($modulePKey <= 0) {

    $modulePKey = class1_detail_resolve_module_pkey();

}

if (!class1_detail_load($editPKey, $modulePKey)) {

    manage_alert_script('查無要修改資料!', $listUrl);

    exit;

}



$breadcrumbs = manage_breadcrumbs_for_form('編輯');

$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);



require_once '_detail.php';

