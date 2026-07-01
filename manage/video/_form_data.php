<?php
declare(strict_types=1);
/**
 * video 表單資料：預設值與自 DB 載入
 */

if (!function_exists('class1_detail_tables')) {
    function class1_detail_tables(): array {
        return manage_detail_tables();
    }
}

if (!function_exists('class1_detail_init_defaults')) {
    function class1_detail_init_defaults(): void {
        global $array_lang;

        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;

        $GLOBALS['class1_form_vars'] = [
            'Update_PKey' => 0,
            'Sort'        => 0,
            'Upload'      => 'Yes',
            'Home'        => 'No',
            'Class1'      => 0,
            'Class2'      => 0,
            'Class3'      => 0,
            'dtUDate'     => '',
            'UserID'      => '',
            'isShow'      => [],
            'strName'     => [],
            'Interview'   => [],
            'Movielink'   => [],
            'Description' => [],
            'Keywords'    => [],
            'Photo'       => [],
            'PhotoS'      => [],
            'PhotoM'      => [],
        ];

        for ($i = 1; $i <= $langCount; $i++) {
            $GLOBALS['class1_form_vars']['isShow'][$i]      = '';
            $GLOBALS['class1_form_vars']['strName'][$i]     = '';
            $GLOBALS['class1_form_vars']['Interview'][$i]  = '';
            $GLOBALS['class1_form_vars']['Movielink'][$i]   = '';
            $GLOBALS['class1_form_vars']['Description'][$i] = '';
        }
        for ($n = 0; $n < 5; $n++) {
            $GLOBALS['class1_form_vars']['Keywords'][$n] = [];
            for ($i = 1; $i <= $langCount; $i++) {
                $GLOBALS['class1_form_vars']['Keywords'][$n][$i] = '';
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

        $v['Sort']   = (int)($row['Sort'] ?? 0);
        $v['Upload'] = (string)($row['Upload'] ?? 'Yes');
        $v['Home']   = (string)($row['Home'] ?? 'No');
        $v['dtUDate'] = (string)($row['dtUDate'] ?? '');
        $v['UserID']  = (string)($row['UserID'] ?? '');

        if (isset($row['strName'])) {
            $v['strName'][1] = (string)$row['strName'];
        }
        if (isset($row['Interview'])) {
            $v['Interview'][1] = (string)$row['Interview'];
        }
        if (isset($row['Movielink'])) {
            $v['Movielink'][1] = (string)$row['Movielink'];
        }
        if (isset($row['Description'])) {
            $v['Description'][1] = (string)$row['Description'];
        }
        if (!empty($row['Keywords'])) {
            $parts = explode(',', (string)$row['Keywords']);
            foreach ($parts as $idx => $word) {
                $v['Keywords'][$idx][1] = trim($word);
            }
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

        class1_detail_export_vars();
    }
}

if (!function_exists('class1_detail_load_children')) {
    function class1_detail_load_children(int $pkey): void {
        global $array_lang;

        $tables = class1_detail_tables();
        $fk     = $tables['fk'];
        $v      = &$GLOBALS['class1_form_vars'];
        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;

        if (($tables['img'] ?? '') !== '') {
            $imgData = crud_load_img_slots_data($tables['img'], $fk, $pkey, 1);
            $v['Photo']  = $imgData['Photo'];
            $v['PhotoS'] = $imgData['PhotoS'];
            $v['PhotoM'] = $imgData['PhotoM'];
        }

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
            foreach ($langData['Movielink'] as $i => $mov) {
                $v['Movielink'][$i] = (string)$mov;
            }
            foreach ($langData['Description'] ?? [] as $i => $desc) {
                $v['Description'][$i] = (string)$desc;
            }
            foreach ($langData['Keywords'] ?? [] as $i => $kwRaw) {
                $kwRaw = trim((string)$kwRaw);
                if ($kwRaw === '') {
                    continue;
                }
                $parts = explode(',', $kwRaw);
                foreach ($parts as $idx => $word) {
                    $v['Keywords'][$idx][$i] = trim($word);
                }
            }
            manage_lang_apply_seo_from_lang_data($langData);
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
            'Sort', 'strName', 'Interview', 'Movielink', 'Description', 'Keywords',
            'Home', 'Upload', 'UserID', 'dtUDate', 'dtDate',
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
