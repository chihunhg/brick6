<?php
declare(strict_types=1);

/**
 * Sec.php — 安全工具函式集合（Fortify 強化版）
 * - 文字/屬性輸出：e(), e_attr(), js_str()
 * - URL 驗證與白名單：safe_url(), safe_href(), href_attr()
 * - 安全重新導向：safe_redirect()
 * - JSON 安全嵌入：json_script()
 * - Session / Cookie：start_secure_session(), set_secure_cookie(), clear_cookie()
 * - CSRF：csrf_seed(), csrf_token(), csrf_input(), csrf_check(), require_post()
 * - 同源檢查：check_same_origin()
 * - CSP（nonce + strict-dynamic）：csp_nonce(), csp_policy_frontend_string(), csp_frontend_script_elem_directives(), send_security_headers(), script_open(), script_close(), manage_inline_script(), json_ld_script_tag()
 * - Token 簽章：sign_id(), verify_id()
 * - 視圖常用安全輔助：safe_tel(), tel_href(), is_external_link(), safe_inline_html()
 */

/* -------------------------------------------------------
 * 常用輸出編碼
 * -----------------------------------------------------*/

if (!function_exists('e')) {
/** 純文字節點（含標題/內文等） */
    function e(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('youtube_extract_id')) {
/** 從 Movielink 欄位解析 YouTube 影片 ID（11 碼或常見網址格式） */
    function youtube_extract_id(string $input): ?string {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
            return $input;
        }
        $patterns = [
            '#youtu\.be/([A-Za-z0-9_-]{11})#i',
            '#youtube\.com/embed/([A-Za-z0-9_-]{11})#i',
            '#youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})#i',
            '#youtube\.com/watch\?[^ ]*v=([A-Za-z0-9_-]{11})#i',
            '#youtube\.com/v/([A-Za-z0-9_-]{11})#i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $input, $m)) {
                return $m[1];
            }
        }
        return null;
    }
}

if (!function_exists('youtube_embed_src')) {
/** 前台影音區 iframe 用 embed URL；無效 Movielink 回傳 null */
    function youtube_embed_src(string $movielink): ?string {
        $id = youtube_extract_id($movielink);
        return $id !== null ? ('https://www.youtube-nocookie.com/embed/' . $id) : null;
    }
}

if (!function_exists('youtube_watch_url')) {
/** YouTube 外連網址（watch?v=）；無效 Movielink 回傳 null */
    function youtube_watch_url(string $movielink): ?string {
        $id = youtube_extract_id($movielink);
        return $id !== null ? ('https://www.youtube.com/watch?v=' . $id) : null;
    }
}

if (!function_exists('youtube_thumbnail_url')) {
/** YouTube 縮圖 URL；$quality 常用 hqdefault、mqdefault、sddefault */
    function youtube_thumbnail_url(string $movielink, string $quality = 'hqdefault'): ?string {
        $id = youtube_extract_id($movielink);
        if ($id === null) {
            return null;
        }
        $quality = preg_replace('/[^a-z0-9]/i', '', $quality) ?: 'hqdefault';

        return 'https://img.youtube.com/vi/' . $id . '/' . $quality . '.jpg';
    }
}

if (!function_exists('editor_html_decode_stored')) {
/**
 * 還原曾 htmlspecialchars 存入 DB 的 CKEditor 內容（僅在無真實標籤、含 &lt; 時解一次）
 */
    function editor_html_decode_stored(string $html): string {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }
        // 舊資料可能 htmlspecialchars 一次或兩次（&lt;p&gt; / &amp;lt;p&amp;gt;）
        for ($pass = 0; $pass < 2; $pass++) {
            if (!preg_match('/<[a-z][\s>\/]/i', $html)
                && (strpos($html, '&lt;') !== false || strpos($html, '&amp;lt;') !== false)) {
                $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($decoded === $html) {
                    break;
                }
                $html = $decoded;
                continue;
            }
            break;
        }
        return $html;
    }
}

if (!function_exists('e_editor_html')) {
/**
 * CKEditor textarea 初值：輸出 HTML（勿用 e()，否則編輯器會顯示 &lt;p&gt; 等實體）
 * 會移除 script、防止 </textarea> 跳出標籤；若舊資料曾整段 escape 則嘗試還原一次
 */
    function e_editor_html(?string $html): string {
        $html = editor_html_decode_stored((string)($html ?? ''));
        if ($html === '') {
            return '';
        }
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/iu', '', $html) ?? $html;
        return preg_replace('/<\/textarea>/i', '&lt;/textarea&gt;', $html) ?? $html;
    }
}

