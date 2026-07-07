<?php
declare(strict_types=1);

/**
 * Gemini 產文：依後台語系（language / $array_lang）決定輸出語言
 */

if (!function_exists('gemini_fetch_active_language_labels')) {
    /**
     * 與 manage/_inc.php 相同：Upload=Yes，依 Sort 排序，索引由 1 起
     *
     * @return array<int, string>
     */
    function gemini_fetch_active_language_labels(): array {
        static $labels = null;
        if ($labels !== null) {
            return $labels;
        }

        $labels = [];
        try {
            $pdo = null;
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                $pdo = $GLOBALS['pdo'];
            } else {
                require_once __DIR__ . '/Conn.php';
                if (function_exists('sql_conn')) {
                    $pdo = sql_conn();
                }
            }
            if (!$pdo instanceof PDO) {
                return $labels;
            }

            $stmt = $pdo->query(
                "SELECT strName FROM language WHERE Upload = 'Yes' ORDER BY Sort"
            );
            if ($stmt === false) {
                return $labels;
            }

            $index = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $index++;
                $labels[$index] = trim((string)($row['strName'] ?? ''));
            }
        } catch (Throwable) {
            return $labels;
        }

        return $labels;
    }
}

if (!function_exists('gemini_normalize_output_locale')) {
    /** 後台語系標籤 → en / zh-tw / zh-cn / ja */
    function gemini_normalize_output_locale(string $langLabel, int $langSlot = 0): string {
        $label = mb_strtolower(trim($langLabel), 'UTF-8');

        if ($label !== '') {
            if (str_contains($label, '英') || $label === 'en' || str_contains($label, 'english')) {
                return 'en';
            }
            if (str_contains($label, '簡') || str_contains($label, '简') || str_contains($label, '简体')) {
                return 'zh-cn';
            }
            if (str_contains($label, '日') || $label === 'ja' || str_contains($label, 'japanese')) {
                return 'ja';
            }
            if (str_contains($label, '繁') || str_contains($label, '台') || str_contains($label, '中文')) {
                return 'zh-tw';
            }
        }

        return match ($langSlot) {
            2 => 'en',
            3 => 'zh-cn',
            4 => 'ja',
            default => 'zh-tw',
        };
    }
}

if (!function_exists('gemini_output_language_display_name')) {
    /** locale 對應的顯示語言名稱 */
    function gemini_output_language_display_name(string $locale, string $langLabel = ''): string {
        return match ($locale) {
            'en' => 'English',
            'zh-cn' => '简体中文',
            'ja' => '日本語',
            default => '繁體中文（台灣）',
        };
    }
}

if (!function_exists('gemini_output_language_rules')) {
    /** 產文 prompt 的輸出語言規則區塊 */
    function gemini_output_language_rules(string $locale, string $langLabel = ''): string {
        $display = gemini_output_language_display_name($locale, $langLabel);
        $adminLabel = $langLabel !== '' ? $langLabel : $display;

        return "【輸出語言（最高優先）】\n"
            . "- 後台語系：{$adminLabel}\n"
            . "- html_content、title、description、keywords 等所有可見文字，必須「全部」使用「{$display}」撰寫。\n"
            . "- 產業 Few-Shot 範本若為繁體中文，僅供結構、合規與段落節奏參考，實際輸出文字仍須改寫為「{$display}」。\n"
            . "- 禁止混用其他語系或未要求的語言。\n";
    }
}

if (!function_exists('gemini_resolve_output_language')) {
    /**
     * @param array<string, mixed> $input
     * @return array{locale: string, label: string, display: string, slot: int}
     */
    function gemini_resolve_output_language(array $input): array {
        $langSlot = max(0, (int)($input['lang_slot'] ?? $input['langSlot'] ?? 0));
        $langLabel = trim((string)($input['lang_label'] ?? $input['langLabel'] ?? ''));

        if ($langLabel === '' && $langSlot > 0) {
            $labels = gemini_fetch_active_language_labels();
            if (isset($labels[$langSlot]) && $labels[$langSlot] !== '') {
                $langLabel = $labels[$langSlot];
            }
        }

        if ($langLabel === '' && $langSlot > 0) {
            $langLabel = match ($langSlot) {
                2 => '英文',
                3 => '簡中',
                4 => '日文',
                default => '中文',
            };
        }

        $locale = gemini_normalize_output_locale($langLabel, $langSlot);
        $display = gemini_output_language_display_name($locale, $langLabel);

        return [
            'locale' => $locale,
            'label' => $langLabel !== '' ? $langLabel : $display,
            'display' => $display,
            'slot' => $langSlot,
        ];
    }
}

if (!function_exists('gemini_language_hint_for_user_prompt')) {
    /** @param array{locale: string, label: string, display: string, slot: int} $langContext */
    function gemini_language_hint_for_user_prompt(array $langContext): string {
        $slotHint = $langContext['slot'] > 0
            ? '（語系索引 ' . $langContext['slot'] . '）'
            : '';

        return '【輸出語言】：' . $langContext['label'] . $slotHint . "\n"
            . '請將所有產出內容以「' . $langContext['display'] . '」撰寫。' . "\n\n";
    }
}
