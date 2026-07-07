<?php
declare(strict_types=1);
/**
 * member 表單資料：預設值與自 DB 載入
 */

if (!function_exists('member_detail_tables')) {
    /** 讀取 member/_config.php 子表設定 */
    function member_detail_tables(): array
    {
        return manage_detail_tables();
    }
}

if (!function_exists('member_detail_defaults')) {
    /** @return array<string, mixed> */
    function member_detail_defaults(): array
    {
        return [
            'Update_PKey' => 0,
            'intLang'     => 1,
            'EMail'       => '',
            'strPW'       => '',
            'strName'     => '',
            'Sex'         => '',
            'Birth_Y'     => '',
            'Birth_M'     => '',
            'Birth_D'     => '',
            'Tel'         => '',
            'Mobile'      => '',
            'PostCode'    => '',
            'strCounty'   => '',
            'strCity'     => '',
            'Address'     => '',
            'ePaper'      => 'Yes',
            'isCheck'     => 'Yes',
            'dtUDate'     => '',
            'dtDate'      => '',
            'UserID'      => '',
        ];
    }
}

if (!function_exists('member_detail_init_defaults')) {
    /** 初始化會員表單預設變數 */
    function member_detail_init_defaults(): void
    {
        $GLOBALS['member_form_vars'] = member_detail_defaults();
        member_detail_export_vars();
    }
}

if (!function_exists('member_detail_export_vars')) {
    /** 將 member_form_vars 匯出至 $GLOBALS */
    function member_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['member_form_vars'] ?? member_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['member_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('member_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function member_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['member_form_vars'];
        $fields = [
            'intLang', 'EMail', 'strName', 'Sex', 'Birth_Y', 'Birth_M', 'Birth_D',
            'Tel', 'Mobile', 'PostCode', 'strCounty', 'strCity', 'Address',
            'ePaper', 'isCheck', 'dtUDate', 'dtDate', 'UserID',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $row)) {
                $v[$f] = (string)$row[$f];
            }
        }
        $v['strPW'] = '';
        member_detail_export_vars();
    }
}

if (!function_exists('member_detail_resolve_module_pkey')) {
    /** 解析目前單元 Module_PKey（manNo） */
    function member_detail_resolve_module_pkey(): int
    {
        $mpk = (int)($GLOBALS['Module_PKey'] ?? 0);
        if ($mpk > 0) {
            return $mpk;
        }
        global $filter_array;
        return safe_int($_GET['manNo'] ?? $filter_array['manNo'] ?? 0);
    }
}

if (!function_exists('member_detail_load')) {
    /** 自 DB 載入一筆會員主檔 */
    function member_detail_load(int $pkey, ?int $modulePKey = null): bool
    {
        if ($pkey <= 0) {
            return false;
        }
        $tables = member_detail_tables();
        $master = (string)($tables['master'] ?? 'member');
        $moduleCol = (string)($tables['module_pk_col'] ?? 'Module_PKey');
        $row = crud_fetch_one("SELECT * FROM {$master} WHERE PKey = :pk LIMIT 1", ['pk' => $pkey]);
        if ($row === null) {
            return false;
        }
        if ($modulePKey !== null && $modulePKey > 0) {
            $rowModule = (int)($row[$moduleCol] ?? 0);
            $manNo = safe_int($_GET['manNo'] ?? ($GLOBALS['filter_array']['manNo'] ?? 0));
            if ($rowModule !== $modulePKey && !($manNo > 0 && $rowModule === $manNo)) {
                return false;
            }
        }
        if (!isset($GLOBALS['member_form_vars'])) {
            member_detail_init_defaults();
        }
        $GLOBALS['member_form_vars']['Update_PKey'] = $pkey;
        member_detail_apply_master($row);
        return true;
    }
}

if (!function_exists('member_email_exists')) {
    /** Email 是否已被其他會員使用 */
    function member_email_exists(string $email, int $excludePKey = 0): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        $sql = 'SELECT PKey FROM member WHERE EMail = :em';
        $params = ['em' => $email];
        if ($excludePKey > 0) {
            $sql .= ' AND PKey <> :pk';
            $params['pk'] = $excludePKey;
        }
        $sql .= ' LIMIT 1';
        return crud_fetch_one($sql, $params) !== null;
    }
}

