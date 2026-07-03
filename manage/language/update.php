<?php
declare(strict_types=1);

require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig, true);

require_once '_form_data.php';
language_require_admin();

$manNo = 97;
$GLOBALS['manNo'] = $manNo;

$__csrf_key = (string)($detailConfig['csrf'] ?? 'language_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

$editPKey = manage_request_pkey();
if ($editPKey <= 0) {
    manage_alert_script('參數錯誤：PKey 無效', 'list.php?manNo=97');
    exit;
}

language_detail_init_defaults();

if (!language_detail_load($editPKey)) {
    manage_alert_script('查無要修改資料!', 'list.php?manNo=97');
    exit;
}

$breadcrumbs = [
    ['label' => '系統管理'],
    ['label' => '語系設定', 'href' => 'list.php?manNo=97'],
    ['label' => manage_breadcrumb_form_action_label()],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
