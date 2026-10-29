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
        $_SESSION['MFA_Pending'] = true;
        $_SESSION['MFA_User_PKey'] = (int)($row['PKey'] ?? 0);
        $_SESSION['MFA_strID'] = (string)($row['strID'] ?? '');
        $_SESSION['MFA_UserName'] = (string)($row['strName'] ?? '');
        $_SESSION['MFA_FunctionID'] = (string)($row['FunctionID'] ?? '');
        $_SESSION['MFA_Started'] = time();
        $_SESSION['MFA_Attempts'] = 0;
        unset($_SESSION['Manage'], $_SESSION['Login_ID'], $_SESSION['UserName'], $_SESSION['FunctionID'], $_SESSION['Login_PKey']);
        regenerate_session_on_login();
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
        $_SESSION['Manage'] = 'Yes';
        $_SESSION['Login_ID'] = (string)($_SESSION['MFA_strID'] ?? '');
        $_SESSION['UserName'] = (string)($_SESSION['MFA_UserName'] ?? '');
        $_SESSION['FunctionID'] = (string)($_SESSION['MFA_FunctionID'] ?? '');
        $_SESSION['Login_PKey'] = (int)($_SESSION['MFA_User_PKey'] ?? 0);
        manage_mfa_clear_pending_login();
        regenerate_session_on_login();
    }
}

if (!function_exists('manage_mfa_establish_session')) {
    function manage_mfa_establish_session(array $row): void
    {
        $_SESSION['Manage'] = 'Yes';
        $_SESSION['UserName'] = (string)($row['strName'] ?? '');
        $_SESSION['Login_ID'] = (string)($row['strID'] ?? '');
        $_SESSION['FunctionID'] = (string)($row['FunctionID'] ?? '');
        $_SESSION['Login_PKey'] = (int)($row['PKey'] ?? 0);
        manage_mfa_clear_pending_login();
        regenerate_session_on_login();
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


if (!function_exists('manage_mfa_matching_time_step')) {
    /** 驗證 TOTP 並回傳命中的 30 秒時間步；不符回傳 null */
    function manage_mfa_matching_time_step(string $secretBase32, string $code, int $window = 1): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code) || $secretBase32 === '') {
            return null;
        }
        $counter = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            $step = $counter + $i;
            if (hash_equals(manage_mfa_hotp($secretBase32, $step), $code)) {
                return $step;
            }
        }
        return null;
    }
}

if (!function_exists('manage_export_fail')) {
    /** 匯出失敗：清掉二進位緩衝後以 alert 提示（隱藏 iframe 仍看得到） */
    function manage_export_fail(string $message): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        if (function_exists('manage_alert_script')) {
            manage_alert_script($message);
        }
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }
}

if (!function_exists('manage_export_totp_state')) {
    /** @return array{fails:int,lock_until:int,last_step:int} */
    function manage_export_totp_state(): array
    {
        $state = $_SESSION['Export_TOTP'] ?? null;
        if (!is_array($state)) {
            return ['fails' => 0, 'lock_until' => 0, 'last_step' => 0];
        }
        return [
            'fails' => (int)($state['fails'] ?? 0),
            'lock_until' => (int)($state['lock_until'] ?? 0),
            'last_step' => (int)($state['last_step'] ?? 0),
        ];
    }
}

if (!function_exists('manage_export_log_totp')) {
    function manage_export_log_totp(string $action, string $detail): void
    {
        if (!function_exists('manage_history')) {
            return;
        }
        $loginId = (string)($_SESSION['Login_ID'] ?? '');
        manage_history(
            (int)($GLOBALS['Module_PKey'] ?? 0),
            (string)($GLOBALS['Module_Name'] ?? '匯出'),
            $detail,
            (string)($GLOBALS['WorkFile'] ?? ($_SERVER['PHP_SELF'] ?? 'output.php')),
            $loginId !== '' ? $loginId : 'system',
            $action,
            'Upload/export_log'
        );
    }
}

