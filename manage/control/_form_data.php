<?php
declare(strict_types=1);

if (!function_exists('control_detail_defaults')) {
    /** @return array<string, mixed> */
    function control_detail_defaults(): array {
        return [
            'Update_PKey' => 0,
            'strID'       => '',
            'strName'     => '',
            'strPW'       => '',
            'M1'          => [],
            'dtUDate'     => '',
            'UserID'      => '',
        ];
    }
}

if (!function_exists('control_detail_init_defaults')) {
    /** 初始化後台帳號表單預設變數 */
    function control_detail_init_defaults(): void {
        $GLOBALS['control_form_vars'] = control_detail_defaults();
        $GLOBALS['Update_PKey']       = 0;
    }
}

if (!function_exists('control_detail_export_vars')) {
    /** 將 control_form_vars 匯出至 $GLOBALS */
    function control_detail_export_vars(): void {
        $v = (array)($GLOBALS['control_form_vars'] ?? control_detail_defaults());
        foreach ($v as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($v['Update_PKey'] ?? 0);
    }
}

if (!function_exists('control_detail_load')) {
    /**
     * 載入 webcontrol 一筆
     */
    function control_detail_load(int $pkey): bool {
        if ($pkey <= 0) {
            return false;
        }
        $row = crud_fetch_one(
            'SELECT * FROM webcontrol WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
        if ($row === null) {
            return false;
        }

        $fnIds = array_filter(
            array_map('intval', explode(',', (string)($row['FunctionID'] ?? ''))),
            static fn(int $id): bool => $id > 0
        );

        $GLOBALS['control_form_vars'] = [
            'Update_PKey' => $pkey,
            'strID'       => (string)($row['strID'] ?? ''),
            'strName'     => (string)($row['strName'] ?? ''),
            'strPW'       => '',
            'M1'          => $fnIds,
            'dtUDate'     => (string)($row['dtUDate'] ?? ''),
            'UserID'      => (string)($row['UserID'] ?? ''),
        ];
        $GLOBALS['Update_PKey'] = $pkey;
        control_detail_export_vars();
        return true;
    }
}

if (!function_exists('control_list_apply_keyword_search')) {
    /** 帳號列表關鍵字／語意搜尋條件 */
    function control_list_apply_keyword_search(
        string &$where,
        array &$params,
        array $filter,
        string $placeholder = '請輸入名稱或帳號搜尋'
    ): string {
        $kw = trim(manage_list_search_filter_value($filter, 'Keywords'));
        $kw = mb_substr($kw, 0, 50);

        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        $hasKeyword = $kw !== '' && $kw !== $placeholder;

        if ((!$submitted && !$hasKeyword) || !$hasKeyword) {
            return $placeholder;
        }

        if (!function_exists('manage_semantic_apply_keyword_filter')) {
            require_once dirname(__DIR__, 2) . '/include/manage_semantic_search_helpers.php';
        }

        manage_semantic_apply_keyword_filter(
            $where,
            $params,
            $kw,
            ['strName', 'strID'],
            [
                'table' => 'webcontrol',
                'pk' => 'PKey',
                'base_where' => $where,
                'base_params' => $params,
            ]
        );

        return $kw;
    }
}

if (!function_exists('control_strid_exists')) {
    /** 帳號 strID 是否已被使用 */
    function control_strid_exists(string $strId, int $excludePKey = 0): bool {
        $strId = strtolower(trim($strId));
        if ($strId === '') {
            return false;
        }
        $sql = 'SELECT 1 AS ok FROM webcontrol WHERE LOWER(strID) = :strID';
        $params = ['strID' => $strId];
        if ($excludePKey > 0) {
            $sql .= ' AND PKey <> :pk';
            $params['pk'] = $excludePKey;
        }
        return crud_fetch_one($sql . ' LIMIT 1', $params) !== null;
    }
}

if (!function_exists('control_parse_function_fields')) {
    /**
     * @param list<mixed> $functions
     * @return array{ids: list<int>, names: list<string>}
     */
    function control_parse_function_fields(array $functions): array {
        $ids   = [];
        $names = [];
        foreach ($functions as $f) {
            $parts = explode('|', (string)$f, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $fid   = trim($parts[0]);
            $fname = trim($parts[1]);
            if (!ctype_digit($fid)) {
                continue;
            }
            $ids[]   = (int)$fid;
            $names[] = str_replace(['|', ','], ['／', '，'], strip_tags($fname));
        }
        return [
            'ids'   => array_values(array_unique($ids)),
            'names' => array_values(array_unique($names)),
        ];
    }
}

if (!function_exists('control_validate_form')) {
    /** @param array<string, mixed> $filter */
    function control_validate_form(array $filter, int $formPKey): string {
        $msg       = '';
        $strName   = trim((string)($filter['strName'] ?? ''));
        $strID     = strtolower(trim((string)($filter['strID'] ?? '')));
        $strPW     = trim((string)($filter['strPW'] ?? ''));
        $functions = $filter['FunctionName'] ?? [];
        $isNew     = $formPKey <= 0;

        if ($strName === '') {
            $msg .= "【姓名】為空白\n";
        }

        if (strlen($strID) > 20) {
            $msg .= "【帳號】長度不可超過 20 碼\n";
        }
        if (!preg_match('/^[a-z0-9]{2,20}$/', $strID)) {
            $msg .= "【帳號】空白或格式錯誤（限 2~20 碼英文或數字）\n";
        } elseif (control_strid_exists($strID, $formPKey)) {
            $msg .= "【帳號】已被申請過\n";
        }

        $policy = is_numeric($GLOBALS['PW_Match'] ?? null) ? (int)$GLOBALS['PW_Match'] : 2;
        if ($isNew) {
            if ($strPW === '') {
                $msg .= "【密碼】不可空白\n";
            } else {
                $msg .= crud_validate_password_complexity($strPW, $policy);
            }
        } elseif ($strPW !== '') {
            $msg .= crud_validate_password_complexity($strPW, $policy);
        }

        if (!is_array($functions) || $functions === []) {
            $msg .= "【權限】請選擇\n";
        } else {
            $parsed = control_parse_function_fields($functions);
            if ($parsed['ids'] === []) {
                $msg .= "【權限】格式錯誤\n";
            }
        }

        return $msg;
    }
}

if (!function_exists('control_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function control_build_master_data(
        array $filter,
        int $modulePKey,
        string $loginId,
        bool $hashPassword
    ): array {
        $parsed = control_parse_function_fields(
            is_array($filter['FunctionName'] ?? null) ? $filter['FunctionName'] : []
        );
        $fn = implode(',', $parsed['names']);
        $fi = implode(',', $parsed['ids']);
        if (strlen($fn) > 1000) {
            $fn = substr($fn, 0, 1000);
        }
        if (strlen($fi) > 1000) {
            $fi = substr($fi, 0, 1000);
        }

        $data = [
            'Module_PKey'  => SqlFilter($modulePKey, 'int'),
            'strID'        => SqlFilter(strtolower(trim((string)($filter['strID'] ?? ''))), 'tab'),
            'strName'      => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'FunctionName' => $fn,
            'FunctionID'   => $fi,
            'dtUDate'      => date('Y-m-d H:i:s'),
            'UserID'       => SqlFilter($loginId, 'tab'),
        ];

        if ($hashPassword) {
            $data['strPW']    = crud_hash_password(trim((string)($filter['strPW'] ?? '')));
            $data['isLock']   = 0;
            $data['error']    = 0;
        }

        return $data;
    }
}
