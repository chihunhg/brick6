<?php
declare(strict_types=1);

/**
 * Gemini 編輯器 HTML 產生：清理、驗證與產業規範
 */

if (!function_exists('gemini_editor_parse_request')) {
    /**
     * 解析 POST 表單或 JSON body（application/json）
     *
     * @return array<string, mixed>
     */
    function gemini_editor_parse_request(): array {
        $input = $_POST;
        if ($input !== []) {
            return $input;
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('gemini_normalize_industry')) {
    function gemini_normalize_industry(string $industry): string {
        $key = strtolower(trim($industry));

        return match ($key) {
            'medical', 'healthcare', '醫療' => 'medical',
            'biotech', '生技', '保健食品' => 'biotech',
            'electronics', 'electronic', '電子', '半導體', '高科技' => 'electronics',
            'listed_company', 'listed', 'ir', '上市櫃', '上櫃' => 'listed_company',
            'japanese_client', 'japanese', '日系', '日商' => 'japanese_client',
            'finance', 'financial', '金融' => 'finance',
            'beauty', 'cosmetics', '美妝', '化妝品' => 'beauty',
            default => 'general',
        };
    }
}

if (!function_exists('gemini_industry_options')) {
    /**
     * @return array<string, string> value => 顯示名稱
     */
    function gemini_industry_options(): array {
        return [
            'general' => '一般',
            'electronics' => '電子',
            'medical' => '醫療',
            'biotech' => '生技',
            'listed_company' => '上市櫃',
            'japanese_client' => '日系',
            'finance' => '金融',
            'beauty' => '美妝',
        ];
    }
}

if (!function_exists('gemini_editor_allowed_html_tags')) {
    /** CKEditor 產文允許的 HTML 標籤（供 strip_tags 與 System Instruction 共用） */
    function gemini_editor_allowed_html_tags(): string {
        return '<h2><h3><h4><h5><h6><p><br><strong><em><span><ul><ol><li><blockquote><small><div>'
            . '<table><caption><thead><tbody><tr><th><td>';
    }
}

if (!function_exists('gemini_editor_allowed_html_tag_names')) {
    function gemini_editor_allowed_html_tag_names(): string {
        return 'h2, h3, h4, h5, h6, p, br, strong, em, span, ul, ol, li, blockquote, small, div, '
            . 'table, caption, thead, tbody, tr, th, td';
    }
}

if (!function_exists('gemini_editor_allowed_font_sizes')) {
    /** 與 CKEditor fontSize_sizes 對齊的建議字級 */
    function gemini_editor_allowed_font_sizes(): array {
        return ['14px', '16px', '18px', '20px', '22px', '24px'];
    }
}

if (!function_exists('gemini_editor_allowed_colors')) {
    /** 建議色碼（CKEditor 色盤子集＋站內常用色） */
    function gemini_editor_allowed_colors(): array {
        return [
            '#000000', '#333333', '#666666', '#3498db', '#2980b9', '#2c3e50',
            '#e74c3c', '#c0392b', '#16a085', '#27ae60', '#bf9771', '#698396',
        ];
    }
}

if (!function_exists('gemini_editor_style_rules')) {
    /** AI 產文：字級、顏色、粗體使用規範（與 CKEditor 工具列一致） */
    function gemini_editor_style_rules(): string {
        $colorSamples = implode(', ', array_map(
            static fn(string $color): string => strtoupper($color),
            gemini_editor_allowed_colors()
        ));
        $sizeSamples = implode(', ', gemini_editor_allowed_font_sizes());

        return "【字級、顏色與粗體】\n"
            . "- 粗體：使用 <strong>（勿用 <b>）。\n"
            . "- 顏色：使用 <span style=\"color:#RRGGBB\">文字</span>，建議色碼：{$colorSamples}。\n"
            . "- 字級：使用 <span style=\"font-size:NNpx\">文字</span>，建議字級：{$sizeSamples}。\n"
            . "- 可組合範例：<span style=\"color:#2980B9;font-size:18px\"><strong>重點標語</strong></span>。\n"
            . "- 表格標籤（table/th/td）與標題（h2～h6）不要加 style；span 只用於段落或清單內的重點強調。\n";
    }
}

if (!function_exists('gemini_editor_style_hint_line')) {
    function gemini_editor_style_hint_line(): string {
        return "- 適度使用 <strong> 粗體，並以 <span style=\"color:#2980B9\"> 或 <span style=\"font-size:18px\"> 強調關鍵字句。\n";
    }
}

if (!function_exists('gemini_format_mode_options')) {
    /**
     * @return array<string, string> value => 顯示名稱
     */
    function gemini_format_mode_options(): array {
        return [
            'auto' => '自動混排',
            'prose' => '圖文段落',
            'table' => '表格為主',
            'list' => '條列重點',
        ];
    }
}

if (!function_exists('gemini_normalize_format_mode')) {
    function gemini_normalize_format_mode(string $formatMode): string {
        $key = strtolower(trim($formatMode));

        return match ($key) {
            'prose', 'text', 'paragraph', '圖文', '段落' => 'prose',
            'table', 'tables', '表格' => 'table',
            'list', 'bullet', '條列', '清單' => 'list',
            default => 'auto',
        };
    }
}

if (!function_exists('gemini_editor_format_rules')) {
    function gemini_editor_format_rules(string $formatMode): string {
        $styleHint = gemini_editor_style_hint_line();

        return match (gemini_normalize_format_mode($formatMode)) {
            'prose' => "【排版模式：圖文段落】\n"
                . "- 以 <h2>、<h3> 分段，內文用 <p>，重點以 <strong>、<em> 標示。\n"
                . $styleHint
                . "- 可搭配 <ul>/<ol> 補充說明，非必要不使用表格。\n",

            'table' => "【排版模式：表格為主】\n"
                . "- 至少包含一個完整 <table>（含 <thead> 或 <tbody>、<tr>、<th>、<td>）。\n"
                . "- 表頭列請放在 <thead>，資料列放在 <tbody>；欄位標題用 <th>，內容用 <td>。\n"
                . "- 規格、方案比較、流程步驟、數據整理優先放入表格；表格外以 <h2>/<p> 簡短引言。\n"
                . "- 表格外段落可用 <strong> 與 <span style> 強調重點；表格內請勿加 style。\n"
                . "- 系統會自動為表格加上 RWD 捲動外層與框線、隔行底色樣式，請勿自行加 class 或 style。\n",

            'list' => "【排版模式：條列重點】\n"
                . "- 以 <ul> 或 <ol> 為主體呈現重點，每項可用 <strong> 標示關鍵字。\n"
                . $styleHint
                . "- 搭配 <h2> 小標與 <p> 摘要，非必要不使用表格。\n",

            default => "【排版模式：自動混排】\n"
                . "- 依內容性質混合標題、段落、清單與表格。\n"
                . "- 有規格／比較／數據時用 <table>；敘述用 <p>；重點用 <ul>/<ol>；關鍵字用 <strong>/<em>。\n"
                . $styleHint,
        };
    }
}

if (!function_exists('gemini_industry_rules')) {
    function gemini_industry_rules(string $industry): string {
        return match (gemini_normalize_industry($industry)) {
            'medical' => "【醫療產業廣告法規限制】：\n"
                . "- 嚴格禁止提及任何『宣稱療效』、『保證治癒』、『100%有效』或影射具備醫療效果的誇大詞彙（違反醫療法）。\n"
                . "- 語氣必須極度客觀、中立、科學、嚴謹。\n"
                . "- 內文格式要求：適當使用 <h2> 標記臨床數據或醫學原理，禁止使用帶有煽動性的 <strong> 粗體字。\n"
                . "- 強制警語：必須在文章最後加入一行段落：『<p><em>*本文內容僅供參考，任何醫療行為與疾病診斷請務必諮詢專業醫師。</em></p>』",

            'biotech' => "【生技與保健食品規範】：\n"
                . "- 禁止使用涉及影響生理機能、減肥瘦身、或與醫療效果混淆的違規字眼（如：降三高、抗癌、根治）。\n"
                . "- 內容著重於『營養補給』、『維持健康』、『調整體質』與『專利研發技術』描述。\n"
                . "- 格式要求：請使用 <ul> 和 <li> 清單詳細列出『專利成分』與『國際認證』，提高視覺信任度。",

            'electronics' => "【電子與高科技產業規範】：\n"
                . "- 用詞必須符合產業標準（如：低延遲、高擴展性、封裝、良率、製程、符合 RoHS/REACH 環保規範）。\n"
                . "- 語氣要展現『創新』、『高技術門檻』與『系統穩定度』。\n"
                . "- 格式要求：規格、效能對比、參數等數據，必須使用標準的 HTML <table> 標籤呈現，不得散亂在一般段落中。",

            'listed_company' => "【上市櫃公司企業公告與 IR 規範】：\n"
                . "- 語氣必須絕對官方、沉穩、宏觀，符合法人與股東的期待。\n"
                . "- 嚴格禁止涉及內線交易、保證未來 EPS、預測股價等敏感內容。\n"
                . "- 提及未來展望時，必須使用『預期』、『旨在』等保守中性詞，並符合證交法公開資訊觀測站之揭露原則。\n"
                . "- 強制免責聲明：結尾必須加上：『<p><small>免責聲明：本內容包含前瞻性陳述，受市場風險影響，實際營運結果可能與預期有所差異，投資人應審慎評估。</small></p>』",

            'japanese_client' => "【日系企業商務語氣規範】：\n"
                . "- 語氣必須展現極高的『職人精神』、『嚴格品質把關』與『客戶至上（御中）』的謙遜與誠懇。\n"
                . "- 內容多強調：細節（こだわり）、安心安全、售後服務、長期信任關係，而非一味低價競爭。\n"
                . "- 格式要求：文章結構要極度井然有序。標題（<h1>~<h3>）必須層級嚴明，段落與段落之間必須有清晰的起承轉合。",

            'finance' => "【金融產業規範】：\n"
                . "- 嚴格禁止保證獲利、承諾投資報酬率或暗示零風險。\n"
                . "- 內容必須包含風險揭露，且僅能改寫參考網址中已提及的產品或資訊。\n"
                . "- 文末必須加上：<p><small>投資一定有風險，基金投資有賺有賠，申購前應詳閱公開說明書</small></p>",

            'beauty' => "【美妝化妝品規範】：\n"
                . "- 禁止使用「換膚」「根除皺紋」「立即見效」等誇大詞彙。\n"
                . "- 必須符合化妝品廣告法規，著重於修飾、清潔與保養描述。\n"
                . "- 不得捏造參考網址未提及的成分功效或檢驗數據。",

            default => "【一般商業規範】：請確保內容用詞專業、通順，符合企業官方部落格語氣。不得捏造參考網址未提及的關鍵事實、數據或承諾。\n"
                . "- 格式建議：標題層級分明，段落清晰，適度使用粗體強調與清單；有數據比較時可輸出表格。",
        };
    }
}

if (!function_exists('gemini_validate_source_url')) {
    /**
     * 驗證參考網址；空字串表示未提供（選填）
     *
     * @throws InvalidArgumentException 格式不正確時
     */
    function gemini_validate_source_url(string $sourceUrl): string {
        $sourceUrl = trim($sourceUrl);
        if ($sourceUrl === '') {
            return '';
        }
        if (filter_var($sourceUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('source_url is invalid');
        }

        $scheme = strtolower((string)parse_url($sourceUrl, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('source_url must use http or https');
        }

        return $sourceUrl;
    }
}

if (!function_exists('gemini_strip_markdown_fences')) {
    /** 移除模型可能回傳的 Markdown 程式碼區塊標記 */
    function gemini_strip_markdown_fences(string $text): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (preg_match('/^```(?:html)?\s*([\s\S]*?)\s*```$/i', $text, $m)) {
            return trim((string)$m[1]);
        }
        $text = preg_replace('/^```(?:html)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```\s*$/', '', $text) ?? $text;

        return trim($text);
    }
}

if (!function_exists('gemini_sanitize_color_value')) {
    function gemini_sanitize_color_value(string $value): string {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }
        if (preg_match('/^#([0-9a-f]{3})$/', $value, $shortMatch)) {
            $hex = $shortMatch[1];
            return '#' . $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (preg_match('/^#([0-9a-f]{6})$/', $value)) {
            return $value;
        }

        return '';
    }
}

if (!function_exists('gemini_sanitize_font_size_value')) {
    function gemini_sanitize_font_size_value(string $value): string {
        $value = strtolower(trim($value));
        if (!preg_match('/^(\d{1,2})px$/', $value, $match)) {
            return '';
        }
        $size = (int)$match[1];
        if ($size < 10 || $size > 72) {
            return '';
        }

        return $size . 'px';
    }
}

if (!function_exists('gemini_sanitize_span_style')) {
    function gemini_sanitize_span_style(string $style): string {
        $parts = [];
        foreach (explode(';', $style) as $declaration) {
            $declaration = trim($declaration);
            if ($declaration === '' || !str_contains($declaration, ':')) {
                continue;
            }
            [$property, $rawValue] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            if ($property === 'color') {
                $color = gemini_sanitize_color_value($rawValue);
                if ($color !== '') {
                    $parts['color'] = 'color:' . $color;
                }
                continue;
            }
            if ($property === 'font-size') {
                $fontSize = gemini_sanitize_font_size_value($rawValue);
                if ($fontSize !== '') {
                    $parts['font-size'] = 'font-size:' . $fontSize;
                }
            }
        }

        return implode(';', $parts);
    }
}

if (!function_exists('gemini_dom_unwrap_element')) {
    function gemini_dom_unwrap_element(DOMNode $element): void {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }
        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }
}

if (!function_exists('gemini_dom_replace_element_tag')) {
    function gemini_dom_replace_element_tag(DOMElement $element, string $newTag, DOMDocument $document): DOMElement {
        $replacement = $document->createElement($newTag);
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attribute) {
                $replacement->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }
        }
        while ($element->firstChild !== null) {
            $replacement->appendChild($element->firstChild);
        }
        $element->parentNode?->replaceChild($replacement, $element);

        return $replacement;
    }
}

