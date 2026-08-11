<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig, true);

require_once '_form_data.php';

$__csrf_key = (string)($detailConfig['csrf'] ?? 'module_class_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

$listUrl = manage_breadcrumbs_list_href('list.php');
$editPKey = manage_request_pkey();
if ($editPKey <= 0) {
    manage_alert_script('參數錯誤：PKey 無效', $listUrl);
    exit;
}

module_class_detail_init_defaults();

if (!module_class_detail_load($editPKey)) {
    manage_alert_script('查無要修改資料!', $listUrl);
    exit;
}

$breadcrumbs = manage_breadcrumbs_for_form('編輯');
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