if (!function_exists('e_attr')) {
/** HTML 屬性值（href/src/content 等） */
    function e_attr(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('js_str')) {
/** 內嵌 JS 字串：用 json_encode 生成合法字面值 */
    function js_str($v): string {
        return json_encode(
            (string)$v,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }
}

/* -------------------------------------------------------
 * 內部工具
 * -----------------------------------------------------*/

if (!function_exists('_is_https')) {
/** 是否為 HTTPS（含常見反代 header） */
    function _is_https(): bool {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
        $xfp = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
        if ($xfp === 'https') return true;
        return false;
    }
}

if (!function_exists('request_transport_https')) {
/**
 * 目前請求是否以 HTTPS 傳輸（_inc.php CSP：是否加上 upgrade-insecure-requests）
 * 以實際連線／可信反代為準，與 _is_https() 一致。
 */
    function request_transport_https(): bool {
        return _is_https();
    }
}

if (!function_exists('is_trustworthy_origin')) {
/**
 * 是否為「可信來源」（COOP / COEP 等僅在此時由瀏覽器採用）
 * HTTPS，或 HTTP 的 localhost / 127.0.0.1 / [::1]
 *
 * @see https://www.w3.org/TR/powerful-features/#potentially-trustworthy-origin
 */
    function is_trustworthy_origin(): bool {
        if (_is_https()) {
            return true;
        }
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        if ($host === '') {
            return false;
        }
        $hostname = $host;
        if (str_contains($host, ':')) {
            $parsed = parse_url('http://' . $host);
            if (!empty($parsed['host'])) {
                $hostname = strtolower((string)$parsed['host']);
            } else {
                $hostname = strtolower((string)explode(':', $host, 2)[0]);
            }
        }
        return in_array($hostname, ['localhost', '127.0.0.1', '[::1]'], true);
    }
}

if (!function_exists('security_cross_origin_headers')) {
/** COOP / COEP / CORP：僅在可信來源送出，避免 HTTP 非 localhost 時主控台警告 */
    function security_cross_origin_headers(bool $includeCoep = false): array {
        if (!is_trustworthy_origin()) {
            return [];
        }
        $headers = [
            'Cross-Origin-Opener-Policy'   => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-site',
        ];
        if ($includeCoep) {
            $headers['Cross-Origin-Embedder-Policy'] = 'require-corp';
        }
        return $headers;
    }
}

if (!function_exists('_strip_crlf')) {
/** 移除 CR/LF（避免 header 注入） */
    function _strip_crlf(string $s): string {
        return str_replace(["\r", "\n"], '', $s);
    }
}

/* -------------------------------------------------------
 * URL 驗證 / 安全導向
 * -----------------------------------------------------*/

if (!function_exists('safe_url')) {
/**
 * 驗證並回傳安全 URL（允許相對路徑 & 白名單絕對網址）
 * - 允許：/..., ./..., ../..., 或純路徑/檔名（英數、_-.?/&=%）
 * - 阻擋：空字串、CRLF、scheme-relative(//)、非 http/https 的絕對網址、javascript:
 */
    function safe_url(string $url, array $allowedHosts = []): ?string {
        $url = trim($url);
        if ($url === '') return null;
        $url = _strip_crlf($url);

        // #純錨點
        if ($url[0] === '#') {
            if (preg_match('/^#[A-Za-z0-9_-]*$/', $url)) return $url;
            return null;
        }

        // 阻擋 //example.com
        if (strpos($url, '//') === 0) return null;

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host   = parse_url($url, PHP_URL_HOST);

        if ($scheme !== null) {
            $scheme = strtolower($scheme);
            if (!in_array($scheme, ['http','https'], true)) return null;
            if (!$host) return null;
            $h = strtolower($host);
            if ($allowedHosts && !in_array($h, array_map('strtolower', $allowedHosts), true)) {
                return null;
            }
            return $url; // 合法絕對 URL
        }

        // 無 scheme：相對路徑
        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $url)) return null; // 冒號技巧（阻擋 data:, javascript: 等）

        if (
            $url[0] === '/' ||
            str_starts_with($url, './') ||
            str_starts_with($url, '../') ||
            preg_match('#^[A-Za-z0-9_\-./?=&%]+$#', $url)
        ) {
            $parts = parse_url($url) ?: [];
            $path  = str_replace('\\','/', (string)($parts['path'] ?? ''));
            // dot-segments 正規化
            $in = explode('/', $path);
            $out = [];
            foreach ($in as $seg) {
                if ($seg === '' || $seg === '.') continue;
                if ($seg === '..') { array_pop($out); continue; }
                $out[] = $seg;
            }
            $norm = implode('/', $out);
            $leading = ($url[0] === '/') ? '/' : '';
            $q = isset($parts['query'])    ? ('?' . $parts['query'])    : '';
            $f = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';
            return $leading . $norm . $q . $f;
        }
        return null;
    }
}

