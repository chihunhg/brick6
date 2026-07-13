<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/include/coupon_helpers.php';

if (!function_exists('couponreg_detail_defaults')) {
    /** @return array<string, mixed> */
    function couponreg_detail_defaults(): array
    {
        return [
            'Update_PKey' => 0,
            'strName'     => '',
            'intType'     => 1,
            'Price'       => 0,
            'BuyPrice'    => 0,
            'intDay'      => 0,
            'OpenDate'    => '',
            'EndDate'     => '',
            'dtUDate'     => '',
            'UserID'      => '',
        ];
    }
}

if (!function_exists('couponreg_detail_init_defaults')) {
    function couponreg_detail_init_defaults(): void
    {
        $GLOBALS['couponreg_form_vars'] = couponreg_detail_defaults();
        couponreg_detail_export_vars();
    }
}

if (!function_exists('couponreg_detail_export_vars')) {
    function couponreg_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['couponreg_form_vars'] ?? couponreg_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['couponreg_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('couponreg_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function couponreg_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['couponreg_form_vars'];
        $v['Update_PKey'] = (int)($row['PKey'] ?? 0);
        $v['strName'] = (string)($row['strName'] ?? '');
        $v['intType'] = (int)($row['intType'] ?? 1);
        $v['Price'] = (int)($row['Price'] ?? 0);
        $v['BuyPrice'] = (int)($row['BuyPrice'] ?? 0);
        $v['intDay'] = (int)($row['intDay'] ?? 0);
        $v['OpenDate'] = coupon_date_for_form($row['OpenDate'] ?? '');
        $v['EndDate'] = coupon_date_for_form($row['EndDate'] ?? '');
        $v['dtUDate'] = (string)($row['dtUDate'] ?? '');
        $v['UserID'] = (string)($row['UserID'] ?? '');
        couponreg_detail_export_vars();
    }
}

if (!function_exists('couponreg_detail_load')) {
    function couponreg_detail_load(int $pkey, int $modulePKey): bool
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
        if (!isset($GLOBALS['couponreg_form_vars'])) {
            couponreg_detail_init_defaults();
        }
        couponreg_detail_apply_master($row);
        return true;
    }
}

if (!function_exists('couponreg_detail_load_copy')) {
    function couponreg_detail_load_copy(int $pkey): void
    {
        if ($pkey <= 0) {
            return;
        }
        $row = crud_fetch_one('SELECT * FROM coupon_p WHERE PKey = :pk LIMIT 1', ['pk' => $pkey]);
        if ($row === null) {
            return;
        }
        couponreg_detail_init_defaults();
        couponreg_detail_apply_master($row);
        $GLOBALS['couponreg_form_vars']['Update_PKey'] = 0;
        couponreg_detail_export_vars();
    }
}

if (!function_exists('couponreg_list_period_text')) {
    /** @param array<string, mixed> $row */
    function couponreg_list_period_text(array $row): array
    {
        $intType = (int)($row['intType'] ?? 1);
        if ($intType === 2) {
            return ['發送日期', '發送日期 + ' . (int)($row['intDay'] ?? 0) . ' 天'];
        }
        return [
            coupon_date_for_list($row['OpenDate'] ?? ''),
            coupon_date_for_list($row['EndDate'] ?? ''),
        ];
    }
}

if (!function_exists('couponreg_validate_form')) {
    /** @param array<string, mixed> $filter */
    function couponreg_validate_form(array $filter): string
    {
        $msg = '';
        if (trim((string)($filter['strName'] ?? '')) === '') {
            $msg .= "【活動名稱】為空白\n";
        }
        if (!isset($filter['Price']) || !is_numeric($filter['Price'])) {
            $msg .= "【折抵金額】空白或非數字格式\n";
        }
        if (!isset($filter['BuyPrice']) || !is_numeric($filter['BuyPrice'])) {
            $msg .= "【購買金額】空白或非數字格式\n";
        }
        $intType = safe_int($filter['intType'] ?? 1);
        if ($intType === 1) {
            $msg .= coupon_validate_date_range(
                trim(str_replace('/', '-', (string)($filter['OpenDate'] ?? ''))),
                trim(str_replace('/', '-', (string)($filter['EndDate'] ?? '')))
            );
        } elseif (!isset($filter['intDay']) || !is_numeric($filter['intDay'])) {
            $msg .= "【自訂天數】空白或非數字格式\n";
        }
        return $msg;
    }
}

if (!function_exists('couponreg_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function couponreg_build_master_data(array $filter, int $modulePKey, string $loginId, bool $isNew): array
    {
        $intType = safe_int($filter['intType'] ?? 1);
        $data = [
            'Module_PKey' => SqlFilter($modulePKey, 'int'),
            'strName'     => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'Price'       => SqlFilter($filter['Price'] ?? 0, 'int'),
            'BuyPrice'    => SqlFilter($filter['BuyPrice'] ?? 0, 'int'),
            'intType'     => SqlFilter($intType, 'int'),
            'intDay'      => SqlFilter($filter['intDay'] ?? 0, 'int'),
            'dtUDate'     => date('Y-m-d H:i:s'),
            'UserID'      => SqlFilter($loginId, 'tab'),
        ];
        if ($intType === 1) {
            $data['OpenDate'] = coupon_normalize_datetime((string)($filter['OpenDate'] ?? ''));
            $data['EndDate'] = coupon_normalize_datetime((string)($filter['EndDate'] ?? ''));
        }
        if ($isNew) {
            $data['dtDate'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
}