if (!function_exists('manage_export_assert_module_access')) {
    /** 與 _module.php 相同：最高權限或 FunctionID 含此單元才可進入 */
    function manage_export_assert_module_access(): void
    {
        $loginId = trim((string)($_SESSION['Login_ID'] ?? ''));
        $modulePKey = (int)($GLOBALS['Module_PKey'] ?? 0);
        $moduleName = trim((string)($GLOBALS['Module_Name'] ?? ''));
        $moduleList = [];
        if (!empty($_SESSION['FunctionID'])) {
            $moduleList = array_values(array_unique(array_filter(
                array_map('intval', explode(',', (string)$_SESSION['FunctionID'])),
                static fn(int $id): bool => $id > 0
            )));
        }
        $isSuper = function_exists('manage_is_super_admin') && manage_is_super_admin();
        $inModule = $modulePKey > 0 && in_array($modulePKey, $moduleList, true);
        if ($loginId !== '' && ($isSuper || $inModule)) {
            return;
        }
        $denyMsg = '無進入[' . $moduleName . ']權限!';
        manage_export_log_totp('匯出拒絕', $denyMsg);
        manage_export_fail($denyMsg);
    }
}

if (!function_exists('manage_export_require_totp')) {
    /**
     * 匯出前閘門：須為 POST、CSRF 正確、可進入此單元，且本次 TOTP／備用碼正確。
     * 通過後才可產生 Excel。不記錄驗證碼。
     *
     * @param array<string, mixed> $filter
     */
    function manage_export_require_totp(string $csrfKey, array $filter): void
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            manage_export_fail('請由列表頁送出匯出');
        }
        if (function_exists('crud_csrf_normalize')) {
            crud_csrf_normalize();
        }
        $postedToken = '';
        if (isset($filter['csrf_token']) && is_scalar($filter['csrf_token'])) {
            $postedToken = (string)$filter['csrf_token'];
        }
        $sessionToken = (string)($_SESSION['csrf'][$csrfKey] ?? '');
        if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
            manage_export_fail('表單已逾期，請重新整理列表後再匯出');
        }

        manage_export_assert_module_access();

        if (!manage_mfa_schema_ready()) {
            manage_export_fail('MFA 資料表欄位尚未建立，請先執行 sql/webcontrol_totp_mfa.sql');
        }

        $pkey = (int)($_SESSION['Login_PKey'] ?? 0);
        $row = manage_mfa_fetch_user($pkey);
        if ($row === null || !manage_mfa_is_enabled_for_user($row)) {
            manage_export_log_totp('匯出拒絕', '尚未啟用 TOTP');
            manage_export_fail('尚未啟用雙因素驗證，請先完成 TOTP 綁定後再匯出');
        }

        $state = manage_export_totp_state();
        $now = time();
        if ($state['lock_until'] > $now) {
            $waitMin = (int) ceil(($state['lock_until'] - $now) / 60);
            manage_export_fail('驗證失敗次數過多，請 ' . max(1, $waitMin) . ' 分鐘後再試');
        }
        if ($state['lock_until'] > 0 && $state['lock_until'] <= $now) {
            $state['fails'] = 0;
            $state['lock_until'] = 0;
        }

        $code = '';
        if (isset($filter['totp_code']) && is_scalar($filter['totp_code'])) {
            $code = trim((string)$filter['totp_code']);
        }
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if ($code === '' || !preg_match('/^[0-9A-Za-z-]{6,16}$/', $code)) {
            manage_export_fail('請輸入 TOTP 驗證碼');
        }

        $secret = manage_mfa_user_secret($row);
        $matchedStep = manage_mfa_matching_time_step($secret, $code);
        if ($matchedStep !== null) {
            if ($matchedStep <= $state['last_step']) {
                manage_export_log_totp('匯出拒絕', 'TOTP 驗證碼已使用');
                manage_export_fail('此驗證碼已使用，請等待下一組驗證碼');
            }
            $state['fails'] = 0;
            $state['lock_until'] = 0;
            $state['last_step'] = $matchedStep;
            $_SESSION['Export_TOTP'] = $state;
            return;
        }

        if (manage_mfa_verify_backup_code($pkey, $code)) {
            $state['fails'] = 0;
            $state['lock_until'] = 0;
            $_SESSION['Export_TOTP'] = $state;
            return;
        }

        $state['fails']++;
        $maxFails = 5;
        if ($state['fails'] >= $maxFails) {
            $state['fails'] = 0;
            $state['lock_until'] = $now + 600;
            $_SESSION['Export_TOTP'] = $state;
            manage_export_log_totp('匯出拒絕', 'TOTP 失敗次數過多');
            manage_export_fail('驗證失敗次數過多，請 10 分鐘後再試');
        }
        $_SESSION['Export_TOTP'] = $state;
        $remaining = $maxFails - $state['fails'];
        manage_export_log_totp('匯出拒絕', 'TOTP 驗證失敗，剩餘 ' . $remaining . ' 次');
        manage_export_fail('驗證碼錯誤，剩餘 ' . $remaining . ' 次');
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
    /** @return array{ok: bool, message: string, skipped: bool} */
    function manage_mfa_send_onboard_email(string $userName, string $email, string $loginId): array
    {
        $email = trim($email);
        if ($email === '' || !function_exists('CheckMail') || !CheckMail($email)) {
            return ['ok' => false, 'message' => '收件 Email 格式不正確', 'skipped' => false];
        }
        if (!manage_mfa_onboard_email_enabled()) {
            return ['ok' => true, 'message' => '已略過寄信（MANAGE_MFA_ONBOARD_EMAIL 關閉）', 'skipped' => true];
        }
        if (!function_exists('SendMail')) {
            return ['ok' => false, 'message' => 'SendMail 函式不存在', 'skipped' => false];
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
            return ['ok' => false, 'message' => '寄件信箱未設定或格式錯誤（請至 SEO/GEO 基本設定）', 'skipped' => false];
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

        $resp = SendMail($userName, $email, $fromName, $fromMail, $subject, $body);
        $ok = function_exists('sendmail_response_is_success') && sendmail_response_is_success($resp);
        $detail = function_exists('sendmail_response_message')
            ? sendmail_response_message($resp)
            : '未知錯誤';

        if ($ok) {
            return ['ok' => true, 'message' => 'MFA 綁定指引信已寄至 ' . $email, 'skipped' => false];
        }

        return ['ok' => false, 'message' => 'MFA 指引信寄送失敗：' . $detail, 'skipped' => false];
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

if (!function_exists('manage_mfa_resend_onboard_mail')) {
    /** @return array{ok: bool, message: string, skipped: bool} */
    function manage_mfa_resend_onboard_mail(int $pkey): array
    {
        if ($pkey <= 0) {
            return ['ok' => false, 'message' => '帳號 PKey 無效', 'skipped' => false];
        }

        $row = crud_fetch_one(
            'SELECT PKey, strID, strName, intType' . manage_mfa_user_extra_select_sql()
            . ' FROM webcontrol WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
        if ($row === null || (int)($row['intType'] ?? -1) !== 0) {
            return ['ok' => false, 'message' => '查無帳號或無權限', 'skipped' => false];
        }

        if (manage_mfa_is_enabled_for_user($row)) {
            return ['ok' => false, 'message' => '此帳號已完成 MFA 綁定，無需重發綁定信', 'skipped' => false];
        }

        $email = trim((string)($row['strEmail'] ?? ''));
        if ($email === '') {
            return ['ok' => false, 'message' => '此帳號未設定 Email，請先編輯儲存 Email 後再重發', 'skipped' => false];
        }

        if (manage_mfa_onboard_schema_ready()) {
            manage_mfa_mark_setup_pending($pkey);
        }

        $result = manage_mfa_send_onboard_email(
            (string)($row['strName'] ?? ''),
            $email,
            (string)($row['strID'] ?? '')
        );

        if (($result['skipped'] ?? false) === true) {
            return $result;
        }

        if ($result['ok'] ?? false) {
            if (function_exists('manage_history')) {
                manage_history(
                    (int)($GLOBALS['Module_PKey'] ?? 3),
                    '帳號管理',
                    '重發 MFA 綁定指引信',
                    $_SERVER['PHP_SELF'] ?? 'mfa_resend_mail.php',
                    (string)($_SESSION['Login_ID'] ?? 'system'),
                    (string)($result['message'] ?? '')
                );
            }
        }

        return $result;
    }
}