if (!function_exists('safe_href')) {
/** 視圖層 href 用：失敗回傳 '#' */
    function safe_href(string $url, array $allowedHosts = []): string {
        return safe_url($url, $allowedHosts) ?? '#';
    }
}

if (!function_exists('href_attr')) {
/** 直接輸出到 HTML 屬性（= safe_href + 屬性轉義） */
    function href_attr(string $url, array $allowedHosts = []): string {
        return e_attr(safe_href($url, $allowedHosts));
    }
}

if (!function_exists('safe_redirect')) {
/** 安全重新導向（避免 Open Redirect / header split） */
    function safe_redirect(string $url, array $allowedHosts = [], int $code = 302): void {
        $safe = safe_url($url, $allowedHosts) ?? '/';
        if (!headers_sent()) {
            header('Location: ' . _strip_crlf($safe), true, $code);
        }
        exit;
    }
}

/* -------------------------------------------------------
 * JSON 安全輸出（<script type="application/ld+json">）
 * -----------------------------------------------------*/
if (!function_exists('json_script')) {
    /** JSON 安全嵌入 script 用字串（hex 跳脫防 XSS） */
    function json_script($data): string {
        $json = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
        return $json === false ? '{}' : $json;
    }
}

if (!function_exists('json_ld_script_tag')) {
    /** JSON-LD 結構化資料（帶 nonce，符合 script-src CSP） */
    function json_ld_script_tag($data): string {
        return '<script type="application/ld+json"' . csp_script_nonce_attr() . '>'
            . json_script($data) . '</script>';
    }
}

/* -------------------------------------------------------
 * Session / Cookie
 * -----------------------------------------------------*/
if (!function_exists('start_secure_session')) {
    /** 啟動安全 session cookie（httponly、secure、SameSite） */
    function start_secure_session(?string $domain = null, string $sameSite = 'Lax'): void {
        if (session_status() === PHP_SESSION_NONE) {
            $p = session_get_cookie_params();

            // 防禦性備援：確保 INI 層面也打開（不是必須，但可覆蓋 edge cases）
            @ini_set('session.cookie_httponly', '1');
            @ini_set('session.cookie_secure',    _is_https() ? '1' : '0');
            @ini_set('session.cookie_samesite',  $sameSite);

            // PHP 7.3+：使用陣列參數設定完整旗標（Fortify 可清楚辨識）
            session_set_cookie_params([
                'lifetime' => 0,                          // 非持久化
                'path'     => $p['path'] ?: '/',
                'domain'   => $domain ?: ($p['domain'] ?? ''),
                'secure'   => _is_https(),                // 僅在 HTTPS 下傳輸
                'httponly' => true,                       // JS 不可讀取
                'samesite' => $sameSite,                  // 'Lax' / 'Strict' / 'None'(需 HTTPS)
            ]);

            session_start();
        }
    }
}

if (!function_exists('set_secure_cookie')) {
    /** 設定安全 cookie（預設 httponly + secure + SameSite=Lax） */
    function set_secure_cookie(string $name, string $value, array $opts = []): void {
        $d = [
            'expires'  => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => _is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        $opts = array_merge($d, $opts);
        $opts['path']   = $opts['path']   ?? '/';
        $opts['domain'] = $opts['domain'] ?? '';
        setcookie($name, $value, $opts);
    }
}

if (!function_exists('clear_cookie')) {
    /** 清除 cookie（expires 設為過去） */
    function clear_cookie(string $name, array $opts = []): void {
        $opts['expires'] = time() - 42000;
        set_secure_cookie($name, '', $opts);
    }
}

/* -------------------------------------------------------
 * CSRF
 * -----------------------------------------------------*/
if (!function_exists('csrf_seed')) {
    /** 確保 $_SESSION['csrf'] 種子存在 */
    function csrf_seed(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }
}

if (!function_exists('csrf_token')) {
    /** 取得 CSRF token 字串 */
    function csrf_token(): string {
        csrf_seed();
        return (string)($_SESSION['csrf'] ?? '');
    }
}

if (!function_exists('csrf_input')) {
    /** 輸出 hidden csrf 欄位 HTML */
    function csrf_input(): string {
        return '<input type="hidden" name="csrf" value="' . e_attr(csrf_token()) . '">';
    }
}

if (!function_exists('require_post')) {
    /** 非 POST 請求回 405 並 exit */
    function require_post(): void {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Method Not Allowed');
        }
    }
}

