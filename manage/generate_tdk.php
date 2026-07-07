<?php
declare(strict_types=1);

/**
 * SEO TDK 產生 API（SSE 串流）
 */
$manage_binary_export = true;

require_once __DIR__ . '/_api_inc.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed'], 405);
}

require_once dirname(__DIR__) . '/include/gemini_editor_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_tdk_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_lang_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_stream_helpers.php';

$input = gemini_editor_parse_request();

$prompt = trim((string)($input['prompt'] ?? $input['Prompt'] ?? ''));
$industry = trim((string)($input['industry'] ?? 'general'));
$langContext = gemini_resolve_output_language($input);

if ($prompt === '') {
    json_out(['success' => false, 'error' => 'prompt is required'], 400);
}

$apiKey = gemini_resolve_api_key();
if ($apiKey === '') {
    $envHint = defined('APP_ENV_DIR') ? ('（已載入：' . APP_ENV_DIR . '）') : '（未找到 .env）';
    json_out([
        'success' => false,
        'error' => 'GEMINI_API_KEY 未設定' . $envHint . '。正式機請確認 private/.env 含金鑰，或建立 config/env.path.php',
    ], 500);
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    json_out(['success' => false, 'error' => '找不到 vendor/autoload.php，請於伺服器執行 composer install'], 500);
}
require_once $autoload;
require_once dirname(__DIR__) . '/include/gemini_client.php';

use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Data\ThinkingConfig;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;

try {
    $client = gemini_create_client($apiKey, 120);
    $normalizedIndustry = gemini_normalize_industry($industry);
    $finalPrompt = gemini_build_tdk_user_prompt($prompt, $normalizedIndustry, $langContext);

    $generationConfig = new GenerationConfig(
        maxOutputTokens: 512,
        responseMimeType: ResponseMimeType::APPLICATION_JSON,
        thinkingConfig: new ThinkingConfig(
            includeThoughts: false,
            thinkingBudget: 0,
        ),
        responseSchema: new Schema(
            type: DataType::OBJECT,
            properties: [
                'title' => new Schema(type: DataType::STRING),
                'description' => new Schema(type: DataType::STRING),
                'keywords' => new Schema(type: DataType::STRING),
            ],
            required: ['title', 'description', 'keywords'],
        ),
    );

    $model = $client
        ->generativeModel(model: 'gemini-2.5-flash')
        ->withSystemInstruction(Content::parse(
            gemini_sanitize_utf8_text(gemini_tdk_system_instruction(
                $normalizedIndustry,
                $langContext['locale'],
                $langContext['label'],
            ))
        ))
        ->withGenerationConfig($generationConfig);

    $model = gemini_generative_model_with_safety_settings($model, $normalizedIndustry);

    gemini_stream_generate_content_sse(
        $model,
        $finalPrompt,
        static function (array $data): array {
            $tdk = [
                'title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 255),
                'description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 500),
                'keywords' => trim((string)($data['keywords'] ?? '')),
            ];

            if ($tdk['title'] === '' || $tdk['description'] === '' || $tdk['keywords'] === '') {
                throw new RuntimeException('Incomplete model response');
            }

            return array_merge(['success' => true], $tdk);
        }
    );
} catch (Throwable $e) {
    error_log('[generate_tdk] ' . $e->getMessage());
    json_out(['success' => false, 'error' => gemini_api_error_message($e)], 500);
}
