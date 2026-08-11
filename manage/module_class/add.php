<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

require_once '_form_data.php';

$__csrf_key = (string)($detailConfig['csrf'] ?? 'module_class_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

module_class_detail_init_defaults();

$copyPkey = safe_int($filter_array['PKey'] ?? 0);
if ($copyPkey > 0) {
    module_class_detail_load_copy($copyPkey);
} else {
    $GLOBALS['module_class_form_vars']['Sort'] = module_class_next_sort();
    module_class_detail_export_vars();
}

$breadcrumbs = manage_breadcrumbs_for_form('新增');
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
