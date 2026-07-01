<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

$__csrf_key = (string)($detailConfig['csrf'] ?? 'member_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

require_once __DIR__ . '/_form_data.php';

member_detail_init_defaults();

$breadcrumbs = manage_breadcrumbs_for_form('新增');
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once __DIR__ . '/_detail.php';
