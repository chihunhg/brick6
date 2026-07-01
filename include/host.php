<?php
// host.php — hardened bootstrap for env, host/scheme detection, and path utilities
// ------------------------------------------------------------
// 強化點：
// - env 載入 (php 陣列/env 檔)
// - Proxy / Host 安全
// - 路徑與 URL 處理 (safe_request_path, RootURL, RootForder)
// - 密碼 Pepper
// ------------------------------------------------------------
/* ========== Project root auto-detect ========== */
if (!function_exists('find_project_root')) {
    /**
     * 從起點往上找專案根目錄：找到任一錨點就回傳該層路徑
     * @param string $startDir 起點資料夾（建議用 __DIR__）
     * @param array $markers  判定根目錄的錨點檔案/資料夾
     * @param int $maxDepth   最多往上幾層（避免無限迴圈）
     */
    function find_project_root(string $startDir, array $markers, int $maxDepth = 10): string {
        $dir = realpath($startDir) ?: $startDir;
        $dir = rtrim(str_replace('\\', '/', $dir), '/');

        for ($i = 0; $i <= $maxDepth; $i++) {
            foreach ($markers as $m) {
                $p = $dir . '/' . ltrim($m, '/');
                if (is_file($p) || is_dir($p)) {
                    return $dir;
                }
            }
            $parent = rtrim(str_replace('\\', '/', dirname($dir)), '/');
            if ($parent === '' || $parent === $dir) break;
            $dir = $parent;
        }

        // 找不到就退回到 startDir 的上一層（相對保守）
        $fallback = rtrim(str_replace('\\', '/', dirname($startDir)), '/');
        return $fallback !== '' ? $fallback : rtrim(str_replace('\\', '/', $startDir), '/');
    }
}

/* ========== .env 載入 ========== */
if (!function_exists('load_env_text')) {
    /**
     * @param bool $onlyIfMissing true 時僅補齊尚未設定或為空值的變數（供上層 .env 合併）
     */
    function load_env_text(string $file, bool $onlyIfMissing = false): bool {
        if (!is_file($file)) return false;
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) return false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (!str_contains($line, '=')) continue;

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // 行內 # 註解（未加引號時）
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $value = preg_replace('/(?<!\\\\)#.*$/', '', $value);
                $value = trim($value);
                $value = str_replace('\#', '#', $value);
            }

            // 引號與跳脫
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $q = $value[0];
                if (substr($value, -1) === $q) $value = substr($value, 1, -1);
                if ($q === '"') {
                    $value = strtr($value, [
                        '\n' => "\n", '\r' => "\r", '\t' => "\t",
                        '\"' => '"', "\\'" => "'", '\\\\' => '\\'
                    ]);
                } else {
                    $value = str_replace('\\\\', '\\', $value);
                }
            }

            if ($onlyIfMissing) {
                $existing = $_ENV[$name] ?? getenv($name);
                if ($existing !== false && trim((string)$existing) !== '') {
                    continue;
                }
            } else {
                // 尊重已存在的環境變數與 $_ENV
                if (getenv($name) !== false) continue;
                if (array_key_exists($name, $_ENV)) continue;
            }

            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
        return true;
    }
}

if (!function_exists('load_env_php')) {
    function load_env_php(string $file): bool {
        if (!is_file($file)) return false;
        /** @noinspection PhpIncludeInspection */
        $vars = include $file;
        if (!is_array($vars)) return false;
        foreach ($vars as $k => $v) {
            if ($k === '' || $v === '') continue;
            if (getenv($k) !== false) continue;
            if (array_key_exists($k, $_ENV)) continue;
            $_ENV[$k] = (string)$v;
            putenv($k . '=' . $v);
        }
        return true;
    }
}

if (!function_exists('load_env_any')) {
    function load_env_any(string $file): bool {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'php')  return load_env_php($file);
        return load_env_text($file);
    }
}

