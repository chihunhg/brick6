<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/_inc.php';

$class2 = safe_int($_POST['Class2'] ?? $_GET['Class2'] ?? 0);
$showYear = 0;

if ($class2 > 0
    && function_exists('crud_fetch_one')
    && function_exists('crud_table_has_column')
    && crud_table_has_column('dbclass2', 'show_year')) {
    $row = crud_fetch_one('SELECT show_year FROM dbclass2 WHERE PKey = :pk LIMIT 1', ['pk' => $class2]);
    $showYear = (int)($row['show_year'] ?? 0);
}

header('Content-Type: text/plain; charset=utf-8');
echo (string)$showYear;
