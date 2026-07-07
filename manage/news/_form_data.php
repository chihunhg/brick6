<?php
declare(strict_types=1);
/**
 * class1 表單資料：預設值與自 DB 載入（供 add.php / update.php 使用）
 */

require_once dirname(__DIR__, 2) . '/include/tag_relation_helpers.php';

if (!function_exists('news_show_type_label')) {
    /** 列表顯示方式標籤 HTML（內容／連結） */
    function news_show_type_label(int $showType): string
    {
        switch ($showType) {
            case 1:
                return '<span class="typeColor--1">連結</span>';
            case 2:
                return '<span class="typeColor--2">內容</span>';
            default:
                return '<span class="typeColor--2">內容</span>';
        }
    }
}

if (!function_exists('news_validate_by_show_type')) {
    /** 依 show_type 驗證連結模式語系 URL */
    function news_validate_by_show_type(array $filter): string
    {
        global $array_lang;

        $msg       = '';
        $langCount = !empty($array_lang) && is_array($array_lang) ? count($array_lang) : 6;
        $showType  = (int)($filter['show_type'] ?? 2);

        if ($showType !== 1) {
            return $msg;
        }

        for ($i = 1; $i <= $langCount; $i++) {
            if (($filter['Show' . $i] ?? '') !== 'Y') {
                continue;
            }
            $label = (string)($array_lang[$i] ?? ('語系' . $i));
            if (trim((string)($filter['strURL' . $i] ?? '')) === '') {
                $msg .= '【' . $label . "】連結空白\n";
            }
        }

        return $msg;
    }
}

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
            'OpenDate'    => '',
            'strDate'     => date('Y/m/d'),
            'EndDate'     => '',
            'NoEndDate'   => '0',
            'NoOpenDate'  => '0',
            'Home'        => 'No',
            'show_type'   => 2,
            'dtUDate'     => '',
            'UserID'      => '',
            'isShow'      => [],
            'language'    => [],
            'strName'     => [],
            'Interview'   => [],
            'Title'       => [],
            'Link'        => [],
            'Movielink'   => [],
            'strURL'      => [],
            'intLink'     => [],
            'Contents'    => [],
            'MsgShow'     => [],
            'ImgType'     => manage_content_img_layout_defaults(),
            'Photo'       => [],
            'PhotoS'      => [],
            'PhotoM'      => [],
            'tagRelations' => [],
            'Tag_Total'   => 0,
        ];

        for ($i = 1; $i <= $langCount; $i++) {
            $GLOBALS['class1_form_vars']['isShow'][$i] = '';
            $GLOBALS['class1_form_vars']['strName'][$i] = '';
            $GLOBALS['class1_form_vars']['Interview'][$i] = '';
            $GLOBALS['class1_form_vars']['Movielink'][$i] = '';
            $GLOBALS['class1_form_vars']['strURL'][$i]    = '';
            $GLOBALS['class1_form_vars']['intLink'][$i]   = 2;
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
    /** 將 class1_form_vars 匯出為區域變數 */
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
        if (array_key_exists('strDate', $row)) {
            $v['strDate'] = function_exists('crud_strdate_for_form')
                ? crud_strdate_for_form($row['strDate'])
                : (string)$row['strDate'];
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

        if (function_exists('crud_detail_resolve_publish_dates_for_form')) {
            crud_detail_resolve_publish_dates_for_form($v, $row, class1_detail_tables()['master'] ?? 'news');
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
        $master = (string)(class1_detail_tables()['master'] ?? 'news');
        if (crud_table_has_column($master, 'show_type')) {
            $v['show_type'] = (int)($row['show_type'] ?? 2);
            if ($v['show_type'] < 1 || $v['show_type'] > 2) {
                $v['show_type'] = 2;
            }
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
    /** 載入 news 語系、內文、連結、標籤等子表 */
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
            $v['Photo']   = $imgData['Photo'];
            $v['PhotoS']  = $imgData['PhotoS'];
            $v['PhotoM']  = $imgData['PhotoM'];
            $v['ImgType'] = manage_content_img_layout_merge_defaults($imgData['intType'] ?? []);
        } else {
            $v['ImgType'] = manage_content_img_layout_defaults();
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
            foreach ($langData['Description'] ?? [] as $i => $desc) {
                $v['Description'][$i] = (string)$desc;
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

        $moduleConfig = is_file(__DIR__ . '/_config.php') ? require __DIR__ . '/_config.php' : [];
        $parentCol = (string)($moduleConfig['tag_relation_parent_col'] ?? '');
        if ($parentCol !== ''
            && function_exists('manage_module_show_detail_field')
            && manage_module_show_detail_field('tag')
        ) {
            $v['tagRelations'] = tag_relation_load($pkey, $parentCol);
            $v['Tag_Total'] = count($v['tagRelations']);
        }

        class1_detail_export_vars();
    }
}

if (!function_exists('class1_detail_resolve_module_pkey')) {
    /** 解析目前單元 Module_PKey（manNo） */
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
    /** 將 recordset 列轉為關聯陣列 */
    function class1_recordset_row_to_array(recordset $rs): array {
        $cols = [
            'PKey', 'Module_PKey', 'Class1_PKey', 'Class2_PKey', 'Class3_PKey',
            'Sort', 'strName', 'Home', 'Upload', 'show_type',
            'UserID', 'dtUDate', 'dtDate', 'strDate', 'OpenDate', 'EndDate', 'NoOpenDate', 'NoEndDate',
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
    /** debug_load=1 時輸出載入步驟並結束（除錯用） */
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
                'pkey'       => $pkey,
                'modulePKey' => $modulePKey,
                'sql'        => $sql,
                'params'     => $params,
                'master'     => $master,
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
                    'pkey'         => $pkey,
                    'modulePKey'   => $modulePKey,
                    'rowModule'    => $rowModule,
                    'manNoFromUrl' => $manNoFromUrl,
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
        return true;
    }
}
