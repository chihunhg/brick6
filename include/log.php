<?php
/**
 * log.php — 固定以 APP_PROJECT_ROOT / PathForder 為寫檔根目錄的版本
 */

declare(strict_types=1);

/* ===================== 可調整開關（預設即可） ===================== */
if (!defined('LOG_SQL_TEXT_ENABLED'))   define('LOG_SQL_TEXT_ENABLED', true);
if (!defined('LOG_SQL_JSON_ENABLED'))   define('LOG_SQL_JSON_ENABLED', true);
if (!defined('LOG_MANAGE_TEXT_ENABLED')) define('LOG_MANAGE_TEXT_ENABLED', true);
if (!defined('LOG_MANAGE_JSON_ENABLED')) define('LOG_MANAGE_JSON_ENABLED', true);
/** 後台 manage_history：是否在「檔案日誌」寫入完整 SqlCommand（常含綁定值）；managelog 資料表預設一律寫入。 */
if (!defined('LOG_MANAGE_FULL_SQL')) {
    $manageFullSql = false;
    if (function_exists('host_env_bool')) {
        $manageFullSql = host_env_bool('LOG_MANAGE_FULL_SQL', '0');
    } else {
        $raw = getenv('LOG_MANAGE_FULL_SQL');
        if ($raw !== false && trim((string)$raw) !== '') {
            $manageFullSql = in_array(strtolower(trim((string)$raw)), ['1', 'true', 'yes', 'on'], true);
        }
    }
    define('LOG_MANAGE_FULL_SQL', $manageFullSql);
}

if (!function_exists('manage_history_file_sql_enabled')) {
    /** 是否將完整 SqlCommand 寫入 manage_history 檔案日誌 */
    function manage_history_file_sql_enabled(): bool
    {
        return defined('LOG_MANAGE_FULL_SQL') && LOG_MANAGE_FULL_SQL;
    }
}

if (!defined('LOG_CALLER_SKIP_REGEX')) {
    define('LOG_CALLER_SKIP_REGEX', '#/(include|_inc|_module|lib|libs|vendor)/#i');
}

