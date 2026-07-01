<?php
declare(strict_types=1);
/**
 * 刪除圖片子表一筆（由各模組 _del_img.php 定義 MANAGE_DEL_IMG_MODULE_DIR 後 require）
 * 圖表名稱讀取同目錄 _config.php 的 img（空白則以 master + '_img' 推斷）
 */

if (!defined('MANAGE_DEL_IMG_MODULE_DIR')) {
    crud_json_response(false, '設定錯誤');
}

require_once MANAGE_DEL_IMG_MODULE_DIR . '/../_inc.php';
require_once MANAGE_DEL_IMG_MODULE_DIR . '/../_module.php';

$configFile = MANAGE_DEL_IMG_MODULE_DIR . '/_config.php';
if (!is_file($configFile)) {
    crud_json_response(false, '設定檔不存在');
}

$detailConfig = require $configFile;
$tableImg     = trim((string)($detailConfig['img'] ?? ''));
if ($tableImg === '') {
    $master = trim((string)($detailConfig['master'] ?? ''));
    if ($master !== '') {
        $tableImg = $master . '_img';
    }
}

if ($tableImg === ''
    || (function_exists('crud_is_safe_sql_identifier') && !crud_is_safe_sql_identifier($tableImg))
) {
    crud_json_response(false, 'img 表名無效');
}

$pkey = safe_int($filter_array['PKey'] ?? 0);
if ($pkey <= 0) {
    crud_json_response(false, '參數錯誤');
}

$ok = crud_delete_img_row($tableImg, $pkey);
crud_json_response($ok, $ok ? 'OK' : '刪除失敗');
