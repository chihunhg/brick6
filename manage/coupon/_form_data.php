<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/include/coupon_helpers.php';

if (!function_exists('coupon_detail_defaults')) {
    /** @return array<string, mixed> */
    function coupon_detail_defaults(): array
    {
        return [
            'Update_PKey'  => 0,
            'strName'      => '',
            'Coupon_Code'  => '',
            'oldCode'      => '',
            'intType'      => 1,
            'Price'        => 0,
            'BuyPrice'     => 0,
            'intQ'         => 0,
            'useQ'         => 0,
            'OpenDate'     => '',
            'EndDate'      => '',
            'dtUDate'      => '',
            'UserID'       => '',
        ];
    }
}

if (!function_exists('coupon_detail_init_defaults')) {
    function coupon_detail_init_defaults(): void
    {
        $GLOBALS['coupon_form_vars'] = coupon_detail_defaults();
        $GLOBALS['coupon_form_vars']['Coupon_Code'] = coupon_generate_unique_code(10);
        coupon_detail_export_vars();
    }
}

if (!function_exists('coupon_detail_export_vars')) {
    function coupon_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['coupon_form_vars'] ?? coupon_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['coupon_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('coupon_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function coupon_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['coupon_form_vars'];
        $v['Update_PKey'] = (int)($row['PKey'] ?? 0);
        $v['strName'] = (string)($row['strName'] ?? '');
        $v['Coupon_Code'] = (string)($row['Coupon_Code'] ?? '');
        $v['oldCode'] = (string)($row['Coupon_Code'] ?? '');
        $v['intType'] = (int)($row['intType'] ?? 1);
        $v['Price'] = (int)($row['Price'] ?? 0);
        $v['BuyPrice'] = (int)($row['BuyPrice'] ?? 0);
        $v['intQ'] = (int)($row['intQ'] ?? 0);
        $v['useQ'] = (int)($row['useQ'] ?? 0);
        $v['OpenDate'] = coupon_date_for_form($row['OpenDate'] ?? '');
        $v['EndDate'] = coupon_date_for_form($row['EndDate'] ?? '');
        $v['dtUDate'] = (string)($row['dtUDate'] ?? '');
        $v['UserID'] = (string)($row['UserID'] ?? '');
        coupon_detail_export_vars();
    }
}

if (!function_exists('coupon_detail_load')) {
    function coupon_detail_load(int $pkey, int $modulePKey): bool
    {
        if ($pkey <= 0) {
            return false;
        }
        $row = crud_fetch_one('SELECT * FROM coupon_p WHERE PKey = :pk LIMIT 1', ['pk' => $pkey]);
        if ($row === null) {
            return false;
        }
        if ($modulePKey > 0 && (int)($row['Module_PKey'] ?? 0) !== $modulePKey) {
            return false;
        }
        if (!isset($GLOBALS['coupon_form_vars'])) {
            coupon_detail_init_defaults();
        }
        coupon_detail_apply_master($row);
        return true;
    }
}

if (!function_exists('coupon_detail_load_copy')) {
    function coupon_detail_load_copy(int $pkey): void
    {
        if ($pkey <= 0) {
            return;
        }
        $row = crud_fetch_one('SELECT * FROM coupon_p WHERE PKey = :pk LIMIT 1', ['pk' => $pkey]);
        if ($row === null) {
            return;
        }
        coupon_detail_init_defaults();
        coupon_detail_apply_master($row);
        $GLOBALS['coupon_form_vars']['Update_PKey'] = 0;
        $GLOBALS['coupon_form_vars']['Coupon_Code'] = coupon_generate_unique_code(10);
        $GLOBALS['coupon_form_vars']['oldCode'] = '';
        coupon_detail_export_vars();
    }
}

if (!function_exists('coupon_discount_type_label')) {
    function coupon_discount_type_label(int $intType): string
    {
        return match ($intType) {
            2       => '百分比',
            default => '固定金額',
        };
    }
}

if (!function_exists('coupon_format_list_price')) {
    function coupon_format_list_price(array $row): string
    {
        $price = (int)($row['Price'] ?? 0);
        $intType = (int)($row['intType'] ?? 1);
        if ($intType === 2) {
            return $price . '%';
        }
        return number_format($price);
    }
}

if (!function_exists('coupon_validate_form')) {
    /** @param array<string, mixed> $filter */
    function coupon_validate_form(array $filter, int $formPKey, bool $isEdit): string
    {
        $msg = '';
        $strName = trim((string)($filter['strName'] ?? ''));
        $couponCode = trim((string)($filter['Coupon_Code'] ?? ''));
        $oldCode = trim((string)($filter['oldCode'] ?? ''));
        $intType = safe_int($filter['intType'] ?? 1);
        $price = trim((string)($filter['Price'] ?? ''));
        $buyPrice = trim((string)($filter['BuyPrice'] ?? ''));
        $intQ = trim((string)($filter['intQ'] ?? ''));

        if ($strName === '') {
            $msg .= "【活動名稱】為空白\n";
        }
        if (!$isEdit) {
            if (!coupon_code_is_valid_format($couponCode)) {
                $msg .= "【活動序號】英文或數字1~50碼\n";
            } elseif ($couponCode !== $oldCode && coupon_code_exists($couponCode, $formPKey)) {
                $msg .= "【活動序號】重複\n";
            }
        }
        if ($intQ === '' || !is_numeric($intQ)) {
            $msg .= "【序號數量】空白或非數字格式\n";
        }
        if ($price === '' || !is_numeric($price)) {
            $msg .= "【折抵金額】空白或非數字格式\n";
        } elseif ($intType === 2 && ((int)$price < 50 || (int)$price > 100)) {
            $msg .= "【折抵百分比】需介於50~100\n";
        }
        if ($buyPrice === '' || !is_numeric($buyPrice)) {
            $msg .= "【購買金額】空白或非數字格式\n";
        }
        $msg .= coupon_validate_date_range(
            trim(str_replace('/', '-', (string)($filter['OpenDate'] ?? ''))),
            trim(str_replace('/', '-', (string)($filter['EndDate'] ?? '')))
        );
        return $msg;
    }
}

if (!function_exists('coupon_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function coupon_build_master_data(array $filter, int $modulePKey, string $loginId, bool $isEdit): array
    {
        $data = [
            'Module_PKey' => SqlFilter($modulePKey, 'int'),
            'strName'     => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'intType'     => SqlFilter($filter['intType'] ?? 1, 'int'),
            'Price'       => SqlFilter($filter['Price'] ?? 0, 'int'),
            'BuyPrice'    => SqlFilter($filter['BuyPrice'] ?? 0, 'int'),
            'intQ'        => SqlFilter($filter['intQ'] ?? 0, 'int'),
            'OpenDate'    => coupon_normalize_datetime((string)($filter['OpenDate'] ?? '')),
            'EndDate'     => coupon_normalize_datetime((string)($filter['EndDate'] ?? '')),
            'dtUDate'     => date('Y-m-d H:i:s'),
            'UserID'      => SqlFilter($loginId, 'tab'),
        ];
        if (!$isEdit) {
            $data['Coupon_Code'] = SqlFilter(trim((string)($filter['Coupon_Code'] ?? '')), 'tab');
            $data['dtDate'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
}