if (!function_exists('csrf_check')) {
    /** 驗證 POST csrf（通過或失敗皆消耗 token） */
    function csrf_check(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }
        $posted = (string)($_POST['csrf'] ?? '');
        $token  = (string)($_SESSION['csrf'] ?? '');
        $ok = ($posted !== '' && $token !== '' && hash_equals($token, $posted));
        // 一次性：通過或失敗都作廢
        unset($_SESSION['csrf']);
        if (!$ok) {
            http_response_code(403);
            exit('CSRF validation failed');
        }
    }
}

/* -------------------------------------------------------
 * 同源檢查
 * -----------------------------------------------------*/
if (!function_exists('check_same_origin')) {
    /** Origin/Referer 主機須在白名單內，否則 403 */
    function check_same_origin(array $allowedHosts = []): void {
        $origin = $_SERVER['HTTP_ORIGIN']  ?? ($_SERVER['HTTP_REFERER'] ?? '');
        if ($origin === '') return;
        $host = parse_url($origin, PHP_URL_HOST);
        if (!$host) return;
        if (!$allowedHosts) $allowedHosts = [$_SERVER['HTTP_HOST'] ?? ''];
        $host = strtolower((string)$host);
        $ok   = in_array($host, array_map('strtolower', $allowedHosts), true);
        if (!$ok) {
            http_response_code(403);
            exit('Cross-origin blocked');
        }
    }
}

/* -------------------------------------------------------
 * CSP with nonce / 常用安全標頭
 * -----------------------------------------------------*/
if (!function_exists('csp_nonce')) {
    /** 本請求 CSP script nonce（$GLOBALS['csp_nonce']） */
    function csp_nonce(): string {
        if (empty($GLOBALS['csp_nonce'])) {
            $GLOBALS['csp_nonce'] = bin2hex(random_bytes(16));
        }
        return (string)$GLOBALS['csp_nonce'];
    }
}

if (!function_exists('send_frame_options_header')) {
    /** 防點擊劫持：X-Frame-Options（舊版瀏覽器）；請與 CSP frame-ancestors 政策一致 */
    function send_frame_options_header(string $policy = 'SAMEORIGIN'): void {
        $policy = strtoupper(trim($policy));
        if (!in_array($policy, ['DENY', 'SAMEORIGIN'], true)) {
            $policy = 'SAMEORIGIN';
        }
        if (!headers_sent()) {
            header('X-Frame-Options: ' . _strip_crlf($policy));
        }
    }
}

if (!function_exists('csp_policy_string')) {
    /**
     * 組裝 CSP（不含 unsafe-inline；腳本僅 nonce + strict-dynamic + 白名單網域）
     *
     * @param list<string> $scriptHosts  額外允許的 script-src 來源（如 Google reCAPTCHA）
     * @param list<string> $styleHosts   額外允許的 style-src 來源
     * @param list<string> $frameHosts   額外允許的 frame-src 來源
     * @param list<string> $connectHosts 額外允許的 connect-src 來源
     */
    function csp_policy_string(
        array $scriptHosts = [],
        array $styleHosts = [],
        array $frameHosts = [],
        array $connectHosts = []
    ): string {
        $nonce = csp_nonce();
        $hosts = static function (array $list): string {
            $out = [];
            foreach ($list as $h) {
                $h = trim((string)$h);
                if ($h !== '') {
                    $out[] = $h;
                }
            }
            return $out ? (' ' . implode(' ', array_unique($out))) : '';
        };

        $upgrade = request_transport_https() ? ' upgrade-insecure-requests;' : '';

        return "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic'" . $hosts($scriptHosts) . '; ' .
            "style-src 'self'" . $hosts($styleHosts) . '; ' .
            "img-src 'self' data: https:; " .
            "font-src 'self' data: https:; " .
            "object-src 'none'; base-uri 'self'; frame-ancestors 'none'; " .
            "form-action 'self';" .
            "frame-src 'self'" . $hosts($frameHosts) . '; ' .
            "connect-src 'self'" . $hosts($connectHosts) . ';' .
            $upgrade;
    }
}

if (!function_exists('csp_recaptcha_extra_directives')) {
/** reCAPTCHA：Google 建議路徑 + Web Worker（blob / gstatic）— 後台沿用 */
    function csp_recaptcha_extra_directives(string $nonce, array $scriptHosts): string {
        $hosts = static function (array $list): string {
            $out = [];
            foreach ($list as $h) {
                $h = trim((string)$h);
                if ($h !== '') {
                    $out[] = $h;
                }
            }
            return $out ? (' ' . implode(' ', array_unique($out))) : '';
        };
        $recaptchaPaths = ' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://www.recaptcha.net/recaptcha/';
        return "script-src-elem 'self' 'nonce-{$nonce}'" . $hosts($scriptHosts) . $recaptchaPaths . '; '
            . "worker-src 'self' blob: https://www.gstatic.com https://www.google.com https://www.recaptcha.net; ";
    }
}

