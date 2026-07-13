<?php
declare(strict_types=1);

if (!function_exists('epaper_detail_defaults')) {
    /** @return array<string, mixed> */
    function epaper_detail_defaults(): array
    {
        return [
            'Update_PKey' => 0,
            'EMail'       => '',
            'dtUDate'     => '',
            'dtDate'      => '',
            'UserID'      => '',
        ];
    }
}

if (!function_exists('epaper_detail_init_defaults')) {
    function epaper_detail_init_defaults(): void
    {
        $GLOBALS['epaper_form_vars'] = epaper_detail_defaults();
        epaper_detail_export_vars();
    }
}

if (!function_exists('epaper_detail_export_vars')) {
    function epaper_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['epaper_form_vars'] ?? epaper_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['epaper_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('epaper_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function epaper_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['epaper_form_vars'];
        foreach (['EMail', 'dtUDate', 'dtDate', 'UserID'] as $f) {
            if (array_key_exists($f, $row)) {
                $v[$f] = (string)$row[$f];
            }
        }
        epaper_detail_export_vars();
    }
}

if (!function_exists('epaper_detail_resolve_module_pkey')) {
    function epaper_detail_resolve_module_pkey(): int
    {
        $mpk = (int)($GLOBALS['Module_PKey'] ?? 0);
        if ($mpk > 0) {
            return $mpk;
        }
        global $filter_array;
        return safe_int($_GET['manNo'] ?? $filter_array['manNo'] ?? 0);
    }
}

if (!function_exists('epaper_detail_load')) {
    function epaper_detail_load(int $pkey, ?int $modulePKey = null): bool
    {
        if ($pkey <= 0) {
            return false;
        }
        $row = crud_fetch_one('SELECT * FROM epaper WHERE PKey = :pk LIMIT 1', ['pk' => $pkey]);
        if ($row === null) {
            return false;
        }
        if ($modulePKey !== null && $modulePKey > 0) {
            $rowModule = (int)($row['Module_PKey'] ?? 0);
            if ($rowModule !== $modulePKey) {
                return false;
            }
        }
        if (!isset($GLOBALS['epaper_form_vars'])) {
            epaper_detail_init_defaults();
        }
        $GLOBALS['epaper_form_vars']['Update_PKey'] = $pkey;
        epaper_detail_apply_master($row);
        return true;
    }
}

if (!function_exists('epaper_validate_form')) {
    /** @param array<string, mixed> $filter */
    function epaper_validate_form(array $filter, int $modulePKey): string
    {
        $msg = '';
        $email = trim((string)($filter['EMail'] ?? ''));

        if ($email === '') {
            $msg .= "【E-Mail】為空白\n";
        } elseif (!function_exists('CheckMail') || !CheckMail($email)) {
            $msg .= "【E-Mail】格式錯誤\n";
        }

        if ($modulePKey <= 0) {
            $msg .= "【模組】參數錯誤\n";
        }

        return $msg;
    }
}

if (!function_exists('epaper_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function epaper_build_master_data(array $filter, int $modulePKey, string $loginId): array
    {
        return [
            'Module_PKey' => SqlFilter($modulePKey, 'int'),
            'EMail'       => SqlFilter(trim((string)($filter['EMail'] ?? '')), 'tab'),
            'dtUDate'     => date('Y-m-d H:i:s'),
            'UserID'      => SqlFilter($loginId, 'tab'),
        ];
    }
}

if (!function_exists('epaper_list_apply_keyword_search')) {
    /** 列表 E-mail 關鍵字搜尋 */
    function epaper_list_apply_keyword_search(
        string &$where,
        array &$params,
        array $filter,
        string $placeholder = '請輸入 E-Mail 搜尋'
    ): string {
        $kw = trim(manage_list_search_filter_value($filter, 'Keywords'));
        $kw = mb_substr($kw, 0, 100);

        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        $hasKeyword = $kw !== '' && $kw !== $placeholder;

        if ($submitted && $hasKeyword) {
            $params['Keyword'] = $kw;
            $where .= ' AND LOCATE(:Keyword, EMail) > 0';
        }

        if ($kw === '' || (!$submitted && !$hasKeyword)) {
            return $placeholder;
        }

        return $kw;
    }
}
