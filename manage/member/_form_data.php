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

if (!function_exists('member_sec_encrypt_field')) {
    /** 敏感欄位寫入 DB 前加密；空白維持空白 */
    function member_sec_encrypt_field(string $plaintext): string
    {
        $plaintext = trim($plaintext);
        if ($plaintext === '') {
            return '';
        }
        if (!function_exists('sec_encrypt')) {
            return $plaintext;
        }

        return sec_encrypt($plaintext);
    }
}

if (!function_exists('member_sec_decrypt_field')) {
    /**
     * 敏感欄位自 DB 取出後解密；失敗時相容尚未加密的舊明文
     */
    function member_sec_decrypt_field(string $ciphertext): string
    {
        $ciphertext = trim($ciphertext);
        if ($ciphertext === '') {
            return '';
        }
        if (function_exists('sec_decrypt')) {
            $plain = sec_decrypt($ciphertext);
            if ($plain !== '') {
                return $plain;
            }
        }
        // 舊資料可能仍為明文（Email / 電話）
        if (str_contains($ciphertext, '@')
            || (bool)preg_match('/^[0-9+\-\s()]{6,20}$/', $ciphertext)
        ) {
            return $ciphertext;
        }

        return '';
    }
}

if (!function_exists('member_decrypt_row_sensitive')) {
    /**
     * 解密列上的 EMail / Tel / Mobile（就地回傳新陣列）
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    function member_decrypt_row_sensitive(array $row): array
    {
        foreach (['EMail', 'Tel', 'Mobile'] as $f) {
            if (array_key_exists($f, $row)) {
                $row[$f] = member_sec_decrypt_field((string)$row[$f]);
            }
        }

        return $row;
    }
}

if (!function_exists('member_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function member_detail_apply_master(array $row): void
    {
        $row = member_decrypt_row_sensitive($row);
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
    /**
     * Email 是否已被其他會員使用
     * （EMail 為 sec_encrypt 密文，需解密後比對）
     */
    function member_email_exists(string $email, int $excludePKey = 0): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        $emailNorm = strtolower($email);
        $sql = 'SELECT PKey, EMail FROM member';
        $params = [];
        if ($excludePKey > 0) {
            $sql .= ' WHERE PKey <> :pk';
            $params['pk'] = $excludePKey;
        }
        $rows = crud_fetch_all($sql, $params);
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $stored = strtolower(member_sec_decrypt_field((string)($row['EMail'] ?? '')));
            if ($stored !== '' && $stored === $emailNorm) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('member_find_pkeys_by_sensitive_keyword')) {
    /**
     * 以關鍵字比對解密後的 EMail / Mobile / Tel，回傳符合的 PKey 清單
     *
     * @return list<int>
     */
    function member_find_pkeys_by_sensitive_keyword(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }
        $needle = str_replace(' ', '', mb_strtolower($keyword));
        $rows = crud_fetch_all('SELECT PKey, EMail, Mobile, Tel FROM member', []);
        $pkeys = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hay = str_replace(' ', '', mb_strtolower(
                member_sec_decrypt_field((string)($row['EMail'] ?? ''))
                . member_sec_decrypt_field((string)($row['Mobile'] ?? ''))
                . member_sec_decrypt_field((string)($row['Tel'] ?? ''))
            ));
            if ($hay !== '' && mb_strpos($hay, $needle) !== false) {
                $pkeys[] = (int)($row['PKey'] ?? 0);
            }
        }

        return array_values(array_filter($pkeys, static fn(int $pk): bool => $pk > 0));
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
            'EMail'       => member_sec_encrypt_field((string)SqlFilter(trim((string)($filter['EMail'] ?? '')), 'tab')),
            'strName'     => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'Sex'         => SqlFilter(trim((string)($filter['Sex'] ?? '')), 'tab'),
            'Birth_Y'     => SqlFilter(trim((string)($filter['Birth_Y'] ?? '')), 'tab'),
            'Birth_M'     => SqlFilter(trim((string)($filter['Birth_M'] ?? '')), 'tab'),
            'Birth_D'     => SqlFilter(trim((string)($filter['Birth_D'] ?? '')), 'tab'),
            'Tel'         => member_sec_encrypt_field((string)SqlFilter(trim((string)($filter['Tel'] ?? '')), 'tab')),
            'Mobile'      => member_sec_encrypt_field((string)SqlFilter(trim((string)($filter['Mobile'] ?? '')), 'tab')),
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
    /**
     * 會員列表關鍵字／語意搜尋條件
     * 姓名走 SQL；EMail／Mobile／Tel 為密文，改解密後比對 PKey
     */
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

        if (!function_exists('manage_semantic_expand_terms')) {
            require_once dirname(__DIR__, 2) . '/include/manage_semantic_search_helpers.php';
        }

        $orParts = [];
        if (!function_exists('gemini_token_usage_bind_module')) {
            require_once dirname(__DIR__, 2) . '/include/gemini_token_usage.php';
        }
        gemini_token_usage_bind_module('member');
        $terms = manage_semantic_expand_terms($kw);
        $idx = 0;
        foreach ($terms as $term) {
            $paramKey = 'MemKw' . $idx++;
            $needle = str_replace(' ', '', $term);
            if (function_exists('manage_semantic_is_mostly_latin') && manage_semantic_is_mostly_latin($term)) {
                $needle = strtolower($needle);
                $params[$paramKey] = $needle;
                $orParts[] = "LOCATE(:{$paramKey}, REPLACE(LOWER(strName), ' ', '')) > 0";
            } else {
                $params[$paramKey] = $needle;
                $orParts[] = "LOCATE(:{$paramKey}, REPLACE(strName, ' ', '')) > 0";
            }
        }

        $matchedPks = member_find_pkeys_by_sensitive_keyword($kw);
        if ($matchedPks !== []) {
            $inParts = [];
            foreach ($matchedPks as $i => $pk) {
                $paramKey = 'MemEncPk' . $i;
                $params[$paramKey] = $pk;
                $inParts[] = ':' . $paramKey;
            }
            $orParts[] = 'PKey IN (' . implode(', ', $inParts) . ')';
        }

        if ($orParts !== []) {
            $where .= ' AND (' . implode(' OR ', $orParts) . ')';
        }

        return $kw;
    }
}

