<?php
// manage/ad/update.php
declare(strict_types=1);

$manage_csp_editor = true;
require_once '../_inc.php';
require_once '../_module.php';

$table_name = 'dbad';
$table_img  = $table_name . '_img';
$table_lang = $table_name . '_lang';
$FKName     = 'AD_PKey';

$hasHomeCol = true;

$Update_PKey = safe_int($filter_array['PKey'] ?? 0);
crud_require_pkey($Update_PKey);

$row = crud_load_master_row($table_name, $Update_PKey);

$AD_PKey     = (int)$row['PKey'];
$Update_PKey = $AD_PKey;
$Sort        = (int)($row['Sort'] ?? 0);
$strLink      = (string)($row['strLink'] ?? '');
$Target       = (string)($row['Target'] ?? '');
$presentMode  = (int)($row['isShow'] ?? 1);
$Movielink    = (string)($row['Movielink'] ?? '');
$Upload       = (string)($row['Upload'] ?? 'Yes');
$dtUDate  = (string)($row['dtUDate'] ?? '');
$dtDate   = (string)($row['dtDate'] ?? '');
$UserID   = (string)($row['UserID'] ?? '');

$langData = crud_load_lang_slots_data($table_lang, $FKName, $AD_PKey);
$language = $langData['language'];
$langIsShow = $langData['isShow'];
$strName  = $langData['strName'];
$Subject  = $langData['Subject'];

$imgData = crud_load_img_slots_data($table_img, $FKName, $AD_PKey);
$Photo   = $imgData['Photo'];
$PhotoS  = $imgData['PhotoS'];
$PhotoM  = $imgData['PhotoM'];

$detailConfig = require __DIR__ . '/_config.php';
$__csrf_key = (string)($detailConfig['csrf'] ?? 'dbad_addin');
$csrf_token = crud_csrf_ensure_page($__csrf_key);

$breadcrumbs = manage_breadcrumbs_for_form('編輯');
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

require_once '_detail.php';