if (!function_exists('member_validate_form')) {
    /** @param array<string, mixed> $filter */
    function member_validate_form(array $filter, int $formPKey, int $modulePKey): string
    {
        $msg = '';
        $email = trim((string)($filter['EMail'] ?? ''));
        $strName = trim((string)($filter['strName'] ?? ''));
        $mobile = trim((string)($filter['Mobile'] ?? ''));
        $strPW = trim((string)($filter['strPW'] ?? ''));
        $isNew = $formPKey <= 0;

        if ($email === '') {
            $msg .= "【會員帳號（Email）】為空白\n";
        } elseif (!function_exists('CheckMail') || !CheckMail($email)) {
            $msg .= "【會員帳號（Email）】格式錯誤\n";
        } elseif (member_email_exists($email, $formPKey)) {
            $msg .= "【會員帳號（Email）】已被使用，不得重複\n";
        }

        if ($strName === '') {
            $msg .= "【姓名】為空白\n";
        }

        if ($mobile === '') {
            $msg .= "【手機號碼】為空白\n";
        } elseif (!function_exists('chkMobile') || !chkMobile($mobile)) {
            $msg .= "【手機號碼】格式錯誤（請輸入 09 開頭共 10 碼）\n";
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

        $county = trim((string)($filter['strCounty'] ?? ''));
        $city = trim((string)($filter['strCity'] ?? ''));
        $address = trim((string)($filter['Address'] ?? ''));
        if ($county === '' || $county === '請選擇') {
            $msg .= "【地址】請選擇縣市\n";
        }
        if ($city === '' || $city === '請選擇') {
            $msg .= "【地址】請選擇鄉鎮市區\n";
        }
        if ($address === '') {
            $msg .= "【地址】請輸入詳細地址\n";
        }

        if ($modulePKey <= 0) {
            $msg .= "【模組】參數錯誤\n";
        }

        return $msg;
    }
}

if (!function_exists('member_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function member_build_master_data(array $filter, int $modulePKey, string $loginId, bool $hashPassword): array
    {
        $data = [
            'Module_PKey' => SqlFilter($modulePKey, 'int'),
            'intLang'     => SqlFilter($filter['intLang'] ?? 1, 'int'),
            'EMail'       => SqlFilter(trim((string)($filter['EMail'] ?? '')), 'tab'),
            'strName'     => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'Sex'         => SqlFilter(trim((string)($filter['Sex'] ?? '')), 'tab'),
            'Birth_Y'     => SqlFilter(trim((string)($filter['Birth_Y'] ?? '')), 'tab'),
            'Birth_M'     => SqlFilter(trim((string)($filter['Birth_M'] ?? '')), 'tab'),
            'Birth_D'     => SqlFilter(trim((string)($filter['Birth_D'] ?? '')), 'tab'),
            'Tel'         => SqlFilter(trim((string)($filter['Tel'] ?? '')), 'tab'),
            'Mobile'      => SqlFilter(trim((string)($filter['Mobile'] ?? '')), 'tab'),
            'PostCode'    => SqlFilter(trim((string)($filter['PostCode'] ?? '')), 'tab'),
            'strCounty'   => SqlFilter(trim((string)($filter['strCounty'] ?? '')), 'tab'),
            'strCity'     => SqlFilter(trim((string)($filter['strCity'] ?? '')), 'tab'),
            'Address'     => SqlFilter(trim((string)($filter['Address'] ?? '')), 'tab'),
            'dtUDate'     => date('Y-m-d H:i:s'),
            'UserID'      => SqlFilter($loginId, 'tab'),
        ];
        if ($hashPassword) {
            $data['strPW'] = crud_hash_password(trim((string)($filter['strPW'] ?? '')));
        }
        return $data;
    }
}

if (!function_exists('member_list_apply_keyword_search')) {
    /** 會員列表關鍵字／語意搜尋條件 */
    function member_list_apply_keyword_search(
        string &$where,
        array &$params,
        array $filter,
        string $placeholder = '請輸入姓名或帳號搜尋'
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
            ['strName', 'EMail', 'Mobile'],
            [
                'table' => 'member',
                'pk' => 'PKey',
                'base_where' => $where,
                'base_params' => $params,
            ]
        );

        return $kw;
    }
}
