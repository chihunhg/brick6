<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/include/coupon_helpers.php';

if (!function_exists('discount_detail_defaults')) {
    /** @return array<string, mixed> */
    function discount_detail_defaults(): array
    {
        return [
            'Update_PKey' => 0,
            'strName'     => '',
            'intType'     => 1,
            'BuyQ'        => 0,
            'BuyPrice'    => 0,
            'Price'       => 100,
            'Interview'   => '',
            'OpenDate'    => '',
            'EndDate'     => '',
            'oldOpen'     => '',
            'oldEnd'      => '',
            'dtUDate'     => '',
            'UserID'      => '',
        ];
    }
}

if (!function_exists('discount_detail_init_defaults')) {
    function discount_detail_init_defaults(): void
    {
        $GLOBALS['discount_form_vars'] = discount_detail_defaults();
        discount_detail_export_vars();
    }
}

if (!function_exists('discount_detail_export_vars')) {
    function discount_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['discount_form_vars'] ?? discount_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['discount_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('discount_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function discount_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['discount_form_vars'];
        $v['Update_PKey'] = (int)($row['PKey'] ?? 0);
        $v['strName'] = (string)($row['strName'] ?? '');
        $v['intType'] = (int)($row['intType'] ?? 1);
        $v['BuyQ'] = (int)($row['BuyQ'] ?? 0);
        $v['BuyPrice'] = (int)($row['BuyPrice'] ?? 0);
        $price = (int)($row['Price'] ?? 0);
        $v['Price'] = $price > 0 ? $price : 100;
        $v['Interview'] = (string)($row['Interview'] ?? '');
        $openDate = coupon_date_for_form($row['OpenDate'] ?? '');
        $endDate = coupon_date_for_form($row['EndDate'] ?? '');
        $v['OpenDate'] = $openDate;
        $v['EndDate'] = $endDate;
        $v['oldOpen'] = $openDate;
        $v['oldEnd'] = $endDate;
        $v['dtUDate'] = (string)($row['dtUDate'] ?? '');
        $v['UserID'] = (string)($row['UserID'] ?? '');
        discount_detail_export_vars();
    }
}

if (!function_exists('discount_detail_load')) {
    function discount_detail_load(int $pkey, int $modulePKey): bool
    {
        if ($pkey <= 0) {
            return false;
        }
        $row = crud_fetch_one('SELECT * FROM discount_p WHERE PKey = :pk LIMIT 1', ['pk' => $pkey]);
        if ($row === null) {
            return false;
        }
        if ($modulePKey > 0 && (int)($row['Module_PKey'] ?? 0) !== $modulePKey) {
            return false;
        }
        if (!isset($GLOBALS['discount_form_vars'])) {
            discount_detail_init_defaults();
        }
        discount_detail_apply_master($row);
        return true;
    }
}

if (!function_exists('discount_detail_load_copy')) {
    function discount_detail_load_copy(int $pkey): void
    {
        if ($pkey <= 0) {
            return;
        }
        $row = crud_fetch_one('SELECT * FROM discount_p WHERE PKey = :pk LIMIT 1', ['pk' => $pkey]);
        if ($row === null) {
            return;
        }
        discount_detail_init_defaults();
        discount_detail_apply_master($row);
        $GLOBALS['discount_form_vars']['Update_PKey'] = 0;
        $GLOBALS['discount_form_vars']['oldOpen'] = '';
        $GLOBALS['discount_form_vars']['oldEnd'] = '';
        discount_detail_export_vars();
    }
}

if (!function_exists('discount_type_label')) {
    function discount_type_label(int $intType): string
    {
        return match ($intType) {
            2       => '滿額折抵',
            default => '滿件折抵',
        };
    }
}

if (!function_exists('discount_format_list_plan')) {
    /** 列表「折抵方案」文案 */
    function discount_format_list_plan(array $row): string
    {
        $price = (int)($row['Price'] ?? 0);
        if ($price <= 0) {
            $price = 100;
        }
        $intType = (int)($row['intType'] ?? 1);
        if ($intType === 2) {
            return '購物滿' . (int)($row['BuyPrice'] ?? 0) . '元，折抵運費$' . $price . '元';
        }
        return '購物滿' . (int)($row['BuyQ'] ?? 0) . '件，折抵運費$' . $price . '元';
    }
}