if (!function_exists('gemini_sanitize_editor_html_dom')) {
    function gemini_sanitize_editor_html_dom(string $html): string {
        if (!class_exists(DOMDocument::class)) {
            return '';
        }

        $allowedTags = array_flip(array_map(
            static fn(string $tag): string => strtolower(trim($tag)),
            explode(',', str_replace(' ', '', gemini_editor_allowed_html_tag_names()))
        ));
        $tableTags = ['table', 'caption', 'thead', 'tbody', 'tr', 'th', 'td'];
        $headingTags = ['h2', 'h3', 'h4', 'h5', 'h6'];

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousLibxmlState = libxml_use_internal_errors(true);
        $wrapperId = 'gemini_editor_root';
        $wrappedHtml = '<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . '<div id="' . $wrapperId . '">' . $html . '</div>';
        $document->loadHTML(
            $wrappedHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        $xpath = new DOMXPath($document);
        $wrapper = $xpath->query('//*[@id="' . $wrapperId . '"]')->item(0);
        if (!$wrapper instanceof DOMElement) {
            return '';
        }

        foreach ($xpath->query('.//comment()', $wrapper) as $comment) {
            $comment->parentNode?->removeChild($comment);
        }
        foreach ($xpath->query('.//script|.//style', $wrapper) as $unsafeNode) {
            $unsafeNode->parentNode?->removeChild($unsafeNode);
        }

        $nodes = [];
        foreach ($xpath->query('.//*', $wrapper) as $node) {
            $nodes[] = $node;
        }
        $nodes = array_reverse($nodes);

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->nodeName);
            if ($tag === 'b') {
                $node = gemini_dom_replace_element_tag($node, 'strong', $document);
                $tag = 'strong';
            } elseif ($tag === 'i') {
                $node = gemini_dom_replace_element_tag($node, 'em', $document);
                $tag = 'em';
            } elseif ($tag === 'font') {
                $styleParts = [];
                $color = gemini_sanitize_color_value((string)$node->getAttribute('color'));
                if ($color !== '') {
                    $styleParts[] = 'color:' . $color;
                }
                $node = gemini_dom_replace_element_tag($node, 'span', $document);
                if ($styleParts !== []) {
                    $node->setAttribute('style', implode(';', $styleParts));
                }
                $tag = 'span';
            }

            if (!isset($allowedTags[$tag])) {
                gemini_dom_unwrap_element($node);
                continue;
            }

            $preserveStyle = $tag === 'span' && !in_array($tag, $tableTags, true) && !in_array($tag, $headingTags, true);
            $styleValue = $preserveStyle ? gemini_sanitize_span_style((string)$node->getAttribute('style')) : '';

            $attributeNames = [];
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attribute) {
                    $attributeNames[] = $attribute->nodeName;
                }
            }
            foreach ($attributeNames as $attributeName) {
                $node->removeAttribute($attributeName);
            }

            if ($tag === 'span' && $styleValue !== '') {
                $node->setAttribute('style', $styleValue);
            } elseif ($tag === 'span') {
                gemini_dom_unwrap_element($node);
            }
        }

        $output = '';
        foreach ($wrapper->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }
}

