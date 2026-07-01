<?php
declare(strict_types=1);
/**
 * module 表單資料（module_p / module_d / module_lang）
 */

if (!function_exists('module_lang_is_show_on')) {
    function module_lang_is_show_on($value): bool {
        if (function_exists('class1_lang_is_show_on')) {
            return class1_lang_is_show_on($value);
        }
        $v = strtolower(trim((string)$value));
        return in_array($v, ['y', 'yes', '1', 'true', 'on'], true);
    }
}

/** module_p 新增預設 Sort：自動 +1，略過保留值 50（權限用） */
if (!function_exists('module_next_sort')) {
    function module_next_sort(): int {
        return crud_next_sort('module_p', [], 'Sort', [50]);
    }
}

if (!function_exists('module_require_admin')) {
    function module_require_admin(): void {
        global $Login_ID, $m98;
        if (($Login_ID ?? '') !== 'Admin') {
            manage_alert_script('無進入[' . ($m98 ?? '單元設定') . ']權限!', '../index.php');
            exit;
        }
    }
}

if (!function_exists('module_detail_tables')) {
    function module_detail_tables(): array {
        return manage_detail_tables();
    }
}

if (!function_exists('module_detail_init_defaults')) {
    function module_detail_init_defaults(): void {
        global $array_lang;

        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;

        $GLOBALS['module_form_vars'] = [
            'Update_PKey'  => 0,
            'Module_PKey'  => 0,
            'copySourcePKey' => 0,
            'Sort'         => 0,
            'strName'      => '',
            'langStrName'  => [],
            'Home'         => '',
            'intType'      => 1,
            'intUse'       => 0,
            'intPage'      => 0,
            'PageLink'     => '',
            'intLayer'     => 0,
            'oldLayer'     => 0,
            'intColum'     => 0,
            'Upload'       => 'Yes',
            'dtUDate'      => '',
            'UserID'       => '',
            'Description'  => [],
            'Keywords'     => [],
            'isShow'       => [],
            'layerNames'   => [],
            'MaxLayer'     => 0,
            'isColum'      => 0,
        ];

        for ($i = 1; $i <= $langCount; $i++) {
            $GLOBALS['module_form_vars']['isShow'][$i] = '';
            $GLOBALS['module_form_vars']['langStrName'][$i] = '';
            $GLOBALS['module_form_vars']['Description'][$i] = '';
            $GLOBALS['module_form_vars']['Keywords'][$i] = '';
        }

        module_detail_export_vars();
    }
}

if (!function_exists('module_detail_export_vars')) {
    function module_detail_export_vars(): void {
        foreach ($GLOBALS['module_form_vars'] as $key => $val) {
            $GLOBALS[$key] = $val;
        }
    }
}

if (!function_exists('module_detail_apply_program_meta')) {
    function module_detail_apply_program_meta(int $intUse): void {
        $prog = crud_load_program_meta($intUse);
        $v    = &$GLOBALS['module_form_vars'];
        $v['MaxLayer'] = $prog['MaxLayer'];
        $v['isColum']  = $prog['isColum'];
        module_detail_export_vars();
    }
}

if (!function_exists('module_detail_apply_master')) {
    /** @param array<string,mixed> $row */
    function module_detail_apply_master(array $row): void {
        $v = &$GLOBALS['module_form_vars'];

        $pkey = (int)($row['PKey'] ?? 0);
        $v['Update_PKey'] = $pkey;
        $v['Module_PKey'] = $pkey;
        $v['Sort']        = (int)($row['Sort'] ?? 0);
        $v['strName']     = (string)($row['strName'] ?? '');
        $v['Home']        = (string)($row['Home'] ?? '');
        $v['intType']     = (int)($row['intType'] ?? 1);
        $v['intUse']      = (int)($row['intUse'] ?? 0);
        $v['intPage']     = (int)($row['intPage'] ?? 0);
        $v['PageLink']    = (string)($row['PageLink'] ?? '');
        $v['intLayer']    = (int)($row['intLayer'] ?? 0);
        $v['oldLayer']    = (int)($row['intLayer'] ?? 0);
        $v['intColum']    = (int)($row['intColum'] ?? 0);
        $v['Upload']      = (string)($row['Upload'] ?? 'Yes');
        $v['dtUDate']     = (string)($row['dtUDate'] ?? '');
        $v['UserID']      = (string)($row['UserID'] ?? '');

        module_detail_apply_program_meta($v['intUse']);
        if ((int)($v['MaxLayer'] ?? 0) <= 0) {
            $v['intLayer'] = 0;
            $v['oldLayer'] = 0;
        }
        module_detail_export_vars();
    }
}