if (!function_exists('discount_verify_module_row')) {
    function discount_verify_module_row(int $pkey, int $modulePKey): bool
    {
        if ($pkey <= 0 || $modulePKey <= 0) {
            return false;
        }
        $row = crud_fetch_one(
            'SELECT Module_PKey FROM discount_p WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
        return $row !== null && (int)($row['Module_PKey'] ?? 0) === $modulePKey;
    }
}

if (!function_exists('discount_date_range_overlaps')) {
    /**
     * 同模組活動日期區間是否與既有資料重疊
     * 重疊條件：既有.OpenDate <= 新迄 AND 既有.EndDate >= 新起
     */
    function discount_date_range_overlaps(
        int $modulePKey,
        string $openDate,
        string $endDate,
        int $excludePKey = 0
    ): bool {
        $openDate = coupon_normalize_datetime($openDate);
        $endDate = coupon_normalize_datetime($endDate);
        if ($modulePKey <= 0 || $openDate === '' || $endDate === '') {
            return false;
        }

        $sql = 'SELECT PKey FROM discount_p
            WHERE Module_PKey = :mpk
              AND OpenDate <= :newEnd
              AND EndDate >= :newOpen';
        $params = [
            'mpk'     => $modulePKey,
            'newOpen' => $openDate,
            'newEnd'  => $endDate,
        ];
        if ($excludePKey > 0) {
            $sql .= ' AND PKey <> :pk';
            $params['pk'] = $excludePKey;
        }
        $sql .= ' LIMIT 1';

        return crud_fetch_one($sql, $params) !== null;
    }
}

if (!function_exists('discount_validate_form')) {
    /** @param array<string, mixed> $filter */
    function discount_validate_form(array $filter, int $formPKey, int $modulePKey): string
    {
        $msg = '';
        $strName = trim((string)($filter['strName'] ?? ''));
        $intType = safe_int($filter['intType'] ?? 0);
        $buyQ = trim((string)($filter['BuyQ'] ?? ''));
        $buyPrice = trim((string)($filter['BuyPrice'] ?? ''));
        $price = trim((string)($filter['Price'] ?? ''));
        $openRaw = trim(str_replace('/', '-', (string)($filter['OpenDate'] ?? '')));
        $endRaw = trim(str_replace('/', '-', (string)($filter['EndDate'] ?? '')));

        if ($strName === '') {
            $msg .= "【活動名稱】為空白\n";
        }
        if ($intType !== 1 && $intType !== 2) {
            $msg .= "【折抵方式】請選擇\n";
        } elseif ($intType === 1) {
            if ($buyQ === '' || !is_numeric($buyQ) || (int)$buyQ <= 0) {
                $msg .= "【數量】空白或非數字格式\n";
            }
        } elseif ($buyPrice === '' || !is_numeric($buyPrice) || (int)$buyPrice <= 0) {
            $msg .= "【金額】空白或非數字格式\n";
        }

        if ($price === '' || !is_numeric($price) || (int)$price < 0) {
            $msg .= "【折抵金額】空白或非數字格式\n";
        }

        $msg .= coupon_validate_date_range($openRaw, $endRaw);

        if ($msg === '' && $modulePKey > 0
            && discount_date_range_overlaps($modulePKey, $openRaw, $endRaw, $formPKey)) {
            $msg .= "【活動日期重複】\n";
        }

        if ($modulePKey <= 0) {
            $msg .= "【模組】參數錯誤\n";
        }

        return $msg;
    }
}

if (!function_exists('discount_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function discount_build_master_data(array $filter, int $modulePKey, string $loginId, bool $isEdit): array
    {
        $intType = safe_int($filter['intType'] ?? 1);
        $buyQ = $intType === 1 ? safe_int($filter['BuyQ'] ?? 0) : 0;
        $buyPrice = $intType === 2 ? safe_int($filter['BuyPrice'] ?? 0) : 0;
        $price = safe_int($filter['Price'] ?? 100);
        if ($price <= 0) {
            $price = 100;
        }

        $data = [
            'Module_PKey' => SqlFilter($modulePKey, 'int'),
            'strName'     => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'intType'     => SqlFilter($intType, 'int'),
            'BuyQ'        => SqlFilter($buyQ, 'int'),
            'BuyPrice'    => SqlFilter($buyPrice, 'int'),
            'Price'       => SqlFilter($price, 'int'),
            'Interview'   => SqlFilter(trim((string)($filter['Interview'] ?? '')), 'tab'),
            'Contents'    => SqlFilter(trim((string)($filter['Contents'] ?? '')), 'tab'),
            'OpenDate'    => coupon_normalize_datetime((string)($filter['OpenDate'] ?? '')),
            'EndDate'     => coupon_normalize_datetime((string)($filter['EndDate'] ?? '')),
            'dtUDate'     => date('Y-m-d H:i:s'),
            'UserID'      => SqlFilter($loginId, 'tab'),
        ];
        if (!$isEdit) {
            $data['dtDate'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
}
