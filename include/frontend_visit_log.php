<?php
declare(strict_types=1);

if (!function_exists('frontend_visit_env_flag')) {
    /** 讀取 .env 開關（getenv 未設定時回傳 false，不可直接用 ?? 接預設值） */
    function frontend_visit_env_flag(string $name, string $default = '1'): bool
    {
        $raw = $_ENV[$name] ?? null;
        if ($raw === null || trim((string)$raw) === '') {
            $fromGetenv = getenv($name);
            $raw = ($fromGetenv !== false) ? $fromGetenv : $default;
        }

        return in_array(strtolower(trim((string)$raw)), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('frontend_visit_log_enabled')) {
    function frontend_visit_log_enabled(): bool
    {
        return frontend_visit_env_flag('FRONTEND_VISIT_LOG_ENABLED', '1');
    }
}

if (!function_exists('frontend_visit_log_table')) {
    function frontend_visit_log_table(): string
    {
        return 'frontend_visit_log';
    }
}

if (!function_exists('frontend_visit_skip_bots')) {
    function frontend_visit_skip_bots(): bool
    {
        return frontend_visit_env_flag('FRONTEND_VISIT_LOG_SKIP_BOTS', '1');
    }
}

if (!function_exists('frontend_visit_request_user_agent')) {
    function frontend_visit_request_user_agent(): string
    {
        return trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}

if (!function_exists('frontend_visit_is_crawler')) {
    /**
     * 判斷是否為搜尋引擎／監測／自動化爬蟲（依 User-Agent 與常見 bot 特徵）
     */
    function frontend_visit_is_crawler(?string $userAgent = null): bool
    {
        if (!frontend_visit_skip_bots()) {
            return false;
        }

        $ua = $userAgent !== null ? trim($userAgent) : frontend_visit_request_user_agent();
        if ($ua === '') {
            return true;
        }

        static $patterns = [
            '/googlebot/i',
            '/google-inspectiontool/i',
            '/adsbot-google/i',
            '/mediapartners-google/i',
            '/bingbot/i',
            '/bingpreview/i',
            '/slurp/i',
            '/duckduckbot/i',
            '/baiduspider/i',
            '/yandexbot/i',
            '/sogou/i',
            '/exabot/i',
            '/facebot/i',
            '/facebookexternalhit/i',
            '/twitterbot/i',
            '/linkedinbot/i',
            '/whatsapp/i',
            '/telegrambot/i',
            '/applebot/i',
            '/petalbot/i',
            '/semrushbot/i',
            '/ahrefsbot/i',
            '/mj12bot/i',
            '/dotbot/i',
            '/rogerbot/i',
            '/seznambot/i',
            '/ia_archiver/i',
            '/archive\.org_bot/i',
            '/screaming[\s_-]?frog/i',
            '/\bcrawler\b/i',
            '/\bspider\b/i',
            '/\bbot\b/i',
            '/headlesschrome/i',
            '/phantomjs/i',
            '/puppeteer/i',
            '/lighthouse/i',
            '/curl\//i',
            '/wget\//i',
            '/python-requests/i',
            '/go-http-client/i',
            '/java\//i',
            '/libwww-perl/i',
            '/scrapy/i',
            '/httpclient/i',
            '/bytespider/i',
            '/gptbot/i',
            '/claudebot/i',
            '/anthropic-ai/i',
            '/amazonbot/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $ua) === 1) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('frontend_visit_geo_enabled')) {
    function frontend_visit_geo_enabled(): bool
    {
        return frontend_visit_env_flag('FRONTEND_VISIT_GEO_ENABLED', '1');
    }
}

if (!function_exists('frontend_visit_log_session_key')) {
    function frontend_visit_log_session_key(string $pageLink): string
    {
        return 'frontend_visit_log_' . hash('sha256', frontend_visit_normalize_page_link($pageLink));
    }
}

if (!function_exists('frontend_visit_log_already_recorded')) {
    function frontend_visit_log_already_recorded(string $pageLink): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        return !empty($_SESSION[frontend_visit_log_session_key($pageLink)]);
    }
}

if (!function_exists('frontend_visit_log_mark_recorded')) {
    function frontend_visit_log_mark_recorded(string $pageLink): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION[frontend_visit_log_session_key($pageLink)] = time();
    }
}

if (!function_exists('frontend_visit_http_get')) {
    function frontend_visit_http_get(string $url, int $timeoutSec = 2): string
    {
        if (!function_exists('curl_init')) {
            return '';
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => max(1, $timeoutSec),
            CURLOPT_TIMEOUT        => max(1, $timeoutSec),
            CURLOPT_USERAGENT      => 'brick6-visit-log/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        return is_string($body) ? $body : '';
    }
}

if (!function_exists('frontend_visit_client_ip')) {
    function frontend_visit_client_ip(): string
    {
        if (function_exists('is_trusted_proxy') && is_trusted_proxy()) {
            $xff = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
            if ($xff !== '') {
                foreach (explode(',', $xff) as $part) {
                    $candidate = trim($part);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }

        $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP)) {
            return $remote;
        }

        return function_exists('UserIP') ? trim((string)UserIP()) : 'unknown';
    }
}

if (!function_exists('frontend_visit_is_public_ip')) {
    function frontend_visit_is_public_ip(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}

if (!function_exists('frontend_visit_lookup_geo')) {
    /**
     * 依 IP 查詢國別（伺服器端 GeoIP）
     *
     * @return array{country:string, code:string}
     */
    function frontend_visit_lookup_geo(string $ip): array
    {
        $empty = ['country' => '', 'code' => ''];
        $ip = trim($ip);
        if ($ip === '' || $ip === 'unknown') {
            return $empty;
        }
        if (!frontend_visit_is_public_ip($ip)) {
            return ['country' => 'Local', 'code' => ''];
        }

        if (!frontend_visit_geo_enabled()) {
            return $empty;
        }

        $geoUrl = trim((string)($_ENV['FRONTEND_VISIT_GEO_URL'] ?? getenv('FRONTEND_VISIT_GEO_URL') ?? ''));
        if ($geoUrl === '') {
            $geoUrl = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,countryCode';
        } else {
            $geoUrl = str_replace('{ip}', rawurlencode($ip), $geoUrl);
        }

        $json = frontend_visit_http_get($geoUrl, 2);
        if (trim($json) === '') {
            return $empty;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $empty;
        }

        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return $empty;
        }

        return [
            'country' => trim((string)($data['country'] ?? '')),
            'code'    => strtoupper(trim((string)($data['countryCode'] ?? ''))),
        ];
    }
}

if (!function_exists('frontend_visit_lookup_country')) {
    /** @deprecated 請改用 frontend_visit_lookup_geo() */
    function frontend_visit_lookup_country(string $ip): string
    {
        $geo = frontend_visit_lookup_geo($ip);
        $country = $geo['country'];
        $code = $geo['code'];
        if ($country === '') {
            return $code;
        }
        if ($code !== '') {
            return $country . ' (' . $code . ')';
        }

        return $country;
    }
}

if (!function_exists('frontend_visit_normalize_page_link')) {
    function frontend_visit_normalize_page_link(string $pageLink): string
    {
        $pageLink = trim($pageLink);
        if ($pageLink === '') {
            $pageLink = (string)($GLOBALS['REQUEST_URI_PATH'] ?? $GLOBALS['page_link'] ?? '');
        }

        $pageLink = preg_replace('/[\x00-\x1F\x7F]/u', '', $pageLink) ?? '';
        $pageLink = str_replace('\\', '/', $pageLink);

        if ($pageLink !== '' && $pageLink[0] !== '/') {
            $pageLink = '/' . ltrim($pageLink, '/');
        }

        if (strlen($pageLink) > 500) {
            $pageLink = mb_strcut($pageLink, 0, 500, 'UTF-8');
        }

        return $pageLink;
    }
}

if (!function_exists('frontend_visit_resolve_module_pkey')) {
    function frontend_visit_resolve_module_pkey(int $modulePKey, string $pageLink): int
    {
        if ($modulePKey > 0) {
            return $modulePKey;
        }

        $basename = basename(trim($pageLink, '/'));
        if ($basename === '') {
            return 0;
        }

        if (!function_exists('frontend_module_pkey_for_link')) {
            return 0;
        }

        $resolved = frontend_module_pkey_for_link($basename);
        if ($resolved > 0) {
            return $resolved;
        }

        $detailBase = preg_replace('/_\d+.*$/', '', $basename);
        if (is_string($detailBase) && $detailBase !== '' && $detailBase !== $basename) {
            return frontend_module_pkey_for_link($detailBase . '.htm');
        }

        return 0;
    }
}

if (!function_exists('frontend_visit_log_table_ready')) {
    function frontend_visit_log_table_ready(?PDO $pdo = null): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $table = frontend_visit_log_table();
        if (function_exists('crud_table_exists')) {
            $ready = crud_table_exists($table);
            return $ready;
        }

        $pdo = $pdo instanceof PDO ? $pdo : (function_exists('sql_conn') ? sql_conn() : null);
        if (!$pdo instanceof PDO) {
            $ready = false;
            return false;
        }

        $ready = function_exists('db_pdo_table_exists')
            ? db_pdo_table_exists($pdo, $table)
            : false;

        return $ready;
    }
}

if (!function_exists('frontend_visit_log_insert')) {
    /**
     * @return array{success:bool, pkey?:int, skipped?:bool, error?:string}
     */
    function frontend_visit_log_insert(int $modulePKey, string $pageLink): array
    {
        if (!frontend_visit_log_enabled()) {
            return ['success' => false, 'error' => 'disabled'];
        }

        if (frontend_visit_is_crawler()) {
            return ['success' => true, 'skipped' => true];
        }

        $pageLink = frontend_visit_normalize_page_link($pageLink);
        if (frontend_visit_log_already_recorded($pageLink)) {
            return ['success' => true, 'skipped' => true];
        }

        $pdo = function_exists('sql_conn') ? sql_conn() : null;
        if (!$pdo instanceof PDO) {
            return ['success' => false, 'error' => 'db_unavailable'];
        }

        if (!frontend_visit_log_table_ready($pdo)) {
            return ['success' => false, 'error' => 'table_missing'];
        }

        $modulePKey = frontend_visit_resolve_module_pkey($modulePKey, $pageLink);
        $ip = frontend_visit_client_ip();
        $geo = frontend_visit_lookup_geo($ip);

        $table = frontend_visit_log_table();
        $row = [
            'Module_PKey'     => max(0, $modulePKey),
            'strLink'         => $pageLink,
            'UserIP'          => $ip,
            'strCountry'      => $geo['country'],
            'strCountryCode'  => $geo['code'],
            'dtDate'          => date('Y-m-d H:i:s'),
        ];

        $sql = 'INSERT INTO `' . $table . '` (`Module_PKey`, `strLink`, `UserIP`, `strCountry`, `strCountryCode`, `dtDate`)'
            . ' VALUES (:Module_PKey, :strLink, :UserIP, :strCountry, :strCountryCode, :dtDate)';

        try {
            $st = $pdo->prepare($sql);
            if (function_exists('db_pdo_bind_values')) {
                db_pdo_bind_values($st, $row);
            } else {
                foreach ($row as $col => $val) {
                    $st->bindValue(':' . $col, $val);
                }
            }
            $st->execute();
            $newPKey = (int)$pdo->lastInsertId();

            frontend_visit_log_mark_recorded($pageLink);

            return ['success' => true, 'pkey' => $newPKey];
        } catch (Throwable $e) {
            error_log('[frontend_visit_log] insert failed: ' . $e->getMessage());

            return ['success' => false, 'error' => 'insert_failed'];
        }
    }
}

if (!function_exists('frontend_visit_log_archive_table_name')) {
    /** 依月份取得封存表名，如 frontend_visit_log_202606 */
    function frontend_visit_log_archive_table_name(DateTimeInterface $month): string
    {
        $ym = DateTimeImmutable::createFromInterface($month)
            ->modify('first day of this month')
            ->format('Ym');

        return frontend_visit_log_table() . '_' . $ym;
    }
}

if (!function_exists('frontend_visit_log_is_valid_archive_table')) {
    function frontend_visit_log_is_valid_archive_table(string $table): bool
    {
        if (!function_exists('crud_is_safe_sql_identifier') || !crud_is_safe_sql_identifier($table)) {
            return false;
        }

        return (bool)preg_match('/^frontend_visit_log_\d{6}$/', $table);
    }
}

if (!function_exists('frontend_visit_log_archive_month_bounds')) {
    /**
     * @return array{start:string,end:string,ym:string,label:string}
     */
    function frontend_visit_log_archive_month_bounds(DateTimeInterface $month): array
    {
        $start = DateTimeImmutable::createFromInterface($month)
            ->modify('first day of this month')
            ->setTime(0, 0, 0);
        $end = $start->modify('first day of next month');

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end'   => $end->format('Y-m-d H:i:s'),
            'ym'    => $start->format('Ym'),
            'label' => $start->format('Y-m'),
        ];
    }
}

if (!function_exists('frontend_visit_log_archive_create_table_sql')) {
    function frontend_visit_log_archive_create_table_sql(string $archiveTable, string $monthLabel): string
    {
        if (!frontend_visit_log_is_valid_archive_table($archiveTable)) {
            throw new InvalidArgumentException('Invalid archive table name');
        }

        $comment = '單元瀏覽記錄 ' . $monthLabel;

        return 'CREATE TABLE IF NOT EXISTS `' . $archiveTable . '` ('
            . ' `PKey` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . ' `Module_PKey` INT UNSIGNED NULL DEFAULT 0 COMMENT \'單元主鍵\','
            . ' `strLink` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT \'\' COMMENT \'頁面連結\','
            . ' `UserIP` VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT \'\' COMMENT \'來源IP\','
            . ' `strCountry` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT \'\' COMMENT \'國家\','
            . ' `strCountryCode` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT \'\' COMMENT \'國家代碼\','
            . ' `dtDate` DATETIME NOT NULL,'
            . ' PRIMARY KEY (`PKey`) USING BTREE,'
            . ' INDEX `idx_frontend_visit_module_date` (`Module_PKey`, `dtDate`) USING BTREE,'
            . ' INDEX `idx_frontend_visit_date` (`dtDate`) USING BTREE,'
            . ' INDEX `idx_frontend_visit_ip` (`UserIP`) USING BTREE'
            . ') ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            . ' COMMENT=' . json_encode($comment, JSON_UNESCAPED_UNICODE)
            . ' ROW_FORMAT=Dynamic';
    }
}

if (!function_exists('frontend_visit_log_archive_create_table')) {
    function frontend_visit_log_archive_create_table(PDO $pdo, string $archiveTable, string $monthLabel): bool
    {
        $sql = frontend_visit_log_archive_create_table_sql($archiveTable, $monthLabel);
        $pdo->exec($sql);

        return function_exists('crud_table_exists') && crud_table_exists($archiveTable);
    }
}

if (!function_exists('frontend_visit_log_archive_list_tables')) {
    /**
     * 列出已建立的封存表（frontend_visit_log_YYYYMM，新到舊）
     *
     * @return list<string>
     */
    function frontend_visit_log_archive_list_tables(?PDO $pdo = null): array
    {
        $pdo = $pdo instanceof PDO ? $pdo : (function_exists('sql_conn') ? sql_conn() : null);
        if (!$pdo instanceof PDO) {
            return [];
        }

        $like = frontend_visit_log_table() . '\\_%';
        if (function_exists('db_pdo_show_tables_like')) {
            $names = db_pdo_show_tables_like($pdo, frontend_visit_log_table() . '_%');
        } else {
            $params = [frontend_visit_log_table() . '_%'];
            $sql = 'SHOW TABLES LIKE ?';
            if (function_exists('db_expand_show_like_sql')) {
                $sql = db_expand_show_like_sql($pdo, $sql, $params);
            }
            if ($params === []) {
                $st = $pdo->query($sql);
            } else {
                $st = $pdo->prepare($sql);
                $st->execute($params);
            }
            $rows = $st->fetchAll(PDO::FETCH_NUM);
            $names = [];
            foreach ($rows as $row) {
                $names[] = (string)($row[0] ?? '');
            }
        }

        $tables = [];
        foreach ($names as $name) {
            if (frontend_visit_log_is_valid_archive_table($name)) {
                $tables[] = $name;
            }
        }

        rsort($tables, SORT_STRING);

        return $tables;
    }
}

if (!function_exists('frontend_visit_log_archive_resolve_month')) {
    /** 預設封存「上個月」；可傳 YYYY-MM 指定月份 */
    function frontend_visit_log_archive_resolve_month(?string $monthArg = null): DateTimeImmutable
    {
        if ($monthArg !== null && trim($monthArg) !== '') {
            $dt = DateTimeImmutable::createFromFormat('!Y-m', trim($monthArg));
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
            throw new InvalidArgumentException('Invalid --month format, use YYYY-MM');
        }

        return new DateTimeImmutable('first day of last month midnight');
    }
}

if (!function_exists('frontend_visit_log_archive_current_month_start')) {
    function frontend_visit_log_archive_current_month_start(): DateTimeImmutable
    {
        return new DateTimeImmutable('first day of this month midnight');
    }
}

if (!function_exists('frontend_visit_log_archive_pending_months')) {
    /**
     * 主表中「早于當月」且仍有資料的月份（漏跑 job 時會累積在這裡）
     *
     * @return list<array{label:string,count:int,month:DateTimeImmutable}>
     */
    function frontend_visit_log_archive_pending_months(?PDO $pdo = null): array
    {
        $pdo = $pdo instanceof PDO ? $pdo : (function_exists('sql_conn') ? sql_conn() : null);
        if (!$pdo instanceof PDO) {
            return [];
        }

        $mainTable = frontend_visit_log_table();
        if (!function_exists('crud_table_exists') || !crud_table_exists($mainTable)) {
            return [];
        }

        $cutoff = frontend_visit_log_archive_current_month_start()->format('Y-m-d H:i:s');
        $sql = 'SELECT DATE_FORMAT(`dtDate`, \'%Y-%m\') AS ym, COUNT(*) AS cnt'
            . ' FROM `' . $mainTable . '`'
            . ' WHERE `dtDate` < :cutoff'
            . ' GROUP BY DATE_FORMAT(`dtDate`, \'%Y-%m\')'
            . ' ORDER BY ym ASC';

        $st = $pdo->prepare($sql);
        $st->execute(['cutoff' => $cutoff]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $pending = [];
        foreach ($rows as $row) {
            $label = trim((string)($row['ym'] ?? ''));
            if ($label === '') {
                continue;
            }
            $month = DateTimeImmutable::createFromFormat('!Y-m', $label);
            if (!$month instanceof DateTimeImmutable) {
                continue;
            }
            $pending[] = [
                'label' => $label,
                'count' => (int)($row['cnt'] ?? 0),
                'month' => $month,
            ];
        }

        return $pending;
    }
}

if (!function_exists('frontend_visit_log_archive_status')) {
    /**
     * 檢視主表積壓狀況（供補跑前確認）
     *
     * @return array{
     *   success:bool,
     *   current_month:string,
     *   pending_months:list<array{label:string,count:int,archive_table:string}>,
     *   pending_total:int,
     *   archived_tables:list<string>,
     *   error?:string
     * }
     */
    function frontend_visit_log_archive_status(?PDO $pdo = null): array
    {
        $pdo = $pdo instanceof PDO ? $pdo : (function_exists('sql_conn') ? sql_conn() : null);
        if (!$pdo instanceof PDO) {
            return ['success' => false, 'error' => 'db_unavailable'];
        }

        $items = [];
        $pendingTotal = 0;
        foreach (frontend_visit_log_archive_pending_months($pdo) as $item) {
            $pendingTotal += (int)$item['count'];
            $items[] = [
                'label'         => (string)$item['label'],
                'count'         => (int)$item['count'],
                'archive_table' => frontend_visit_log_archive_table_name($item['month']),
            ];
        }

        return [
            'success'         => true,
            'current_month'   => frontend_visit_log_archive_current_month_start()->format('Y-m'),
            'pending_months'  => $items,
            'pending_total'   => $pendingTotal,
            'archived_tables' => frontend_visit_log_archive_list_tables($pdo),
        ];
    }
}

if (!function_exists('frontend_visit_log_archive_catch_up')) {
    /**
     * 補跑：依序封存主表內所有「早于當月」的月份（可重複執行）
     *
     * @return array{
     *   success:bool,
     *   dry_run?:bool,
     *   processed?:int,
     *   pending_total?:int,
     *   inserted_total?:int,
     *   deleted_total?:int,
     *   results?:list<array<string,mixed>>,
     *   error?:string
     * }
     */
    function frontend_visit_log_archive_catch_up(bool $dryRun = false, ?PDO $pdo = null): array
    {
        $pdo = $pdo instanceof PDO ? $pdo : (function_exists('sql_conn') ? sql_conn() : null);
        if (!$pdo instanceof PDO) {
            return ['success' => false, 'error' => 'db_unavailable'];
        }

        $pending = frontend_visit_log_archive_pending_months($pdo);
        if ($pending === []) {
            return [
                'success'        => true,
                'dry_run'        => $dryRun,
                'processed'      => 0,
                'pending_total'  => 0,
                'inserted_total' => 0,
                'deleted_total'  => 0,
                'results'        => [],
            ];
        }

        $results = [];
        $insertedTotal = 0;
        $deletedTotal = 0;
        $pendingTotal = 0;

        foreach ($pending as $item) {
            $pendingTotal += (int)$item['count'];
            $result = frontend_visit_log_archive_run($item['month'], $dryRun, $pdo);
            $results[] = $result;

            if (!$result['success']) {
                return [
                    'success'        => false,
                    'dry_run'        => $dryRun,
                    'processed'      => count($results),
                    'pending_total'  => $pendingTotal,
                    'inserted_total' => $insertedTotal,
                    'deleted_total'  => $deletedTotal,
                    'results'        => $results,
                    'error'          => (string)($result['error'] ?? 'archive_failed'),
                ];
            }

            $insertedTotal += (int)($result['inserted'] ?? 0);
            $deletedTotal += (int)($result['deleted'] ?? 0);
        }

        return [
            'success'        => true,
            'dry_run'        => $dryRun,
            'processed'      => count($results),
            'pending_total'  => $pendingTotal,
            'inserted_total' => $insertedTotal,
            'deleted_total'  => $deletedTotal,
            'results'        => $results,
        ];
    }
}

if (!function_exists('frontend_visit_log_archive_run')) {
    /**
     * 將主表指定月份資料移入 frontend_visit_log_YYYYMM
     *
     * @return array{
     *   success:bool,
     *   archive_table?:string,
     *   month?:string,
     *   pending?:int,
     *   inserted?:int,
     *   deleted?:int,
     *   dry_run?:bool,
     *   error?:string
     * }
     */
    function frontend_visit_log_archive_run(
        DateTimeInterface $month,
        bool $dryRun = false,
        ?PDO $pdo = null
    ): array {
        $pdo = $pdo instanceof PDO ? $pdo : (function_exists('sql_conn') ? sql_conn() : null);
        if (!$pdo instanceof PDO) {
            return ['success' => false, 'error' => 'db_unavailable'];
        }

        $mainTable = frontend_visit_log_table();
        if (!function_exists('crud_table_exists') || !crud_table_exists($mainTable)) {
            return ['success' => false, 'error' => 'main_table_missing'];
        }

        $bounds = frontend_visit_log_archive_month_bounds($month);
        $archiveTable = frontend_visit_log_archive_table_name($month);

        $countSt = $pdo->prepare(
            'SELECT COUNT(*) FROM `' . $mainTable . '`'
            . ' WHERE `dtDate` >= :start AND `dtDate` < :end'
        );
        $countSt->execute(['start' => $bounds['start'], 'end' => $bounds['end']]);
        $pending = (int)$countSt->fetchColumn();

        if ($pending === 0) {
            return [
                'success'       => true,
                'archive_table' => $archiveTable,
                'month'         => $bounds['label'],
                'pending'       => 0,
                'inserted'      => 0,
                'deleted'       => 0,
                'dry_run'       => $dryRun,
            ];
        }

        if ($dryRun) {
            return [
                'success'       => true,
                'archive_table' => $archiveTable,
                'month'         => $bounds['label'],
                'pending'       => $pending,
                'inserted'      => $pending,
                'deleted'       => $pending,
                'dry_run'       => true,
            ];
        }

        try {
            $pdo->beginTransaction();

            if (!crud_table_exists($archiveTable)) {
                if (!frontend_visit_log_archive_create_table($pdo, $archiveTable, $bounds['label'])) {
                    throw new RuntimeException('create_archive_table_failed');
                }
            }

            $insertSql = 'INSERT IGNORE INTO `' . $archiveTable . '`'
                . ' (`PKey`, `Module_PKey`, `strLink`, `UserIP`, `strCountry`, `strCountryCode`, `dtDate`)'
                . ' SELECT `PKey`, `Module_PKey`, `strLink`, `UserIP`, `strCountry`, `strCountryCode`, `dtDate`'
                . ' FROM `' . $mainTable . '`'
                . ' WHERE `dtDate` >= :start AND `dtDate` < :end';
            $insertSt = $pdo->prepare($insertSql);
            $insertSt->execute(['start' => $bounds['start'], 'end' => $bounds['end']]);
            $inserted = $insertSt->rowCount();

            $deleteSql = 'DELETE m FROM `' . $mainTable . '` m'
                . ' INNER JOIN `' . $archiveTable . '` a ON m.`PKey` = a.`PKey`'
                . ' WHERE m.`dtDate` >= :start AND m.`dtDate` < :end';
            $deleteSt = $pdo->prepare($deleteSql);
            $deleteSt->execute(['start' => $bounds['start'], 'end' => $bounds['end']]);
            $deleted = $deleteSt->rowCount();

            $pdo->commit();

            return [
                'success'       => true,
                'archive_table' => $archiveTable,
                'month'         => $bounds['label'],
                'pending'       => $pending,
                'inserted'      => $inserted,
                'deleted'       => $deleted,
                'dry_run'       => false,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[frontend_visit_log_archive] failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
