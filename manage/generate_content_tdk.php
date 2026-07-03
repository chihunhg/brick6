<?php
declare(strict_types=1);

/**
 * 同步產生 CKEditor HTML 與 SEO TDK（單次 Gemini 請求）
 *
 * POST / JSON body 參數：
 * - prompt（必填）使用者提示詞
 * - lang_slot / lang_label（選填）後台語系索引與名稱（對應 language / $array_lang）
 * - source_url（選填）參考網址
 * - industry（可選）產業別
 * - format_mode（可選）auto | prose | table | list
 */
$manage_binary_export = true;

require_once __DIR__ . '/_api_inc.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed'], 405);
}

require_once dirname(__DIR__) . '/include/gemini_editor_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_tdk_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_combined_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_lang_helpers.php';
require_once dirname(__DIR__) . '/include/crud_helpers.php';

$input = gemini_editor_parse_request();

$prompt = trim((string)($input['prompt'] ?? $input['Prompt'] ?? ''));
$sourceUrlRaw = trim((string)($input['source_url'] ?? $input['sourceUrl'] ?? ''));
$industry = trim((string)($input['industry'] ?? 'general'));
$formatMode = trim((string)($input['format_mode'] ?? $input['formatMode'] ?? 'auto'));
$langContext = gemini_resolve_output_language($input);

if ($prompt === '') {
    json_out(['success' => false, 'error' => 'prompt is required'], 400);
}

try {
    $sourceUrl = gemini_validate_source_url($sourceUrlRaw);
} catch (InvalidArgumentException $e) {
    json_out(['success' => false, 'error' => $e->getMessage()], 400);
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
    $client = gemini_create_client($apiKey);
    $normalizedIndustry = gemini_normalize_industry($industry);
    $normalizedFormatMode = gemini_normalize_format_mode($formatMode);

    $sourcePageText = '';
    if ($sourceUrl !== '') {
        try {
            $sourcePageText = gemini_fetch_source_page_text($sourceUrl);
        } catch (RuntimeException $e) {
            json_out(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    $finalPrompt = gemini_build_combined_user_prompt(
        userPrompt: $prompt,
        sourceUrl: $sourceUrl,
        formatMode: $normalizedFormatMode,
        sourcePageText: $sourcePageText,
        industry: $normalizedIndustry,
        langContext: $langContext,
    );

    $generationConfig = new GenerationConfig(
        maxOutputTokens: 3584,
        responseMimeType: ResponseMimeType::APPLICATION_JSON,
        thinkingConfig: new ThinkingConfig(
            includeThoughts: false,
            thinkingBudget: 0,
        ),
        responseSchema: new Schema(
            type: DataType::OBJECT,
            properties: [
                'html_content' => new Schema(type: DataType::STRING),
                'title' => new Schema(type: DataType::STRING),
                'description' => new Schema(type: DataType::STRING),
                'keywords' => new Schema(type: DataType::STRING),
            ],
            required: ['html_content', 'title', 'description', 'keywords'],
        ),
    );

    $model = $client
        ->generativeModel(model: 'gemini-2.5-flash')
        ->withSystemInstruction(Content::parse(
            gemini_combined_system_instruction(
                industry: $normalizedIndustry,
                sourceUrl: $sourceUrl,
                formatMode: $normalizedFormatMode,
                outputLocale: $langContext['locale'],
                langLabel: $langContext['label'],
            )
        ))
        ->withGenerationConfig($generationConfig);

    $model = gemini_generative_model_with_safety_settings($model, $normalizedIndustry);

    $result = $model->generateContent($finalPrompt);

    $safetyBlockedMessage = gemini_response_safety_blocked_message($result);
    if ($safetyBlockedMessage !== null) {
        json_out(['success' => false, 'error' => $safetyBlockedMessage], 422);
    }

    $data = $result->json(associative: true);
    if (!is_array($data)) {
        json_out(['success' => false, 'error' => 'Invalid model response'], 502);
    }

    $htmlContent = gemini_sanitize_editor_html((string)($data['html_content'] ?? ''));
    $title = mb_substr(trim((string)($data['title'] ?? '')), 0, 255);
    $description = mb_substr(trim((string)($data['description'] ?? '')), 0, 500);
    $keywords = trim((string)($data['keywords'] ?? ''));

    if ($htmlContent === '' || $title === '' || $description === '' || $keywords === '') {
        json_out(['success' => false, 'error' => 'Incomplete model response'], 502);
    }

    json_out([
        'success' => true,
        'html_content' => $htmlContent,
        'title' => $title,
        'description' => $description,
        'keywords' => $keywords,
    ]);
} catch (Throwable $e) {
    error_log('[generate_content_tdk] ' . $e->getMessage());
    json_out(['success' => false, 'error' => gemini_api_error_message($e)], 500);
}
