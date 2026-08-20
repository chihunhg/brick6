<?php
declare(strict_types=1);

/**
 * 後台 TOTP 多因素驗證（自建 RFC 6238，相容 Google / Microsoft Authenticator）
 */

if (!function_exists('manage_mfa_schema_ready')) {
    function manage_mfa_schema_ready(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        if (!function_exists('crud_table_has_column')) {
            $ready = false;
            return $ready;
        }
        $ready = crud_table_has_column('webcontrol', 'mfa_enabled')
            && crud_table_has_column('webcontrol', 'totp_secret')
            && crud_table_has_column('webcontrol', 'mfa_backup_codes');
        return $ready;
    }
}

if (!function_exists('manage_mfa_onboard_schema_ready')) {
    function manage_mfa_onboard_schema_ready(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        if (!function_exists('crud_table_has_column')) {
            $ready = false;
            return $ready;
        }
        $ready = crud_table_has_column('webcontrol', 'strEmail')
            && crud_table_has_column('webcontrol', 'mfa_setup_pending');
        return $ready;
    }
}

if (!function_exists('manage_mfa_user_extra_select_sql')) {
    function manage_mfa_user_extra_select_sql(): string
    {
        $cols = [];
        if (manage_mfa_schema_ready()) {
            $cols[] = 'mfa_enabled';
            $cols[] = 'totp_secret';
            $cols[] = 'mfa_backup_codes';
        }
        if (manage_mfa_onboard_schema_ready()) {
            $cols[] = 'strEmail';
            $cols[] = 'mfa_setup_pending';
        }
        return $cols === [] ? '' : ', ' . implode(', ', $cols);
    }
}

if (!function_exists('manage_mfa_base32_chars')) {
    function manage_mfa_base32_chars(): string
    {
        return 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    }
}

if (!function_exists('manage_mfa_base32_encode')) {
    function manage_mfa_base32_encode(string $data): string
    {
        if ($data === '') {
            return '';
        }
        $alphabet = manage_mfa_base32_chars();
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $chunks = str_split($binary, 5);
        $encoded = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $encoded .= $alphabet[bindec($chunk)];
        }
        $padding = (8 - (strlen($encoded) % 8)) % 8;
        return $encoded . str_repeat('=', $padding);
    }
}

if (!function_exists('manage_mfa_base32_decode')) {
    function manage_mfa_base32_decode(string $value): string
    {
        $value = strtoupper(preg_replace('/[^A-Z2-7=]/', '', $value) ?? '');
        $value = rtrim($value, '=');
        if ($value === '') {
            return '';
        }
        $alphabet = manage_mfa_base32_chars();
        $binary = '';
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($alphabet, $value[$i]);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = str_split($binary, 8);
        $decoded = '';
        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }
        return $decoded;
    }
}

if (!function_exists('manage_mfa_generate_secret')) {
    function manage_mfa_generate_secret(int $bytes = 20): string
    {
        return manage_mfa_base32_encode(random_bytes(max(10, $bytes)));
    }
}

