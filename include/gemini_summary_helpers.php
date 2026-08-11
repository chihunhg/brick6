<?php
declare(strict_types=1);

if (!function_exists('gemini_summary_system_instruction')) {
    /** 組裝 AI 搜尋核心摘要產生的 system instruction */
    function gemini_summary_system_instruction(
        string $outputLocale = 'zh-tw',
        string $langLabel = '',
    ): string {
        require_once __DIR__ . '/gemini_lang_helpers.php';

        $languageRules = gemini_output_language_rules($outputLocale, $langLabel);

        return <<<TEXT
你是一位專業的 SEO／GEO 內容編輯助理，服務企業官網後台。

{$languageRules}

【基本格式要求】
- 你必須只回傳一個 JSON 物件，且僅包含 summary 一個欄位（字串）。
- 禁止輸出 JSON 以外的說明文字或 Markdown 程式碼區塊。
- summary：200–400 字以內，純文字段落，不含 HTML 標籤。

【撰寫規範】
- 產出「搜尋核心摘要」：精煉概括文章重點，語氣客觀、易讀，適合搜尋引擎與 AI 摘要引用。
- 保留關鍵資訊、核心論點與重要關鍵概念，避免贅詞與主觀評論。
- 若原文為英文或其他語言，摘要仍須依「輸出語言」規則撰寫。
TEXT;
    }
}

if (!function_exists('gemini_build_summary_user_prompt')) {
    /** 組裝 AI 搜尋核心摘要 user prompt */
    function gemini_build_summary_user_prompt(
        string $userPrompt,
        array $langContext = [],
    ): string {
        require_once __DIR__ . '/gemini_editor_helpers.php';
        require_once __DIR__ . '/gemini_lang_helpers.php';

        if ($langContext === []) {
            $langContext = gemini_resolve_output_language([]);
        }

        $taskHint = "【任務】：請根據以下網頁資料，產出精簡的 AI 搜尋核心摘要（summary）。\n\n";

        return gemini_language_hint_for_user_prompt($langContext) . $taskHint . trim($userPrompt);
    }
}
