<?php
declare(strict_types=1);

/**
 * SEO TDK 產生 API（供 manage 各模組 _detail.php 後台 AJAX 呼叫）
 *
 * POST 參數：prompt（使用者提示詞）
 * 環境變數：GEMINI_API_KEY 或 GOOGLE_API_KEY（.env）
 *
 * 成功回應（Content-Type: application/json）：
 * {"title":"...","description":"...","keywords":"..."}
 */
$manage_binary_export = true;

require_once __DIR__ . '/_api_inc.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed'], 405);
}

$prompt = trim((string)($_POST['prompt'] ?? $_POST['Prompt'] ?? ''));
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
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;

try {
    $client = gemini_create_client($apiKey);

    $systemInstruction = <<<'TEXT'
你是一位專業的 SEO 文案助理。請依使用者提供的頁面內容或提示，產出繁體中文（台灣）的 SEO 中繼資料。
回傳 JSON 物件，且僅包含以下三個欄位：
- title：SEO 標題，60 字以內，精準概括頁面主題
- description：meta description，120–160 字以內，吸引點擊且忠實描述內容
- keywords：5 個以內的 SEO 關鍵字，以半形逗號分隔，每個關鍵字 20 字以內
TEXT;

    $generationConfig = new GenerationConfig(
        responseMimeType: ResponseMimeType::APPLICATION_JSON,
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

    $result = $client
        ->generativeModel(model: 'gemini-2.5-flash')
        ->withSystemInstruction(Content::parse($systemInstruction))
        ->withGenerationConfig($generationConfig)
        ->generateContent($prompt);

    $data = $result->json(associative: true);
    if (!is_array($data)) {
        json_out(['success' => false, 'error' => 'Invalid model response'], 502);
    }

    $tdk = [
        'title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 255),
        'description' => mb_substr(trim((string)($data['description'] ?? '')), 0, 500),
        'keywords' => trim((string)($data['keywords'] ?? '')),
    ];

    if ($tdk['title'] === '' || $tdk['description'] === '' || $tdk['keywords'] === '') {
        json_out(['success' => false, 'error' => 'Incomplete model response'], 502);
    }

    json_out(array_merge(['success' => true], $tdk));
} catch (Throwable $e) {
    error_log('[generate_tdk] ' . $e->getMessage());
    json_out(['success' => false, 'error' => gemini_api_error_message($e)], 500);
}