if (!function_exists('manage_mfa_hotp')) {
    function manage_mfa_hotp(string $secretBase32, int $counter): string
    {
        $key = manage_mfa_base32_decode($secretBase32);
        if ($key === '') {
            return '';
        }
        $binCounter = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $binCounter, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $truncated = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        );
        return str_pad((string)($truncated % 1_000_000), 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('manage_mfa_totp')) {
    function manage_mfa_totp(string $secretBase32, ?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        $counter = (int) floor($ts / 30);
        return manage_mfa_hotp($secretBase32, $counter);
    }
}

if (!function_exists('manage_mfa_verify_totp')) {
    function manage_mfa_verify_totp(string $secretBase32, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $counter = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(manage_mfa_hotp($secretBase32, $counter + $i), $code)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('manage_mfa_encrypt_secret')) {
    function manage_mfa_encrypt_secret(string $secretBase32): string
    {
        if (!function_exists('Authcode')) {
            return '';
        }
        return (string)Authcode($secretBase32, 'ENCODE', (string)($_ENV['APP_SECRET_KEY'] ?? ''), 0);
    }
}

if (!function_exists('manage_mfa_decrypt_secret')) {
    function manage_mfa_decrypt_secret(string $encrypted): string
    {
        if ($encrypted === '' || !function_exists('Authcode')) {
            return '';
        }
        return (string)Authcode($encrypted, 'DECODE', (string)($_ENV['APP_SECRET_KEY'] ?? ''), 0);
    }
}

if (!function_exists('manage_mfa_otpauth_uri')) {
    function manage_mfa_otpauth_uri(string $secretBase32, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        $params = http_build_query([
            'secret' => $secretBase32,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);
        return 'otpauth://totp/' . $label . '?' . $params;
    }
}

if (!function_exists('manage_mfa_qr_image_url')) {
    function manage_mfa_qr_image_url(string $otpauthUri, int $size = 220): string
    {
        $size = max(120, min(400, $size));
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
            . '&data=' . rawurlencode($otpauthUri);
    }
}

if (!function_exists('manage_mfa_fetch_user')) {
    /** @return array<string, mixed>|null */
    function manage_mfa_fetch_user(int $pkey): ?array
    {
        if ($pkey <= 0) {
            return null;
        }
        return crud_fetch_one(
            'SELECT PKey, strID, strName, FunctionID' . manage_mfa_user_extra_select_sql()
            . ' FROM webcontrol WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
    }
}

if (!function_exists('manage_mfa_fetch_user_by_login')) {
    /** @return array<string, mixed>|null */
    function manage_mfa_fetch_user_by_login(string $loginId): ?array
    {
        $loginId = trim($loginId);
        if ($loginId === '') {
            return null;
        }
        return crud_fetch_one(
            'SELECT PKey, strID, strName, FunctionID' . manage_mfa_user_extra_select_sql()
            . ' FROM webcontrol WHERE strID = :id LIMIT 1',
            ['id' => $loginId]
        );
    }
}

if (!function_exists('manage_mfa_is_enabled_for_user')) {
    function manage_mfa_is_enabled_for_user(array $row): bool
    {
        if (!manage_mfa_schema_ready()) {
            return false;
        }
        return (int)($row['mfa_enabled'] ?? 0) === 1
            && trim((string)($row['totp_secret'] ?? '')) !== '';
    }
}

if (!function_exists('manage_mfa_user_secret')) {
    function manage_mfa_user_secret(array $row): string
    {
        $encrypted = trim((string)($row['totp_secret'] ?? ''));
        if ($encrypted === '') {
            return '';
        }
        return manage_mfa_decrypt_secret($encrypted);
    }
}

if (!function_exists('manage_mfa_generate_backup_codes')) {
    /** @return list<string> */
    function manage_mfa_generate_backup_codes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}

if (!function_exists('manage_mfa_hash_backup_codes')) {
    /** @param list<string> $plainCodes @return list<string> */
    function manage_mfa_hash_backup_codes(array $plainCodes): array
    {
        $hashed = [];
        foreach ($plainCodes as $code) {
            $code = strtoupper(trim($code));
            if ($code !== '') {
                $hashed[] = password_hash($code, PASSWORD_DEFAULT);
            }
        }
        return $hashed;
    }
}

if (!function_exists('manage_mfa_backup_codes_remaining')) {
    function manage_mfa_backup_codes_remaining(array $row): int
    {
        $json = trim((string)($row['mfa_backup_codes'] ?? ''));
        if ($json === '') {
            return 0;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? count($decoded) : 0;
    }
}

if (!function_exists('manage_mfa_verify_backup_code')) {
    function manage_mfa_verify_backup_code(int $pkey, string $code): bool
    {
        $row = manage_mfa_fetch_user($pkey);
        if ($row === null) {
            return false;
        }
        $json = trim((string)($row['mfa_backup_codes'] ?? ''));
        if ($json === '') {
            return false;
        }
        $hashes = json_decode($json, true);
        if (!is_array($hashes) || $hashes === []) {
            return false;
        }
        $code = strtoupper(preg_replace('/\s+/', '', $code) ?? '');
        if ($code === '') {
            return false;
        }
        $matchedIndex = null;
        foreach ($hashes as $idx => $hash) {
            if (is_string($hash) && password_verify($code, $hash)) {
                $matchedIndex = $idx;
                break;
            }
        }
        if ($matchedIndex === null) {
            return false;
        }
        unset($hashes[$matchedIndex]);
        $hashes = array_values($hashes);
        $pdo = new dbPDO();
        $pdo->update('webcontrol', [
            'mfa_backup_codes' => json_encode($hashes, JSON_UNESCAPED_UNICODE),
            'dtUDate' => date('Y-m-d H:i:s'),
        ], 'PKey', $pkey);
        $pdo->close();
        return true;
    }
}

if (!function_exists('manage_mfa_save_enabled')) {
    /** @param list<string> $plainBackupCodes */
    function manage_mfa_save_enabled(int $pkey, string $secretBase32, array $plainBackupCodes): void
    {
        if (!manage_mfa_schema_ready()) {
            throw new RuntimeException('MFA 資料表欄位尚未建立，請先執行 sql/webcontrol_totp_mfa.sql');
        }
        $pdo = new dbPDO();
        $update = [
            'mfa_enabled' => 1,
            'totp_secret' => manage_mfa_encrypt_secret($secretBase32),
            'mfa_backup_codes' => json_encode(manage_mfa_hash_backup_codes($plainBackupCodes), JSON_UNESCAPED_UNICODE),
            'dtUDate' => date('Y-m-d H:i:s'),
        ];
        if (manage_mfa_onboard_schema_ready()) {
            $update['mfa_setup_pending'] = 0;
        }
        $pdo->update('webcontrol', $update, 'PKey', $pkey);
        $pdo->close();
    }
}

if (!function_exists('manage_mfa_disable')) {
    function manage_mfa_disable(int $pkey): void
    {
        if (!manage_mfa_schema_ready()) {
            return;
        }
        $pdo = new dbPDO();
        $pdo->update('webcontrol', [
            'mfa_enabled' => 0,
            'totp_secret' => '',
            'mfa_backup_codes' => null,
            'dtUDate' => date('Y-m-d H:i:s'),
        ], 'PKey', $pkey);
        $pdo->close();
    }
}

if (!function_exists('manage_mfa_pending_timeout')) {
    function manage_mfa_pending_timeout(): int
    {
        return 300;
    }
}

if (!function_exists('manage_mfa_begin_pending_login')) {
    function manage_mfa_begin_pending_login(array $row): void
    {
        session_regenerate_id(true);
        $_SESSION['MFA_Pending'] = true;
        $_SESSION['MFA_User_PKey'] = (int)($row['PKey'] ?? 0);
        $_SESSION['MFA_strID'] = (string)($row['strID'] ?? '');
        $_SESSION['MFA_UserName'] = (string)($row['strName'] ?? '');
        $_SESSION['MFA_FunctionID'] = (string)($row['FunctionID'] ?? '');
        $_SESSION['MFA_Started'] = time();
        $_SESSION['MFA_Attempts'] = 0;
        unset($_SESSION['Manage'], $_SESSION['Login_ID'], $_SESSION['UserName'], $_SESSION['FunctionID'], $_SESSION['Login_PKey']);
    }
}

if (!function_exists('manage_mfa_pending_valid')) {
    function manage_mfa_pending_valid(): bool
    {
        if (empty($_SESSION['MFA_Pending']) || empty($_SESSION['MFA_User_PKey'])) {
            return false;
        }
        $started = (int)($_SESSION['MFA_Started'] ?? 0);
        if ($started <= 0 || (time() - $started) > manage_mfa_pending_timeout()) {
            manage_mfa_clear_pending_login();
            return false;
        }
        return true;
    }
}

if (!function_exists('manage_mfa_clear_pending_login')) {
    function manage_mfa_clear_pending_login(): void
    {
        unset(
            $_SESSION['MFA_Pending'],
            $_SESSION['MFA_User_PKey'],
            $_SESSION['MFA_strID'],
            $_SESSION['MFA_UserName'],
            $_SESSION['MFA_FunctionID'],
            $_SESSION['MFA_Started'],
            $_SESSION['MFA_Attempts']
        );
    }
}

if (!function_exists('manage_mfa_complete_login')) {
    function manage_mfa_complete_login(): void
    {
        if (!manage_mfa_pending_valid()) {
            throw new RuntimeException('MFA 驗證逾時，請重新登入');
        }
        session_regenerate_id(true);
        $_SESSION['Manage'] = 'Yes';
        $_SESSION['Login_ID'] = (string)($_SESSION['MFA_strID'] ?? '');
        $_SESSION['UserName'] = (string)($_SESSION['MFA_UserName'] ?? '');
        $_SESSION['FunctionID'] = (string)($_SESSION['MFA_FunctionID'] ?? '');
        $_SESSION['Login_PKey'] = (int)($_SESSION['MFA_User_PKey'] ?? 0);
        manage_mfa_clear_pending_login();
    }
}

if (!function_exists('manage_mfa_establish_session')) {
    function manage_mfa_establish_session(array $row): void
    {
        session_regenerate_id(true);
        $_SESSION['Manage'] = 'Yes';
        $_SESSION['UserName'] = (string)($row['strName'] ?? '');
        $_SESSION['Login_ID'] = (string)($row['strID'] ?? '');
        $_SESSION['FunctionID'] = (string)($row['FunctionID'] ?? '');
        $_SESSION['Login_PKey'] = (int)($row['PKey'] ?? 0);
        manage_mfa_clear_pending_login();
    }
}

if (!function_exists('manage_mfa_verify_for_user')) {
    function manage_mfa_verify_for_user(int $pkey, string $code): bool
    {
        $row = manage_mfa_fetch_user($pkey);
        if ($row === null || !manage_mfa_is_enabled_for_user($row)) {
            return false;
        }
        $secret = manage_mfa_user_secret($row);
        if ($secret !== '' && manage_mfa_verify_totp($secret, $code)) {
            return true;
        }
        return manage_mfa_verify_backup_code($pkey, $code);
    }
}

if (!function_exists('manage_mfa_required_globally')) {
    function manage_mfa_required_globally(): bool
    {
        $raw = $_ENV['MANAGE_MFA_REQUIRED'] ?? getenv('MANAGE_MFA_REQUIRED') ?: '0';
        return in_array(strtolower(trim((string)$raw)), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('manage_mfa_onboard_email_enabled')) {
    function manage_mfa_onboard_email_enabled(): bool
    {
        $raw = $_ENV['MANAGE_MFA_ONBOARD_EMAIL'] ?? getenv('MANAGE_MFA_ONBOARD_EMAIL') ?: '1';
        return in_array(strtolower(trim((string)$raw)), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('manage_mfa_manage_base_url')) {
    function manage_mfa_manage_base_url(): string
    {
        $base = defined('APP_WEB_URL') ? rtrim((string)APP_WEB_URL, '/') : '';
        $root = trim(str_replace('\\', '/', (string)($GLOBALS['web_root'] ?? '')), '/');
        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
            $base = $scheme . '://' . $host;
            if ($root !== '') {
                $base .= '/' . $root;
            }
            return rtrim($base, '/');
        }
        if ($root !== '' && stripos($base, '/' . $root) === false) {
            $base .= '/' . $root;
        }
        return rtrim($base, '/');
    }
}

if (!function_exists('manage_mfa_user_must_setup')) {
    function manage_mfa_user_must_setup(?array $row): bool
    {
        if ($row === null || manage_mfa_is_enabled_for_user($row)) {
            return false;
        }
        if (manage_mfa_onboard_schema_ready() && (int)($row['mfa_setup_pending'] ?? 0) === 1) {
            return true;
        }
        return manage_mfa_required_globally() && manage_mfa_schema_ready();
    }
}

if (!function_exists('manage_mfa_redirect_setup_if_needed')) {
    function manage_mfa_redirect_setup_if_needed(): void
    {
        if (empty($_SESSION['Manage']) || $_SESSION['Manage'] !== 'Yes' || empty($_SESSION['Login_ID'])) {
            return;
        }
        $self = (string)($_SERVER['PHP_SELF'] ?? '');
        if (stripos($self, '/control/mfa_setup.php') !== false) {
            return;
        }
        $row = manage_mfa_fetch_user_by_login((string)$_SESSION['Login_ID']);
        if (!manage_mfa_user_must_setup($row)) {
            return;
        }
        $rel = (string)($GLOBALS['web_root'] ?? '') . 'manage/control/mfa_setup.php';
        manage_alert_script('請先完成雙因素驗證綁定。', $rel);
        exit;
    }
}

if (!function_exists('manage_mfa_post_login_url')) {
    function manage_mfa_post_login_url(?array $row): string
    {
        $root = (string)($GLOBALS['web_root'] ?? '');
        if (manage_mfa_user_must_setup($row)) {
            return $root . 'manage/control/mfa_setup.php';
        }
        return $root . 'manage/login/login.php';
    }
}

if (!function_exists('manage_mfa_send_onboard_email')) {
    function manage_mfa_send_onboard_email(string $userName, string $email, string $loginId): bool
    {
        $email = trim($email);
        if ($email === '' || !function_exists('CheckMail') || !CheckMail($email)) {
            return false;
        }
        if (!manage_mfa_onboard_email_enabled() || !function_exists('SendMail')) {
            return false;
        }

        $siteName = trim((string)($GLOBALS['WebName'] ?? '後台管理系統'));
        $fromRow = function_exists('crud_fetch_one')
            ? crud_fetch_one('SELECT strName, FromMail FROM webset WHERE intLang = 1 LIMIT 1', [])
            : null;
        $fromName = trim((string)($fromRow['strName'] ?? $siteName));
        $fromMail = trim((string)($fromRow['FromMail'] ?? ''));
        if ($fromMail === '' || !CheckMail($fromMail)) {
            $fromMail = trim((string)($GLOBALS['Web_Mail'] ?? ''));
        }
        if ($fromMail === '' || !CheckMail($fromMail)) {
            return false;
        }

        $loginUrl = manage_mfa_manage_base_url() . '/manage/login/index.php';
        $mfaUrl = manage_mfa_manage_base_url() . '/manage/control/mfa_setup.php';
        $subject = '【' . $siteName . '】後台帳號建立通知與雙因素驗證綁定指引';
        $body = '<p>' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . ' 您好：</p>'
            . '<p>已為您建立後台管理帳號，請依下列步驟完成首次登入與雙因素驗證（TOTP）綁定。</p>'
            . '<ol>'
            . '<li>登入網址：<a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a></li>'
            . '<li>帳號：<strong>' . htmlspecialchars($loginId, ENT_QUOTES, 'UTF-8') . '</strong></li>'
            . '<li>密碼：請向建立帳號的管理員索取（本信不含密碼）。</li>'
            . '<li>登入後請至「雙因素驗證」完成綁定，或開啟：<a href="' . htmlspecialchars($mfaUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($mfaUrl, ENT_QUOTES, 'UTF-8') . '</a></li>'
            . '<li>可使用 Google Authenticator 或 Microsoft Authenticator 掃描 QR Code。</li>'
            . '<li>綁定完成後請妥善保存備用碼。</li>'
            . '</ol>'
            . '<p>首次登入未完成綁定前，系統將引導您至雙因素驗證設定頁。</p>'
            . '<p style="color:#666;font-size:12px;">本信由系統自動發送，請勿回覆。</p>';

        SendMail($userName, $email, $fromName, $fromMail, $subject, $body);
        return true;
    }
}

if (!function_exists('manage_mfa_mark_setup_pending')) {
    function manage_mfa_mark_setup_pending(int $pkey): void
    {
        if ($pkey <= 0 || !manage_mfa_onboard_schema_ready()) {
            return;
        }
        $pdo = new dbPDO();
        $pdo->update('webcontrol', [
            'mfa_setup_pending' => 1,
            'dtUDate' => date('Y-m-d H:i:s'),
        ], 'PKey', $pkey);
        $pdo->close();
    }
}
