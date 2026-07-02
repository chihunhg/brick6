<?php
declare(strict_types=1);

/**
 * investor 表單資料：預設值與自 DB 載入（供 add.php / update.php 使用）
 */

if (!function_exists('class1_detail_tables')) {
    function class1_detail_tables(): array {
        return manage_detail_tables();
    }
}

if (!function_exists('investor_lang_count')) {
    function investor_lang_count(): int {
        global $array_lang;
        return !empty($array_lang) && is_array($array_lang) ? count($array_lang) : 6;
    }
}

if (!function_exists('investor_show_type_label')) {
    /** 列表顯示方式標籤 HTML */
    function investor_show_type_label(int $showType): string {
        switch ($showType) {
            case 1:
                return '<span class="typeColor--1">連結</span>';
            case 2:
                return '<span class="typeColor--2">內容</span>';
            case 3:
                return '<span class="typeColor--3">檔案</span>';
            default:
                return '<span class="typeColor--2">內容</span>';
        }
    }
}

if (!function_exists('investor_file_slot_for_lang')) {
    /** 語系對應檔案上傳欄（Sort 8/9/10） */
    function investor_file_slot_for_lang(int $langIndex): int {
        $cfg = function_exists('manage_detail_tables') ? manage_detail_tables() : [];
        $map = (array)($cfg['file_lang_slots'] ?? [1 => 8, 2 => 9, 3 => 10]);
        return (int)($map[$langIndex] ?? (7 + $langIndex));
    }
}

if (!function_exists('investor_resolve_show_year')) {
    function investor_resolve_show_year(): void {
        $v = &$GLOBALS['class1_form_vars'];
        $class2 = (int)($v['Class2'] ?? 0);
        $v['show_year'] = 0;
        if ($class2 > 0
            && function_exists('crud_fetch_one')
            && function_exists('crud_table_has_column')
            && crud_table_has_column('dbclass2', 'show_year')) {
            $row = crud_fetch_one('SELECT show_year FROM dbclass2 WHERE PKey = :pk LIMIT 1', ['pk' => $class2]);
            $v['show_year'] = (int)($row['show_year'] ?? 0);
        }
        class1_detail_export_vars();
    }
}

if (!function_exists('class1_detail_init_defaults')) {
    function class1_detail_init_defaults(): void {
        global $array_lang;

        $langCount = investor_lang_count();

        $GLOBALS['class1_form_vars'] = [
            'Update_PKey' => 0,
            'Class1_PKey' => 0,
            'Sort'        => 0,
            'Upload'      => 'Yes',
            'Home'        => 'No',
            'show_type'   => 2,
            'year'        => 0,
            'show_year'   => 0,
            'Class1'      => 0,
            'Class2'      => 0,
            'Class3'      => 0,
            'dtUDate'     => '',
            'UserID'      => '',
            'isShow'      => [],
            'strName'     => [],
            'Interview'   => [],
            'Movielink'   => [],
            'strURL'      => [],
            'strLink'     => [],
            'intLink'     => [],
            'Contents'    => [],
            'Photo'       => [],
            'PhotoS'      => [],
            'PhotoM'      => [],
            'Ext'         => [],
        ];

        for ($i = 1; $i <= $langCount; $i++) {
            $GLOBALS['class1_form_vars']['isShow'][$i]    = '';
            $GLOBALS['class1_form_vars']['strName'][$i]   = '';
            $GLOBALS['class1_form_vars']['Interview'][$i] = '';
            $GLOBALS['class1_form_vars']['Movielink'][$i] = '';
            $GLOBALS['class1_form_vars']['strURL'][$i]    = '';
            $GLOBALS['class1_form_vars']['strLink'][$i]   = '';
            $GLOBALS['class1_form_vars']['intLink'][$i]   = 2;
        }
        for ($b = 1; $b <= 6; $b++) {
            $GLOBALS['class1_form_vars']['Contents'][$b] = [];
            for ($i = 1; $i <= $langCount; $i++) {
                $GLOBALS['class1_form_vars']['Contents'][$b][$i] = '';
            }
        }

        class1_detail_export_vars();
    }
}

if (!function_exists('class1_lang_is_show_on')) {
    function class1_lang_is_show_on($value): bool {
        $v = strtolower(trim((string)$value));
        return in_array($v, ['y', 'yes', '1', 'true', 'on'], true);
    }
}

