<?php
declare(strict_types=1);
/**
 * 折價券模組共用函式（coupon / couponreg）
 */

if (!function_exists('coupon_code_is_valid_format')) {
    function coupon_code_is_valid_format(string $code): bool
    {
        return (bool)preg_match('/^[a-z0-9]{1,50}$/i', trim($code));
    }
}

if (!function_exists('coupon_code_exists')) {
    function coupon_code_exists(string $code, int $excludePKey = 0): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }
        $sql = 'SELECT PKey FROM coupon_p WHERE Coupon_Code = :code';
        $params = ['code' => $code];
        if ($excludePKey > 0) {
            $sql .= ' AND PKey <> :pk';
            $params['pk'] = $excludePKey;
        }
        return crud_fetch_one($sql . ' LIMIT 1', $params) !== null;
    }
}

if (!function_exists('coupon_generate_unique_code')) {
    function coupon_generate_unique_code(int $length = 10): string
    {
        for ($n = 0; $n < 6; $n++) {
            $code = function_exists('getGUID') ? getGUID($length) : bin2hex(random_bytes((int)ceil($length / 2)));
            $code = substr($code, 0, $length);
            if (!coupon_code_exists($code)) {
                return $code;
            }
        }
        return function_exists('getGUID') ? getGUID($length) : uniqid('cp', false);
    }
}

if (!function_exists('coupon_date_for_form')) {
    function coupon_date_for_form(mixed $raw): string
    {
        if (!is_scalar($raw) || trim((string)$raw) === '') {
            return '';
        }
        return function_exists('Date_EN') ? (string)(Date_EN((string)$raw, 2) ?? '') : trim((string)$raw);
    }
}

if (!function_exists('coupon_date_for_list')) {
    function coupon_date_for_list(mixed $raw): string
    {
        if (!is_scalar($raw) || trim((string)$raw) === '') {
            return '';
        }
        return function_exists('Date_EN') ? (string)(Date_EN((string)$raw, 1) ?? '') : trim((string)$raw);
    }
}

if (!function_exists('coupon_normalize_datetime')) {
    function coupon_normalize_datetime(string $value): string
    {
        $value = trim(str_replace('/', '-', $value));
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : '';
    }
}

if (!function_exists('coupon_validate_date_range')) {
    function coupon_validate_date_range(string $openDate, string $endDate): string
    {
        if ($openDate === '') {
            return "【開始日期】為空白\n";
        }
        if ($endDate === '') {
            return "【結束日期】為空白\n";
        }
        if (function_exists('datediff') && datediff('day', $openDate, $endDate) < 0) {
            return "【開始日期大於結束日期】\n";
        }
        return '';
    }
}

if (!function_exists('coupon_verify_module_row')) {
    function coupon_verify_module_row(int $pkey, int $modulePKey): bool
    {
        if ($pkey <= 0 || $modulePKey <= 0) {
            return false;
        }
        $row = crud_fetch_one(
            'SELECT Module_PKey FROM coupon_p WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
        return $row !== null && (int)($row['Module_PKey'] ?? 0) === $modulePKey;
    }
}
