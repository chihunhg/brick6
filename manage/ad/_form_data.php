<?php
declare(strict_types=1);
/**
 * 廣告（dbad）表單資料：預設值與自 DB 載入（供 add.php / update.php 使用）
 */

if (!function_exists('ad_detail_tables')) {
    /** 讀取 ad/_config.php 子表設定 */
    function ad_detail_tables(): array
    {
        return manage_detail_tables();
    }
}

if (!function_exists('ad_lang_is_show_on')) {
    /** 語系 isShow 是否視為開啟 */
    function ad_lang_is_show_on($value): bool
    {
        if (function_exists('class1_lang_is_show_on')) {
            return class1_lang_is_show_on($value);
        }
        $v = strtolower(trim((string)$value));
        return in_array($v, ['y', 'yes', '1', 'true', 'on'], true);
    }
}

if (!function_exists('ad_detail_defaults')) {
    /** @return array<string, mixed> */
    function ad_detail_defaults(): array
    {
        global $array_lang;

        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;

        $defaults = [
            'Update_PKey'  => 0,
            'Sort'         => 0,
            'strLink'      => '',
            'Target'       => '_blank',
            'presentMode'  => 1,
            'Movielink'    => '',
            'Upload'       => 'Yes',
            'langIsShow'   => [],
            'strName'      => [],
            'Subject'      => [],
            'Photo'        => [],
            'PhotoS'       => [],
            'PhotoM'       => [],
        ];

        for ($i = 1; $i <= $langCount; $i++) {
            $defaults['langIsShow'][$i] = '';
            $defaults['strName'][$i]     = '';
            $defaults['Subject'][$i]     = '';
        }

        return $defaults;
    }
}

if (!function_exists('ad_detail_init_defaults')) {
    /** 初始化廣告表單預設變數 */
    function ad_detail_init_defaults(): void
    {
        $GLOBALS['ad_form_vars'] = ad_detail_defaults();
        ad_detail_export_vars();
    }
}

if (!function_exists('ad_detail_export_vars')) {
    /** 將 ad_form_vars 匯出至 $GLOBALS */
    function ad_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['ad_form_vars'] ?? ad_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['ad_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('ad_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function ad_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['ad_form_vars'];

        $present = (int)($row['isShow'] ?? 1);
        $v['Sort']        = (int)($row['Sort'] ?? 0);
        $v['strLink']     = (string)($row['strLink'] ?? '');
        $v['Target']      = (string)($row['Target'] ?? '');
        $v['presentMode'] = ($present === 2) ? 2 : 1;
        $v['Movielink']   = (string)($row['Movielink'] ?? '');
        $v['Upload']      = (string)($row['Upload'] ?? 'Yes');

        $target = trim($v['Target']);
        if ($target !== '_self') {
            $v['Target'] = '_blank';
        }

        ad_detail_export_vars();
    }
}

if (!function_exists('ad_detail_load_lang')) {
    /** 載入廣告語系列 */
    function ad_detail_load_lang(int $pkey): void
    {
        global $array_lang;

        $tables = ad_detail_tables();
        $fk     = (string)($tables['fk'] ?? 'AD_PKey');
        $tableLang = (string)($tables['lang'] ?? 'dbad_lang');
        $v      = &$GLOBALS['ad_form_vars'];
        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;

        for ($i = 1; $i <= $langCount; $i++) {
            $v['langIsShow'][$i] = '';
            $v['strName'][$i]     = '';
            $v['Subject'][$i]     = '';
        }

        if ($tableLang === '' || !function_exists('chkTable') || !chkTable($tableLang)) {
            ad_detail_export_vars();
            return;
        }

        $langData = crud_load_lang_slots_data($tableLang, $fk, $pkey);
        foreach ($langData['isShow'] as $i => $show) {
            $v['langIsShow'][$i] = ad_lang_is_show_on($show) ? 'Y' : '';
        }
        foreach ($langData['strName'] as $i => $name) {
            $v['strName'][$i] = (string)$name;
        }
        foreach ($langData['Subject'] as $i => $subj) {
            $v['Subject'][$i] = (string)$subj;
        }

        ad_detail_export_vars();
    }
}

if (!function_exists('ad_detail_resolve_module_pkey')) {
    /** 解析目前單元 Module_PKey（manNo） */
    function ad_detail_resolve_module_pkey(): int
    {
        $mpk = (int)($GLOBALS['Module_PKey'] ?? 0);
        if ($mpk > 0) {
            return $mpk;
        }
        global $filter_array;
        return safe_int($_GET['manNo'] ?? $filter_array['manNo'] ?? 0);
    }
}

if (!function_exists('ad_detail_load')) {
    /**
     * 自 DB 載入一筆廣告
     *
     * @param bool $forCopy 複製新增：不帶圖檔、Update_PKey 為 0
     */
    function ad_detail_load(int $pkey, ?int $modulePKey = null, bool $forCopy = false): bool
    {
        if ($pkey <= 0) {
            return false;
        }

        if ($modulePKey === null || $modulePKey <= 0) {
            $modulePKey = ad_detail_resolve_module_pkey();
        }

        $tables = ad_detail_tables();
        $master = (string)($tables['master'] ?? 'dbad');
        if (!crud_is_safe_sql_identifier($master)) {
            return false;
        }

        $row = crud_fetch_one("SELECT * FROM {$master} WHERE PKey = :pk LIMIT 1", ['pk' => $pkey]);
        if ($row === null) {
            return false;
        }

        if ($modulePKey > 0) {
            $rowModule = (int)($row['Module_PKey'] ?? 0);
            if ($rowModule !== $modulePKey) {
                return false;
            }
        }

        if (!isset($GLOBALS['ad_form_vars'])) {
            ad_detail_init_defaults();
        }

        $GLOBALS['ad_form_vars']['Update_PKey'] = $forCopy ? 0 : $pkey;
        ad_detail_apply_master($row);
        ad_detail_load_lang($pkey);

        if ($forCopy) {
            $v = &$GLOBALS['ad_form_vars'];
            $v['Photo']  = [];
            $v['PhotoS'] = [];
            $v['PhotoM'] = [];
            ad_detail_export_vars();
        }

        return true;
    }
}