if (!function_exists('gemini_sanitize_editor_html')) {
    /**
     * 僅保留允許的 HTML 標籤（與 System Instruction 一致）；span 僅允許 color / font-size
     */
    function gemini_sanitize_editor_html(string $html): string {
        $html = gemini_strip_markdown_fences($html);
        if ($html === '') {
            return '';
        }

        $sanitized = gemini_sanitize_editor_html_dom($html);
        if ($sanitized === '') {
            $html = strip_tags($html, gemini_editor_allowed_html_tags());
        } else {
            $html = $sanitized;
        }

        $html = preg_replace("/\r\n|\r/", "\n", $html) ?? $html;
        $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;
        $html = trim($html);

        if ($html !== '' && function_exists('manage_enhance_content_tables')) {
            $html = manage_enhance_content_tables($html);
        }

        return $html;
    }
}

if (!function_exists('gemini_editor_system_instruction')) {
    function gemini_editor_system_instruction(
        string $industry = 'general',
        string $sourceUrl = '',
        string $formatMode = 'auto',
    ): string {
        $industryRules = gemini_industry_rules($industry);
        $formatRules = gemini_editor_format_rules($formatMode);
        $styleRules = gemini_editor_style_rules();
        $allowedTags = gemini_editor_allowed_html_tag_names();
        $sourceRule = $sourceUrl !== ''
            ? "【資料來源限制】\n你被嚴格限制只能依據使用者提供的參考網址（{$sourceUrl}）內容來改寫或延伸。\n絕對禁止捏造該網址未提及的關鍵事實、數據、統計、客戶案例、療效、報酬率或承諾。\n若參考網址資訊不足，僅能保守改寫已知內容，不可自行補充未出現的細節。\n"
            : "【資料來源限制】\n使用者未提供參考網址時，請僅依寫作任務與產業規範產文，不得捏造具體數據、統計、客戶案例或法規未允許的承諾。\n";

        return <<<TEXT
你是一位高階網頁內容與 SEO 專家，服務繁體中文（台灣）企業官網後台 CKEditor 編輯器。

【基本格式要求】
- 你必須只回傳一個 JSON 物件，欄位名稱為 html_content（字串）。
- html_content 的值必須是「純 HTML 原始碼」，絕對不要在開頭或結尾加上 ```html 或任何 Markdown 語法格式。
- 禁止輸出 JSON 以外的說明文字。

【允許的 HTML 標籤（僅能使用以下標籤）】
{$allowedTags}

{$sourceRule}
{$styleRules}
{$formatRules}
{$industryRules}

【撰寫規範】
- 嚴格遵循上述排版模式、字級／顏色／粗體規範、產業規範的語氣、架構與強制警語要求。
- 善用標題、段落、粗體、色字、字級、清單與表格，讓內容易讀且符合 CKEditor 編輯需求。
- 文筆精準，符合企業官網與該產業的合規語氣。
TEXT;
    }
}

if (!function_exists('gemini_build_editor_user_prompt')) {
    function gemini_build_editor_user_prompt(
        string $userPrompt,
        string $sourceUrl,
        string $formatMode = 'auto',
    ): string {
        $formatLabel = gemini_format_mode_options()[gemini_normalize_format_mode($formatMode)] ?? '自動混排';
        $formatHint = '【排版格式】：' . $formatLabel . "\n";

        if ($sourceUrl !== '') {
            return "【參考網址】：{$sourceUrl}\n"
                . $formatHint
                . "【寫作任務】：{$userPrompt}\n"
                . "請結合上述參考網址的內容，僅進行改寫或延伸，嚴格遵循排版模式與產業規範，產出 CKEditor 用的 HTML 文章。\n"
                . '禁止捏造參考網址中未提及的關鍵事實或數據。';
        }

        return $formatHint
            . "【寫作任務】：{$userPrompt}\n"
            . "請依寫作任務、排版模式與產業規範產出 CKEditor 用的 HTML 文章。\n"
            . '禁止捏造具體數據、統計或未經允許的承諾。';
    }
}