if (!function_exists('csp_frontend_script_elem_directives')) {
/**
 * 前台 script-src-elem：nonce + strict-dynamic，不列第三方網域（避免 CSP Evaluator 繞過警告）。
 * 初始腳本須帶 nonce（script_src_tag / recaptcha_script_tag）；後續由 strict-dynamic 信任鏈載入。
 * unsafe-inline / https: 僅供不支援 CSP3 的舊瀏覽器後備，現代瀏覽器會忽略。
 */
    function csp_frontend_script_elem_directives(string $nonce): string {
        return "script-src-elem 'nonce-{$nonce}' 'strict-dynamic' 'unsafe-inline' https:; "
            . "worker-src 'self' blob: https://www.gstatic.com https://www.google.com https://www.recaptcha.net; ";
    }
}

if (!function_exists('csp_policy_manage_string')) {
    /**
     * 後台列表／表單 CSP：script 仍用 nonce + strict-dynamic；
     * style 允許 unsafe-inline（相容 style 屬性與舊版表單標記）
     */
    function csp_policy_manage_string(
        array $scriptHosts = [],
        array $styleHosts = [],
        array $frameHosts = [],
        array $connectHosts = []
    ): string {
        $nonce = csp_nonce();
        $hosts = static function (array $list): string {
            $out = [];
            foreach ($list as $h) {
                $h = trim((string)$h);
                if ($h !== '') {
                    $out[] = $h;
                }
            }
            return $out ? (' ' . implode(' ', array_unique($out))) : '';
        };

        $upgrade = request_transport_https() ? ' upgrade-insecure-requests;' : '';

        return "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic'" . $hosts($scriptHosts) . '; ' .
            csp_recaptcha_extra_directives($nonce, $scriptHosts) .
            "style-src 'self' 'unsafe-inline'" . $hosts($styleHosts) . '; ' .
            "img-src 'self' data: https:; " .
            "font-src 'self' data: https:; " .
            "object-src 'none'; base-uri 'self'; frame-ancestors 'none'; " .
            "form-action 'self';" .
            "frame-src 'self'" . $hosts($frameHosts) . '; ' .
            "connect-src 'self'" . $hosts($connectHosts) . ';' .
            $upgrade;
    }
}

if (!function_exists('csp_policy_frontend_string')) {
    /**
     * 前台 CSP：script / script-src-elem 用 nonce + strict-dynamic；
     * script-src / script-src-elem 皆含 unsafe-inline https: 後備（CSP3 下由 nonce 覆蓋，舊瀏覽器仍可用）；
     * script-src-attr 'none'：禁止 onclick 等行內事件（問卷已改 jQuery change 綁定）
     */
    function csp_policy_frontend_string(
        array $scriptHosts = [],
        array $styleHosts = [],
        array $frameHosts = [],
        array $connectHosts = []
    ): string {
        $nonce = csp_nonce();
        $hosts = static function (array $list): string {
            $out = [];
            foreach ($list as $h) {
                $h = trim((string)$h);
                if ($h !== '') {
                    $out[] = $h;
                }
            }
            return $out ? (' ' . implode(' ', array_unique($out))) : '';
        };

        $upgrade = request_transport_https() ? ' upgrade-insecure-requests;' : '';

        return "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' 'unsafe-inline' https:; " .
            csp_frontend_script_elem_directives($nonce) .
            "script-src-attr 'none'; " .
            "style-src 'self' 'unsafe-inline'" . $hosts($styleHosts) . '; ' .
            "style-src-elem 'self' 'unsafe-inline'" . $hosts($styleHosts) . '; ' .
            "style-src-attr 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' data: https:; " .
            "media-src 'self' https:; " .
            "manifest-src 'self'; " .
            "object-src 'none'; base-uri 'self'; frame-ancestors 'none'; " .
            "form-action 'self';" .
            "frame-src 'self'" . $hosts($frameHosts) . '; ' .
            "connect-src 'self'" . $hosts($connectHosts) . ';' .
            $upgrade;
    }
}

