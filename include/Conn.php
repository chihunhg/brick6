<?php
// Conn.php — DB連線設定 + HSTS（強化版）
// 需搭配前面提供的 host.php（含 is_trusted_proxy() / current_scheme() / TRUSTED_DOMAINS 等）

// ===== HSTS（僅 APP_ENABLE_HSTS=1 且 production + HTTPS；Plesk/nginx 已送時請設 0 避免重複標頭）=====
(function () {
    if (PHP_SAPI === 'cli') return; // CLI 不送

    // 取 env
    $env        = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
    $enabledRaw = getenv('APP_ENABLE_HSTS') ?: ($_ENV['APP_ENABLE_HSTS'] ?? '1');
    $enabled    = in_array(strtolower((string)$enabledRaw), ['1','true','yes','on'], true);

    // 允許自訂 max-age / includeSubDomains / preload
    $maxAge     = (int) (getenv('APP_HSTS_MAX_AGE') ?: ($_ENV['APP_HSTS_MAX_AGE'] ?? 63072000)); // 2y
    $incSubRaw  = getenv('APP_HSTS_INCLUDE_SUBDOMAINS') ?: ($_ENV['APP_HSTS_INCLUDE_SUBDOMAINS'] ?? '1');
    $preloadRaw = getenv('APP_HSTS_PRELOAD') ?: ($_ENV['APP_HSTS_PRELOAD'] ?? '1');
    $incSubs    = in_array(strtolower((string)$incSubRaw),  ['1','true','yes','on'], true);
    $preload    = in_array(strtolower((string)$preloadRaw), ['1','true','yes','on'], true);

    // 以 host.php 的邏輯為準：信任網域 + 是否 https + 例外清單
    $host      = $_SERVER['HTTP_HOST'] ?? '';
    $reqHost   = strtolower(parse_url('//' . $host, PHP_URL_HOST) ?? '');
    $isTrusted = in_array($reqHost, (defined('TRUSTED_DOMAINS') ? TRUSTED_DOMAINS : [$reqHost]), true);
    $scheme    = function_exists('current_scheme') ? current_scheme() : ((($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http');

    // 單標籤本機（localhost）不送；含點的開發網域（如 brick6.local）可送
    $noHttps = function_exists('normalize_hostlist') && function_exists('env_list')
        ? normalize_hostlist(env_list('NO_HTTPS_DOMAINS'))
        : [];
    $isNoHttps = in_array($reqHost, $noHttps, true)
        || ($reqHost === 'localhost' || $reqHost === '127.0.0.1');

    // 僅在 production + 開啟 + https + 可信主機 + 非例外 才送
    if ($enabled && $env === 'production' && $scheme === 'https' && $isTrusted && !$isNoHttps) {
        if (!headers_sent()) {
            if (function_exists('header_remove')) {
                @header_remove('Strict-Transport-Security'); // 避免重複
            }
            $val = "max-age={$maxAge}";
            if ($incSubs) $val .= '; includeSubDomains';
            if ($preload) $val .= '; preload';
            header('Strict-Transport-Security: ' . $val);
        }
    }
})();

// ===== 安全化的 PDO 連線（支援 Unix Socket 與完整 TLS 選項）=====
function sql_conn(): ?PDO {
    // 基本連線參數
    $host   = getenv('DB_HOST')   ?: ($_ENV['DB_HOST']   ?? 'localhost');
    $port   = getenv('DB_PORT')   ?: ($_ENV['DB_PORT']   ?? '3306');
    $name   = getenv('DB_NAME')   ?: ($_ENV['DB_NAME']   ?? '');
    $user   = getenv('DB_USER')   ?: ($_ENV['DB_USER']   ?? '');
    $pass   = getenv('DB_PASSWD') ?: ($_ENV['DB_PASSWD'] ?? '');

    if (trim((string)$name) === '') {
        error_log('[DB ERROR] DB_NAME 未設定。開發機：brick6/.env；正式機：private/.env + config/env.path.php（並刪除 httpdocs/.env）');
        return null;
    }

    // 可選：Unix Socket（若設置則優先使用）
    $socket = getenv('DB_SOCKET') ?: ($_ENV['DB_SOCKET'] ?? '');

    // TLS 相關（依需求提供其中任一）
    $ssl_ca     = getenv('MYSQL_SSL_CA')     ?: ($_ENV['MYSQL_SSL_CA']     ?? '');
    $ssl_cert   = getenv('MYSQL_SSL_CERT')   ?: ($_ENV['MYSQL_SSL_CERT']   ?? '');
    $ssl_key    = getenv('MYSQL_SSL_KEY')    ?: ($_ENV['MYSQL_SSL_KEY']    ?? '');
    $ssl_capath = getenv('MYSQL_SSL_CAPATH') ?: ($_ENV['MYSQL_SSL_CAPATH'] ?? '');
    $ssl_cipher = getenv('MYSQL_SSL_CIPHER') ?: ($_ENV['MYSQL_SSL_CIPHER'] ?? '');
    $ssl_verify = getenv('MYSQL_SSL_VERIFY') ?: ($_ENV['MYSQL_SSL_VERIFY'] ?? '1'); // 1=true

    // 可選：設定時區（例：+08:00 或 UTC）
    $tz = getenv('DB_TIMEZONE') ?: ($_ENV['DB_TIMEZONE'] ?? '');

    try {
        // 組 DSN：優先走 unix_socket
        if ($socket !== '') {
            $dsn = "mysql:unix_socket={$socket};dbname={$name};charset=utf8mb4";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        }

        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // 原生預處理，防多語句注入
            PDO::ATTR_TIMEOUT            => 5,      // 註：MySQL 對此支援有限，仍可保留
        ];

        // TLS 啟用：只要任一 SSL 參數存在就嘗試設定
        $wantsTls = ($ssl_ca !== '' || $ssl_cert !== '' || $ssl_key !== '' || $ssl_capath !== '' || $ssl_cipher !== '');
        if ($wantsTls) {
            if ($ssl_ca     !== '') $opts[PDO::MYSQL_ATTR_SSL_CA]     = $ssl_ca;
            if ($ssl_cert   !== '') $opts[PDO::MYSQL_ATTR_SSL_CERT]   = $ssl_cert;
            if ($ssl_key    !== '') $opts[PDO::MYSQL_ATTR_SSL_KEY]    = $ssl_key;
            if ($ssl_capath !== '') $opts[PDO::MYSQL_ATTR_SSL_CAPATH] = $ssl_capath;
            if ($ssl_cipher !== '') $opts[PDO::MYSQL_ATTR_SSL_CIPHER] = $ssl_cipher;
            // 核對伺服器證書（部分版本才有）
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = in_array(strtolower((string)$ssl_verify), ['1','true','yes','on'], true);
            }
        }

        $pdo = new PDO($dsn, $user, $pass, $opts);

        // 可選：設定連線層參數（時區、嚴格模式等）
        if ($tz !== '') {
            // MySQL 接受 '+08:00' 或 'UTC'；不在這裡做校驗，交由 DB 檢查
            $pdo->exec("SET time_zone = " . $pdo->quote($tz));
        }
        // 建議開啟嚴格模式（依你的相容性斟酌）
        // $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

        return $pdo;
    } catch (PDOException $e) {
        // 避免把密碼等敏感資訊打到 log；只記核心訊息
        error_log('[DB ERROR] 連線失敗：' . $e->getMessage());
        return null; // 保持與你原本介面相容
    } catch (Throwable $e) {
        error_log('[DB ERROR] 未預期錯誤：' . $e->getMessage());
        return null;
    }
}

?>
