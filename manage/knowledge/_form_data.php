<?php
declare(strict_types=1);
/**
 * knowledge 表單資料：預設值與自 DB 載入（供 add.php / update.php 使用）
 */

if (!function_exists('knowledge_detail_tables')) {
    function knowledge_detail_tables(): array
    {
        return manage_detail_tables();
    }
}

if (!function_exists('knowledge_detail_defaults')) {
    /** @return array<string, mixed> */
    function knowledge_detail_defaults(): array
    {
        $defaults = [
            'Update_PKey'    => 0,
            'Knowledge_PKey' => 0,
            'Sort'           => 0,
            'Class1'         => 0,
            'strName'        => '',
            'intLink'        => 2,
            'strLink'        => '',
            'intSource'      => 0,
            'Upload'         => 'Yes',
            'dtUDate'        => '',
            'UserID'         => '',
            'Photo'          => [],
            'PhotoS'         => [],
            'PhotoM'         => [],
            'Ext'            => [],
        ];
        for ($i = 1; $i <= 6; $i++) {
            $defaults['Contents' . $i] = '';
            $defaults['isShow' . $i]   = 1;
        }
        return $defaults;
    }
}

if (!function_exists('knowledge_detail_init_defaults')) {
    function knowledge_detail_init_defaults(): void
    {
        $GLOBALS['knowledge_form_vars'] = knowledge_detail_defaults();
        knowledge_detail_export_vars();
    }
}

if (!function_exists('knowledge_detail_export_vars')) {
    function knowledge_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['knowledge_form_vars'] ?? knowledge_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['knowledge_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('knowledge_detail_ext_from_photo')) {
    /** @param array<int, string> $photo */
    function knowledge_detail_ext_from_photo(array $photo): array
    {
        $ext = [];
        foreach ($photo as $i => $path) {
            $path = (string)$path;
            if ($path === '') {
                continue;
            }
            $ext[$i] = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }
        return $ext;
    }
}

if (!function_exists('knowledge_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function knowledge_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['knowledge_form_vars'];

        $pkey = (int)($row['PKey'] ?? 0);
        $v['Knowledge_PKey'] = $pkey;
        $v['Sort']           = (int)($row['Sort'] ?? 0);
        $v['Class1']         = (int)($row['Class1_PKey'] ?? 0);
        $v['strName']        = (string)($row['strName'] ?? '');
        $v['intLink']        = (int)($row['intLink'] ?? 2);
        if ($v['intLink'] <= 0) {
            $v['intLink'] = 2;
        }
        $v['strLink']   = (string)($row['strLink'] ?? '');
        $v['intSource'] = (int)($row['intSource'] ?? 0);
        $v['Upload']    = (string)($row['Upload'] ?? 'Yes');
        $v['dtUDate']   = (string)($row['dtUDate'] ?? '');
        $v['UserID']    = (string)($row['UserID'] ?? '');

        knowledge_detail_export_vars();
    }
}

if (!function_exists('knowledge_detail_load_children')) {
    function knowledge_detail_load_children(int $pkey): void
    {
        $tables = knowledge_detail_tables();
        $fk     = (string)($tables['fk'] ?? 'Knowledge_PKey');
        $v      = &$GLOBALS['knowledge_form_vars'];

        if (($tables['msg'] ?? '') !== '') {
            $msgData = crud_load_msg_blocks_data((string)$tables['msg'], $fk, $pkey);
            for ($i = 1; $i <= 6; $i++) {
                $v['Contents' . $i] = $msgData['contents'][$i] ?? '';
                $v['isShow' . $i]   = $msgData['isShow'][$i] ?? 1;
            }
        }

        if (($tables['img'] ?? '') !== '') {
            $imgData = crud_load_img_slots_data((string)$tables['img'], $fk, $pkey);
            $v['Photo']  = $imgData['Photo'];
            $v['PhotoS'] = $imgData['PhotoS'];
            $v['PhotoM'] = $imgData['PhotoM'];
            $v['Ext']    = knowledge_detail_ext_from_photo($imgData['Photo']);
        }

        knowledge_detail_export_vars();
    }
}

if (!function_exists('knowledge_detail_resolve_module_pkey')) {
    function knowledge_detail_resolve_module_pkey(): int
    {
        $mpk = (int)($GLOBALS['Module_PKey'] ?? 0);
        if ($mpk > 0) {
            return $mpk;
        }
        global $filter_array;
        return safe_int($_GET['manNo'] ?? $filter_array['manNo'] ?? 0);
    }
}

if (!function_exists('knowledge_detail_load')) {
    /**
     * @param int      $pkey
     * @param int|null $modulePKey 編輯時比對 Module_PKey
     * @param bool     $forCopy     複製新增：不帶 PKey／附件
     */
    function knowledge_detail_load(int $pkey, ?int $modulePKey = null, bool $forCopy = false): bool
    {
        if ($pkey <= 0) {
            return false;
        }

        $tables = knowledge_detail_tables();
        $master = (string)($tables['master'] ?? 'knowledge');
        $row    = crud_fetch_one("SELECT * FROM {$master} WHERE PKey = :pk LIMIT 1", ['pk' => $pkey]);
        if ($row === null) {
            return false;
        }

        if ($modulePKey !== null && $modulePKey > 0) {
            $rowModule = (int)($row['Module_PKey'] ?? 0);
            $manNo     = safe_int($_GET['manNo'] ?? ($GLOBALS['filter_array']['manNo'] ?? 0));
            $moduleOk  = ($rowModule === $modulePKey)
                || ($manNo > 0 && $rowModule === $manNo);
            if (!$moduleOk) {
                return false;
            }
        }

        if (!isset($GLOBALS['knowledge_form_vars'])) {
            knowledge_detail_init_defaults();
        }

        $GLOBALS['knowledge_form_vars']['Update_PKey'] = $forCopy ? 0 : $pkey;
        knowledge_detail_apply_master($row);
        knowledge_detail_load_children($pkey);

        if ($forCopy) {
            $v = &$GLOBALS['knowledge_form_vars'];
            $v['Knowledge_PKey'] = 0;
            $v['Photo']          = [];
            $v['PhotoS']         = [];
            $v['PhotoM']         = [];
            $v['Ext']            = [];
            knowledge_detail_export_vars();
        }

        return true;
    }
}
