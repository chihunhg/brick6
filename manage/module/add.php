<?php
declare(strict_types=1);

require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

require_once '_form_data.php';
module_require_admin();

$__csrf_key = (string)($detailConfig['csrf'] ?? 'module_p_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

module_detail_init_defaults();

$copyPkey = manage_request_pkey();
if ($copyPkey > 0 && module_detail_load($copyPkey)) {
    $GLOBALS['module_form_vars']['Update_PKey'] = 0;
    $GLOBALS['module_form_vars']['Module_PKey'] = 0;
    $GLOBALS['module_form_vars']['copySourcePKey'] = $copyPkey;
    module_detail_export_vars();
}

$GLOBALS['module_form_vars']['Sort'] = module_next_sort();
module_detail_export_vars();

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => '單元設定', 'href' => 'list.php'],
    ['label' => manage_breadcrumb_form_action_label()],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
