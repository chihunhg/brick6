<?php
declare(strict_types=1);

/**
 * Gemini 編輯器 HTML 產生：清理、驗證與產業規範
 */

if (!function_exists('gemini_sanitize_utf8_text')) {
    /**
     * 確保字串為有效 UTF-8（Gemini SDK json_encode 不接受畸形位元組）
     */
    function gemini_sanitize_utf8_text(string $text): string {
        if ($text === '') {
            return '';
        }
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        if (is_string($clean) && mb_check_encoding($clean, 'UTF-8')) {
            return $clean;
        }

        if (function_exists('iconv')) {
            $iconv = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
            if (is_string($iconv)) {
                return $iconv;
            }
        }

        $stripped = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        return is_string($stripped) ? $stripped : '';
    }
}

if (!function_exists('gemini_sanitize_utf8_array')) {
    /** @param array<string, mixed> $data @return array<string, mixed> */
    function gemini_sanitize_utf8_array(array $data): array {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = gemini_sanitize_utf8_text($value);
            } elseif (is_array($value)) {
                $data[$key] = gemini_sanitize_utf8_array($value);
            }
        }

        return $data;
    }
}

if (!function_exists('gemini_editor_parse_request')) {
    /**
     * 解析 POST 表單或 JSON body（application/json）
     *
     * @return array<string, mixed>
     */
    function gemini_editor_parse_request(): array {
        $input = $_POST;
        if ($input === []) {
            $raw = file_get_contents('php://input');
            if (!is_string($raw) || trim($raw) === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            $input = is_array($decoded) ? $decoded : [];
        }

        return gemini_sanitize_utf8_array($input);
    }
}

if (!function_exists('gemini_normalize_industry')) {
    /** 正規化產業代碼（中英文別名對應至標準值） */
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
        return '<h1><h2><h3><p><strong><ul><li><table><tr><td><a>';
    }
}

if (!function_exists('gemini_editor_allowed_html_tag_names')) {
    /** 白名單 HTML 標籤名稱（逗號分隔，供 DOM 清理與規則共用） */
    function gemini_editor_allowed_html_tag_names(): string {
        return 'h1, h2, h3, p, strong, ul, li, table, tr, td, a';
    }
}

if (!function_exists('gemini_editor_forbidden_html_tag_names')) {
    /** AI 產文絕對禁止的 HTML 標籤（資安與破版防護） */
    function gemini_editor_forbidden_html_tag_names(): string {
        return 'html, body, script, style, iframe';
    }
}

