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

if (!function_exists('gemini_strict_safety_industry_keys')) {
    /**
     * 需啟用嚴格 Safety Settings 的產業（醫療、生技、上市櫃等）
     *
     * @return list<string>
     */
    function gemini_strict_safety_industry_keys(): array {
        return ['medical', 'biotech', 'listed_company', 'finance'];
    }
}

if (!function_exists('gemini_is_strict_safety_industry')) {
    function gemini_is_strict_safety_industry(string $industry): bool {
        $key = strtolower(trim($industry));
        if (function_exists('gemini_normalize_industry')) {
            $key = gemini_normalize_industry($industry);
        }

        return in_array($key, gemini_strict_safety_industry_keys(), true);
    }
}

if (!function_exists('gemini_build_safety_settings')) {
    /**
     * 依產業建立 Gemini Safety Settings
     *
     * @return list<\Gemini\Data\SafetySetting>
     */
    function gemini_build_safety_settings(string $industry = 'general'): array {
        $strict = gemini_is_strict_safety_industry($industry);
        $threshold = $strict
            ? \Gemini\Enums\HarmBlockThreshold::BLOCK_LOW_AND_ABOVE
            : \Gemini\Enums\HarmBlockThreshold::BLOCK_MEDIUM_AND_ABOVE;

        $categories = [
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_HATE_SPEECH,
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_HARASSMENT,
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_DANGEROUS_CONTENT,
            \Gemini\Enums\HarmCategory::HARM_CATEGORY_SEXUALLY_EXPLICIT,
        ];

        if ($strict) {
            $categories[] = \Gemini\Enums\HarmCategory::HARM_CATEGORY_MEDICAL;
            $categories[] = \Gemini\Enums\HarmCategory::HARM_CATEGORY_CIVIC_INTEGRITY;
        }

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