/* =========================== 共用工具 =========================== */
/** 取得使用者 IP（優先 UserIP()） */
function _log_ip(): string {
    return function_exists('UserIP') ? UserIP() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/** 清理日誌字串：換行轉義、移除控制字元 */
function _log_sanitize(string $s): string {
    $s = str_replace(["\r", "\n"], ['\r', '\n'], $s);
    return (string)preg_replace('/[\x00-\x1F\x7F]/', '', $s);
}

/** 將多行字串壓成單行（換行改 tab） */
function _oneline(string $s): string {
    return str_replace(["\r\n", "\n", "\r"], "\t", $s);
}

/** 偵測網站根目錄（APP_WEB_ROOT / web_root / RootForder） */
function _detect_web_root(?string $fallbackWorkFile = null): string {
    if (defined('APP_WEB_ROOT')) return (string)APP_WEB_ROOT;
    if (!empty($GLOBALS['web_root'])) return (string)$GLOBALS['web_root'];
    if (function_exists('RootForder') && $fallbackWorkFile) {
        $root = RootForder($fallbackWorkFile);
        if (is_string($root) && $root !== '') return $root;
    }
    return '';
}

/**
 * 固定抓「專案實體根目錄」
 * 優先順序：
 * 1. $GLOBALS['PathForder']
 * 2. APP_PROJECT_ROOT
 * 3. APP_DOCUMENT_ROOT
 * 4. $GLOBALS['DOCUMENT_ROOT']
 * 5. $_SERVER['DOCUMENT_ROOT']
 * 6. log.php 上一層
 */
function _detect_project_root(): string {
    if (!empty($GLOBALS['PathForder']) && is_string($GLOBALS['PathForder'])) {
        return rtrim(str_replace('\\', '/', $GLOBALS['PathForder']), '/');
    }

    if (defined('APP_PROJECT_ROOT')) {
        return rtrim(str_replace('\\', '/', (string)APP_PROJECT_ROOT), '/');
    }

    if (defined('APP_DOCUMENT_ROOT')) {
        return rtrim(str_replace('\\', '/', (string)APP_DOCUMENT_ROOT), '/');
    }

    if (!empty($GLOBALS['DOCUMENT_ROOT']) && is_string($GLOBALS['DOCUMENT_ROOT'])) {
        return rtrim(str_replace('\\', '/', $GLOBALS['DOCUMENT_ROOT']), '/');
    }

    $v = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (is_string($v) && $v !== '') {
        return rtrim(str_replace('\\', '/', $v), '/');
    }

    $fallback = realpath(__DIR__ . '/..');
    if ($fallback !== false) {
        return rtrim(str_replace('\\', '/', $fallback), '/');
    }

    return rtrim(str_replace('\\', '/', __DIR__), '/');
}

/** 以正斜線正規化後串接路徑，回傳 OS 分隔符格式 */
function _join_path(string ...$parts): string {
    $norm = array();

    foreach ($parts as $idx => $p) {
        $p = str_replace('\\', '/', (string)$p);
        if ($idx === 0) {
            $p = rtrim($p, '/');
        } else {
            $p = trim($p, '/');
        }
        if ($p !== '') {
            $norm[] = $p;
        }
    }

    $path = implode('/', $norm);
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

/** 將絕對路徑轉為相對於 baseDir 的路徑 */
function _rel_from_base(string $abs, string $baseDir): string {
    $abs = str_replace('\\', '/', $abs);
    $baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');

    if ($baseDir !== '' && strncasecmp($abs, $baseDir . '/', strlen($baseDir) + 1) === 0) {
        return substr($abs, strlen($baseDir) + 1);
    }

    return $abs;
}

/**
 * @return array{file:string,line:int}
 */
function _find_caller_location(array $extraSkipFunctions = array()): array {
    $skipFunctions = array_map('strtolower', array_merge(array(
        'write_log', 'write_log_json', 'sql_error', 'manage_history',
        'product_history', 'member_history', '_log_mysql_pdo'
    ), $extraSkipFunctions));

    $selfFile = realpath(__FILE__) ?: __FILE__;
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 32);

    foreach ($bt as $frame) {
        $func = strtolower($frame['function'] ?? '');
        $file = $frame['file'] ?? '';
        $line = (int)($frame['line'] ?? 0);

        if ($file === '' || in_array($func, $skipFunctions, true)) {
            continue;
        }

        $real = realpath($file) ?: $file;
        if ($real === $selfFile) {
            continue;
        }

        if (preg_match(LOG_CALLER_SKIP_REGEX, str_replace('\\', '/', $real))) {
            continue;
        }

        return array('file' => $real, 'line' => $line);
    }

    return array('file' => __FILE__, 'line' => __LINE__);
}

/* ========================= DB 連線存取 ========================= */
/** 取得 dbPDO 實例供日誌寫入使用，無類別時回傳 null */
function _log_mysql_pdo(): ?object {
    if (class_exists('dbPDO')) {
        return new dbPDO();
    }
    return null;
}

/* ========================= 資料表寫入 API ========================= */
/** 寫入 product_h 商品操作紀錄 */
function product_history($Product_PKey, string $Product_Name, string $SqlCommand, string $strLink, string $UserID): void {
    $ip = _log_ip();
    $data_array = array(
        'Product_PKey' => function_exists('SqlFilter') ? SqlFilter($Product_PKey, 'int') : (int)$Product_PKey,
        'Product_Name' => function_exists('SqlFilter') ? SqlFilter($Product_Name, 'tab') : $Product_Name,
        'strLink'      => function_exists('SqlFilter') ? SqlFilter($strLink, 'tab') : $strLink,
        'SqlCommand'   => function_exists('SqlFilter') ? SqlFilter($SqlCommand, 'tab') : $SqlCommand,
        'UserIP'       => function_exists('SqlFilter') ? SqlFilter($ip, 'tab') : $ip,
        'UserID'       => function_exists('SqlFilter') ? SqlFilter($UserID, 'tab') : $UserID,
        'dtDate'       => date('Y-m-d H:i:s'),
    );

    $MAX_BYTES = 64000;
    if (strlen($data_array['SqlCommand']) > $MAX_BYTES) {
        $data_array['SqlCommand'] = mb_strcut($data_array['SqlCommand'], 0, $MAX_BYTES - 20, 'UTF-8') . '...[truncated]';
    }

    if ($pdo = _log_mysql_pdo()) {
        $pdo->insert('product_h', $data_array);
        if (method_exists($pdo, 'getErrorMessage') && $pdo->getErrorMessage()) {
            $lastSql = method_exists($pdo, 'getLastSql') ? $pdo->getLastSql() : '';
            if (function_exists('sql_error')) {
                sql_error($lastSql, $pdo->getErrorMessage(), $strLink, 'system');
            } else {
                error_log("[DB ERROR] product_h insert failed: {$pdo->getErrorMessage()} | SQL: {$lastSql}");
            }
        }
        $pdo->close();
    }
}

/** 寫入 member_h 會員操作紀錄 */
function member_history($Member_PKey, string $Member_Name, string $SqlCommand, string $strLink, string $UserID): void {
    $ip = _log_ip();
    $data_array = array(
        'Member_PKey' => function_exists('SqlFilter') ? SqlFilter($Member_PKey, 'int') : (int)$Member_PKey,
        'Member_Name' => function_exists('SqlFilter') ? SqlFilter($Member_Name, 'tab') : $Member_Name,
        'strLink'     => function_exists('SqlFilter') ? SqlFilter($strLink, 'tab') : $strLink,
        'SqlCommand'  => function_exists('SqlFilter') ? SqlFilter($SqlCommand, 'tab') : $SqlCommand,
        'UserIP'      => function_exists('SqlFilter') ? SqlFilter($ip, 'tab') : $ip,
        'UserID'      => function_exists('SqlFilter') ? SqlFilter($UserID, 'tab') : $UserID,
        'dtDate'      => date('Y-m-d H:i:s'),
    );

    $MAX_BYTES = 64000;
    if (strlen($data_array['SqlCommand']) > $MAX_BYTES) {
        $data_array['SqlCommand'] = mb_strcut($data_array['SqlCommand'], 0, $MAX_BYTES - 20, 'UTF-8') . '...[truncated]';
    }

    if ($pdo = _log_mysql_pdo()) {
        $pdo->insert('member_h', $data_array);
        if (method_exists($pdo, 'getErrorMessage') && $pdo->getErrorMessage()) {
            $lastSql = method_exists($pdo, 'getLastSql') ? $pdo->getLastSql() : '';
            if (function_exists('sql_error')) {
                sql_error($lastSql, $pdo->getErrorMessage(), $strLink, 'system');
            } else {
                error_log("[DB ERROR] member_h insert failed: {$pdo->getErrorMessage()} | SQL: {$lastSql}");
            }
        }
        $pdo->close();
    }
}

/** 寫入 managelog 並同步輸出文字／JSON 檔案日誌 */
function manage_history(
    $Module_PKey,
    string $Module_Name,
    string $SqlCommand,
    string $strLink,
    string $UserID,
    string $Action = '',
    ?string $logSubDir = null,
    bool $writeFiles = true
): void {
    $ip = _log_ip();
    $Module_PKey = function_exists('SqlFilter') ? SqlFilter($Module_PKey, 'int') : (int)$Module_PKey;

    $sqlForDb = function_exists('SqlFilter') ? SqlFilter($SqlCommand, 'tab') : $SqlCommand;
    $sqlForFile = manage_history_file_sql_enabled() ? $sqlForDb : '';

    $data_array = array(
        'Module_PKey' => $Module_PKey,
        'Module_Name' => function_exists('SqlFilter') ? SqlFilter($Module_Name, 'tab') : $Module_Name,
        'Method'      => function_exists('SqlFilter') ? SqlFilter($Action, 'tab') : $Action,
        'strLink'     => function_exists('SqlFilter') ? SqlFilter($strLink, 'tab') : $strLink,
        'SqlCommand'  => $sqlForDb,
        'UserIP'      => function_exists('SqlFilter') ? SqlFilter($ip, 'tab') : $ip,
        'UserID'      => function_exists('SqlFilter') ? SqlFilter($UserID, 'tab') : $UserID,
        'dtDate'      => date('Y-m-d H:i:s'),
    );

    $MAX_BYTES = 64000;
    if (strlen($data_array['SqlCommand']) > $MAX_BYTES) {
        $data_array['SqlCommand'] = mb_strcut($data_array['SqlCommand'], 0, $MAX_BYTES - 20, 'UTF-8') . '...[truncated]';
    }

    $SqlCommandOneLine = _oneline($sqlForFile);

    if ($pdo = _log_mysql_pdo()) {
        $pdo->insert('managelog', $data_array);
        if (method_exists($pdo, 'getErrorMessage') && $pdo->getErrorMessage()) {
            $lastSql = method_exists($pdo, 'getLastSql') ? $pdo->getLastSql() : '';
            if (function_exists('sql_error')) {
                sql_error($lastSql, $pdo->getErrorMessage(), ($GLOBALS['WorkFile'] ?? ''), 'system');
            } else {
                error_log("[DB ERROR] managelog insert failed: {$pdo->getErrorMessage()} | SQL: {$lastSql}");
            }
        }
        $pdo->close();
    }

    if (!$writeFiles) {
        return;
    }

    $loc = _find_caller_location();

    if (LOG_MANAGE_TEXT_ENABLED) {
        write_log(
            Module_Name: $Module_Name,
            MSG: $Action,
            SqlCommand: $SqlCommandOneLine,
            strLink: $strLink,
            UserID: $UserID,
            LogType: 'web',
            subDir: $logSubDir,
            filePrefix: 'log_',
            file: $loc['file'] ?? null,
            line: $loc['line'] ?? null
        );
    }

    if (LOG_MANAGE_JSON_ENABLED && function_exists('write_log_json')) {
        write_log_json(
            Module_Name: $Module_Name,
            MSG: $Action,
            SqlCommand: $SqlCommandOneLine,
            strLink: $strLink,
            UserID: $UserID,
            LogType: 'web',
            subDir: $logSubDir,
            filePrefix: 'json_',
            file: $loc['file'] ?? null,
            line: $loc['line'] ?? null
        );
    }
}

/** 記錄 SQL 錯誤至 error_log 表與檔案，並寫入 manage_history */
function sql_error(string $SqlCommand, string $ErrorMessage, string $strLink, string $UserID, ?string $srcFile = null, ?int $srcLine = null): array {
    $SqlCommand   = trim($SqlCommand);
    $ErrorMessage = trim($ErrorMessage);
    $ip = _log_ip();

    $data_array = array(
        'strLink'      => $strLink,
        'SqlCommand'   => $SqlCommand,
        'ErrorMessage' => $ErrorMessage,
        'UserIP'       => $ip,
        'UserID'       => $UserID,
        'dtDate'       => date('Y-m-d H:i:s'),
    );

    $MAX_BYTES = 64000;
    if (strlen($data_array['SqlCommand']) > $MAX_BYTES) {
        $data_array['SqlCommand'] = mb_strcut($data_array['SqlCommand'], 0, $MAX_BYTES - 20, 'UTF-8') . '...[truncated]';
    }

    if ($pdo = _log_mysql_pdo()) {
        try {
            $okToInsert = true;
            if (function_exists('chkTable')) {
                $okToInsert = chkTable('error_log');
            }
            if ($okToInsert) {
                $pdo->insert('error_log', $data_array);
            }
            $pdo->close();
        } catch (Throwable $e) {
            error_log('[sql_error] DB insert fail: ' . $e->getMessage() . ' | SQL=' . $SqlCommand);
        }
    }

    $Module_Name = $GLOBALS['Module_Name'] ?? '未知模組';

    if ($srcFile === null || $srcLine === null) {
        $loc = _find_caller_location();
        $srcFile = $srcFile ?? $loc['file'];
        $srcLine = $srcLine ?? $loc['line'];
    }

    $errLine = _oneline($ErrorMessage);

    if (LOG_SQL_TEXT_ENABLED) {
        write_log(
            Module_Name: $Module_Name,
            MSG: '程式錯誤',
            SqlCommand: $errLine,
            strLink: $strLink,
            UserID: $UserID,
            LogType: 'sql',
            file: $srcFile,
            line: $srcLine
        );
    }

    if (LOG_SQL_JSON_ENABLED && function_exists('write_log_json')) {
        write_log_json(
            Module_Name: $Module_Name,
            MSG: '程式錯誤',
            SqlCommand: $errLine,
            strLink: $strLink,
            UserID: $UserID,
            LogType: 'sql',
            file: $srcFile,
            line: $srcLine
        );
    }

    if (function_exists('manage_history')) {
        manage_history(
            Module_PKey: 0,
            Module_Name: $Module_Name,
            SqlCommand:  $SqlCommand,
            strLink:     $strLink,
            UserID:      $UserID,
            Action:      '程式錯誤',
            logSubDir:   null,
            writeFiles:  false
        );
    }

    return array(
        'Command' => $SqlCommand,
        'Message' => $ErrorMessage,
        'UserIP'  => $ip,
        'UserID'  => $UserID,
        'File'    => $srcFile,
        'Line'    => (int)$srcLine,
    );
}

/* =========================== 檔案寫入（文字） =========================== */
/** 以管道分隔格式追加寫入文字日誌，回傳日誌檔完整路徑 */
function write_log(
    string $Module_Name = '',
    string $MSG = '',
    string $SqlCommand = '',
    string $strLink = '',
    string $UserID = '',
    string $LogType = 'web',
    ?string $baseDir = null,
    ?string $subDir = null,
    ?string $filePrefix = 'log_',
    ?string $web_root = null,
    ?string $file = null,
    ?int    $line = null
): string {

    // 固定以專案根目錄為基準，不再拼 web_root
    if ($baseDir === null || $baseDir === '') {
        $baseDir = _detect_project_root();
    }
    $baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');

    if ($subDir === null || $subDir === '') {
        $subDir = ($LogType === 'sql') ? 'Upload/sql_error' : 'Upload/weblog';
    }
    $subDir = trim(str_replace('\\', '/', $subDir), '/');

    if ($LogType === 'sql' && preg_match('#^upload/sql/?$#i', $subDir)) {
        $subDir = 'Upload/sql_error';
    }

    $filePrefix = $filePrefix ?? 'log_';
    $filePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filePrefix);
    if ($filePrefix === '') {
        $filePrefix = 'log_';
    }

    $logDir = _join_path($baseDir, $subDir);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $logFile = _join_path($logDir, $filePrefix . date('Y-m-d') . '.log');

    if ($file === null || $line === null) {
        $loc = _find_caller_location();
        $file = $file ?? $loc['file'];
        $line = $line ?? $loc['line'];
    }

    $relFile = _rel_from_base((string)$file, $baseDir);
    $atField = $relFile !== '' ? "at={$relFile}:{$line}" : "at=unknown:0";

    $ip = _log_ip();
    $lineOut = implode(' | ', array(
        date('c'),
        _log_sanitize($Module_Name),
        _log_sanitize($strLink),
        _log_sanitize($SqlCommand),
        _log_sanitize($MSG),
        _log_sanitize($ip),
        _log_sanitize($UserID),
        $atField,
    ));

    @file_put_contents($logFile, $lineOut . PHP_EOL, FILE_APPEND | LOCK_EX);
    @chmod($logFile, 0664);

    return $logFile;
}

