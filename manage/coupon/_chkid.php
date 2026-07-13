<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once __DIR__ . '/_form_data.php';

header('Content-Type: text/plain; charset=utf-8');

$code = trim((string)($filter_array['Coupon_Code'] ?? $_POST['Coupon_Code'] ?? ''));
if ($code === '') {
    echo '';
    exit;
}

if (!coupon_code_is_valid_format($code)) {
    echo '活動序號格式錯誤';
    exit;
}

if (coupon_code_exists($code)) {
    echo '活動序號重複';
    exit;
}

echo '';