if (!function_exists('module_detail_apply_lang_fallback')) {
    /** 語系名稱空白時，以主檔 strName 填入第一語系（編輯頁顯示用） */
    function module_detail_apply_lang_fallback(): void {
        $v = &$GLOBALS['module_form_vars'];
        $master = trim((string)($v['strName'] ?? ''));
        if ($master === '') {
            return;
        }
        if (!isset($v['langStrName']) || !is_array($v['langStrName'])) {
            $v['langStrName'] = [];
        }
        $hasAny = false;
        foreach ($v['langStrName'] as $name) {
            if (trim((string)$name) !== '') {
                $hasAny = true;
                break;
            }
        }
        if (!$hasAny) {
            $v['langStrName'][1] = $master;
        }
        module_detail_export_vars();
    }
}

if (!function_exists('module_detail_load_children')) {
    function module_detail_load_children(int $modulePKey): void {
        global $array_lang;

        $tables = module_detail_tables();
        $v      = &$GLOBALS['module_form_vars'];
        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;

        $v['layerNames'] = [];
        if ($modulePKey > 0 && function_exists('chkTable') && chkTable('module_d')) {
            $rows = crud_fetch_all(
                'SELECT Sort, strName FROM module_d WHERE Module_PKey = :fk ORDER BY Sort',
                ['fk' => $modulePKey]
            );
            foreach ($rows as $r) {
                $sort = (int)($r['Sort'] ?? 0);
                if ($sort > 0) {
                    $v['layerNames'][$sort] = (string)($r['strName'] ?? '');
                }
            }
        }

        if (($tables['lang'] ?? '') !== '' && function_exists('chkTable') && chkTable($tables['lang'])) {
            $rows = crud_fetch_all(
                'SELECT Sort, intLang, isShow, strName, Description, Keywords FROM ' . $tables['lang']
                . ' WHERE Module_PKey = :fk ORDER BY Sort',
                ['fk' => $modulePKey]
            );
            foreach ($rows as $r) {
                $i = (int)($r['intLang'] ?? $r['Sort'] ?? 0);
                if ($i < 1 || $i > $langCount) {
                    continue;
                }
                $v['isShow'][$i] = (string)($r['isShow'] ?? '');
                $v['langStrName'][$i] = (string)($r['strName'] ?? '');
                $v['Description'][$i] = (string)($r['Description'] ?? '');
                $v['Keywords'][$i] = (string)($r['Keywords'] ?? '');
            }
        }

        module_detail_export_vars();
    }
}

if (!function_exists('module_detail_load')) {
    function module_detail_load(int $pkey): bool {
        if ($pkey <= 0) {
            return false;
        }
        $tables = module_detail_tables();
        $master = (string)($tables['master'] ?? 'module_p');
        $row    = crud_fetch_one(
            'SELECT * FROM ' . $master . ' WHERE PKey = :PKey LIMIT 1',
            ['PKey' => $pkey]
        );
        if ($row === null) {
            return false;
        }
        if (!isset($GLOBALS['module_form_vars'])) {
            module_detail_init_defaults();
        }
        module_detail_apply_master($row);
        module_detail_load_children($pkey);
        module_detail_apply_lang_fallback();
        return true;
    }
}

