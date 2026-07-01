<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/_inc.php';
require_once dirname(__DIR__) . '/investor/_form_data.php';

$detailConfig = require dirname(__DIR__) . '/investor/_config.php';
manage_detail_set_config($detailConfig);

$modulePKey = safe_int($_POST['Module_PKey'] ?? $_GET['Module_PKey'] ?? 0);
$layer = (int)($GLOBALS['Layer'] ?? 1);

$filter = [
    'Class1' => safe_int($_POST['Class1'] ?? $_GET['Class1'] ?? 0),
    'Class2' => safe_int($_POST['Class2'] ?? $_GET['Class2'] ?? 0),
    'Class3' => safe_int($_POST['Class3'] ?? $_GET['Class3'] ?? 0),
    'year'   => safe_int($_POST['year'] ?? $_GET['year'] ?? 0),
];

$sort = investor_next_sort($modulePKey, $filter, $layer);

header('Content-Type: text/plain; charset=utf-8');
echo (string)$sort;
