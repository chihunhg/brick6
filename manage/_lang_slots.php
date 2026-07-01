<?php

declare(strict_types=1);

/**
 * 後台多語系表單 slot 共用（strName1、strName2…）
 */

if (!function_exists('manage_lang_count')) {
    function manage_lang_count(): int
    {
        global $array_lang;

        return !empty($array_lang) && is_array($array_lang) ? count($array_lang) : 6;
    }
}

if (!function_exists('manage_lang_filter_key')) {
    function manage_lang_filter_key(string $fieldPrefix, int $lang): string
    {
        return $fieldPrefix . $lang;
    }
}

if (!function_exists('manage_empty_lang_slots')) {
    /** @return array<int,string> */
    function manage_empty_lang_slots(?int $langCount = null): array
    {
        $langCount = $langCount ?? manage_lang_count();
        $slots = [];
        for ($i = 1; $i <= $langCount; $i++) {
            $slots[$i] = '';
        }

        return $slots;
    }
}

if (!function_exists('manage_empty_nested_lang_slots')) {
    /**
     * 二維語系 slot（例如答案 A_Name[語系][順序]）
     *
     * @return array<int,array<int,string>>
     */
    function manage_empty_nested_lang_slots(int $innerCount, ?int $langCount = null): array
    {
        $langCount = $langCount ?? manage_lang_count();
        $slots = [];
        for ($lang = 1; $lang <= $langCount; $lang++) {
            $slots[$lang] = [];
            for ($i = 1; $i <= $innerCount; $i++) {
                $slots[$lang][$i] = '';
            }
        }

        return $slots;
    }
}

if (!function_exists('manage_resolve_lang_field_from_filter')) {
    /**
     * @param list<string> $fallbackKeys  額外 fallback 欄位（如單一 strName）
     */
    function manage_resolve_lang_field_from_filter(
        array $filter,
        string $fieldPrefix = 'strName',
        ?int $langCount = null,
        array $fallbackKeys = []
    ): string {
        $langCount = $langCount ?? manage_lang_count();
        for ($i = 1; $i <= $langCount; $i++) {
            $name = trim((string)($filter[manage_lang_filter_key($fieldPrefix, $i)] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
        foreach ($fallbackKeys as $key) {
            $name = trim((string)($filter[$key] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }
}

if (!function_exists('manage_validate_lang_field_from_filter')) {
    /**
     * @param string $emptyMessage  含【標籤】的完整錯誤行（須含 \n）
     */
    function manage_validate_lang_field_from_filter(
        array $filter,
        string $emptyMessage,
        string $fieldPrefix = 'strName',
        ?int $langCount = null,
        array $fallbackKeys = []
    ): string {
        if (manage_resolve_lang_field_from_filter($filter, $fieldPrefix, $langCount, $fallbackKeys) === '') {
            return $emptyMessage;
        }

        return '';
    }
}
