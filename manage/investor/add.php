<?php
declare(strict_types=1);

$manage_csp_editor = true;
require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

$table_name = $detailConfig['master'];
$PKName     = 'PKey';
$FKName     = $detailConfig['fk'];

$__csrf_key = $detailConfig['csrf'];
$csrf_token = crud_csrf_ensure_page($__csrf_key);

require_once '_form_data.php';

class1_detail_init_defaults();
$Update_PKey = 0;

$copyPkey = safe_int($filter_array['PKey'] ?? 0);
if ($copyPkey > 0) {
    class1_detail_load($copyPkey);
    /** 複製僅帶入欄位內容；新增儲存須產生新 PKey，不可沿用來源主鍵 */
    $GLOBALS['class1_form_vars']['Update_PKey'] = 0;
    $GLOBALS['class1_form_vars']['Photo']  = [];
    $GLOBALS['class1_form_vars']['PhotoS'] = [];
    $GLOBALS['class1_form_vars']['PhotoM'] = [];
    $GLOBALS['class1_form_vars']['Ext']    = [];
    class1_detail_export_vars();
}

$Sort = investor_next_sort(
    (int)($Module_PKey ?? 0),
    ['Class1' => (int)($Class1 ?? 0), 'Class2' => (int)($Class2 ?? 0), 'Class3' => (int)($Class3 ?? 0), 'year' => 0],
    (int)($Layer ?? 1)
);
$GLOBALS['class1_form_vars']['Sort'] = $Sort;
class1_detail_export_vars();

$breadcrumbs = manage_breadcrumbs_for_form('新增');
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
