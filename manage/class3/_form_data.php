<?php
declare(strict_types=1);
/**
 * class3 表單資料：預設值與自 DB 載入（函式名沿用 class1_detail_*，表名由 _config 決定）
 */

if (!function_exists('class1_detail_tables')) {
    /** 讀取 class1/_config.php 的設定（委派 manage_detail_tables） */
    function class1_detail_tables(): array {
        return manage_detail_tables();
    }
}

if (!function_exists('class1_detail_init_defaults')) {
    /** 新增頁預設變數（_detail.php 使用） */
    function class1_detail_init_defaults(): void {
        global $array_lang;

        $langCount = !empty($array_lang) && is_array($array_lang)
            ? count($array_lang)
            : 6;

        $GLOBALS['class1_form_vars'] = [
            'Update_PKey' => 0,
            'Class1_PKey' => 0,
            'Sort'        => 0,
            'Upload'      => 'Yes',
            'Description' => [],
            'Keywords'    => [],
            'Class1'      => 0,
            'Class2'      => 0,
            'Class3'      => 0,
            'OpenDate'    => date('Y-m-d'),
            'EndDate'     => '',
            'NoEndDate'   => '0',
            'NoOpenDate'  => '0',
            'Home'        => 'No',
            'dtUDate'     => '',
            'UserID'      => '',
            'isShow'      => [],
            'language'    => [],
            'strName'     => [],
            'Interview'   => [],
            'Title'       => [],
            'Link'        => [],
            'Movielink'   => [],
            'Contents'    => [],
            'MsgShow'     => [],
            'Photo'       => [],
            'PhotoS'      => [],
            'PhotoM'      => [],
        ];

        for ($i = 1; $i <= $langCount; $i++) {
            $GLOBALS['class1_form_vars']['isShow'][$i] = '';
            $GLOBALS['class1_form_vars']['strName'][$i] = '';
            $GLOBALS['class1_form_vars']['Interview'][$i] = '';
            $GLOBALS['class1_form_vars']['Movielink'][$i] = '';
            $GLOBALS['class1_form_vars']['Description'][$i] = '';
        }
        for ($n = 0; $n < 5; $n++) {
            $GLOBALS['class1_form_vars']['Keywords'][$n] = [];
            for ($i = 1; $i <= $langCount; $i++) {
                $GLOBALS['class1_form_vars']['Keywords'][$n][$i] = '';
            }
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
    /** 語系顯示勾選：DB/表單可能為 Y、1、Yes 等 */
    function class1_lang_is_show_on($value): bool {
        $v = strtolower(trim((string)$value));
        return in_array($v, ['y', 'yes', '1', 'true', 'on'], true);
    }
}

if (!function_exists('class1_detail_flag01')) {
    /**
     * 將舊資料（Y/N、Yes/No、true/false）統一轉成 '1' / '0'
     * @param mixed $value
     */
    function class1_detail_flag01($value): string {
        $v = strtolower(trim((string)$value));
        return in_array($v, ['1', 'y', 'yes', 'true', 'on'], true) ? '1' : '0';
    }
}

if (!function_exists('class1_detail_export_vars')) {
    /** 將 class1_form_vars 匯出為全域變數（供 _detail.php 使用） */
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

        $v['Class1_PKey'] = (int)($row['PKey'] ?? 0);
        $v['Sort']        = (int)($row['Sort'] ?? 0);
        $v['Upload']      = (string)($row['Upload'] ?? 'Yes');
        $v['dtUDate']     = (string)($row['dtUDate'] ?? '');
        $v['UserID']      = (string)($row['UserID'] ?? '');
        $v['Home']        = (string)($row['Home'] ?? 'No');

        if (isset($row['strName'])) {
            $v['strName'][1] = (string)$row['strName'];
        }

        if (array_key_exists('OpenDate', $row)) {
            $v['OpenDate'] = (string)$row['OpenDate'];
        }
        if (array_key_exists('EndDate', $row)) {
            $v['EndDate'] = (string)$row['EndDate'];
        }
        if (array_key_exists('NoEndDate', $row)) {
            $v['NoEndDate'] = class1_detail_flag01($row['NoEndDate']);
        }
        if (array_key_exists('NoOpenDate', $row)) {
            $v['NoOpenDate'] = class1_detail_flag01($row['NoOpenDate']);
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
        if (isset($row['Description'])) {
            $v['Description'][1] = (string)$row['Description'];
        }
        if (!empty($row['Keywords'])) {
            $kwParts = explode(',', (string)$row['Keywords']);
            foreach ($kwParts as $idx => $word) {
                $v['Keywords'][$idx][1] = trim($word);
            }
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
            $imgData = crud_load_img_slots_data($tables['img'], $fk, $pkey);
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
        }

        if (($tables['msg'] ?? '') !== '' && function_exists('chkTable') && chkTable($tables['msg'])) {
            for ($n = 1; $n <= $langCount; $n++) {
                $sql = 'SELECT Sort, Contents, isShow FROM ' . $tables['msg']
                    . ' WHERE ' . $fk . ' = :fk AND intLang = :lang ORDER BY Sort';
                $rows = crud_fetch_all($sql, ['fk' => $pkey, 'lang' => $n]);
                foreach ($rows as $r) {
                    $sort = (int)($r['Sort'] ?? 0);
                    if ($sort >= 1 && $sort <= 6) {
                        $v['Contents'][$sort][$n] = (string)($r['Contents'] ?? '');
                        $v['MsgShow'][$sort] = (string)($r['isShow'] ?? '');
                    }
                }
            }
        }

        if (($tables['link'] ?? '') !== '' && function_exists('chkTable') && chkTable($tables['link'])) {
            for ($n = 1; $n <= $langCount; $n++) {
                $sql = 'SELECT Sort, strLink, strName FROM ' . $tables['link']
                    . ' WHERE ' . $fk . ' = :fk AND intLang = :lang ORDER BY Sort';
                $rows = crud_fetch_all($sql, ['fk' => $pkey, 'lang' => $n]);
                foreach ($rows as $r) {
                    $sort = (int)($r['Sort'] ?? 0);
                    if ($sort === 1) {
                        $v['Movielink'][$n] = (string)($r['strLink'] ?? '');
                    }
                    $v['Link'][$sort][$n] = (string)($r['strLink'] ?? '');
                    $v['Title'][$sort][$n] = (string)($r['strName'] ?? '');
                }
            }
        }

        class1_detail_export_vars();
    }
}

if (!function_exists('class1_detail_resolve_module_pkey')) {
    /**
     * 目前單元主鍵：優先 URL/session 的 manNo（與列表篩選一致），避免 subNo 改寫 Module_PKey
     */
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
    /** 將 recordset 目前列轉成關聯陣列（對應 dbclass1 主檔欄位） */
    function class1_recordset_row_to_array(recordset $rs): array {
        $cols = [
            'PKey', 'Module_PKey', 'Class1_PKey', 'Class2_PKey', 'Class3_PKey',
            'Sort', 'strName', 'Home', 'Upload',
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

if (!function_exists('class1_detail_debug_load')) {
    /**
     * 除錯：網址加 &debug_load=1（例：update.php?PKey=1&manNo=4&subNo=1&debug_load=1）
     */
    function class1_detail_debug_load(string $step, array $ctx = []): void {
        if (!isset($_GET['debug_load']) || (string)$_GET['debug_load'] !== '1') {
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<pre style="background:#f0f0f0;padding:12px;font:13px/1.5 Consolas,monospace">';
        echo '<strong>class1_detail_load</strong> → ' . htmlspecialchars($step, ENT_QUOTES, 'UTF-8') . "\n\n";
        print_r($ctx);
        echo '</pre>';
        exit;
    }
}

if (!function_exists('class1_detail_load')) {
    /**
     * 自 DB 載入一筆主檔；須符合 PKey，且 Module_PKey 與目前單元一致（與列表相同條件）
     *
     * @param int      $pkey       主鍵
     * @param int|null $modulePKey 模組主鍵（預設 $GLOBALS['Module_PKey']）
     */
    function class1_detail_load(int $pkey, ?int $modulePKey = null): bool {
        if ($pkey <= 0) {
            class1_detail_debug_load('pkey_invalid', ['pkey' => $pkey]);
            return false;
        }
        if ($modulePKey === null || $modulePKey <= 0) {
            $modulePKey = class1_detail_resolve_module_pkey();
        }

        $tables = class1_detail_tables();
        $master = str_replace('`', '', (string)($tables['master'] ?? ''));
        if ($master === '' || !crud_is_safe_sql_identifier($master)) {
            class1_detail_debug_load('master_invalid', ['master' => $master, 'tables' => $tables]);
            return false;
        }

        $moduleCol = str_replace('`', '', (string)($tables['module_pk_col'] ?? 'Module_PKey'));
        if (!crud_is_safe_sql_identifier($moduleCol)) {
            $moduleCol = 'Module_PKey';
        }

        $params = ['PKey' => $pkey];
        $sql    = 'SELECT * FROM `' . $master . '` WHERE `PKey` = :PKey LIMIT 1';

        $row = null;
        $guardPrev = $GLOBALS['crud_sql_table_guard'] ?? true;
        $GLOBALS['crud_sql_table_guard'] = false;

        try {
            if (function_exists('crud_pdo_query_raw')) {
                $rows = crud_pdo_query_raw($sql, $params);
                $row  = $rows[0] ?? null;
            }
            if ($row === null && class_exists('recordset')) {
                $rs = new recordset($sql, $params);
                if (!$rs->eof && $rs->getErrorMessage() === '') {
                    $row = class1_recordset_row_to_array($rs);
                    if (!isset($row['PKey']) && !$rs->eof) {
                        $row['PKey'] = $rs->field('PKey');
                        $row['Module_PKey'] = $rs->field('Module_PKey');
                        $row['Sort'] = $rs->field('Sort');
                        $row['strName'] = $rs->field('strName');
                        $row['Home'] = $rs->field('Home');
                        $row['Upload'] = $rs->field('Upload');
                        $row['UserID'] = $rs->field('UserID');
                        $row['dtUDate'] = $rs->field('dtUDate');
                        $row['dtDate'] = $rs->field('dtDate');
                    }
                }
                $rs->close();
            }
        } finally {
            $GLOBALS['crud_sql_table_guard'] = $guardPrev;
        }

        $rowPkey = function_exists('crud_row_int')
            ? crud_row_int($row, 'PKey')
            : (int)($row['PKey'] ?? $row['pkey'] ?? 0);
        if ($row === null || $rowPkey <= 0) {
            class1_detail_debug_load('no_row', [
                'pkey'          => $pkey,
                'modulePKey'    => $modulePKey,
                'sql'           => $sql,
                'params'        => $params,
                'row'           => $row,
                'rowPkey'       => $rowPkey,
                'master'        => $master,
                'Module_PKey'   => $GLOBALS['Module_PKey'] ?? null,
                'manNo_GET'     => $_GET['manNo'] ?? null,
            ]);
            return false;
        }
        $row['PKey'] = $rowPkey;

        if ($modulePKey > 0) {
            $rowModule = function_exists('crud_row_int')
                ? crud_row_int($row, $moduleCol)
                : (int)($row[$moduleCol] ?? $row['Module_PKey'] ?? 0);
            $manNoFromUrl = safe_int($_GET['manNo'] ?? ($GLOBALS['filter_array']['manNo'] ?? 0));
            $moduleOk = ($rowModule === $modulePKey)
                || ($manNoFromUrl > 0 && $rowModule === $manNoFromUrl);
            if (!$moduleOk) {
                class1_detail_debug_load('module_mismatch', [
                    'pkey'          => $pkey,
                    'modulePKey'    => $modulePKey,
                    'rowModule'     => $rowModule,
                    'manNoFromUrl'  => $manNoFromUrl,
                    'row'           => $row,
                    'moduleCol'     => $moduleCol,
                    'Module_PKey'   => $GLOBALS['Module_PKey'] ?? null,
                ]);
                return false;
            }
        }
        if (!isset($GLOBALS['class1_form_vars'])) {
            class1_detail_init_defaults();
        }
        $GLOBALS['class1_form_vars']['Update_PKey'] = $pkey;
        class1_detail_apply_master($row);
        class1_detail_load_children($pkey);
        class1_detail_debug_load('ok', [
            'pkey'       => $pkey,
            'modulePKey' => $modulePKey,
            'row'        => $row,
        ]);
        return true;
    }
}
