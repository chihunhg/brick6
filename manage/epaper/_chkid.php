<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';
require_once __DIR__ . '/_form_data.php';

header('Content-Type: text/plain; charset=utf-8');

$email = trim((string)($filter_array['EMail'] ?? $_POST['EMail'] ?? ''));
$excludePKey = safe_int($filter_array['excludePKey'] ?? $_POST['excludePKey'] ?? 0);
$modulePKey = (int)($GLOBALS['Module_PKey'] ?? 0);
if ($modulePKey <= 0) {
    $modulePKey = epaper_detail_resolve_module_pkey();
}

if ($email === '') {
    echo '';
    exit;
}

if (!function_exists('CheckMail') || !CheckMail($email)) {
    echo 'E-Mail 格式錯誤';
    exit;
}

if ($modulePKey <= 0) {
    echo '模組參數錯誤';
    exit;
}

if (epaper_email_exists($email, $modulePKey, $excludePKey)) {
    echo 'E-Mail 已存在';
    exit;
}

echo '';
