<?php
/**
 * 單次 CSRF token（session 儲存、驗證後作廢）
 *
 * 使用方式：
 *   csrf_issue('form_key')  // GET 渲染表單
 *   csrf_verify('form_key', $_POST['csrf'] ?? '')  // POST 驗證
 */
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

const CSRF_NS  = 'csrf_single';

/** 產生並存入 session，回傳 token（僅 GET 渲染表單時呼叫） */
function csrf_issue(string $key): string {
    // 只在渲染表單（GET）時產生；不要在 POST 時動到它
    $_SESSION[CSRF_NS][$key] = bin2hex(random_bytes(32));
    return $_SESSION[CSRF_NS][$key];
}

/** 讀取 session 中現有 token（不重新產生） */
function csrf_token(string $key): string {
    return (string)($_SESSION[CSRF_NS][$key] ?? '');
}

/** 比對 POST token 與 session；成功後作廢 */
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