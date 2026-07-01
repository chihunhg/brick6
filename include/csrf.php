<?php
// csrf.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

const CSRF_NS  = 'csrf_single';

function csrf_issue(string $key): string {
    // 只在渲染表單（GET）時產生；不要在 POST 時動到它
    $_SESSION[CSRF_NS][$key] = bin2hex(random_bytes(32));
    return $_SESSION[CSRF_NS][$key];
}

function csrf_token(string $key): string {
    return (string)($_SESSION[CSRF_NS][$key] ?? '');
}

function csrf_verify(string $key, string $posted): bool {
    $session = (string)($_SESSION[CSRF_NS][$key] ?? '');
    $ok = ($posted !== '' && $session !== '' && hash_equals($session, $posted));
    if ($ok) {
        // 一次性：比對成功後作廢
        unset($_SESSION[CSRF_NS][$key]);
    }
    return $ok;
}
?>