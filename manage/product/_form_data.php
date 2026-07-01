<?php
declare(strict_types=1);
/**
 * product 表單資料：預設值與自 DB 載入（供 add.php / update.php 使用）
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
            'Sort'        => 0,
            'strNo'       => '',
            'Upload'      => 'Yes',
            'Description' => [],
            'Keywords'    => [],
            'Class1'      => 0,
            'Class2'      => 0,
            'Class3'      => 0,
            'Home'        => 'No',
            'dtUDate'     => '',
            'UserID'      => '',
            'isShow'      => [],
            'language'    => [],
            'strName'     => [],
            'Interview'   => [],
            'Title'       => [],
            'Movielink'   => [],
            'Contents'    => [],
            'ImgType'     => [],
            'Ext'         => [],
            'Photo'       => [],
            'PhotoS'      => [],
            'PhotoM'      => [],
            'productRelations' => [],
            'Accessory_Total' => 0,
        ];
        for ($s = 1; $s <= 8; $s++) {
            $GLOBALS['class1_form_vars']['ImgType'][$s] = $s >= 7 ? 2 : 1;
            $GLOBALS['class1_form_vars']['Ext'][$s] = '';
        }

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

        $v['Sort']   = (int)($row['Sort'] ?? 0);
        $v['strNo']  = (string)($row['strNo'] ?? '');
        $v['Upload'] = (string)($row['Upload'] ?? 'Yes');
        $v['dtUDate'] = (string)($row['dtUDate'] ?? '');
        $v['UserID']  = (string)($row['UserID'] ?? '');
        $v['Home']    = (string)($row['Home'] ?? 'No');

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
            $imgData = product_load_img_slots_data((string)$tables['img'], $fk, $pkey);
            $v['Photo']   = $imgData['Photo'];
            $v['PhotoS']  = $imgData['PhotoS'];
            $v['PhotoM']  = $imgData['PhotoM'];
            $v['ImgType'] = $imgData['intType'];
            $v['Ext']     = $imgData['Ext'];
        } else {
            for ($s = 1; $s <= 8; $s++) {
                $v['ImgType'][$s] = $s >= 7 ? 2 : 1;
                $v['Ext'][$s] = '';
            }
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

        $v['productRelations'] = product_load_relations($pkey);
        $v['Accessory_Total'] = count($v['productRelations']);

        class1_detail_export_vars();
    }
}

if (!function_exists('product_relation_target_col')) {
    /** product_relation 關聯目標欄位（依實際 schema 自動判斷） */
    function product_relation_target_col(): string {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }
        $table = 'product_relation';
        if (!function_exists('chkTable') || !chkTable($table)) {
            $resolved = 'Relation_PKey';
            return $resolved;
        }
        foreach (['Relation_PKey', 'Accessory_PKey', 'Paper_PKey'] as $candidate) {
            if (function_exists('crud_table_has_column') && crud_table_has_column($table, $candidate)) {
                $resolved = $candidate;
                return $resolved;
            }
        }
        $resolved = 'Relation_PKey';
        return $resolved;
    }
}

if (!function_exists('product_load_relations')) {
    /**
     * @return list<array{rowPKey:int,targetPKey:int,strName:string}>
     */
    function product_load_relations(int $productPKey): array {
        $table = 'product_relation';
        if ($productPKey <= 0 || !function_exists('chkTable') || !chkTable($table)) {
            return [];
        }
        $targetCol = product_relation_target_col();
        if (!crud_is_safe_sql_identifier($targetCol)) {
            return [];
        }
        $sql = 'SELECT PKey, `' . $targetCol . '` AS TargetPKey, strName FROM `' . $table
            . '` WHERE Product_PKey = :pk ORDER BY Sort, PKey';
        $rows = crud_fetch_all($sql, ['pk' => $productPKey]);
        $out = [];
        foreach ($rows as $r) {
            $target = (int)($r['TargetPKey'] ?? 0);
            if ($target <= 0) {
                continue;
            }
            $out[] = [
                'rowPKey'    => (int)($r['PKey'] ?? 0),
                'targetPKey' => $target,
                'strName'    => (string)($r['strName'] ?? ''),
            ];
        }
        return $out;
    }
}

