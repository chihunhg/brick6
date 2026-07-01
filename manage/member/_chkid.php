<?php
declare(strict_types=1);

require_once '../_inc.php';

header('Content-Type: text/plain; charset=utf-8');

$email = trim((string)($filter_array['EMail'] ?? $_POST['EMail'] ?? ''));
$excludePKey = safe_int($filter_array['excludePKey'] ?? $_POST['excludePKey'] ?? 0);

if ($email === '') {
    echo '';
    exit;
}

if (!function_exists('CheckMail') || !CheckMail($email)) {
    echo '會員帳號格式錯誤';
    exit;
}

require_once __DIR__ . '/_form_data.php';

if (member_email_exists($email, $excludePKey)) {
    echo '會員帳號重複';
    exit;
}

echo '';
