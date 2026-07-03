<?php
declare(strict_types=1);

require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

require_once '_form_data.php';
language_require_admin();

$manNo = 97;
$GLOBALS['manNo'] = $manNo;

$__csrf_key = (string)($detailConfig['csrf'] ?? 'language_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

language_detail_init_defaults();

$GLOBALS['language_form_vars']['Sort'] = language_next_sort();
language_detail_export_vars();

$breadcrumbs = [
    ['label' => '系統管理'],
    ['label' => '語系設定', 'href' => 'list.php?manNo=97'],
    ['label' => '新增'],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
