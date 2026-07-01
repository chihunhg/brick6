<?php
declare(strict_types=1);
/**
 * 列表單筆／批次上下架（由各模組 _upload.php 定義 MANAGE_UPLOAD_MODULE_DIR 後 require）
 * 資料表名稱讀取同目錄 _config.php 的 master
 */

if (!defined('MANAGE_UPLOAD_MODULE_DIR')) {
    echo '設定錯誤';
    exit;
}

require_once MANAGE_UPLOAD_MODULE_DIR . '/../_inc.php';

$configFile = MANAGE_UPLOAD_MODULE_DIR . '/_config.php';
if (!is_file($configFile)) {
    echo '設定檔不存在';
    exit;
}

$detailConfig = require $configFile;
$table_name   = trim((string)($detailConfig['master'] ?? ''));
$pk_name      = 'PKey';

if ($table_name === ''
    || (function_exists('crud_is_safe_sql_identifier') && !crud_is_safe_sql_identifier($table_name))
) {
    echo 'master 表名無效';
    exit;
}

require_once MANAGE_UPLOAD_MODULE_DIR . '/../_upload_batch_inc.php';
manage_handle_upload_batch_request($table_name, $pk_name);

$PKey = (isset($filter_array[$pk_name]) && is_numeric($filter_array[$pk_name]))
    ? (int)SqlFilter($filter_array[$pk_name], 'int')
    : 0;

$Upload = isset($filter_array['Upload'])
    ? (string)SqlFilter($filter_array['Upload'], 'tab')
    : 'No';

if ($Upload !== 'Yes' && $Upload !== 'No') {
    $Upload = 'No';
}

if ($PKey <= 0) {
    echo 'PKey 錯誤';
    exit;
}

$res = update_upload_by_table(
    $table_name,
    $pk_name,
    $PKey,
    $Upload,
    [
        'WorkFile'    => $WorkFile ?? __FILE__,
        'Login_ID'    => $Login_ID ?? 'system',
        'Module_PKey' => $Module_PKey ?? 0,
        'Module_Name' => $Module_Name ?? '',
        'Action'      => '更新上下架',
    ],
    __FILE__,
    __LINE__
);

if (empty($res['ok'])) {
    echo $res['error'] ?? '更新失敗';
    exit;
}

echo 'OK';
