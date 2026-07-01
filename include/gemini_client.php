<?php
declare(strict_types=1);

/**
 * 建立 Gemini API Client（處理 WAMP 本機 cURL CA 憑證問題）
 */
if (!function_exists('gemini_resolve_ssl_verify')) {
    /**
     * @return bool|string CA 路徑、true（系統預設）、或 false（略過驗證，僅開發用）
     */
    function gemini_resolve_ssl_verify(): bool|string {
        $raw = trim((string)($_ENV['GEMINI_SSL_VERIFY'] ?? getenv('GEMINI_SSL_VERIFY') ?: ''));
        if ($raw !== '') {
            if (in_array(strtolower($raw), ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
            if (is_file($raw)) {
                return $raw;
            }
            return true;
        }

        try {
            return \GuzzleHttp\Utils::defaultCaBundle();
        } catch (Throwable) {
            if (function_exists('app_is_production') && !app_is_production()) {
                return false;
            }
            return true;
        }
    }
}

if (!function_exists('gemini_create_client')) {
    function gemini_create_client(string $apiKey): \Gemini\Client {
        $guzzleOptions = ['timeout' => 60];
        $verify = gemini_resolve_ssl_verify();
        if ($verify !== true) {
            $guzzleOptions['verify'] = $verify;
        }

        $guzzle = new \GuzzleHttp\Client($guzzleOptions);

        return \Gemini::factory()
            ->withApiKey($apiKey)
            ->withHttpClient($guzzle)
            ->make();
    }
}

if (!function_exists('gemini_api_error_message')) {
    /** 開發環境回傳較明確的 API 錯誤訊息 */
    function gemini_api_error_message(Throwable $e): string {
        if (function_exists('app_is_production') && app_is_production()) {
            return 'Internal server error';
        }
        $msg = trim($e->getMessage());
        if ($msg === '') {
            return 'Internal server error';
        }
        if (mb_strlen($msg) > 300) {
            $msg = mb_substr($msg, 0, 300) . '…';
        }
        return $msg;
    }
}