if (!function_exists('product_save_relations')) {
    /** 依表單 Accessory* 欄位同步 product_relation */
    function product_save_relations(int $productPKey, array $filter): void {
        $table = 'product_relation';
        if ($productPKey <= 0 || !function_exists('chkTable') || !chkTable($table)) {
            return;
        }
        $targetCol = product_relation_target_col();
        if (!crud_is_safe_sql_identifier($targetCol)) {
            return;
        }

        $total = (int)($filter['Accessory_Total'] ?? 0);
        $pdo = new dbPDO();
        if (!$pdo->delete($table, 'Product_PKey', SqlFilter($productPKey, 'int'))) {
            $err = method_exists($pdo, 'getErrorMessage') ? trim((string)$pdo->getErrorMessage()) : '';
            if ($err !== '') {
                throw new RuntimeException('清除舊關聯失敗：' . $err);
            }
        }

        $sort = 0;
        $seen = [];
        for ($i = 1; $i <= $total; $i++) {
            $target = (int)($filter['Accessory' . $i] ?? 0);
            if ($target <= 0 || $target === $productPKey || isset($seen[$target])) {
                continue;
            }
            $seen[$target] = true;
            $sort++;
            $strName = trim((string)($filter['Accessory_Name' . $i] ?? ''));
            if ($strName === '') {
                $row = crud_fetch_one(
                    'SELECT strName FROM product WHERE PKey = :pk LIMIT 1',
                    ['pk' => $target]
                );
                $strName = (string)($row['strName'] ?? '');
            }
            $row = [
                'Product_PKey' => SqlFilter($productPKey, 'int'),
                $targetCol     => SqlFilter($target, 'int'),
                'Sort'         => SqlFilter($sort, 'int'),
                'strName'      => SqlFilter($strName, 'tab'),
                'dtDate'       => date('Y-m-d H:i:s'),
            ];
            $row = crud_filter_row_for_table($table, $row);
            if ($row === [] || !array_key_exists($targetCol, $row)) {
                continue;
            }
            if (!$pdo->insert($table, $row)) {
                $err = method_exists($pdo, 'getErrorMessage') ? trim((string)$pdo->getErrorMessage()) : '';
                throw new RuntimeException(
                    $err !== '' ? $err : ('product_relation 寫入失敗（' . $targetCol . '=' . $target . '）')
                );
            }
        }
        $pdo->close();
    }
}

if (!function_exists('product_load_img_slots_data')) {
    /**
     * product_img：intType 1=圖片、2=檔案（非 paper 版面配置）
     *
     * @return array{Photo: array<int,string>, PhotoS: array<int,int>, PhotoM: array<int,string>, intType: array<int,int>, Ext: array<int,string>}
     */
    function product_load_img_slots_data(string $tableImg, string $fkName, int $fkValue, int $maxSlots = 8): array
    {
        $Photo = [];
        $PhotoS = [];
        $PhotoM = [];
        $intType = [];
        $Ext = [];

        for ($s = 1; $s <= $maxSlots; $s++) {
            $intType[$s] = $s >= 7 ? 2 : 1;
            $Ext[$s] = '';
        }

        if (!function_exists('chkTable') || !chkTable($tableImg)) {
            return compact('Photo', 'PhotoS', 'PhotoM', 'intType', 'Ext');
        }

        $cols = 'PKey, Sort, Forder, Photo1, PhotoM, intType';
        $sql = "SELECT {$cols} FROM {$tableImg} WHERE {$fkName} = :fk ORDER BY Sort";
        $rows = crud_fetch_all($sql, ['fk' => $fkValue]);

        foreach ($rows as $r) {
            $i = (int)($r['Sort'] ?? 0);
            if ($i <= 0 || $i > $maxSlots) {
                continue;
            }
            $forder = rtrim((string)($r['Forder'] ?? ''), "/\\");
            $fname = basename((string)($r['Photo1'] ?? ''));
            if ($fname !== '') {
                $rel = ($forder !== '' ? $forder . '/' : '') . $fname;
                $Photo[$i] = $rel;
                $PhotoS[$i] = (int)($r['PKey'] ?? 0);
                $Ext[$i] = manage_file_ext_from_path($rel);
            }
            $rawType = (int)($r['intType'] ?? 0);
            $intType[$i] = ($rawType === 1 || $rawType === 2) ? $rawType : ($i >= 7 ? 2 : 1);
            $PhotoM[$i] = (string)($r['PhotoM'] ?? '');
        }

        return compact('Photo', 'PhotoS', 'PhotoM', 'intType', 'Ext');
    }
}

