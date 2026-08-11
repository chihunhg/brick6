<?php
declare(strict_types=1);

/**
 * 文章 AI 搜尋核心摘要產生 API（SSE 串流）
 */
$manage_binary_export = true;

require_once __DIR__ . '/_api_inc.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed'], 405);
}

require_once dirname(__DIR__) . '/include/gemini_editor_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_summary_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_lang_helpers.php';
require_once dirname(__DIR__) . '/include/gemini_stream_helpers.php';

$input = gemini_editor_parse_request();

$prompt = trim((string)($input['prompt'] ?? $input['Prompt'] ?? ''));
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
    $finalPrompt = gemini_build_summary_user_prompt($prompt, $langContext);

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
                'summary' => new Schema(type: DataType::STRING),
            ],
            required: ['summary'],
        ),
    );

    $model = $client
        ->generativeModel(model: 'gemini-2.5-flash')
        ->withSystemInstruction(Content::parse(
            gemini_sanitize_utf8_text(gemini_summary_system_instruction(
                $langContext['locale'],
                $langContext['label'],
            ))
        ))
        ->withGenerationConfig($generationConfig);

    $model = gemini_generative_model_with_safety_settings($model, 'general');

    gemini_stream_generate_content_sse(
        $model,
        $finalPrompt,
        static function (array $data): array {
            $summary = mb_substr(trim((string)($data['summary'] ?? '')), 0, 400);

            if ($summary === '') {
                throw new RuntimeException('Incomplete model response');
            }

            return [
                'success' => true,
                'summary' => $summary,
            ];
        }
    );
} catch (Throwable $e) {
    error_log('[generate_summary] ' . $e->getMessage());
    json_out(['success' => false, 'error' => gemini_api_error_message($e)], 500);
}
