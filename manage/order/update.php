<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig, true);

$__csrf_key = (string)($detailConfig['csrf'] ?? 'order_update');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

require_once __DIR__ . '/_form_data.php';

$listUrl = manage_breadcrumbs_list_href('list.php');
$editPKey = manage_request_pkey();
if ($editPKey <= 0) {
    manage_alert_script('參數錯誤：PKey 無效', $listUrl);
    exit;
}

order_detail_init_defaults();

$modulePKey = (int)($GLOBALS['Module_PKey'] ?? 0);
if ($modulePKey <= 0) {
    $modulePKey = order_detail_resolve_module_pkey();
}

if (!order_detail_load($editPKey, $modulePKey)) {
    manage_alert_script('查無要修改資料!', $listUrl);
    exit;
}

$Order_PKey = (int)($Order_PKey ?? 0);
$OrderNo = (string)($OrderNo ?? '');
$intState = (int)($intState ?? 1);

if (isset($filter_array['Submit']) && (string)$filter_array['Submit'] === '變更') {
    crud_csrf_verify_form($__csrf_key);
    $oldState = safe_int($filter_array['oldState'] ?? $intState);
    order_process_state_update($filter_array ?? [], $Order_PKey, $OrderNo, $oldState);
}

$breadcrumbs = manage_breadcrumbs_for_form('編輯');
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once __DIR__ . '/_detail.php';
