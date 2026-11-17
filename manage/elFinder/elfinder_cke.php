<?php

declare(strict_types=1);

/**
 * elFinder 彈窗。
 * 後台 PHP 頁會送 Cross-Origin-Opener-Policy: same-origin，
 * 靜態 html 沒有這個標頭時，瀏覽器會切斷 window.opener，選檔無法插回編輯器。
 * 這裡只補上與後台相同的跨來源標頭，不套用後台 CSP（本頁要載入 cdnjs 的 elFinder）。
 */

require_once __DIR__ . '/_auth.php';
elfinder_require_backend_login('page');

require_once dirname(__DIR__, 2) . '/include/sec.php';

foreach (security_cross_origin_headers(false) as $name => $value) {
    header($name . ': ' . $value);
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

readfile(__DIR__ . '/elfinder_cke.html');