if (!function_exists('send_security_headers')) {
    /** 前台公開頁：動態 nonce CSP（見 _inc.php） */
    function send_security_headers(array $extra = []): void {
        $styleHosts = [
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://use.fontawesome.com',
        ];
        $frameHosts = [
            'https://www.youtube.com',
            'https://www.youtube-nocookie.com',
            'https://www.google.com',
            'https://www.google.com/recaptcha/',
            'https://recaptcha.google.com',
            'https://www.facebook.com',
            'https://connect.facebook.net',
            'https://www.googletagmanager.com',
        ];
        $connectHosts = [
            'https://www.google-analytics.com',
            'https://*.google-analytics.com',
            'https://analytics.google.com',
            'https://www.googletagmanager.com',
            'https://*.googletagmanager.com',
            'https://www.google.com',
            'https://www.gstatic.com',
            'https://www.google.com/recaptcha/',
            'https://www.recaptcha.net',
            'https://stats.g.doubleclick.net',
        ];
        $def = array_merge([
            'Content-Security-Policy' => csp_policy_frontend_string([], $styleHosts, $frameHosts, $connectHosts),
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'DENY',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'geolocation=(), camera=(), microphone=()',
        // 前台含 YouTube／Google 地圖 iframe，不可送 COEP require-corp（會導致嵌入「拒絕連線」）
        ], security_cross_origin_headers(false));
        foreach (array_merge($def, $extra) as $k => $v) {
            if (!headers_sent()) {
                header($k . ': ' . _strip_crlf($v));
            }
        }
    }
}

if (!function_exists('csp_policy_editor_string')) {
    /**
     * 後台 CKEditor 編輯頁專用 CSP（允許 inline script/style、寬鬆 frame-src）
     * 僅用於已登入後台且含富文本編輯器的頁面，勿用於一般列表／登入頁。
     */
    function csp_policy_editor_string(
        array $scriptHosts = [],
        array $styleHosts = [],
        array $frameHosts = [],
        array $connectHosts = []
    ): string {
        $nonce = csp_nonce();
        $hosts = static function (array $list): string {
            $out = [];
            foreach ($list as $h) {
                $h = trim((string)$h);
                if ($h !== '') {
                    $out[] = $h;
                }
            }
            return $out ? (' ' . implode(' ', array_unique($out))) : '';
        };

        $upgrade = request_transport_https() ? ' upgrade-insecure-requests;' : '';

        return "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline'" . $hosts($scriptHosts) . '; ' .
            "style-src 'self' 'unsafe-inline'" . $hosts($styleHosts) . '; ' .
            "img-src 'self' data: blob: https:; " .
            "font-src 'self' data: https:; " .
            "object-src 'none'; base-uri 'self'; frame-ancestors 'none'; " .
            "form-action 'self'; " .
            "frame-src 'self' blob: data: *" . $hosts($frameHosts) . '; ' .
            "connect-src 'self'" . $hosts($connectHosts) . ';' .
            $upgrade;
    }
}

if (!function_exists('send_manage_editor_security_headers')) {
    /** 後台 CKEditor／Summernote 編輯頁：放寬 script/style/frame 以相容編輯器與 elFinder */
    function send_manage_editor_security_headers(array $extra = []): void {
        $scriptHosts = [
            'https://www.google.com',
            'https://www.gstatic.com',
            'https://cdnjs.cloudflare.com',
            'https://cdn.jsdelivr.net',
        ];
        $styleHosts = [
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://use.fontawesome.com',
        ];
        $frameHosts = [
            'https://www.youtube.com',
            'https://www.google.com',
        ];
        $connectHosts = [
            'https://www.google.com',
            'https://www.gstatic.com',
        ];
        $def = array_merge([
            'Content-Security-Policy' => csp_policy_editor_string($scriptHosts, $styleHosts, $frameHosts, $connectHosts),
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'DENY',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'geolocation=(), camera=(), microphone=()',
        ], security_cross_origin_headers(false));
        foreach (array_merge($def, $extra) as $k => $v) {
            if (!headers_sent()) {
                header($k . ': ' . _strip_crlf($v));
            }
        }
    }
}

if (!function_exists('send_manage_security_headers')) {
    /** 後台 manage：script 無 unsafe-inline；style 允許 inline（舊表單／style 屬性） */
    function send_manage_security_headers(array $extra = []): void {
        $scriptHosts = [
            'https://www.google.com',
            'https://www.gstatic.com',
        ];
        $styleHosts = [
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://use.fontawesome.com',
        ];
        $frameHosts = [
            'https://www.youtube.com',
            'https://www.google.com',
            'https://www.google.com/recaptcha/',
            'https://recaptcha.google.com',
        ];
        $connectHosts = [
            'https://www.google.com',
            'https://www.google.com/recaptcha/',
            'https://www.gstatic.com',
            'https://www.recaptcha.net',
        ];
        $def = array_merge([
            'Content-Security-Policy' => csp_policy_manage_string($scriptHosts, $styleHosts, $frameHosts, $connectHosts),
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'DENY',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'geolocation=(), camera=(), microphone=()',
        ], security_cross_origin_headers(false));
        foreach (array_merge($def, $extra) as $k => $v) {
            if (!headers_sent()) {
                header($k . ': ' . _strip_crlf($v));
            }
        }
    }
}

