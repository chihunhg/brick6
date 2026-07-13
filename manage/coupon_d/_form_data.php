<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/include/coupon_helpers.php';

if (!function_exists('coupon_d_view_from_sql')) {
    function coupon_d_view_from_sql(): string
    {
        return ' FROM coupon_d d'
            . ' LEFT JOIN member m ON m.PKey = d.Member_PKey'
            . ' LEFT JOIN coupon_p cp ON cp.PKey = d.Coupon_PKey'
            . ' LEFT JOIN order_p op ON op.PKey = d.Order_PKey';
    }
}

if (!function_exists('coupon_d_view_select_sql')) {
    function coupon_d_view_select_sql(): string
    {
        return 'SELECT d.PKey, d.Coupon_PKey, d.Coupon_Code, d.Member_PKey, d.Order_PKey,'
            . ' d.OrderNo, d.OpenDate, d.EndDate, d.dtDate,'
            . ' m.EMail AS Email, m.EMail AS EMail, m.strName AS Member_Name,'
            . ' cp.Price, cp.OpenDate AS OpenDate, cp.EndDate AS EndDate,'
            . ' cp.OpenDate AS Coupon_OpenDate, cp.EndDate AS Coupon_EndDate,'
            . ' op.dtDate AS Order_Date, op.TotalPrice, op.Coupon_Price'
            . coupon_d_view_from_sql();
    }
}

if (!function_exists('coupon_d_resolve_coupon_pkey')) {
    function coupon_d_resolve_coupon_pkey(array $filter): int
    {
        $pkey = safe_int($filter['Coupon_PKey'] ?? 0);
        if ($pkey <= 0) {
            $pkey = safe_int($filter['PKey'] ?? 0);
        }
        if ($pkey <= 0) {
            $pkey = safe_int($_GET['Coupon_PKey'] ?? $_GET['PKey'] ?? 0);
        }
        return $pkey;
    }
}