if (!function_exists('product_save_img_slots')) {
    /** 儲存 product_img（intType 為 1 圖片 / 2 檔案） */
    function product_save_img_slots(
        string $tableImg,
        string $fkName,
        int $parentPKey,
        string $forderVal,
        array $photos,
        array $photoW,
        array $photoH,
        array $photoM,
        array $filter,
        int $maxSlots,
        string $uploadBase
    ): void {
        if (!function_exists('chkTable') || !chkTable($tableImg)) {
            return;
        }

        for ($i = 1; $i <= $maxSlots; $i++) {
            if (array_key_exists($i, $photoM)) {
                $pdo = new dbPDO();
                $sqlU = "UPDATE {$tableImg} SET PhotoM = :PhotoM WHERE {$fkName} = :fk AND Sort = :sort";
                $bind = [
                    'PhotoM' => SqlFilter($photoM[$i], 'tab'),
                    'fk'     => SqlFilter($parentPKey, 'int'),
                    'sort'   => SqlFilter($i, 'int'),
                ];
                $pdo->execute($sqlU, $bind);
                $pdo->close();
            }

            if (empty($photos[$i])) {
                continue;
            }

            $sql = "SELECT PKey, Forder, Photo1 FROM {$tableImg} WHERE {$fkName} = :fk AND Sort = :sort";
            $existing = crud_fetch_one($sql, ['fk' => $parentPKey, 'sort' => $i]);

            $slotType = isset($filter['intType' . $i])
                ? (int)$filter['intType' . $i]
                : ($i >= 7 ? 2 : 1);
            if ($slotType !== 1 && $slotType !== 2) {
                $slotType = $i >= 7 ? 2 : 1;
            }

            $row = [
                $fkName   => SqlFilter($parentPKey, 'int'),
                'Sort'    => SqlFilter($i, 'int'),
                'Forder'  => $forderVal,
                'Photo1'  => $photos[$i],
                'intType' => SqlFilter($slotType, 'int'),
                'dtDate'  => date('Y-m-d H:i:s'),
            ];
            if (isset($photoW[$i])) {
                $row['PhotoW1'] = SqlFilter($photoW[$i], 'int');
            }
            if (isset($photoH[$i])) {
                $row['PhotoH1'] = SqlFilter($photoH[$i], 'int');
            }
            if (isset($photoM[$i])) {
                $row['PhotoM'] = SqlFilter($photoM[$i], 'tab');
            }

            $pdo = new dbPDO();
            if ($existing !== null) {
                crud_delete_image_variants($uploadBase, (string)$existing['Forder'], (string)$existing['Photo1']);
                $pdo->update($tableImg, $row, 'PKey', (int)$existing['PKey']);
            } else {
                $pdo->insert($tableImg, $row);
            }
            $pdo->close();
        }
    }
}

if (!function_exists('class1_detail_resolve_module_pkey')) {
    /**
     * 目前單元主鍵：優先 $GLOBALS['Module_PKey']，否則 URL manNo（與列表篩選一致）
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
    function class1_recordset_row_to_array(recordset $rs): array {
        $cols = [
            'PKey', 'Module_PKey', 'Class1_PKey', 'Class2_PKey', 'Class3_PKey',
            'Sort', 'strName', 'strNo', 'Description', 'Keywords', 'Home', 'Upload',
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
