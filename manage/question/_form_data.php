<?php

declare(strict_types=1);



require_once __DIR__ . '/_helpers.php';



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

            'Sort'        => 0,

            'strNo'       => '',

            'EMail'       => '',

            'OpenDate'    => date('Y-m-d'),

            'EndDate'     => '',

            'Upload'      => 'Yes',

            'Description' => [],

            'Keywords'    => [],

            'dtUDate'     => '',

            'UserID'      => '',

            'isShow'      => [],

            'strName'     => [],

            'Interview'   => [],

            'Contents'    => [],

            'Photo'       => [],

            'PhotoS'      => [],

            'PhotoM'      => [],

        ];



        for ($i = 1; $i <= $langCount; $i++) {

            $GLOBALS['class1_form_vars']['isShow'][$i] = '';

            $GLOBALS['class1_form_vars']['strName'][$i] = '';

            $GLOBALS['class1_form_vars']['Interview'][$i] = '';

            $GLOBALS['class1_form_vars']['Description'][$i] = '';

        }

        for ($n = 0; $n < 5; $n++) {

            $GLOBALS['class1_form_vars']['Keywords'][$n] = [];

            for ($i = 1; $i <= $langCount; $i++) {

                $GLOBALS['class1_form_vars']['Keywords'][$n][$i] = '';

            }

        }

        for ($i = 1; $i <= $langCount; $i++) {
            $GLOBALS['class1_form_vars']['Contents'][1][$i] = '';
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



        $v['Update_PKey'] = (int)($row['PKey'] ?? 0);

        $v['Sort']        = (int)($row['Sort'] ?? 0);

        $v['strNo']       = (string)($row['strNo'] ?? '');

        $v['EMail']       = (string)($row['EMail'] ?? '');

        $v['Upload']      = (string)($row['Upload'] ?? 'Yes');

        $v['dtUDate']     = (string)($row['dtUDate'] ?? '');

        $v['UserID']      = (string)($row['UserID'] ?? '');



        if (array_key_exists('OpenDate', $row)) {

            $v['OpenDate'] = (string)$row['OpenDate'];

        }

        if (array_key_exists('EndDate', $row)) {

            $v['EndDate'] = (string)$row['EndDate'];

        }

        if (isset($row['strName'])) {

            $v['strName'][1] = (string)$row['strName'];

        }



        class1_detail_export_vars();

    }

}



if (!function_exists('class1_detail_load_children')) {

    /** 載入子表資料（語系、圖片、問答內容等） */

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



        $msgByLang = question_load_msg_contents_all($pkey);
        for ($i = 1; $i <= $langCount; $i++) {
            $v['Contents'][1][$i] = (string)($msgByLang[$i] ?? '');
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



        $row = crud_fetch_one(

            'SELECT * FROM `' . $master . '` WHERE `PKey` = :PKey LIMIT 1',

            ['PKey' => $pkey]

        );

        if ($row === null || (int)($row['PKey'] ?? 0) <= 0) {

            return false;

        }



        if ($modulePKey > 0) {

            $rowModule = (int)($row[$moduleCol] ?? 0);

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