if (!function_exists('class1_detail_export_vars')) {
    function class1_detail_export_vars(): void {
        foreach ($GLOBALS['class1_form_vars'] as $key => $val) {
            $GLOBALS[$key] = $val;
        }
    }
}

if (!function_exists('class1_detail_apply_master')) {
    /** @param array<string,mixed> $row */
    function class1_detail_apply_master(array $row): void {
        $v = &$GLOBALS['class1_form_vars'];
        $tables = class1_detail_tables();
        $master = (string)($tables['master'] ?? 'investor');

        $v['Sort']   = (int)($row['Sort'] ?? 0);
        $v['Upload'] = (string)($row['Upload'] ?? 'Yes');
        $v['Home']   = (string)($row['Home'] ?? 'No');
        $v['dtUDate'] = (string)($row['dtUDate'] ?? '');
        $v['UserID']  = (string)($row['UserID'] ?? '');

        if (isset($row['strName'])) {
            $v['strName'][1] = (string)$row['strName'];
        }
        if (array_key_exists('Class1_PKey', $row)) {
            $v['Class1'] = (int)$row['Class1_PKey'];
        }
        if (array_key_exists('Class2_PKey', $row)) {
            $v['Class2'] = (int)$row['Class2_PKey'];
        }
        if (array_key_exists('Class3_PKey', $row)) {
            $v['Class3'] = (int)$row['Class3_PKey'];
        }
        if (crud_table_has_column($master, 'show_type')) {
            $v['show_type'] = (int)($row['show_type'] ?? 2);
            if ($v['show_type'] < 1 || $v['show_type'] > 3) {
                $v['show_type'] = 2;
            }
        }
        if (crud_table_has_column($master, 'year')) {
            $v['year'] = (int)($row['year'] ?? 0);
        }

        investor_resolve_show_year();
        class1_detail_export_vars();
    }
}

if (!function_exists('class1_detail_load_children')) {
    function class1_detail_load_children(int $pkey): void {
        global $array_lang;

        $tables = class1_detail_tables();
        $fk     = $tables['fk'];
        $v      = &$GLOBALS['class1_form_vars'];
        $langCount = investor_lang_count();

        for ($i = 1; $i <= $langCount; $i++) {
            $v['isShow'][$i] = '';
        }

        if (($tables['lang'] ?? '') !== '' && function_exists('chkTable') && chkTable($tables['lang'])) {
            $langData = crud_load_lang_slots_data($tables['lang'], $fk, $pkey);
            foreach ($langData['isShow'] as $i => $show) {
                $v['isShow'][$i] = class1_lang_is_show_on($show) ? 'Y' : '';
            }
            foreach ($langData['strName'] as $i => $name) {
                $v['strName'][$i] = (string)$name;
            }
            foreach ($langData['Subject'] as $i => $subj) {
                $v['Interview'][$i] = (string)$subj;
            }
            foreach ($langData['Movielink'] ?? [] as $i => $mov) {
                $v['Movielink'][$i] = (string)$mov;
            }
            foreach ($langData['intLink'] ?? [] as $i => $linkType) {
                $linkType = (int)$linkType;
                $v['intLink'][$i] = $linkType > 0 ? $linkType : 2;
            }
            foreach ($langData['strURL'] ?? [] as $i => $url) {
                $v['strURL'][$i] = (string)$url;
            }
            foreach ($langData['strLink'] ?? [] as $i => $link) {
                $v['strLink'][$i] = (string)$link;
            }
            // 檔案模式自訂路徑：strLink 空白時才以 strURL 補（連結模式 strURL 為外連網址，不可覆寫 strLink）
            if ((int)($v['show_type'] ?? 2) === 3) {
                for ($i = 1; $i <= $langCount; $i++) {
                    if (trim((string)($v['strLink'][$i] ?? '')) === '' && trim((string)($v['strURL'][$i] ?? '')) !== '') {
                        $v['strLink'][$i] = (string)$v['strURL'][$i];
                    }
                }
            }
        }

        if (($tables['msg'] ?? '') !== '' && function_exists('chkTable') && chkTable($tables['msg'])) {
            for ($n = 1; $n <= $langCount; $n++) {
                $sql = 'SELECT Sort, Contents FROM ' . $tables['msg']
                    . ' WHERE ' . $fk . ' = :fk AND intLang = :lang ORDER BY Sort';
                $rows = crud_fetch_all($sql, ['fk' => $pkey, 'lang' => $n]);
                foreach ($rows as $r) {
                    $sort = (int)($r['Sort'] ?? 0);
                    if ($sort >= 1 && $sort <= 6) {
                        $v['Contents'][$sort][$n] = (string)($r['Contents'] ?? '');
                    }
                }
            }
        }

        if (($tables['img'] ?? '') !== '' && function_exists('chkTable') && chkTable($tables['img'])) {
            $imgData = crud_load_img_slots_data($tables['img'], $fk, $pkey);
            $v['Photo']  = $imgData['Photo'];
            $v['PhotoS'] = $imgData['PhotoS'];
            $v['PhotoM'] = $imgData['PhotoM'] ?? [];
            $sql = 'SELECT PKey, Sort, Forder, Photo1, intType FROM ' . $tables['img']
                . ' WHERE ' . $fk . ' = :fk AND Photo1 <> \'\' ORDER BY Sort';
            $rows = crud_fetch_all($sql, ['fk' => $pkey]);
            foreach ($rows as $r) {
                $sort = (int)($r['Sort'] ?? 0);
                if ($sort <= 0) {
                    continue;
                }
                $forder = trim((string)($r['Forder'] ?? ''));
                $photo1 = trim((string)($r['Photo1'] ?? ''));
                if ($photo1 === '') {
                    continue;
                }
                $path = ($forder !== '' ? $forder . '/' : '') . $photo1;
                $v['Ext'][$sort] = function_exists('manage_file_ext_from_path')
                    ? manage_file_ext_from_path($path)
                    : strtolower(pathinfo($path, PATHINFO_EXTENSION));
            }
        }

        class1_detail_export_vars();
    }
}

