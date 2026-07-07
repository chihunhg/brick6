<?php

declare(strict_types=1);

/**

 * filedown 表單資料：預設值與自 DB 載入（供 add.php / update.php 使用）

 */



if (!function_exists('class1_detail_tables')) {

    /** 讀取模組設定（委派 manage_detail_tables） */

    function class1_detail_tables(): array {

        return manage_detail_tables();

    }

}



if (!function_exists('class1_detail_init_defaults')) {

    /** 初始化新增頁表單預設變數 */

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

            'dtUDate'     => '',

            'UserID'      => '',

            'isShow'      => [],

            'language'    => [],

            'strName'     => [],

            'Contents'    => [],

            'intLink'     => [],

            'strLink'     => [],

            'Photo'       => [],

            'PhotoS'      => [],

            'Ext'         => [],

            'FileSize'   => [],

        ];



        for ($i = 1; $i <= $langCount; $i++) {

            $GLOBALS['class1_form_vars']['isShow'][$i]      = '';

            $GLOBALS['class1_form_vars']['strName'][$i]     = '';

            $GLOBALS['class1_form_vars']['Description'][$i] = '';

            $GLOBALS['class1_form_vars']['Contents'][$i]    = '';

            $GLOBALS['class1_form_vars']['intLink'][$i]     = 2;

            $GLOBALS['class1_form_vars']['strLink'][$i]     = '';

            $GLOBALS['class1_form_vars']['FileSize'][$i]   = '';

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

    /** 判斷語系顯示是否為啟用狀態 */

    function class1_lang_is_show_on($value): bool {

        $v = strtolower(trim((string)$value));

        return in_array($v, ['y', 'yes', '1', 'true', 'on'], true);

    }

}



if (!function_exists('class1_detail_export_vars')) {

    /** 將 class1_form_vars 匯出為全域變數 */

    function class1_detail_export_vars(): void {

        foreach ($GLOBALS['class1_form_vars'] as $key => $val) {

            $GLOBALS[$key] = $val;

        }

    }

}



