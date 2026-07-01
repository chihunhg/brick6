<?php
declare(strict_types=1);
/**
 * knowledge 新增
 */

$manage_csp_editor = true;
require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

$__csrf_key = (string)($detailConfig['csrf'] ?? 'knowledge_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

require_once __DIR__ . '/_form_data.php';

knowledge_detail_init_defaults();

$copyPkey = safe_int($filter_array['PKey'] ?? 0);
if ($copyPkey > 0) {
    knowledge_detail_load($copyPkey, null, true);
}

$tables = knowledge_detail_tables();
$GLOBALS['knowledge_form_vars']['Sort'] = crud_next_sort(
    (string)$tables['master'],
    ['Module_PKey' => SqlFilter($Module_PKey ?? 0, 'int')]
);
knowledge_detail_export_vars();

$breadcrumbs = manage_breadcrumbs_for_form('新增');
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once __DIR__ . '/_detail.php';
