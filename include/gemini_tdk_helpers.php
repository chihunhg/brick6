<?php
declare(strict_types=1);

if (!function_exists('gemini_tdk_template_instruction')) {
/**
 * Gemini SEO TDK 產生：產業範本與 prompt 組裝
 */

    function gemini_tdk_template_instruction(string $industry): string {
        require_once __DIR__ . '/TdkTemplates.php';
        require_once __DIR__ . '/gemini_editor_helpers.php';

        return TdkTemplates::getInstruction(gemini_normalize_industry($industry));
    }
}

if (!function_exists('gemini_tdk_template_priority_block')) {
    /** TDK 產業範本最高優先區塊（含 system instruction） */
    function gemini_tdk_template_priority_block(string $industry): string {
        $template = gemini_tdk_template_instruction($industry);

        return "【TDK 產業範本（風格與結構）】\n"
            . "【角色任務】與【寫作規範】用來決定語氣與合規，但不得推翻【輸出語言】與使用者的寫作任務。\n"
            . "【完美範本參考】只示範 title、description、keywords 的長度與語氣。\n"
            . "禁止把範本用詞原樣輸出、改寫或翻譯。\n\n"
            . $template;
    }
}

if (!function_exists('gemini_tdk_system_instruction')) {
    /** 組裝 TDK 產生的 system instruction */
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
- 產出 TDK 時必須優先遵循「輸出語言」與使用者的寫作任務，再依 TDK 產業範本的語氣與合規。
- 輸出可接近【完美範本參考】的 title 結構、description 語氣與 keywords 選詞，但用詞必須依寫作任務重寫，不得翻譯範本。
TEXT;
    }
}

if (!function_exists('gemini_build_tdk_user_prompt')) {
    /** 組裝 TDK user prompt（語系 + 產業 + 任務） */
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
        $templateHint = "【TDK 產業範本】：{$industryLabel}\n"
            . "請依 TDK 產業範本的語氣與合規產出 title、description、keywords。\n"
            . "不得翻譯或照抄【完美範本參考】；用詞以使用者的寫作任務為準。\n\n";

        return gemini_language_hint_for_user_prompt($langContext) . $templateHint . trim($userPrompt);
    }
}
