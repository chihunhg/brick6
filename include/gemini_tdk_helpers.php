<?php
declare(strict_types=1);

/**
 * Gemini SEO TDK 產生：產業範本與 prompt 組裝
 */

if (!function_exists('gemini_tdk_template_instruction')) {
    function gemini_tdk_template_instruction(string $industry): string {
        require_once __DIR__ . '/TdkTemplates.php';
        require_once __DIR__ . '/gemini_editor_helpers.php';

        return TdkTemplates::getInstruction(gemini_normalize_industry($industry));
    }
}

if (!function_exists('gemini_tdk_template_priority_block')) {
    function gemini_tdk_template_priority_block(string $industry): string {
        $template = gemini_tdk_template_instruction($industry);

        return "【最高優先：TDK 產業範本（產出必須優先依此執行）】\n"
            . "以下範本中的【角色任務】、【寫作規範】與【完美範本參考】為最高準則。\n"
            . "請優先仿效範本的語氣、JSON 欄位風格與合規要求；通用 SEO 規則僅作補充。\n\n"
            . $template;
    }
}

if (!function_exists('gemini_tdk_system_instruction')) {
    function gemini_tdk_system_instruction(
        string $industry = 'general',
        string $outputLocale = 'zh-tw',
        string $langLabel = '',
    ): string {
        require_once __DIR__ . '/gemini_lang_helpers.php';

        $languageRules = gemini_output_language_rules($outputLocale, $langLabel);
        $templateBlock = gemini_tdk_template_priority_block($industry);

        return <<<TEXT
你是一位專業的 SEO 文案助理，服務企業官網後台。

{$languageRules}
{$templateBlock}

【基本格式要求】
- 你必須只回傳一個 JSON 物件，且僅包含 title、description、keywords 三個欄位（字串）。
- 禁止輸出 JSON 以外的說明文字或 Markdown 程式碼區塊。
- title：60 字以內；description：120–160 字以內；keywords：5 個以內，半形逗號分隔，每個關鍵字 20 字以內。

【撰寫規範】
- 產出 TDK 時必須優先遵循「輸出語言」與「最高優先：TDK 產業範本」，再搭配基本格式要求。
- 輸出風格應接近【完美範本參考】的 title 結構、description 語氣與 keywords 選詞。
TEXT;
    }
}

if (!function_exists('gemini_build_tdk_user_prompt')) {
    function gemini_build_tdk_user_prompt(
        string $userPrompt,
        string $industry = 'general',
        array $langContext = [],
    ): string {
        require_once __DIR__ . '/gemini_editor_helpers.php';
        require_once __DIR__ . '/gemini_lang_helpers.php';

        if ($langContext === []) {
            $langContext = gemini_resolve_output_language([]);
        }

        $industryLabel = gemini_industry_options()[gemini_normalize_industry($industry)] ?? '一般';
        $templateHint = "【TDK 產業範本優先】：{$industryLabel}\n"
            . "請優先依 system instruction 中「最高優先：TDK 產業範本」產出 title、description、keywords。\n\n";

        return gemini_language_hint_for_user_prompt($langContext) . $templateHint . trim($userPrompt);
    }
}