if (!function_exists('script_open')) {
    /** 開啟帶 nonce 的 script 標籤 */
    function script_open(): string {
        return '<script nonce="' . e_attr(csp_nonce()) . '">';
    }
}
if (!function_exists('script_close')) {
    /** 關閉 script 標籤 */
    function script_close(): string { return '</script>'; }
}

if (!function_exists('csp_script_nonce_attr')) {
    /** 外連或同源帶 src 的 script 標籤須輸出此屬性，否則 strict-dynamic 下會被擋 */
    function csp_script_nonce_attr(): string {
        return ' nonce="' . e_attr(csp_nonce()) . '"';
    }
}

if (!function_exists('manage_inline_script')) {
    /** 內嵌 JS 片段（帶 nonce），供 echo 後符合 script-src nonce CSP */
    function manage_inline_script(string $js): string {
        return script_open() . $js . script_close();
    }
}

if (!function_exists('sri_is_skipped_url')) {
    /** Google reCAPTCHA 等會頻繁更新的腳本不適用 SRI（hash 失效會導致整段 script 被瀏覽器封鎖） */
    function sri_is_skipped_url(string $src): bool {
        $parts = parse_url(trim($src));
        if (empty($parts['host']) || empty($parts['path'])) {
            return false;
        }
        $host = strtolower((string)$parts['host']);
        $path = strtolower((string)$parts['path']);
        if (in_array($host, ['www.google.com', 'www.gstatic.com', 'www.recaptcha.net'], true)
            && str_contains($path, 'recaptcha')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('sri_integrity_for_url')) {
/** 外連 script 的 SRI integrity（見 include/sri_manifest.php） */
    function sri_integrity_for_url(string $src): ?string {
        static $manifest = null;
        if ($manifest === null) {
            $path = __DIR__ . '/sri_manifest.php';
            $manifest = is_file($path) ? (require $path) : [];
            if (!is_array($manifest)) {
                $manifest = [];
            }
        }
        $src = trim($src);
        if ($src === '' || !str_starts_with(strtolower($src), 'https://')) {
            return null;
        }
        if (sri_is_skipped_url($src)) {
            return null;
        }
        if (isset($manifest[$src])) {
            return (string)$manifest[$src];
        }
        $parts = parse_url($src);
        if (!empty($parts['scheme']) && !empty($parts['host']) && !empty($parts['path'])) {
            $base = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
            if (isset($manifest[$base])) {
                return (string)$manifest[$base];
            }
        }
        return null;
    }
}

if (!function_exists('recaptcha_script_tag')) {
/** 僅表單頁載入 reCAPTCHA（不加 SRI：Google 會不定期更新 api.js） */
    function recaptcha_script_tag(?string $hl = null): string {
        if (!function_exists('recaptcha_site_key') || recaptcha_site_key() === '') {
            return '';
        }
        if ($hl === null || $hl === '') {
            $lang = (int)($GLOBALS['this_lang'] ?? 1);
            $hl = ($lang === 2) ? 'en' : 'zh-TW';
        }
        $src = 'https://www.google.com/recaptcha/api.js?hl=' . rawurlencode($hl);
        return script_src_tag($src);
    }
}

if (!function_exists('script_src_tag')) {
/** 外連 script 標籤（含 nonce，供 strict-dynamic 信任鏈） */
    function script_src_tag(string $src, array $attrs = []): string {
        if (!isset($attrs['integrity'])) {
            $autoSri = sri_integrity_for_url($src);
            if ($autoSri !== null && $autoSri !== '') {
                $attrs['integrity'] = $autoSri;
                if (!isset($attrs['crossorigin'])) {
                    $attrs['crossorigin'] = 'anonymous';
                }
            }
        }
        $html = '<script src="' . e_attr($src) . '"' . csp_script_nonce_attr();
        foreach ($attrs as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $html .= ' ' . e_attr((string)$name);
                continue;
            }
            $html .= ' ' . e_attr((string)$name) . '="' . e_attr((string)$value) . '"';
        }
        return $html . '></script>';
    }
}

if (!function_exists('manage_alert_script')) {
/** 後台 alert / 導向（CSP nonce 腳本，取代 echo "<script>alert...") */
    function manage_alert_script(string $message, ?string $redirect = null, bool $historyBack = false): never {
        $js = 'alert(' . json_encode($message, JSON_UNESCAPED_UNICODE) . ');';
        if ($redirect !== null && $redirect !== '') {
            $js .= 'location.href=' . json_encode($redirect, JSON_UNESCAPED_UNICODE) . ';';
        } elseif ($historyBack) {
            $js .= 'history.back();';
        }
        echo manage_inline_script($js);
        exit;
    }
}

/* -------------------------------------------------------
 * 視圖輔助
 * -----------------------------------------------------*/
if (!function_exists('safe_tel')) {
    /** 電話號碼清理（僅保留數字與 +） */
    function safe_tel(string $tel): string {
        $clean = preg_replace('/[^0-9+]/', '', (string)$tel);
        return $clean === null ? '' : $clean;
    }
}
if (!function_exists('tel_href')) {
    /** tel: 連結（無有效號碼回 null） */
    function tel_href(string $tel): ?string {
        $clean = safe_tel($tel);
        return $clean === '' ? null : ('tel:' . $clean);
    }
}
if (!function_exists('is_external_link')) {
    /** href 是否為外站（有 host 且與 $web_host 不同） */
    function is_external_link(string $href, ?string $web_host = null): bool {
        $href = trim($href);
        if ($href === '') return false;
        $parsed = parse_url($href);
        if (!isset($parsed['host'])) return false;
        if ($web_host === null) return true;
        return strcasecmp((string)$parsed['host'], $web_host) !== 0;
    }
}

if (!function_exists('frontend_render_html')) {
    /**
     * 前台輸出 CKEditor 富文本：移除 script／行內事件，並正規化 Upload 路徑
     * （允許 HTML 標籤，Fortify 若仍告警屬 stored HTML 政策，可再加 HTMLPurifier）
     */
    function frontend_render_html(string $html): string {
        $html = trim(editor_html_decode_stored($html));
        if ($html === '') {
            return '';
        }
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/iu', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/iu', '', $html) ?? $html;
        $html = preg_replace('/\shref\s*=\s*(["\'])\s*javascript:[^"\']*\1/iu', ' href="#"', $html) ?? $html;

        global $web_root;
        $base = rtrim((string)($web_root ?? ''), '/');
        if ($base !== '') {
            $uploadUrl = $base . '/Upload/';
            $html = preg_replace(
                '#(src|href)\s*=\s*(["\'])(?:\.\./\.\./|\.\./|/)?Upload/#iu',
                '$1=$2' . $uploadUrl,
                $html
            ) ?? $html;
        }
        return $html;
    }
}

