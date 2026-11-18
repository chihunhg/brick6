<?php
declare(strict_types=1);

require dirname(__DIR__) . '/include/host.php';
require dirname(__DIR__) . '/include/Conn.php';

$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
$name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
$pass = getenv('DB_PASSWD') ?: ($_ENV['DB_PASSWD'] ?? '');
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');

echo 'DB_HOST=' . ($host !== '' ? $host : '(empty)') . PHP_EOL;
echo 'DB_PORT=' . $port . PHP_EOL;
echo 'DB_NAME=' . ($name !== '' ? $name : '(empty)') . PHP_EOL;
echo 'DB_USER=' . ($user !== '' ? $user : '(empty)') . PHP_EOL;
echo 'DB_PASSWD_set=' . (trim((string)$pass) !== '' ? 'yes' : 'no') . PHP_EOL;

$pdo = sql_conn();
echo 'sql_conn=' . ($pdo instanceof PDO ? 'OK' : 'FAIL') . PHP_EOL;

if (!($pdo instanceof PDO)) {
    exit(1);
}

$row = $pdo->query('SELECT 1 AS ok')->fetch(PDO::FETCH_ASSOC);
echo 'query=' . json_encode($row) . PHP_EOL;
