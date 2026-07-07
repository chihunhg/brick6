<?php
declare(strict_types=1);

/**
 * Gemini API Client 與 SSL 設定
 *
 * 環境變數：GEMINI_API_KEY、GEMINI_SSL_VERIFY（0=略過驗證，或 CA 檔路徑）
 *
 * 使用方式：
 *   require_once __DIR__ . '/gemini_client.php';
 *   $client = gemini_create_client($apiKey, 60);
 *   $model  = gemini_generative_model_with_safety_settings($client, 'gemini-2.0-flash');
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

        foreach (['openssl.cafile', 'curl.cainfo'] as $iniKey) {
            $ca = trim((string)ini_get($iniKey));
            if ($ca !== '' && @is_file($ca)) {
                return $ca;
            }
        }

        $projectRoot = dirname(__DIR__);
        foreach ([
            $projectRoot . '/ssl/cacert.pem',
            $projectRoot . '/config/cacert.pem',
        ] as $localCa) {
            if (is_file($localCa)) {
                return $localCa;
            }
        }

        // Plesk 等 open_basedir 環境：不可掃描 /etc/pki/...（Guzzle defaultCaBundle 會觸發 Warning）
        if (trim((string)ini_get('open_basedir')) !== '') {
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
    /**
     * 建立 google-gemini-php Client（含 SSL 與 timeout）
     *
     * @param int $timeoutSeconds 請求逾時秒數，最少 30
     */
    function gemini_create_client(string $apiKey, int $timeoutSeconds = 60): \Gemini\Client {
        $guzzleOptions = [
            'timeout' => max(30, $timeoutSeconds),
            'verify'  => gemini_resolve_ssl_verify(),
        ];

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
        if ($e instanceof \JsonException
            || stripos($msg, 'Malformed UTF-8') !== false
            || stripos($msg, 'incorrectly encoded') !== false) {
            return '輸入或參考網址內容含有無法辨識的字元編碼，請精簡提示詞、更換參考網址或移除特殊符號後再試。';
        }
        if (mb_strlen($msg) > 300) {
            $msg = mb_substr($msg, 0, 300) . '…';
        }
        return $msg;
    }
}

if (!function_exists('gemini_strict_safety_industry_keys')) {
    /**
     * 曾用於較嚴格 Safety 的產業（現改由 GEMINI_SAFETY_THRESHOLD 統一控制，保留供文件參考）
     *
     * @return list<string>
     */
    function gemini_strict_safety_industry_keys(): array {
        return ['medical', 'biotech', 'listed_company', 'finance'];
    }
}

if (!function_exists('gemini_is_strict_safety_industry')) {
    /** 是否為需較嚴格 Safety 的產業（medical 等，現僅供參考） */
    function gemini_is_strict_safety_industry(string $industry): bool {
        $key = strtolower(trim($industry));
        if (function_exists('gemini_normalize_industry')) {
            $key = gemini_normalize_industry($industry);
        }

        return in_array($key, gemini_strict_safety_industry_keys(), true);
    }
}

if (!function_exists('gemini_resolve_safety_threshold')) {
    /**
     * 讀取 .env GEMINI_SAFETY_THRESHOLD（預設 only_high，僅擋 HIGH 機率危害）
     *
     * 可選：off | none | low | medium | high | only_high
     */
    function gemini_resolve_safety_threshold(): \Gemini\Enums\HarmBlockThreshold {
        $raw = strtolower(trim((string)($_ENV['GEMINI_SAFETY_THRESHOLD'] ?? getenv('GEMINI_SAFETY_THRESHOLD') ?: '')));

        return match ($raw) {
            'off', 'disable', 'disabled' => \Gemini\Enums\HarmBlockThreshold::OFF,
            'none', 'block_none' => \Gemini\Enums\HarmBlockThreshold::BLOCK_NONE,
            'low', 'block_low' => \Gemini\Enums\HarmBlockThreshold::BLOCK_LOW_AND_ABOVE,
            'medium', 'block_medium' => \Gemini\Enums\HarmBlockThreshold::BLOCK_MEDIUM_AND_ABOVE,
            'high', 'only_high', 'block_only_high', '' => \Gemini\Enums\HarmBlockThreshold::BLOCK_ONLY_HIGH,
            default => \Gemini\Enums\HarmBlockThreshold::BLOCK_ONLY_HIGH,
        };
    }
}

if (!function_exists('gemini_build_safety_settings')) {
    /**
     * Gemini Safety Settings（後台產文：預設僅 BLOCK_ONLY_HIGH，避免過度阻擋官網內容）
     * 僅使用 API 允許的 HarmCategory（勿送 HARM_CATEGORY_MEDICAL 等已淘汰類別）
     *
     * @return list<\Gemini\Data\SafetySetting>
     */
    function gemini_build_safety_settings(string $industry = 'general'): array {
        unset($industry);
        $threshold = gemini_resolve_safety_threshold();

        $categories = [
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_HATE_SPEECH,
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_HARASSMENT,
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_DANGEROUS_CONTENT,
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_SEXUALLY_EXPLICIT,
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_CIVIC_INTEGRITY,
        ];

        $settings = [];
        foreach ($categories as $category) {
            $settings[$category->value] = new \Gemini\Data\SafetySetting(
                category: $category,
                threshold: $threshold,
            );
        }

        return array_values($settings);
    }
}

if (!function_exists('gemini_generative_model_with_safety_settings')) {
    /** 套用 gemini_build_safety_settings 至 GenerativeModel */
    function gemini_generative_model_with_safety_settings(
        \Gemini\Resources\GenerativeModel $model,
        string $industry = 'general',
    ): \Gemini\Resources\GenerativeModel {
        foreach (gemini_build_safety_settings($industry) as $setting) {
            $model = $model->withSafetySetting($setting);
        }

        return $model;
    }
}

if (!function_exists('gemini_response_safety_blocked_message')) {
    /** 若回應被 Safety Settings 阻擋，回傳使用者可讀訊息；否則 null */
    function gemini_response_safety_blocked_message(
        \Gemini\Responses\GenerativeModel\GenerateContentResponse $result,
    ): ?string {
        if ($result->promptFeedback?->blockReason !== null) {
            return '內容因安全與合規性過濾而被阻擋，請調整提示詞或參考內容後再試。';
        }

        if ($result->candidates === []) {
            return '模型未回傳內容，可能觸發安全過濾，請調整提示詞後再試。';
        }

        $finishReason = $result->candidates[0]->finishReason;
        if ($finishReason === \Gemini\Enums\FinishReason::SAFETY
            || $finishReason === \Gemini\Enums\FinishReason::PROHIBITED_CONTENT
            || $finishReason === \Gemini\Enums\FinishReason::BLOCKLIST) {
            return '產出內容因安全與合規性過濾而被阻擋，請調整提示詞或參考內容後再試。';
        }

        return null;
    }
}
