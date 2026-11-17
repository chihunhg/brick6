<?php

declare(strict_types=1);

/**
 * 靜態檔與 html 入口的登入閘門。未登入不可讀取本目錄內容。
 */

require_once __DIR__ . '/_auth.php';

function elfinder_requested_rel(): string
{
    $base = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    $raw = (string)($_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '');
    $path = rawurldecode((string)(parse_url($raw, PHP_URL_PATH) ?? ''));
    $path = str_replace('\\', '/', $path);
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return trim($path, '/');
}

function elfinder_local_file(string $rel): ?string
{
    $rel = str_replace('\\', '/', $rel);
    if ($rel === '' || str_contains($rel, "\0") || str_contains($rel, '..')) {
        return null;
    }
    $root = realpath(__DIR__);
    if ($root === false) {
        return null;
    }
    $full = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
    if ($full === false || !is_file($full)) {
        return null;
    }
    $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($full, $prefix)) {
        return null;
    }
    if (str_ends_with(strtolower($full), '.php')) {
        return null;
    }
    return $full;
}

function elfinder_content_type(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'html' => 'text/html; charset=utf-8',
        'htm' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'txt' => 'text/plain; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
    ];
    return $types[$ext] ?? 'application/octet-stream';
}

$rel = elfinder_requested_rel();
if ($rel === '') {
    $rel = 'elfinder.html';
}
$file = elfinder_local_file($rel);
if ($file === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mode = in_array($ext, ['html', 'htm'], true) ? 'page' : 'asset';
elfinder_require_backend_login($mode);

header('Content-Type: ' . elfinder_content_type($file));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Length: ' . (string)filesize($file));
readfile($file);
