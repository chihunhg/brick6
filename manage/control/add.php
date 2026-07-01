<?php
declare(strict_types=1);
/**
 * 後台帳號新增
 */

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';

$__csrf_key = (string)($detailConfig['csrf'] ?? 'manage_form');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

require_once __DIR__ . '/_form_data.php';

control_detail_init_defaults();

$listFile = (string)($detailConfig['list_file'] ?? 'list.php');
$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => (string)($Module_Name ?? '帳號管理'), 'href' => $listFile],
    ['label' => manage_breadcrumb_form_action_label()],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once __DIR__ . '/_detail.php';