if (!function_exists('class1_detail_apply_master')) {

    /** 將主檔列資料寫入 class1_form_vars */

    /** @param array<string,mixed> $row */

    function class1_detail_apply_master(array $row): void {

        $v = &$GLOBALS['class1_form_vars'];



        $v['Class1_PKey'] = (int)($row['PKey'] ?? 0);

        $v['Sort']        = (int)($row['Sort'] ?? 0);

        $v['Upload']      = (string)($row['Upload'] ?? 'Yes');

        $v['dtUDate']     = (string)($row['dtUDate'] ?? '');

        $v['UserID']      = (string)($row['UserID'] ?? '');



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

    /** 載入子表資料（語系、附件等） */

    function class1_detail_load_children(int $pkey): void {

        global $array_lang;



        $tables = class1_detail_tables();

        $fk     = $tables['fk'];

        $v      = &$GLOBALS['class1_form_vars'];

        $langCount = !empty($array_lang) && is_array($array_lang)

            ? count($array_lang)

            : 6;



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

            foreach ($langData['Description'] ?? [] as $i => $desc) {

                $v['Description'][$i] = (string)$desc;

            }

            foreach ($langData['Contents'] ?? [] as $i => $content) {

                $v['Contents'][$i] = (string)$content;

            }

            foreach ($langData['intLink'] ?? [] as $i => $linkType) {

                $linkType = (int)$linkType;

                $v['intLink'][$i] = $linkType > 0 ? $linkType : 2;

            }

            foreach ($langData['strLink'] ?? [] as $i => $link) {

                $v['strLink'][$i] = (string)$link;

            }

            foreach ($langData['FileSize'] ?? [] as $i => $size) {

                $v['FileSize'][$i] = (string)$size;

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



        if (($tables['img'] ?? '') !== '' && function_exists('chkTable') && chkTable($tables['img'])) {

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

                $v['Photo'][$sort]  = $path;

                $v['PhotoS'][$sort] = (int)($r['PKey'] ?? 0);

                $v['Ext'][$sort]    = manage_file_ext_from_path($path);

            }

        }



        class1_detail_export_vars();

    }

}



if (!function_exists('class1_detail_resolve_module_pkey')) {

    /** 解析目前單元 Module_PKey（優先全域或 URL manNo） */

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

    /** 將 recordset 目前列轉成關聯陣列 */

    function class1_recordset_row_to_array(recordset $rs): array {

        $cols = [

            'PKey', 'Module_PKey', 'Class1_PKey', 'Class2_PKey', 'Class3_PKey',

            'Sort', 'strName', 'Upload', 'UserID', 'dtUDate', 'dtDate',

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

    /** 自 DB 載入一筆主檔及子表資料 */

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



        $params = ['PKey' => $pkey];

        $sql    = 'SELECT * FROM `' . $master . '` WHERE `PKey` = :PKey LIMIT 1';

        $row    = crud_fetch_one($sql, $params);



        $rowPkey = function_exists('crud_row_int')

            ? crud_row_int($row, 'PKey')

            : (int)($row['PKey'] ?? 0);

        if ($row === null || $rowPkey <= 0) {

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



if (!function_exists('filedown_lang_count')) {

    /** 取得表單語系數量 */

    function filedown_lang_count(): int {

        global $array_lang;

        return !empty($array_lang) && is_array($array_lang) ? count($array_lang) : 6;

    }

}



if (!function_exists('filedown_validate_lang_link_file')) {

    /**

     * 已勾選語系：intLink=1 需 strLink；intLink=2 需新檔或既有檔案

     */

    function filedown_validate_lang_link_file(array $filter, array $file_array, int $formPKey, string $tableImg, string $fk): string {

        global $array_lang;



        $msg       = '';

        $langCount = filedown_lang_count();

        for ($i = 1; $i <= $langCount; $i++) {

            if (($filter['Show' . $i] ?? '') !== 'Y') {

                continue;

            }

            $label   = (string)($array_lang[$i] ?? ('語系' . $i));

            $intLink = (int)($filter['intLink' . $i] ?? 2);

            if ($intLink === 1) {

                if (trim((string)($filter['strLink' . $i] ?? '')) === '') {

                    $msg .= '【' . $label . "】連結路徑空白\n";

                }

                continue;

            }

            $hasNew = !empty($file_array['Photo' . $i]['tmp_name'])

                && is_uploaded_file((string)$file_array['Photo' . $i]['tmp_name']);

            $hasExisting = false;

            if ($formPKey > 0 && crud_is_safe_sql_identifier($tableImg) && crud_is_safe_sql_identifier($fk)) {

                $existing = crud_fetch_one(

                    "SELECT PKey FROM {$tableImg} WHERE {$fk} = :fk AND Sort = :sort AND Photo1 <> '' LIMIT 1",

                    ['fk' => $formPKey, 'sort' => $i]

                );

                $hasExisting = $existing !== null;

            }

            if (!$hasNew && !$hasExisting) {

                $msg .= '【' . $label . "】請選擇上傳檔案\n";

            }

        }

        return $msg;

    }

}



if (!function_exists('filedown_clear_lang_file')) {

    /** intLink=1 時清除該語系上傳檔 */

    function filedown_clear_lang_file(

        int $parentPKey,

        int $langIndex,

        string $tableImg,

        string $tableLang,

        string $fk,

        string $uploadBase

    ): void {

        if ($parentPKey <= 0 || $langIndex <= 0) {

            return;

        }

        if (!crud_is_safe_sql_identifier($tableImg) || !crud_is_safe_sql_identifier($tableLang) || !crud_is_safe_sql_identifier($fk)) {

            return;

        }



        $existing = crud_fetch_one(

            "SELECT PKey, Forder, Photo1 FROM {$tableImg} WHERE {$fk} = :fk AND Sort = :sort LIMIT 1",

            ['fk' => $parentPKey, 'sort' => $langIndex]

        );

        if ($existing !== null) {

            crud_delete_img_row($tableImg, (int)$existing['PKey']);

        }



        $langRow = crud_fetch_one(

            "SELECT PKey FROM {$tableLang} WHERE {$fk} = :fk AND intLang = :lang LIMIT 1",

            ['fk' => $parentPKey, 'lang' => $langIndex]

        );

        if ($langRow !== null) {

            $clear = ['FileName' => '', 'FileSize' => '', 'Forder' => ''];

            $clear = crud_filter_row_for_table($tableLang, $clear);

            if ($clear !== []) {

                $pdo = new dbPDO();

                $pdo->update($tableLang, $clear, 'PKey', (int)$langRow['PKey']);

                $pdo->close();

            }

        }

    }

}



if (!function_exists('filedown_sync_lang_file_meta')) {

    /** 上傳成功後同步 filedown_lang 的檔案欄位 */

    function filedown_sync_lang_file_meta(

        int $parentPKey,

        int $langIndex,

        string $tableLang,

        string $tableImg,

        string $fk,

        string $uploadBase

    ): void {

        if ($parentPKey <= 0 || $langIndex <= 0) {

            return;

        }

        if (!crud_is_safe_sql_identifier($tableLang) || !crud_is_safe_sql_identifier($tableImg) || !crud_is_safe_sql_identifier($fk)) {

            return;

        }



        $imgRow = crud_fetch_one(

            "SELECT Forder, Photo1 FROM {$tableImg} WHERE {$fk} = :fk AND Sort = :sort LIMIT 1",

            ['fk' => $parentPKey, 'sort' => $langIndex]

        );

        if ($imgRow === null || trim((string)($imgRow['Photo1'] ?? '')) === '') {

            return;

        }



        $forder   = trim((string)($imgRow['Forder'] ?? ''));

        $fileName = trim((string)($imgRow['Photo1'] ?? ''));

        $fileSize = '';

        if ($forder !== '' && $fileName !== '') {

            $fullPath = rtrim($uploadBase, "\\/") . DIRECTORY_SEPARATOR . $forder . DIRECTORY_SEPARATOR . $fileName;

            if (is_file($fullPath)) {

                $bytes = filesize($fullPath);

                if ($bytes !== false) {

                    $fileKB = (int)floor($bytes / 1024);
                    if ($fileKB > 999) {
                        $fileMB = round($fileKB / 1000, 2);
                        $fileSize = number_format($fileMB, 2, '.', '') . ' MB';
                    } else {
                        $fileSize = (string)$fileKB . ' KB';
                    }

                }

            }

        }



        $langRow = crud_fetch_one(

            "SELECT PKey FROM {$tableLang} WHERE {$fk} = :fk AND intLang = :lang LIMIT 1",

            ['fk' => $parentPKey, 'lang' => $langIndex]

        );

        if ($langRow === null) {

            return;

        }



        $row = crud_filter_row_for_table($tableLang, [

            'FileName' => SqlFilter($fileName, 'tab'),

            'FileSize' => SqlFilter($fileSize, 'tab'),

            'Forder'   => SqlFilter($forder, 'tab'),

        ]);

        if ($row === []) {

            return;

        }

        $pdo = new dbPDO();

        $pdo->update($tableLang, $row, 'PKey', (int)$langRow['PKey']);

        $pdo->close();

    }

}