if (!function_exists('gemini_editor_html_structure_rules')) {
    /** System Instruction：標籤白名單、黑名單與防破版要求 */
    function gemini_editor_html_structure_rules(): string {
        $allowedList = implode(', ', array_map(
            static fn(string $tag): string => '<' . trim($tag) . '>',
            explode(',', gemini_editor_allowed_html_tag_names())
        ));
        $forbiddenList = implode(', ', array_map(
            static fn(string $tag): string => '<' . trim($tag) . '>',
            explode(',', gemini_editor_forbidden_html_tag_names())
        ));

        return "【HTML 標籤白名單（僅能使用以下標籤）】\n"
            . "{$allowedList}\n"
            . "（以上標籤必須成對閉合，例如 <h2>標題</h2>）\n\n"
            . "【HTML 標籤黑名單（絕對禁止）】\n"
            . "{$forbiddenList}\n"
            . "禁止輸出上述標籤，以及任何未列於白名單的標籤（含 div、span、br、em、b、ol、object、embed、form、input 等）。\n\n"
            . "【防破版與閉合要求】\n"
            . "- 所有 HTML 標籤必須成對且正確閉合，禁止未閉合或交叉嵌套錯誤。\n"
            . "- 禁止輸出 <!DOCTYPE>、<html>、<head>、<body> 等完整文件結構；html_content 僅能是 CKEditor 編輯區內的片段 HTML。\n"
            . "- 表格結構：<table> 內僅能包含 <tr>，<tr> 內僅能包含 <td>；表頭文字請放在第一列 <td> 並以 <strong> 標示。\n"
            . "- 清單結構：<ul> 內僅能包含 <li>，每個 <li> 必須正確閉合。\n"
            . "- 連結 <a> 僅允許 href 屬性，且僅限 http、https、mailto 或站內相對路徑；禁止 target、onclick、style 等屬性。\n";
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
    /** AI 產文：粗體強調規範（限白名單標籤） */
    function gemini_editor_style_rules(): string {
        return "【文字強調】\n"
            . "- 重點請使用 <strong>（勿用 <b>、<em>、<i> 或 <span>）。\n"
            . "- 禁止 inline style、class、id 或任何非 href 的 HTML 屬性。\n";
    }
}

if (!function_exists('gemini_editor_style_hint_line')) {
    /** 產文提示：適度使用 strong 標示關鍵字句 */
    function gemini_editor_style_hint_line(): string {
        return "- 適度使用 <strong> 標示關鍵字句。\n";
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
    /** 正規化排版模式（auto / prose / table / list） */
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
    /** 依排版模式回傳 System Instruction 段落 */
    function gemini_editor_format_rules(string $formatMode): string {
        $styleHint = gemini_editor_style_hint_line();

        return match (gemini_normalize_format_mode($formatMode)) {
            'prose' => "【排版模式：圖文段落】\n"
                . "- 以 <h1>、<h2>、<h3> 分段，內文用 <p>，重點以 <strong> 標示。\n"
                . $styleHint
                . "- 可搭配 <ul>/<li> 補充說明，非必要不使用表格。\n",

            'table' => "【排版模式：表格為主】\n"
                . "- 至少包含一個完整 <table>（僅含 <tr> 與 <td>）。\n"
                . "- 表頭列請放在第一列 <tr>，各欄標題以 <td><strong>標題</strong></td> 呈現。\n"
                . "- 規格、方案比較、流程步驟、數據整理優先放入表格；表格外以 <h2>/<p> 簡短引言。\n"
                . "- 表格外段落可用 <strong> 強調重點；表格內請勿加任何 HTML 屬性。\n"
                . "- 系統會自動為表格加上 RWD 捲動外層與框線、隔行底色樣式，請勿自行加 class 或 style。\n",

            'list' => "【排版模式：條列重點】\n"
                . "- 以 <ul> 與 <li> 為主體呈現重點，每項可用 <strong> 標示關鍵字。\n"
                . $styleHint
                . "- 搭配 <h2> 小標與 <p> 摘要，非必要不使用表格。\n",

            default => "【排版模式：自動混排】\n"
                . "- 依內容性質混合標題、段落、清單與表格（僅用白名單標籤）。\n"
                . "- 有規格／比較／數據時用 <table><tr><td>；敘述用 <p>；重點用 <ul>/<li>；關鍵字用 <strong>。\n"
                . $styleHint,
        };
    }
}

if (!function_exists('gemini_industry_template_instruction')) {
    /** 取得產業 Few-Shot 範本（IndustryTemplates 為唯一來源） */
    function gemini_industry_template_instruction(string $industry): string {
        require_once __DIR__ . '/IndustryTemplates.php';

        return IndustryTemplates::getInstruction(gemini_normalize_industry($industry));
    }
}

if (!function_exists('gemini_editor_industry_template_priority_block')) {
    /**
     * 產業範本優先區塊：供 system instruction 置頂，要求模型優先依範本產文
     */
    function gemini_editor_industry_template_priority_block(string $industry): string {
        $template = gemini_industry_template_instruction($industry);

        return "【最高優先：產業範本（產文必須優先依此執行）】\n"
            . "以下範本中的【角色任務】、【寫作規範】與【完美範本參考】為最高準則。\n"
            . "請優先仿效範本的語氣、段落結構、HTML 架構與合規警語；其餘規則僅作補充，不得偏離範本風格。\n\n"
            . $template;
    }
}

if (!function_exists('gemini_industry_rules')) {
    /** 取得產業規範指令（IndustryTemplates） */
    function gemini_industry_rules(string $industry): string {
        return gemini_industry_template_instruction($industry);
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

if (!function_exists('gemini_fetch_source_url_html')) {
    /**
     * 以 cURL（優先）或 file_get_contents 抓取參考網址 HTML
     */
    function gemini_fetch_source_url_html(string $url, int $timeoutSec = 20): string {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
        $timeoutSec = max(1, min($timeoutSec, 30));

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => $timeoutSec,
                CURLOPT_TIMEOUT => $timeoutSec,
                CURLOPT_USERAGENT => $userAgent,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8'],
            ]);
            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (is_string($body) && $body !== '' && $httpCode >= 200 && $httpCode < 400) {
                return $body;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeoutSec,
                'header' => "User-Agent: {$userAgent}\r\n"
                    . "Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8\r\n",
                'follow_location' => 1,
                'max_redirects' => 5,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);

        return is_string($body) ? $body : '';
    }
}

if (!function_exists('gemini_normalize_fetched_html_charset')) {
    /** 將抓取 HTML 轉為 UTF-8（依 meta 或偵測編碼） */
    function gemini_normalize_fetched_html_charset(string $html): string {
        if ($html === '') {
            return '';
        }

        if (preg_match('/<meta[^>]+charset=["\']?\s*([^"\'>\s]+)/i', $html, $match)) {
            $charset = trim((string)$match[1]);
            if ($charset !== '' && strtoupper($charset) !== 'UTF-8') {
                $converted = @mb_convert_encoding($html, 'UTF-8', $charset);
                if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                    return $converted;
                }
            }
        }

        if (mb_check_encoding($html, 'UTF-8')) {
            return $html;
        }

        $detected = mb_detect_encoding(
            $html,
            ['UTF-8', 'BIG5', 'CP950', 'GB2312', 'GBK', 'EUC-JP', 'ISO-8859-1', 'Windows-1252'],
            true
        );
        if (is_string($detected) && $detected !== '' && strtoupper($detected) !== 'UTF-8') {
            $converted = @mb_convert_encoding($html, 'UTF-8', $detected);
            if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return gemini_sanitize_utf8_text($html);
    }
}

if (!function_exists('gemini_extract_plain_text_from_html')) {
    /**
     * 自 HTML 萃取出純文字（優先 DOMDocument，失敗時 fallback strip_tags）
     */
    function gemini_extract_plain_text_from_html(string $html): string {
        $html = gemini_normalize_fetched_html_charset(trim($html));
        if ($html === '') {
            return '';
        }

        if (class_exists(DOMDocument::class)) {
            $document = new DOMDocument('1.0', 'UTF-8');
            $previousLibxmlState = libxml_use_internal_errors(true);
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8">' . $html,
                LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET
            );
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlState);

            if ($loaded) {
                $xpath = new DOMXPath($document);
                foreach ($xpath->query('//script|//style|//noscript') as $node) {
                    $node->parentNode?->removeChild($node);
                }

                $text = html_entity_decode((string)$document->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
                $text = preg_replace('/\R{3,}/', "\n\n", $text) ?? $text;

                return trim($text);
            }
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}

if (!function_exists('gemini_fetch_source_page_text')) {
    /**
     * 抓取參考網址並萃取出純文字內容
     *
     * @throws RuntimeException 抓取或萃取失敗時
     */
    function gemini_fetch_source_page_text(string $sourceUrl, int $maxChars = 80000): string {
        $html = gemini_fetch_source_url_html($sourceUrl);
        if ($html === '') {
            throw new RuntimeException('無法抓取參考網址內容，請確認網址可公開存取');
        }

        $text = gemini_extract_plain_text_from_html($html);
        if ($text === '') {
            throw new RuntimeException('參考網址未萃取出有效文字內容');
        }

        if (mb_strlen($text, 'UTF-8') > $maxChars) {
            $text = mb_substr($text, 0, $maxChars, 'UTF-8') . '…（內文已截斷）';
        }

        return gemini_sanitize_utf8_text($text);
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
    /** 驗證色碼（#rgb 展開為 #rrggbb）；無效回傳空字串 */
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
    /** 驗證 px 字級（10–72）；無效回傳空字串 */
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
    /** 過濾 span 允許的 inline style（color、font-size） */
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

if (!function_exists('gemini_sanitize_anchor_href')) {
    /** 清理連結 href（阻擋 javascript/data，僅允許 http(s)/mailto/站內路徑） */
    function gemini_sanitize_anchor_href(string $href): string {
        $href = trim(htmlspecialchars_decode($href, ENT_QUOTES));
        if ($href === '' || $href === '#') {
            return '';
        }
        if (preg_match('/^(javascript|data|vbscript):/i', $href)) {
            return '';
        }
        if (preg_match('/^mailto:/i', $href)) {
            return $href;
        }
        if (filter_var($href, FILTER_VALIDATE_URL) !== false) {
            $scheme = strtolower((string)parse_url($href, PHP_URL_SCHEME));
            if (in_array($scheme, ['http', 'https'], true)) {
                return $href;
            }

            return '';
        }
        if (preg_match('#^(/|\./|\.\./|[a-zA-Z0-9_\-./?&=%#]+)$#', $href)) {
            return $href;
        }

        return '';
    }
}

if (!function_exists('gemini_dom_unwrap_element')) {
    /** 移除元素但保留子節點至父層 */
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
    /** 替換 DOM 元素標籤並保留屬性與子節點 */
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
    /** 以 DOM 清理編輯器 HTML（白名單標籤、移除危險屬性） */
    function gemini_sanitize_editor_html_dom(string $html): string {
        if (!class_exists(DOMDocument::class)) {
            return '';
        }

        $allowedTags = array_flip(array_map(
            static fn(string $tag): string => strtolower(trim($tag)),
            explode(',', str_replace(' ', '', gemini_editor_allowed_html_tag_names()))
        ));

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
        foreach ($xpath->query('.//script|.//style|.//iframe|.//noscript|.//object|.//embed') as $unsafeNode) {
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
            } elseif ($tag === 'i' || $tag === 'em') {
                $node = gemini_dom_replace_element_tag($node, 'strong', $document);
                $tag = 'strong';
            } elseif (in_array($tag, ['h4', 'h5', 'h6'], true)) {
                $node = gemini_dom_replace_element_tag($node, 'h3', $document);
                $tag = 'h3';
            } elseif ($tag === 'th') {
                $node = gemini_dom_replace_element_tag($node, 'td', $document);
                $tag = 'td';
            }

            if (!isset($allowedTags[$tag])) {
                gemini_dom_unwrap_element($node);
                continue;
            }

            $hrefValue = $tag === 'a'
                ? gemini_sanitize_anchor_href((string)$node->getAttribute('href'))
                : '';

            $attributeNames = [];
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attribute) {
                    $attributeNames[] = $attribute->nodeName;
                }
            }
            foreach ($attributeNames as $attributeName) {
                $node->removeAttribute($attributeName);
            }

            if ($tag === 'a') {
                if ($hrefValue !== '') {
                    $node->setAttribute('href', $hrefValue);
                } else {
                    gemini_dom_unwrap_element($node);
                }
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
     * 僅保留白名單 HTML 標籤，移除黑名單與危險屬性，並透過 DOM 修正結構
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
    /** 組裝 Gemini 編輯器 System Instruction（語言、產業、排版、HTML 規則） */
    function gemini_editor_system_instruction(
        string $industry = 'general',
        string $sourceUrl = '',
        string $formatMode = 'auto',
        string $outputLocale = 'zh-tw',
        string $langLabel = '',
    ): string {
        require_once __DIR__ . '/gemini_lang_helpers.php';

        $languageRules = gemini_output_language_rules($outputLocale, $langLabel);
        $industryTemplateBlock = gemini_editor_industry_template_priority_block($industry);
        $formatRules = gemini_editor_format_rules($formatMode);
        $styleRules = gemini_editor_style_rules();
        $structureRules = gemini_editor_html_structure_rules();
        $sourceRule = $sourceUrl !== ''
            ? "【資料來源限制】\n你被嚴格限制只能依據使用者提供的【參考網頁完整內文】（來源：{$sourceUrl}）來改寫或延伸。\n絕對禁止捏造該內文未提及的關鍵事實、數據、統計、客戶案例、療效、報酬率或承諾。\n若參考內文資訊不足，僅能保守改寫已知內容，不可自行補充未出現的細節。\n"
            : "【資料來源限制】\n使用者未提供參考網址時，請僅依寫作任務與產業規範產文，不得捏造具體數據、統計、客戶案例或法規未允許的承諾。\n";

        return <<<TEXT
你是一位高階網頁內容與 SEO 專家，服務企業官網後台 CKEditor 編輯器。

{$languageRules}
{$industryTemplateBlock}

【基本格式要求】
- 你必須只回傳一個 JSON 物件，欄位名稱為 html_content（字串）。
- html_content 的值必須是「純 HTML 原始碼」，絕對不要在開頭或結尾加上 ```html 或任何 Markdown 語法格式。
- 禁止輸出 JSON 以外的說明文字。

{$structureRules}
{$sourceRule}
{$styleRules}
{$formatRules}

【撰寫規範】
- 產文時必須優先遵循「輸出語言」與「最高優先：產業範本」，再搭配 HTML 白名單、排版模式與資料來源限制。
- 輸出結構應接近【完美範本參考】的標題層級、段落節奏與合規警語位置。
- 文筆精準，符合企業官網與該產業的合規語氣。
TEXT;
    }
}

if (!function_exists('gemini_build_editor_user_prompt')) {
    /**
     * 組裝編輯器 User Prompt（語言、產業、參考網址、排版、寫作任務）
     *
     * @param array<string, mixed> $langContext
     */
    function gemini_build_editor_user_prompt(
        string $userPrompt,
        string $sourceUrl,
        string $formatMode = 'auto',
        string $sourcePageText = '',
        string $industry = 'general',
        array $langContext = [],
    ): string {
        require_once __DIR__ . '/gemini_lang_helpers.php';
        if ($langContext === []) {
            $langContext = gemini_resolve_output_language([]);
        }

        $formatLabel = gemini_format_mode_options()[gemini_normalize_format_mode($formatMode)] ?? '自動混排';
        $formatHint = '【排版格式】：' . $formatLabel . "\n";
        $industryLabel = gemini_industry_options()[gemini_normalize_industry($industry)] ?? '一般';
        $languageHint = gemini_language_hint_for_user_prompt($langContext);
        $templateHint = "【產業範本優先】：{$industryLabel}\n"
            . "請優先依 system instruction 中「最高優先：產業範本」的角色、語氣、HTML 結構與合規要求產文，"
            . "並仿效【完美範本參考】的段落節奏與標題層級。\n";

        if ($sourceUrl !== '') {
            $sourceBlock = $sourcePageText !== ''
                ? "【參考網址】：{$sourceUrl}\n【參考網頁完整內文】：{$sourcePageText}\n"
                : "【參考網址】：{$sourceUrl}\n";

            return $languageHint
                . $templateHint
                . $sourceBlock
                . $formatHint
                . "【寫作任務】：{$userPrompt}\n"
                . "請結合上述【參考網頁完整內文】，優先依產業範本改寫或延伸，產出 CKEditor 用的 HTML 文章。\n"
                . '禁止捏造參考內文中未提及的關鍵事實或數據。';
        }

        return $languageHint
            . $templateHint
            . $formatHint
            . "【寫作任務】：{$userPrompt}\n"
            . "請依產業範本、排版模式與寫作任務產出 CKEditor 用的 HTML 文章。\n"
            . '禁止捏造具體數據、統計或未經允許的承諾。';
    }
}
