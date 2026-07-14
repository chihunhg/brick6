<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';
require_once __DIR__ . '/_form_data.php';

header('Content-Type: text/plain; charset=utf-8');

$modulePKey = safe_int($filter_array['manNo'] ?? $_POST['manNo'] ?? ($GLOBALS['Module_PKey'] ?? 0));
$openDate = trim((string)($filter_array['OpenDate'] ?? $_POST['OpenDate'] ?? ''));
$endDate = trim((string)($filter_array['EndDate'] ?? $_POST['EndDate'] ?? ''));
$excludePKey = safe_int($filter_array['excludePKey'] ?? $_POST['excludePKey'] ?? 0);

if ($modulePKey <= 0 || $openDate === '' || $endDate === '') {
    echo '';
    exit;
}

if (discount_date_range_overlaps($modulePKey, $openDate, $endDate, $excludePKey)) {
    echo '活動日期重複';
    exit;
}

echo '';