if (!function_exists('find_env_config_dir')) {
    /**
     * 自 include/ 往上找 .env（可放在 brick6 上層、網站目錄外，弱掃較不易掃到）
     */
    function find_env_config_dir(string $startDir, int $maxDepth = 12): ?string {
        $dir = realpath($startDir) ?: $startDir;
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        $candidates = ['.env', 'config/env.local.php', 'config/env.local'];

        for ($i = 0; $i <= $maxDepth; $i++) {
            foreach ($candidates as $rel) {
                if (is_file($dir . '/' . $rel)) {
                    return $dir;
                }
            }
            // Plesk：private/.env 不在 httpdocs 內，URL 無法直接存取
            if (is_file($dir . '/private/.env')) {
                $resolved = realpath($dir . '/private');
                return $resolved !== false ? $resolved : $dir . '/private';
            }
            $pleskPrivate = $dir . '/../private/.env';
            if (is_file($pleskPrivate)) {
                $resolved = realpath(dirname($pleskPrivate));
                if ($resolved !== false) {
                    return $resolved;
                }
            }
            $parent = rtrim(str_replace('\\', '/', dirname($dir)), '/');
            if ($parent === '' || $parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        return null;
    }
}

if (!function_exists('load_env_from_dir')) {
    function load_env_from_dir(string $dir): bool {
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        return load_env_any($dir . '/.env')
            || load_env_any($dir . '/config/env.local.php')
            || load_env_any($dir . '/config/env.local');
    }
}

// brick6 專案根（本機 WAMP：.env 可與專案同目錄，例 www/brick6/.env）
$__brick6Root = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

if (!function_exists('load_env_from_path_file')) {
    /** 正式機可設 config/env.path.php，指向 private 等非 httpdocs 目錄 */
    function load_env_from_path_file(string $brick6Root): ?string {
        $pathFile = rtrim(str_replace('\\', '/', $brick6Root), '/') . '/config/env.path.php';
        if (!is_file($pathFile)) {
            return null;
        }
        $envRoot = include $pathFile;
        if (!is_string($envRoot) || trim($envRoot) === '') {
            return null;
        }
        $envRoot = rtrim(str_replace('\\', '/', trim($envRoot)), '/');
        return load_env_from_dir($envRoot) ? $envRoot : null;
    }
}

if (!function_exists('load_env_supplement_dir')) {
    /** 合併補齊上層目錄 .env 中尚未設定的變數（正式機 private/.env 常用） */
    function load_env_supplement_dir(string $dir): bool {
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        $file = $dir . '/.env';
        if (!is_file($file)) {
            return false;
        }
        return load_env_text($file, true);
    }
}

// 1) config/env.path.php（正式機優先：指向 private 等非公開目錄）
$__envDir = null;
$__loaded = false;
$__fromPath = load_env_from_path_file($__brick6Root);
if ($__fromPath !== null) {
    $__envDir = $__fromPath;
    $__loaded = true;
}

// 2) 專案同層 brick6/.env（開發機 WAMP：tsg12 等）
if (!$__loaded) {
    $__loaded = load_env_from_dir($__brick6Root);
    if ($__loaded) {
        $__envDir = $__brick6Root;
    }
}

// 3) 再往上找（含 Plesk private/.env；勿把 httpdocs/.env 當對外測試路徑）
if (!$__loaded) {
    $__envDir = find_env_config_dir(__DIR__, 12);
    $__loaded = $__envDir !== null && load_env_from_dir($__envDir);
}

// 3) 程式根目錄仍以 brick6 為準（composer.json）
$__base = find_project_root(__DIR__, ['composer.json', '.git'], 12);
$__base = rtrim(str_replace('\\', '/', $__base), '/');
if (!is_file($__base . '/composer.json') && !is_dir($__base . '/.git')) {
    $__base = $__brick6Root;
}

if (!$__loaded) {
    $__loaded = load_env_from_dir($__base)
        || load_env_any(__DIR__ . '/.env');
    if ($__loaded && $__envDir === null) {
        $__envDir = is_file($__base . '/.env') ? $__base : __DIR__;
    }
}

// 4) 合併補齊：brick6/.env 已載入但 GEMINI 等金鑰在上層 private/.env（tsg5 常見佈局）
$__brick6Parent = dirname($__brick6Root);
if ($__brick6Parent !== $__brick6Root) {
    load_env_supplement_dir($__brick6Parent);
}
$__pleskPrivateDir = realpath($__brick6Root . '/../private');
if ($__pleskPrivateDir !== false && is_dir($__pleskPrivateDir)) {
    load_env_supplement_dir($__pleskPrivateDir);
}

if ($__envDir !== null && !defined('APP_ENV_DIR')) {
    define('APP_ENV_DIR', $__envDir);
}
if (!defined('APP_PROJECT_ROOT')) {
    define('APP_PROJECT_ROOT', $__base);
}

$GLOBALS['__env_loaded'] = $__loaded;

if (!function_exists('app_env_bootstrap_ok')) {
    function app_env_bootstrap_ok(): bool {
        return !empty($GLOBALS['__env_loaded']);
    }
}

/* ========== 依 APP_ENV 設定 PHP 錯誤顯示 ========== */
if (!function_exists('app_env')) {
    function app_env(): string {
        return strtolower(trim((string)($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production')));
    }
}
if (!function_exists('app_is_production')) {
    function app_is_production(): bool {
        return in_array(app_env(), ['production', 'prod'], true);
    }
}
if (!function_exists('app_configure_error_display')) {
    function app_configure_error_display(): void {
        if (app_is_production()) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            ini_set('html_errors', '1');
        }
    }
}
if (!function_exists('app_show_bootstrap_error')) {
    /** 啟動階段 SQL/系統錯誤：開發環境顯示檔案與行號 */
    function app_show_bootstrap_error(array $result): void {
        $file = (string)($result['File'] ?? '');
        $line = (int)($result['Line'] ?? 0);
        $msg  = (string)($result['Message'] ?? '');
        echo '<pre style="background:#fff3f3;border:1px solid #c00;padding:12px;font:13px/1.5 Consolas,monospace">';
        echo '<strong>程式發生錯誤</strong>' . "\n";
        if ($file !== '') {
            echo '位置: ' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . ':' . $line . "\n";
        }
        if ($msg !== '') {
            echo '訊息: ' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "\n\n";
        }
        print_r($result);
        echo '</pre>';
    }
}

/* ========== Host utilities ========== */
if (!function_exists('env_list')) {
    function env_list(string $key): array {
        $raw = $_ENV[$key] ?? '';
        if ($raw === '') return [];
        $parts = preg_split('/[,\s;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        return $parts ?: [];
    }
}

if (!function_exists('normalize_hostlist')) {
    function normalize_hostlist(array $hosts): array {
        $ok = [];
        foreach ($hosts as $h) {
            $h = trim($h);
            if ($h === '') continue;
            // 去協定/路徑，僅取 host:port
            $h = parse_url(str_starts_with($h, '//') ? $h : ('//' . $h), PHP_URL_HOST) ?: $h;
            $h = trim($h, " \t\n\r\0\x0B.");
            // 去除 IPv6 方括號
            if ($h !== '' && $h[0] === '[' && substr($h, -1) === ']') $h = substr($h, 1, -1);
            // IDNA
            if (function_exists('idn_to_ascii')) {
                $idna = idn_to_ascii($h, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                if ($idna !== false) $h = $idna;
            }
            $h = strtolower($h);
            if (filter_var($h, FILTER_VALIDATE_IP)) { $ok[$h] = true; continue; }
            if (preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i', $h)) {
                $ok[$h] = true;
            }
        }
        return array_keys($ok);
    }
}

/* ========== Trusted proxies ========== */
if (!function_exists('cidr_match')) {
    function cidr_match(string $ip, string $cidr): bool {
        if (strpos($cidr, '/') === false) return $ip === $cidr;
        [$subnet, $mask] = explode('/', $cidr, 2);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $mask = (int)$mask;
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask_long = -1 << (32 - $mask);
            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip_bin = inet_pton($ip);
            $subnet_bin = inet_pton($subnet);
            $mask = (int)$mask;
            $bytes = intdiv($mask, 8);
            $bits  = $mask % 8;
            if ($bytes && substr($ip_bin, 0, $bytes) !== substr($subnet_bin, 0, $bytes)) return false;
            if ($bits) {
                $mask_byte = ~((1 << (8 - $bits)) - 1) & 0xFF;
                return (ord($ip_bin[$bytes]) & $mask_byte) === (ord($subnet_bin[$bytes]) & $mask_byte);
            }
            return true;
        }
        return false;
    }
}

if (!function_exists('is_trusted_proxy')) {
    function is_trusted_proxy(): bool {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($remote === '') return false;
        $list = normalize_hostlist(env_list('TRUSTED_PROXIES'));
        if (!$list) return false;
        foreach ($list as $item) {
            if (cidr_match($remote, $item)) return true;
        }
        return false;
    }
}

/* ========== Domain / scheme ========== */
$trustedFromEnv = normalize_hostlist(env_list('TRUSTED_DOMAINS'));
$appDomainEnv   = normalize_hostlist([$_ENV['APP_DOMAIN'] ?? '']);
if (empty($trustedFromEnv)) {
    $currentHost = strtolower(parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?? '');
    $trustedFromEnv = normalize_hostlist([$currentHost ?: 'localhost']);
}
$APP_DOMAIN = $appDomainEnv[0] ?? ($trustedFromEnv[0] ?? 'localhost');
if (!in_array($APP_DOMAIN, $trustedFromEnv, true)) {
    array_unshift($trustedFromEnv, $APP_DOMAIN);
    $trustedFromEnv = array_values(array_unique($trustedFromEnv));
}
$FORCE_HTTPS = $_ENV['FORCE_HTTPS'] ?? '1';
$FORCE_HTTPS = in_array(strtolower((string)$FORCE_HTTPS), ['1','true','yes','on'], true);

if (!defined('TRUSTED_DOMAINS')) define('TRUSTED_DOMAINS', $trustedFromEnv);
if (!defined('APP_DOMAIN'))      define('APP_DOMAIN',      $APP_DOMAIN);
if (!defined('FORCE_HTTPS'))     define('FORCE_HTTPS',     $FORCE_HTTPS);

/* ========== Path: 強化版 safe_request_path() ========== */
if (!function_exists('safe_request_path')) {
    function safe_request_path(): string {
        $raw = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
        if ($raw === null || $raw === false) $raw = $_SERVER['SCRIPT_NAME'] ?? '/';
        $raw = (string)$raw;

        // 只取 path（丟棄 query/fragment）
        $path = parse_url($raw, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = (string)($_SERVER['SCRIPT_NAME'] ?? '/');
        }

        // 移除不可見字元與反斜線
        $path = preg_replace('/[\x00-\x1F\x7F]/u', '', $path); // control chars
        $path = str_replace('\\', '/', $path);

        // 快速阻擋常見 XSS / SQLi 向量
        $suspicious_patterns = [
            '/[<>]/',             // < >
            '/["\'`]/',           // 引號與反引號
            '/\bon\w+\s*=/i',     // onmouseover= onerror= ...
            '/javascript\s*:/i',  // javascript:
            '/<!--/',             // HTML 註解
            '/-->/',              // HTML 註解
            '/\bunion\b/i',       // SQL injection hint（可選）
            '/\-\-/'              // SQL 經典註解（可選）
        ];
        foreach ($suspicious_patterns as $pat) {
            if (preg_match($pat, $path)) {
                error_log(sprintf(
                    "[SEC][%s] suspicious REQUEST_URI detected: raw=%s path=%s remote=%s agent=%s",
                    date('c'),
                    $raw,
                    $path,
                    $_SERVER['REMOTE_ADDR'] ?? '-',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
                ));
                // 保守處理：回根目錄（或改為 http_response_code(400); exit;）
                return '/';
            }
        }

        // 折疊重複斜線
        $path = preg_replace('#/+#', '/', $path);

        // 僅 decode %2E / %2e（避免 %2F → '/'）
        $path = preg_replace_callback('/%[0-9A-Fa-f]{2}/', function($m) {
            $hex = strtolower($m[0]);
            if ($hex === '%2e') return '.';
            return strtoupper($m[0]); // 標準化
        }, $path);

        // RFC 3986 dot-segment removal
        $in  = explode('/', $path);
        $out = [];
        foreach ($in as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($out); continue; }
            $out[] = $seg;
        }
        $normalized = '/' . implode('/', $out);
        if ($normalized === '') $normalized = '/';

        // 僅允許 ASCII printable + '/'
        $normalized = preg_replace('/[^\x20-\x7E\/]/', '', $normalized);
        return $normalized;
    }
}

if (!function_exists('current_host')) {
    function current_host(array $trusted = TRUSTED_DOMAINS): string {
        $reqHost = strtolower(parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?? '');
        return in_array($reqHost, $trusted, true) ? $reqHost : APP_DOMAIN;
    }
}

if (!function_exists('current_scheme')) {
    function current_scheme(): string {
        $reqHost   = current_host();
        $noHttps   = normalize_hostlist(env_list('NO_HTTPS_DOMAINS'));
        $isNoHttps = in_array($reqHost, $noHttps, true) || !str_contains($reqHost, '.');

        if (FORCE_HTTPS && !$isNoHttps) return 'https';

        if (is_trusted_proxy()) {
            $xfp = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
            if ($xfp !== '') {
                $first = strtolower(trim(explode(',', $xfp)[0]));
                if ($first === 'https') return 'https';
            }
            if (strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') return 'https';
        }

        if (($_SERVER['HTTPS'] ?? '') === 'on') return 'https';
        if ((string)($_SERVER['SERVER_PORT'] ?? '') === '443') return 'https';
        return 'http';
    }
}

/* ========== 路徑工具 ========== */
if (!function_exists('WorkForder')) {
    function WorkForder(string $path): string {
        $parts = explode('/', $path);
        array_pop($parts);
        $dir = implode('/', $parts);
        if ($dir === '') $dir = '/';
        if (substr($dir, -1) !== '/') $dir .= '/';
        return preg_replace('#/+#', '/', $dir);
    }
}

if (!function_exists('RootForder')) {
    function RootForder(string $str): string {
        // 1) 只取 URL 的 path（如果不是 URL，就當作原字串）
        $path = parse_url($str, PHP_URL_PATH);
        if ($path === null || $path === false || $path === '') {
            $path = $str;
        }

        // 2) 標準化分隔符與重複斜線
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);

        // 3) 拆段
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn($s) => $s !== ''));

        // 4) 依規則累積保留的段
        $stop_words = ['manage', 'en', 'tc', 'tw', 'sc', 'cn', 'jp'];
        $out = [];
        $n = count($segments);
        for ($i = 0; $i < $n; $i++) {
            $seg = $segments[$i];

            // 檔名：最後一段且含 '.' 視為檔名，停止（不納入）
            $isLast = ($i === $n - 1);
            if ($isLast && strpos($seg, '.') !== false) {
                break;
            }

            // 遇到停用詞就停止（不納入）
            if (in_array($seg, $stop_words, true)) {
                break;
            }

            $out[] = $seg;
        }

        // 5) 重建路徑（至少為 "/"）
        $res = '/' . implode('/', $out);
        if ($res !== '/') $res .= '/';

        return $res;
    }
}

if (!function_exists('RootURL')) {
    function RootURL(string $relativePath = '/'): string {
        $host = current_host();
        $scheme = current_scheme();

        // 若傳入的是完整 URL，就只取 PATH；否則用相對/絕對路徑
        $pathIn = parse_url($relativePath, PHP_URL_SCHEME)
            ? (parse_url($relativePath, PHP_URL_PATH) ?? '/')
            : $relativePath;

        $pathIn = preg_replace('#/+#', '/', parse_url($pathIn, PHP_URL_PATH) ?? '/');

        $segments = array_values(array_filter(explode('/', trim($pathIn, '/'))));
        $stopWords = ['manage','en','toyota','lexus','tc','tw','sc','cn','jp'];
        $keep = [];
        $n = count($segments);
        for ($i = 0; $i < $n; $i++) {
            $seg = $segments[$i];
            if (in_array($seg, $stopWords, true)) break;
            if ($i === $n - 1 && strpos($seg, '.') !== false) break;
            $keep[] = $seg;
        }

        $path = '/' . implode('/', $keep) . '/';
        $path = preg_replace('#/+#', '/', $path);

        return $scheme . '://' . $host . $path;
    }
}

if (!function_exists('site_favicon_href')) {
    /**
     * 從目前頁面目錄到網站根目錄 favicon 的相對路徑（非絕對 URL）
     */
    function site_favicon_href(string $filename = 'favicon.ico', ?string $workFile = null, ?string $webRoot = null): string {
        $filename = ltrim(str_replace('\\', '/', $filename), '/');
        if ($filename === '' || str_contains($filename, '..')) {
            return 'favicon.ico';
        }

        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $pageDir = rtrim(dirname($script !== '' ? $script : '/'), '/');

        if ($webRoot === null && isset($GLOBALS['web_root'])) {
            $webRoot = (string)$GLOBALS['web_root'];
        }
        if (($webRoot === null || $webRoot === '') && $workFile === null && isset($GLOBALS['WorkFile'])) {
            $workFile = (string)$GLOBALS['WorkFile'];
        }
        if ($workFile !== null && $workFile !== '' && function_exists('RootForder')) {
            $webRoot = RootForder($workFile);
        }

        $webRoot = rtrim(str_replace('\\', '/', (string)($webRoot ?? '/')), '/');
        if ($webRoot === '') {
            $webRoot = '/';
        }

        if ($pageDir === $webRoot) {
            return $filename;
        }

        if ($webRoot !== '/' && str_starts_with($pageDir . '/', $webRoot . '/')) {
            $sub = substr($pageDir, strlen($webRoot) + 1);
            if ($sub === '') {
                return $filename;
            }
            $depth = substr_count($sub, '/') + 1;
            return str_repeat('../', $depth) . $filename;
        }

        $trimmed = ltrim($pageDir, '/');
        $depth = $trimmed === '' ? 0 : substr_count($trimmed, '/') + 1;

        return ($depth > 0 ? str_repeat('../', $depth) : '') . $filename;
    }
}

/* ========== 全域變數/常數 ========== */
$REQUEST_URI_PATH = safe_request_path();
$SERVER_NAME      = current_host();
$PHP_SELF         = $_SERVER['SCRIPT_NAME'] ?? $REQUEST_URI_PATH;
$HTTPS_FLAG       = (current_scheme() === 'https') ? 'on' : 'off';

$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] ?? (realpath(__DIR__ . '/..') ?: __DIR__);

$PathForder= dirname(dirname(__FILE__)); // 目前實體目錄
$Forder    = WorkForder($REQUEST_URI_PATH); // 目前工作目錄（URL 路徑）
$page_link = basename($REQUEST_URI_PATH);   // 目前檔名（URL 路徑）
$WorkFile  = $Forder . $page_link;          // 完整相對路徑
$web_root  = RootForder($WorkFile);         // URL 根目錄前綴
$web_url   = RootURL($WorkFile);            // 網站 URL 前綴（結尾含 /）
$seo_url   = $web_url;                      // 同值

/* echo 'PathForder='.$PathForder.'<br/>'.PHP_EOL;
echo 'Forder='.$Forder.'<br/>'.PHP_EOL;
echo 'page_link='.$page_link.'<br/>'.PHP_EOL;
echo 'WorkFile='.$WorkFile.'<br/>'.PHP_EOL;
echo 'web_root='.$web_root.'<br/>'.PHP_EOL;
echo 'web_url='.$web_url.'<br/>'.PHP_EOL;
echo 'seo_url='.$seo_url.'<br/>'.PHP_EOL;
 */
// 全域輸出
$GLOBALS['REQUEST_URI_PATH'] = $REQUEST_URI_PATH;
$GLOBALS['SERVER_NAME']      = $SERVER_NAME;
$GLOBALS['PHP_SELF']         = $PHP_SELF;
$GLOBALS['HTTPS_FLAG']       = $HTTPS_FLAG;
$GLOBALS['DOCUMENT_ROOT']    = $DOCUMENT_ROOT;
$GLOBALS['PathForder']       = $PathForder;
$GLOBALS['Forder']           = $Forder;
$GLOBALS['page_link']        = $page_link;
$GLOBALS['WorkFile']         = $WorkFile;
$GLOBALS['web_root']         = $web_root;
$GLOBALS['web_url']           = $web_url;
$GLOBALS['seo_url']          = $seo_url;

// 常數輸出
if (!defined('APP_DOCUMENT_ROOT')) define('APP_DOCUMENT_ROOT', $DOCUMENT_ROOT);
if (!defined('APP_WEB_ROOT'))      define('APP_WEB_ROOT',      $web_root);
if (!defined('APP_WEB_URL'))       define('APP_WEB_URL',       $web_url);
if (!defined('APP_SEO_URL'))       define('APP_SEO_URL',       $seo_url);
if (!defined('APP_WORKFILE'))      define('APP_WORKFILE',      $WorkFile);
if (!defined('APP_FORDER'))        define('APP_FORDER',        $Forder);

// 修正 APP_WEB_PATH（避免 //）
$tmpRoot = trim($web_root, '/');
if (!defined('APP_WEB_PATH')) define('APP_WEB_PATH', $tmpRoot === '' ? '/' : '/' . $tmpRoot . '/');

/* ========== Cookie domain 建議值 ========== */
if (!function_exists('cookie_domain_from_env')) {
    function cookie_domain_from_env(): ?string {
        $host = current_host();
        return in_array($host, TRUSTED_DOMAINS, true) ? $host : null;
    }
}

/* ========== Upload Base ========== */
if (!defined('UPLOAD_BASE')) {
    $__upload = realpath(__DIR__ . '/../Upload');
    if ($__upload === false) $__upload = __DIR__ . '/../Upload';
    if (!is_dir($__upload)) @mkdir($__upload, 0755, true);
    define('UPLOAD_BASE', rtrim(str_replace('\\', '/', $__upload), '/'));
}

/* ========== 密碼 Pepper ========== */
if (!function_exists('get_password_pepper')) {
    function get_password_pepper(): string {
        $pepper = $_ENV['PASSWORD_PEPPER'] ?? getenv('PASSWORD_PEPPER') ?? '';
        if ($pepper === '') throw new RuntimeException('PASSWORD_PEPPER is not set');
        return $pepper;
    }
}

if (!function_exists('hash_password')) {
    function hash_password(string $plain): string {
        $peppered = hash_hmac('sha256', $plain, get_password_pepper());
        return password_hash($peppered, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verify_password')) {
    function verify_password(string $plain, string $currentHash, ?callable $rehashSaver = null): bool {
        $peppered = hash_hmac('sha256', $plain, get_password_pepper());
        $ok = password_verify($peppered, $currentHash);
        if ($ok && password_needs_rehash($currentHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($peppered, PASSWORD_DEFAULT);
            if ($rehashSaver) {
                try { $rehashSaver($newHash); } catch (Throwable $e) { error_log($e->getMessage()); }
            }
        }
        return $ok;
    }
}