/* =========================== 檔案寫入（JSON） =========================== */
/** 以 JSON Lines 格式追加寫入日誌，回傳日誌檔完整路徑 */
function write_log_json(
    string $Module_Name = '',
    string $MSG = '',
    string $SqlCommand = '',
    string $strLink = '',
    string $UserID = '',
    string $LogType = 'web',
    ?string $baseDir = null,
    ?string $subDir = null,
    ?string $filePrefix = 'json_',
    ?string $web_root = null,
    ?string $file = null,
    ?int    $line = null
): string {

    // 固定以專案根目錄為基準，不再拼 web_root
    if ($baseDir === null || $baseDir === '') {
        $baseDir = _detect_project_root();
    }
    $baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');

    if ($subDir === null || $subDir === '') {
        $subDir = ($LogType === 'sql') ? 'Upload/sql_error' : 'Upload/jsonlog';
    }
    $subDir = trim(str_replace('\\', '/', $subDir), '/');

    if ($LogType === 'sql' && preg_match('#^upload/sql/?$#i', $subDir)) {
        $subDir = 'Upload/sql_error';
    }

    $filePrefix = $filePrefix ?? 'json_';
    $filePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filePrefix);
    if ($filePrefix === '') {
        $filePrefix = 'json_';
    }

    $logDir = _join_path($baseDir, $subDir);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $logFile = _join_path($logDir, $filePrefix . date('Y-m-d') . '.log');

    if ($file === null || $line === null) {
        $loc = _find_caller_location();
        $file = $file ?? $loc['file'];
        $line = $line ?? $loc['line'];
    }

    $relFile = _rel_from_base((string)$file, $baseDir);

    $payload = array(
        'timestamp' => date('c'),
        'module'    => $Module_Name,
        'message'   => $MSG,
        'sql'       => $SqlCommand,
        'link'      => $strLink,
        'ip'        => _log_ip(),
        'user'      => $UserID,
        'type'      => $LogType,
        'file'      => $relFile,
        'line'      => (int)$line,
    );

    $lineJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents($logFile, $lineJson . PHP_EOL, FILE_APPEND | LOCK_EX);
    @chmod($logFile, 0664);

    return $logFile;
}
?>