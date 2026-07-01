<?php
declare(strict_types=1);

/**
 * AI API 連線測試（後台登入後存取）
 * 正常回應：{"success":true,"ok":true,"env_dir":"...","has_gemini_key":true}
 */
$manage_binary_export = true;
require_once __DIR__ . '/_api_inc.php';

$apiKey = gemini_resolve_api_key();
$autoload = dirname(__DIR__) . '/vendor/autoload.php';

json_out([
    'success' => true,
    'ok' => true,
    'env_dir' => defined('APP_ENV_DIR') ? APP_ENV_DIR : null,
    'env_loaded' => !empty($GLOBALS['__env_loaded']),
    'has_gemini_key' => $apiKey !== '',
    'vendor_autoload' => is_file($autoload),
]);