if (!function_exists('class1_detail_resolve_module_pkey')) {
    function class1_detail_resolve_module_pkey(): int {
        $mpk = (int)($GLOBALS['Module_PKey'] ?? 0);
        if ($mpk > 0) {
            return $mpk;
        }
        global $filter_array;
        return safe_int($_GET['manNo'] ?? $filter_array['manNo'] ?? 0);
    }
}

if (!function_exists('class1_recordset_row_to_array')) {
    function class1_recordset_row_to_array(recordset $rs): array {
        $cols = [
            'PKey', 'Module_PKey', 'Class1_PKey', 'Class2_PKey', 'Class3_PKey',
            'Sort', 'strName', 'Home', 'Upload', 'show_type', 'year',
            'UserID', 'dtUDate', 'dtDate',
        ];
        $row = [];
        foreach ($cols as $col) {
            $val = $rs->field($col);
            if ($val !== null) {
                $row[$col] = $val;
            }
        }
        return $row;
    }
}

if (!function_exists('class1_detail_load')) {
    function class1_detail_load(int $pkey, ?int $modulePKey = null): bool {
        if ($pkey <= 0) {
            return false;
        }
        if ($modulePKey === null || $modulePKey <= 0) {
            $modulePKey = class1_detail_resolve_module_pkey();
        }

        $tables = class1_detail_tables();
        $master = str_replace('`', '', (string)($tables['master'] ?? ''));
        if ($master === '' || !crud_is_safe_sql_identifier($master)) {
            return false;
        }

        $moduleCol = str_replace('`', '', (string)($tables['module_pk_col'] ?? 'Module_PKey'));
        if (!crud_is_safe_sql_identifier($moduleCol)) {
            $moduleCol = 'Module_PKey';
        }

        $row = crud_fetch_one('SELECT * FROM `' . $master . '` WHERE `PKey` = :PKey LIMIT 1', ['PKey' => $pkey]);
        if ($row === null || (int)($row['PKey'] ?? 0) <= 0) {
            return false;
        }

        if ($modulePKey > 0) {
            $rowModule = (int)($row[$moduleCol] ?? $row['Module_PKey'] ?? 0);
            $manNoFromUrl = safe_int($_GET['manNo'] ?? ($GLOBALS['filter_array']['manNo'] ?? 0));
            $moduleOk = ($rowModule === $modulePKey)
                || ($manNoFromUrl > 0 && $rowModule === $manNoFromUrl);
            if (!$moduleOk) {
                return false;
            }
        }

        if (!isset($GLOBALS['class1_form_vars'])) {
            class1_detail_init_defaults();
        }
        $GLOBALS['class1_form_vars']['Update_PKey'] = $pkey;
        class1_detail_apply_master($row);
        class1_detail_load_children($pkey);
        return true;
    }
}