if (!function_exists('safe_inline_html')) {
/**
 * 保留少量行內排版標籤，移除屬性與危險標籤，避免 XSS。預設允許：p, span, br
 */
    function safe_inline_html(string $html, array $allowedTags = ['p','span','br']): string {
        $html = (string)$html;
        if (!class_exists('DOMDocument')) {
            return strip_tags($html, '<' . implode('><', $allowedTags) . '>');
        }
        $doc  = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);

        $wrapped = '<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="__w__">'.$html.'</div>';
        $doc->loadHTML($wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath   = new DOMXPath($doc);
        $wrapperList = $xpath->query('//*[@id="__w__"]');
        $wrapper = $wrapperList && $wrapperList->length ? $wrapperList->item(0) : null;
        if (!$wrapper) {
            return strip_tags($html, '<' . implode('><', $allowedTags) . '>');
        }

        foreach ($xpath->query('.//comment()', $wrapper) as $cmt) { $cmt->parentNode->removeChild($cmt); }
        foreach ($xpath->query('.//script|.//style', $wrapper) as $n) { $n->parentNode->removeChild($n); }

        $nodes = [];
        foreach ($xpath->query('.//*', $wrapper) as $n) { $nodes[] = $n; }

        $allowed = array_flip(array_map('strtolower', $allowedTags));
        foreach ($nodes as $el) {
            $tag = strtolower($el->nodeName);
            if (!isset($allowed[$tag])) {
                $frag = $doc->createDocumentFragment();
                while ($el->firstChild) $frag->appendChild($el->firstChild);
                $el->parentNode->replaceChild($frag, $el);
                continue;
            }
            while ($el->attributes && $el->attributes->length) {
                $el->removeAttributeNode($el->attributes->item(0));
            }
        }

        $out = '';
        foreach ($wrapper->childNodes as $child) $out .= $doc->saveHTML($child);
        $out = trim($out);
        if ($out === '') $out = strip_tags($html, '<' . implode('><', $allowedTags) . '>');
        return $out;
    }
}