if (!function_exists('member_export_write_log')) {
    /**
     * 會員匯出稽核：寫入 managelog，並存檔至 Upload/export_log/
     * （不記錄 TOTP 驗證碼）
     *
     * @param array<string, mixed> $filter
     */
    function member_export_write_log(int $rowCount, string $exportFilename, array $filter = []): void
    {
        $loginId = (string)($_SESSION['Login_ID'] ?? $GLOBALS['Login_ID'] ?? '');
        $modulePKey = (int)($GLOBALS['Module_PKey'] ?? 0);
        $moduleName = trim((string)($GLOBALS['Module_Name'] ?? '會員'));
        if ($moduleName === '') {
            $moduleName = '會員';
        }
        $workFile = (string)($GLOBALS['WorkFile'] ?? 'manage/member/output.php');

        $kw = trim((string)($filter['Keywords'] ?? ''));
        $openDate = trim((string)($filter['OpenDate'] ?? ''));
        $endDate = trim((string)($filter['EndDate'] ?? ''));

        $detail = sprintf(
            'file=%s; rows=%d; keywords=%s; OpenDate=%s; EndDate=%s',
            $exportFilename,
            max(0, $rowCount),
            $kw,
            $openDate,
            $endDate
        );

        if (function_exists('manage_history')) {
            manage_history(
                $modulePKey,
                $moduleName,
                $detail,
                $workFile,
                $loginId !== '' ? $loginId : 'system',
                '匯出會員',
                'Upload/export_log'
            );
            return;
        }

        if (function_exists('write_log')) {
            write_log(
                Module_Name: $moduleName,
                MSG: '匯出會員',
                SqlCommand: $detail,
                strLink: $workFile,
                UserID: $loginId !== '' ? $loginId : 'system',
                LogType: 'web',
                subDir: 'Upload/export_log',
                filePrefix: 'member_export_'
            );
        }
    }
}
