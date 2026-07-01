<?php
declare(strict_types=1);
/**
 * 後台帳號編輯
 */

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';

$__csrf_key = (string)($detailConfig['csrf'] ?? 'manage_form');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

require_once __DIR__ . '/_form_data.php';

$listFile = (string)($detailConfig['list_file'] ?? 'list.php');
$listUrl  = manage_breadcrumbs_list_href($listFile);

$editPKey = manage_request_pkey();
if ($editPKey <= 0) {
    manage_alert_script('參數錯誤：PKey 無效', $listUrl);
}

control_detail_init_defaults();

if (!control_detail_load($editPKey)) {
    manage_alert_script('查無要修改資料!', $listUrl);
}

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => (string)($Module_Name ?? '帳號管理'), 'href' => $listFile],
    ['label' => manage_breadcrumb_form_action_label()],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once __DIR__ . '/_detail.php';