if (!function_exists('module_list_program_meta')) {
    /**
     * 列表用：批次載入 program 名稱與版面名稱
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    function module_list_enrich_rows(array $rows): array {
        if ($rows === []) {
            return $rows;
        }

        $useIds = array_values(array_unique(array_filter(array_map(
            static fn(array $r): int => (int)($r['intUse'] ?? 0),
            $rows
        ))));

        $programs = [];
        if ($useIds !== [] && function_exists('chkTable') && chkTable('program')) {
            $ph = [];
            $params = [];
            foreach ($useIds as $idx => $id) {
                $k = 'u' . $idx;
                $ph[] = ':' . $k;
                $params[$k] = $id;
            }
            $progRows = crud_fetch_all(
                'SELECT PKey, strName FROM program WHERE PKey IN (' . implode(',', $ph) . ')',
                $params
            );
            foreach ($progRows as $pr) {
                $programs[(int)$pr['PKey']] = $pr;
            }
        }

        foreach ($rows as &$row) {
            $use = (int)($row['intUse'] ?? 0);
            $prog = $programs[$use] ?? null;
            $row['program_name'] = $prog !== null ? (string)($prog['strName'] ?? '') : '';
            $row['type_label']   = ((int)($row['intType'] ?? 0) === 2) ? '美工頁面' : '功能頁面';
            $layer = (int)($row['intLayer'] ?? 0);
            $row['layer_label'] = $layer > 1 ? $layer . '層' : '';
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('module_resolve_master_strname')) {
    /** 主檔單元名稱：優先 strName，否則取第一個語系 strName{n} */
    function module_resolve_master_strname(array $filter): string {
        $master = trim((string)($filter['strName'] ?? ''));
        if ($master !== '') {
            return $master;
        }
        global $array_lang;
        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;
        for ($i = 1; $i <= $langCount; $i++) {
            $name = trim((string)($filter['strName' . $i] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
        return '';
    }
}

if (!function_exists('module_layer_field_name')) {
    function module_layer_field_name(int $sort): string {
        return 'subName' . $sort;
    }
}

if (!function_exists('module_addin_normalize_layers')) {
    /**
     * 正規化階層：MaxLayer=0 或階層改為空值/0/1 時 intLayer 一律為 0
     *
     * @param array<string, mixed> $filter
     * @return array{intUse:int,maxLayer:int,intLayer:int,oldLayer:int,strLink:string}
     */
    function module_addin_normalize_layers(array &$filter): array {
        $intUse = is_numeric($filter['intUse'] ?? null) ? (int)$filter['intUse'] : 0;
        $prog   = crud_load_program_meta($intUse);
        $maxLayer = (int)($prog['MaxLayer'] ?? 0);

        $rawLayer = trim((string)($filter['intLayer'] ?? ''));
        if ($rawLayer === '' || !ctype_digit($rawLayer)) {
            $intLayer = 0;
        } else {
            $intLayer = (int)$rawLayer;
        }
        if ($maxLayer <= 0 || $intLayer <= 1) {
            $intLayer = 0;
        }
        $filter['intLayer'] = (string)$intLayer;

        $oldLayer = is_numeric($filter['oldLayer'] ?? null) ? (int)$filter['oldLayer'] : 0;

        return [
            'intUse'   => $intUse,
            'maxLayer' => $maxLayer,
            'intLayer' => $intLayer,
            'oldLayer' => $oldLayer,
            'strLink'  => (string)($prog['strLink'] ?? 'none'),
        ];
    }
}

if (!function_exists('module_addin_list_redirect_url')) {
    /** @param array<string, mixed> $filter */
    function module_addin_list_redirect_url(array $filter): string {
        $listFile = (string)($filter['list'] ?? 'list.php');
        $listMod  = (string)($filter['list_module'] ?? '');
        if ($listMod === '' && function_exists('manage_return_list_module_dir')) {
            $listMod = manage_return_list_module_dir();
        }
        return manage_return_list_redirect_target($listFile, $listMod);
    }
}

if (!function_exists('module_addin_validate')) {
    /** @return string 錯誤訊息（空字串表示通過） */
    function module_addin_validate(array $filter): string {
        $msg = '';

        if (module_resolve_master_strname($filter) === '') {
            $msg .= "【單元名稱】為空白（請至少填寫一個語系）\n";
        }

        $sort = $filter['Sort'] ?? '';
        if ($sort === '' || !ctype_digit((string)$sort)) {
            $msg .= "【單元順序】空白或非數字格式\n";
        }

        $intType = is_numeric($filter['intType'] ?? null) ? (int)$filter['intType'] : 1;
        $intUse  = is_numeric($filter['intUse'] ?? null) ? (int)$filter['intUse'] : 0;
        $intPage = is_numeric($filter['intPage'] ?? null) ? (int)$filter['intPage'] : 1;

        if ($intType === 1 && $intUse <= 0) {
            $msg .= "【功能模組】請選擇\n";
        }
        if ($intPage === 2 && trim((string)($filter['PageLink'] ?? '')) === '') {
            $msg .= "【前台連結網址】為空白\n";
        }

        $intLayer = is_numeric($filter['intLayer'] ?? null) ? (int)$filter['intLayer'] : 0;
        $intUse   = is_numeric($filter['intUse'] ?? null) ? (int)$filter['intUse'] : 0;
        $maxLayer = (int)(crud_load_program_meta($intUse)['MaxLayer'] ?? 0);
        if ($maxLayer <= 0 || $intLayer <= 1) {
            return $msg;
        }
        for ($j = 1; $j <= $intLayer; $j++) {
            $layerKey = module_layer_field_name($j);
            $layerVal = trim((string)($filter[$layerKey] ?? $filter['strName' . $j] ?? ''));
            if ($layerVal === '') {
                $msg .= "【子單元名稱{$j}】為空白\n";
            }
        }

        return $msg;
    }
}

if (!function_exists('module_addin_build_master_data')) {
    /** @return array<string,mixed> */
    function module_addin_build_master_data(array $filter): array {
        $strName = mb_substr(module_resolve_master_strname($filter), 0, 50, 'UTF-8');
        $layerCtx = module_addin_normalize_layers($filter);
        $intUse   = $layerCtx['intUse'];
        $intLayer = $layerCtx['intLayer'];
        $prog     = crud_load_program_meta($intUse);

        $data = [
            'Sort'      => SqlFilter($filter['Sort'] ?? 0, 'int'),
            'intType'   => SqlFilter($filter['intType'] ?? 1, 'int'),
            'intUse'    => SqlFilter($intUse, 'int'),
            'strName'   => SqlFilter($strName, 'tab'),
            'Home'      => SqlFilter($prog['Home'], 'tab'),
            'intLocal'  => SqlFilter($prog['intLocal'], 'int'),
            'PageLink'  => SqlFilter(mb_substr(trim((string)($filter['PageLink'] ?? '')), 0, 200, 'UTF-8'), 'tab'),
            'intPage'   => SqlFilter($filter['intPage'] ?? 1, 'int'),
            'intLayer'  => SqlFilter($intLayer, 'int'),
            'intColum'  => SqlFilter($filter['intColum'] ?? 0, 'int'),
            'Upload'    => SqlFilter((($filter['Upload'] ?? 'Yes') === 'No') ? 'No' : 'Yes', 'tab'),
            'dtUDate'   => date('Y-m-d H:i:s'),
            'UserID'    => SqlFilter($GLOBALS['Login_ID'] ?? '', 'tab'),
        ];

        if ($prog['strLink'] !== 'none') {
            $data['strLink'] = SqlFilter($prog['strLink'], 'tab');
        }

        return $data;
    }
}
