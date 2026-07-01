<?php

declare(strict_types=1);

/**
 * 後台子模組表單 bag（$GLOBALS['{module}_form']）共用
 */

if (!function_exists('manage_form_bag_export')) {
    /**
     * 將 form bag 同步至 $GLOBALS 頂層（跳過父層顯示欄，避免覆寫主腳本變數）
     *
     * @param list<string> $skipGlobalKeys
     */
    function manage_form_bag_export(string $bagKey, array $skipGlobalKeys = []): void
    {
        if (!isset($GLOBALS[$bagKey]) || !is_array($GLOBALS[$bagKey])) {
            return;
        }

        $skipFlip = array_fill_keys($skipGlobalKeys, true);
        foreach ($GLOBALS[$bagKey] as $key => $val) {
            if (isset($skipFlip[$key])) {
                continue;
            }
            $GLOBALS[$key] = $val;
        }
    }
}

if (!function_exists('manage_form_bag_init')) {
    /**
     * @param list<string> $skipGlobalKeys
     */
    function manage_form_bag_init(string $bagKey, array $defaults, array $skipGlobalKeys = []): void
    {
        $GLOBALS[$bagKey] = $defaults;
        manage_form_bag_export($bagKey, $skipGlobalKeys);
    }
}

if (!function_exists('manage_form_bag_apply_fields')) {
    /**
     * @param list<string> $skipGlobalKeys
     */
    function manage_form_bag_apply_fields(string $bagKey, array $fields, array $skipGlobalKeys = []): void
    {
        if (!isset($GLOBALS[$bagKey]) || !is_array($GLOBALS[$bagKey])) {
            $GLOBALS[$bagKey] = [];
        }

        foreach ($fields as $key => $val) {
            $GLOBALS[$bagKey][$key] = $val;
        }

        manage_form_bag_export($bagKey, $skipGlobalKeys);
    }
}

if (!function_exists('manage_form_bag_ref')) {
    /** @return array<string,mixed> */
    function manage_form_bag_ref(string $bagKey): array
    {
        if (!isset($GLOBALS[$bagKey]) || !is_array($GLOBALS[$bagKey])) {
            $GLOBALS[$bagKey] = [];
        }

        return $GLOBALS[$bagKey];
    }
}
