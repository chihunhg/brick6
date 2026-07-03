<?php
declare(strict_types=1);

/**
 * Gemini 同步產生 CKEditor HTML 與 SEO TDK（單次 API 複合 Prompt）
 */

if (!function_exists('gemini_combined_system_instruction')) {
    function gemini_combined_system_instruction(
        string $industry = 'general',
        string $sourceUrl = '',
        string $formatMode = 'auto',
        string $outputLocale = 'zh-tw',
        string $langLabel = '',
    ): string {
        require_once __DIR__ . '/gemini_editor_helpers.php';
        require_once __DIR__ . '/gemini_tdk_helpers.php';
        require_once __DIR__ . '/gemini_lang_helpers.php';

        $languageRules = gemini_output_language_rules($outputLocale, $langLabel);
        $industryTemplateBlock = gemini_editor_industry_template_priority_block($industry);
        $tdkTemplate = gemini_tdk_template_instruction($industry);
        $structureRules = gemini_editor_html_structure_rules();
        $formatRules = gemini_editor_format_rules($formatMode);
        $styleRules = gemini_editor_style_rules();

        $sourceRule = $sourceUrl !== ''
            ? "【資料來源限制】\n你只能依據使用者提供的【參考網頁完整內文】（來源：{$sourceUrl}）改寫或延伸 html_content 與 TDK。\nTDK 的 title、description、keywords 必須忠實反映 html_content 主題，禁止捏造未提及的事實。\n"
            : "【資料來源限制】\n未提供參考網址時，html_content 與 TDK 須圍繞同一寫作任務，不得捏造具體數據或未允許的承諾。\n";

        return <<<TEXT
你是一位高階網頁內容、SEO 與 CKEditor 編輯專家，服務企業官網後台。

{$languageRules}

【單次 JSON 回傳格式（必讀）】
- 你必須只回傳一個 JSON 物件，且同時包含以下四個字串欄位：
  - html_content：CKEditor 編輯區用的 HTML 片段
  - title：SEO 標題，60 字以內
  - description：meta description，120–160 字以內
  - keywords：5 個以內關鍵字，半形逗號分隔，每個 20 字以內
- html_content 與 TDK 必須主題一致；TDK 應精準摘要 html_content，而非各自獨立發揮。
- 禁止輸出 JSON 以外的說明文字或 Markdown 程式碼區塊。

{$industryTemplateBlock}

【TDK 產業範本（title / description / keywords 必須優先依此）】
{$tdkTemplate}

{$structureRules}
{$sourceRule}
{$styleRules}
{$formatRules}

【撰寫規範】
- 優先遵循「輸出語言」與產業 Few-Shot 範本，一次產出內文 HTML 與 TDK。
- html_content 結構應接近 IndustryTemplates【完美範本參考】；TDK 應接近 TdkTemplates【完美範本參考】。
TEXT;
    }
}

if (!function_exists('gemini_build_combined_user_prompt')) {
    function gemini_build_combined_user_prompt(
        string $userPrompt,
        string $sourceUrl,
        string $formatMode = 'auto',
        string $sourcePageText = '',
        string $industry = 'general',
        array $langContext = [],
    ): string {
        require_once __DIR__ . '/gemini_editor_helpers.php';
        require_once __DIR__ . '/gemini_lang_helpers.php';

        if ($langContext === []) {
            $langContext = gemini_resolve_output_language([]);
        }

        $industryLabel = gemini_industry_options()[gemini_normalize_industry($industry)] ?? '一般';
        $formatLabel = gemini_format_mode_options()[gemini_normalize_format_mode($formatMode)] ?? '自動混排';

        $header = gemini_language_hint_for_user_prompt($langContext)
            . "【同步產出：HTML 內文 + SEO TDK】\n"
            . "產業範本：{$industryLabel}\n"
            . "排版格式：{$formatLabel}\n"
            . "請在單次回應中同時產出 html_content 以及 title、description、keywords，且彼此主題一致。\n\n";

        if ($sourceUrl !== '') {
            $sourceBlock = $sourcePageText !== ''
                ? "【參考網址】：{$sourceUrl}\n【參考網頁完整內文】：{$sourcePageText}\n"
                : "【參考網址】：{$sourceUrl}\n";

            return $header
                . $sourceBlock
                . "【寫作任務】：{$userPrompt}\n"
                . "請結合參考內容，優先依產業範本同步產出 html_content 與 TDK。\n"
                . '禁止捏造參考內文中未提及的關鍵事實或數據。';
        }

        return $header
            . "【寫作任務】：{$userPrompt}\n"
            . "請依產業範本、排版格式與寫作任務，同步產出 html_content 與 TDK。\n"
            . '禁止捏造具體數據、統計或未經允許的承諾。';
    }
}
