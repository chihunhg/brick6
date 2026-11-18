<?php
declare(strict_types=1);

require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig, true);

require_once '_form_data.php';
module_require_admin();

$__csrf_key = (string)($detailConfig['csrf'] ?? 'module_p_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

$editPKey = manage_request_pkey();
if ($editPKey <= 0) {
    manage_alert_script('參數錯誤：PKey 無效', 'list.php');
    exit;
}

module_detail_init_defaults();

if (!module_detail_load($editPKey)) {
    manage_alert_script('查無要修改資料!', 'list.php');
    exit;
}

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => '單元設定', 'href' => 'list.php'],
    ['label' => manage_breadcrumb_form_action_label()],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