if (!function_exists('coupon_d_load_parent')) {
    /** @return array<string, mixed>|null */
    function coupon_d_load_parent(int $couponPKey): ?array
    {
        if ($couponPKey <= 0) {
            return null;
        }
        $row = crud_fetch_one(
            'SELECT PKey, strName, Module_PKey FROM coupon_p WHERE PKey = :pk LIMIT 1',
            ['pk' => $couponPKey]
        );
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('coupon_d_list_back_url')) {
    function coupon_d_list_back_url(int $couponPKey, array $filter = []): string
    {
        $manNo = safe_int($filter['manNo'] ?? $_GET['manNo'] ?? 0);
        $subNo = safe_int($filter['subNo'] ?? $_GET['subNo'] ?? 0);
        $url = 'list.php?Coupon_PKey=' . $couponPKey;
        if ($manNo > 0) {
            $url .= '&manNo=' . $manNo;
        }
        if ($subNo > 0) {
            $url .= '&subNo=' . $subNo;
        }
        return $url;
    }
}

if (!function_exists('coupon_d_parent_list_url')) {
    function coupon_d_parent_list_url(array $config, array $filter = []): string
    {
        $base = (string)($config['parent_list'] ?? '../couponreg/list.php');
        $manNo = safe_int($filter['manNo'] ?? $_GET['manNo'] ?? 0);
        $subNo = safe_int($filter['subNo'] ?? $_GET['subNo'] ?? 0);
        if ($manNo <= 0) {
            return $base;
        }
        $url = $base . (str_contains($base, '?') ? '&' : '?') . 'manNo=' . $manNo;
        if ($subNo > 0) {
            $url .= '&subNo=' . $subNo;
        }
        return $url;
    }
}

if (!function_exists('coupon_d_build_list_where')) {
    /** @return array{0:string,1:array<string,mixed>,2:string} */
    function coupon_d_build_list_where(int $couponPKey, array $filter): array
    {
        $where = ' WHERE d.Coupon_PKey = :Coupon_PKey';
        $params = ['Coupon_PKey' => $couponPKey];
        $placeholder = '請輸入姓名或訂單編號搜尋';
        $kw = trim(manage_list_search_filter_value($filter, 'Keywords'));
        $kw = mb_substr($kw, 0, 50);
        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        $hasKeyword = $kw !== '' && $kw !== $placeholder;
        if ($submitted && $hasKeyword) {
            $params['Keyword1'] = $kw;
            $params['Keyword2'] = $kw;
            $where .= ' AND (LOCATE(:Keyword1, m.strName) > 0 OR LOCATE(:Keyword2, d.OrderNo) > 0)';
        }
        if ($kw === '' || (!$submitted && !$hasKeyword)) {
            $kw = $placeholder;
        }
        return [$where, $params, $kw];
    }
}

if (!function_exists('coupon_d_fetch_list_rows')) {
    /** @return list<array<string, mixed>> */
    function coupon_d_fetch_list_rows(string $where, array $params, int $limit, int $offset): array
    {
        if (function_exists('chkTable') && chkTable('view_coupon_d')) {
            $viewWhere = str_replace(['d.Coupon_PKey', 'm.strName', 'd.OrderNo'], ['Coupon_PKey', 'Member_Name', 'OrderNo'], $where);
            return crud_fetch_all(
                'SELECT * FROM view_coupon_d ' . $viewWhere . ' ORDER BY PKey DESC LIMIT '
                . (int)$limit . ' OFFSET ' . (int)$offset,
                $params
            );
        }
        return crud_fetch_all(
            coupon_d_view_select_sql() . $where . ' ORDER BY d.PKey DESC LIMIT '
            . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }
}

if (!function_exists('coupon_d_count_list_rows')) {
    function coupon_d_count_list_rows(string $where, array $params): int
    {
        if (function_exists('chkTable') && chkTable('view_coupon_d')) {
            $viewWhere = str_replace(['d.Coupon_PKey', 'm.strName', 'd.OrderNo'], ['Coupon_PKey', 'Member_Name', 'OrderNo'], $where);
            return (int)crud_fetch_scalar(
                'SELECT COUNT(PKey) AS Total FROM view_coupon_d ' . $viewWhere,
                $params,
                'Total'
            );
        }
        return (int)crud_fetch_scalar(
            'SELECT COUNT(d.PKey) AS Total ' . coupon_d_view_from_sql() . $where,
            $params,
            'Total'
        );
    }
}

if (!function_exists('coupon_d_fetch_export_rows')) {
    /** @return list<array<string, mixed>> */
    function coupon_d_fetch_export_rows(int $couponPKey): array
    {
        $where = ' WHERE d.Coupon_PKey = :Coupon_PKey';
        $params = ['Coupon_PKey' => $couponPKey];
        if (function_exists('chkTable') && chkTable('view_coupon_d')) {
            return crud_fetch_all(
                'SELECT * FROM view_coupon_d WHERE Coupon_PKey = :Coupon_PKey ORDER BY PKey DESC',
                $params
            );
        }
        return crud_fetch_all(
            coupon_d_view_select_sql() . $where . ' ORDER BY d.PKey DESC',
            $params
        );
    }
}

if (!function_exists('coupon_d_member_exists_by_email')) {
    /** @return array<string, mixed>|null */
    function coupon_d_member_exists_by_email(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $row = crud_fetch_one('SELECT PKey, strName, EMail FROM member WHERE EMail = :em LIMIT 1', ['em' => $email]);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('coupon_d_already_assigned')) {
    function coupon_d_already_assigned(int $couponPKey, int $memberPKey): bool
    {
        if ($couponPKey <= 0 || $memberPKey <= 0) {
            return true;
        }
        return crud_fetch_one(
            'SELECT PKey FROM coupon_d WHERE Coupon_PKey = :cpk AND Member_PKey = :mpk LIMIT 1',
            ['cpk' => $couponPKey, 'mpk' => $memberPKey]
        ) !== null;
    }
}

if (!function_exists('coupon_d_insert_member')) {
    function coupon_d_insert_member(int $couponPKey, int $memberPKey, string $couponCode = ''): bool
    {
        $data = [
            'Coupon_PKey' => SqlFilter($couponPKey, 'int'),
            'Member_PKey' => SqlFilter($memberPKey, 'int'),
            'dtDate'      => date('Y-m-d H:i:s'),
        ];
        if ($couponCode !== '') {
            $data['Coupon_Code'] = SqlFilter($couponCode, 'tab');
        }
        $pdo = new dbPDO();
        $pdo->insert('coupon_d', $data);
        $err = $pdo->getErrorMessage();
        $pdo->close();
        return $err === '';
    }
}

if (!function_exists('coupon_d_import_emails')) {
    /** @return array{lines:int, success:int, failed:int} */
    function coupon_d_import_emails(int $couponPKey, array $emails, string $couponCode = ''): array
    {
        $lines = count($emails);
        $success = 0;
        $failed = 0;
        foreach ($emails as $rawEmail) {
            $email = trim((string)$rawEmail);
            if ($email === '') {
                continue;
            }
            $member = coupon_d_member_exists_by_email($email);
            if ($member === null) {
                $failed++;
                continue;
            }
            $memberPKey = (int)($member['PKey'] ?? 0);
            if ($memberPKey <= 0 || coupon_d_already_assigned($couponPKey, $memberPKey)) {
                $failed++;
                continue;
            }
            if (coupon_d_insert_member($couponPKey, $memberPKey, $couponCode)) {
                $success++;
            } else {
                $failed++;
            }
        }
        return ['lines' => $lines, 'success' => $success, 'failed' => $failed];
    }
}

if (!function_exists('coupon_d_parse_email_list')) {
    /** @return list<string> */
    function coupon_d_parse_email_list(string $raw): array
    {
        $parts = preg_split('/[;\s,]+/u', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $email = trim((string)$part);
            if ($email !== '') {
                $out[] = $email;
            }
        }
        return $out;
    }
}