if (!function_exists('investor_validate_by_show_type')) {
    function investor_validate_by_show_type(array $filter, array $file_array, int $formPKey, string $tableImg, string $fk): string {
        global $array_lang;

        $msg       = '';
        $langCount = investor_lang_count();
        $showType  = (int)($filter['show_type'] ?? 2);

        for ($i = 1; $i <= $langCount; $i++) {
            if (($filter['Show' . $i] ?? '') !== 'Y') {
                continue;
            }
            $label = (string)($array_lang[$i] ?? ('語系' . $i));

            if ($showType === 1) {
                if (trim((string)($filter['strURL' . $i] ?? '')) === '') {
                    $msg .= '【' . $label . "】連結空白\n";
                }
                continue;
            }

            if ($showType === 3) {
                $intLink = (int)($filter['intFileLink' . $i] ?? $filter['intLink' . $i] ?? 2);
                if ($intLink === 1) {
                    $path = trim((string)($filter['strLink' . $i] ?? ''));
                    if ($path === '') {
                        $path = trim((string)($filter['strURL' . $i] ?? ''));
                    }
                    if ($path === '') {
                        $msg .= '【' . $label . "】連結路徑空白\n";
                    }
                    continue;
                }
                $slot = investor_file_slot_for_lang($i);
                $hasNew = !empty($file_array['Photo' . $slot]['tmp_name'])
                    && is_uploaded_file((string)$file_array['Photo' . $slot]['tmp_name']);
                $hasExisting = false;
                if ($formPKey > 0 && crud_is_safe_sql_identifier($tableImg) && crud_is_safe_sql_identifier($fk)) {
                    $existing = crud_fetch_one(
                        "SELECT PKey FROM {$tableImg} WHERE {$fk} = :fk AND Sort = :sort AND Photo1 <> '' LIMIT 1",
                        ['fk' => $formPKey, 'sort' => $slot]
                    );
                    $hasExisting = $existing !== null;
                }
                if (!$hasNew && !$hasExisting) {
                    $msg .= '【' . $label . "】請選擇上傳檔案\n";
                }
            }
        }

        return $msg;
    }
}

if (!function_exists('investor_validate_year')) {
    function investor_validate_year(array $filter): string {
        $showYear = (int)($filter['show_year'] ?? 0);
        if ($showYear !== 1) {
            return '';
        }
        $year = trim((string)($filter['year'] ?? ''));
        if ($year === '' || !ctype_digit($year) || strlen($year) !== 4) {
            return "【年度】請輸入 4 位數字\n";
        }
        return '';
    }
}

if (!function_exists('investor_next_sort')) {
    /**
     * 依分類與年度計算下一個 Sort（與 get_investor_sort.php 相同邏輯）
     */
    function investor_next_sort(int $modulePKey, array $filter, int $layer): int {
        $tables = class1_detail_tables();
        $master = (string)($tables['master'] ?? 'investor');
        if (!crud_is_safe_sql_identifier($master)) {
            return 1;
        }

        $conds  = ['Module_PKey = :Module_PKey'];
        $params = ['Module_PKey' => $modulePKey];

        $class1 = safe_int($filter['Class1'] ?? 0);
        if ($class1 > 0) {
            $conds[] = 'Class1_PKey = :Class1_PKey';
            $params['Class1_PKey'] = $class1;
        }
        if ($layer > 2) {
            $class2 = safe_int($filter['Class2'] ?? 0);
            if ($class2 > 0) {
                $conds[] = 'Class2_PKey = :Class2_PKey';
                $params['Class2_PKey'] = $class2;
            }
        }
        if ($layer > 3) {
            $class3 = safe_int($filter['Class3'] ?? 0);
            if ($class3 > 0) {
                $conds[] = 'Class3_PKey = :Class3_PKey';
                $params['Class3_PKey'] = $class3;
            }
        }
        if (crud_table_has_column($master, 'year')) {
            $year = safe_int($filter['year'] ?? 0);
            if ($year > 0) {
                $conds[] = 'year = :year';
                $params['year'] = $year;
            }
        }

        $sql = 'SELECT MAX(Sort) AS MaxSort FROM `' . $master . '` WHERE ' . implode(' AND ', $conds);
        $max = (int)crud_fetch_scalar($sql, $params, 'MaxSort');
        return $max > 0 ? $max + 1 : 1;
    }
}
