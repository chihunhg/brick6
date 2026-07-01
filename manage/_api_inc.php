<?php

declare(strict_types=1);



/**

 * Manage JSON API 輕量啟動（Gemini 等 AJAX 端點用，避免載入完整 _inc.php）

 */

ini_set('display_errors', '0');

ini_set('display_startup_errors', '0');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);



if (!ob_get_level()) {

    ob_start();

}



if (session_status() !== PHP_SESSION_ACTIVE) {

    session_start();

}



if (empty($manage_binary_export)) {

    $manage_binary_export = true;

}



require_once dirname(__DIR__) . '/include/host.php';

app_configure_error_display();

require_once dirname(__DIR__) . '/include/json_response.php';



if ((($_SESSION['Manage'] ?? '') !== 'Yes') || empty($_SESSION['Login_ID'])) {

    json_out(['success' => false, 'error' => '未登入或登入已逾時，請重新登入'], 401);

}



if (!function_exists('gemini_resolve_api_key')) {

    function gemini_resolve_api_key(): string {

        $fromEnv = trim((string)($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: ''));

        if ($fromEnv !== '') {

            return $fromEnv;

        }

        return trim((string)($_ENV['GOOGLE_API_KEY'] ?? getenv('GOOGLE_API_KEY') ?: ''));

    }

}

