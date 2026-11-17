<?php

declare(strict_types=1);

/**
 * elFinder 目錄的後台登入門檻。
 * 須已登入且 $_SESSION['Manage']=Yes，與 manage/_inc.php 相同。
 * 本檔只供同目錄 PHP 引入，不可直接當頁面開啟。
 */

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

function elfinder_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function elfinder_backend_logged_in(): bool
{
    elfinder_start_session();
    if (!empty($_SESSION['MFA_Pending'])) {
        return false;
    }
    return !empty($_SESSION['Login_ID']) && (string)($_SESSION['Manage'] ?? '') === 'Yes';
}

function elfinder_login_url(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/manage/elFinder/elfinder.php'));
    $manage = rtrim(dirname($script, 2), '/');
    return $manage . '/login/index.php';
}

/**
 * @param 'page'|'json'|'asset' $mode
 */
function elfinder_require_backend_login(string $mode = 'asset'): void
{
    if (elfinder_backend_logged_in()) {
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Cache-Control: private, no-store');
    if ($mode === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['error' => ['Unauthorized']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($mode === 'page') {
        header('Location: ' . elfinder_login_url(), true, 302);
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}
