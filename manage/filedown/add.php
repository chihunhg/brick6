<?php

declare(strict_types=1);



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

    $Update_PKey = 0;

    $GLOBALS['class1_form_vars']['Photo']  = [];

    $GLOBALS['class1_form_vars']['PhotoS'] = [];

    $GLOBALS['class1_form_vars']['Ext']    = [];

    class1_detail_export_vars();

}



$Sort = crud_next_sort($table_name, ['Module_PKey' => SqlFilter($Module_PKey ?? 0, 'int')]);

$GLOBALS['class1_form_vars']['Sort'] = $Sort;

class1_detail_export_vars();



$breadcrumbs = manage_breadcrumbs_for_form('新增');

$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);



require_once '_detail.php';

