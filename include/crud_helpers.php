<?php
declare(strict_types=1);

/**
 * 後台 CRUD 共用函式（配合 recordset 讀取、PDO 寫入）
 *
 * 需先載入 manage/_inc.php 或 common.php（含 host.php、Function.php、log.php）
 *
 * 常用入口：
 *   crud_cfg($table, $fk)              — 列表/刪除設定
 *   crud_fetch_all / crud_fetch_one    — PDO 查詢（含 SQL 表存在 guard）
 *   crud_module_where()                — 列表 Module_PKey 條件
 *   crud_list_apply_keyword_search()   — 關鍵字 + 語意搜尋
 *   crud_process_list_actions()        — 列表 POST（刪除/排序/上下架）
 *   vector_sync_after_*()              — 向量同步（crud_helpers 內掛點）
 *
 * SQL guard：$GLOBALS['crud_sql_table_guard']=false 可關閉表存在檢查
 */

/* ── 設定與路徑 ───────────────────────────────────────── */

if (!function_exists('crud_upload_base')) {
    /** 上傳根目錄：優先 $PathForder/Upload/，其次 $upload_folder */
    function crud_upload_base(): string {
        if (!empty($GLOBALS['PathForder'])) {
            return rtrim((string)$GLOBALS['PathForder'], "/\\") . '/Upload/';
        }
        if (!empty($GLOBALS['upload_folder'])) {
            return rtrim((string)$GLOBALS['upload_folder'], "/\\") . '/';
        }
        return '';
    }
}

if (!function_exists('crud_module_ctx')) {
    /** 模組上下文（刪除/排序/日誌用） */
    function crud_module_ctx(): array {
        return [
            'Module_PKey' => $GLOBALS['Module_PKey'] ?? 0,
            'Module_Name' => $GLOBALS['Module_Name'] ?? '',
            'WorkFile'    => $GLOBALS['WorkFile'] ?? '',
            'Login_ID'    => $GLOBALS['Login_ID'] ?? '',
        ];
    }
}

if (!function_exists('crud_cfg')) {
    /**
     * 建立 delete_cascade / list 用設定
     *
     * @param string $table 主表名
     * @param string $fk    子表外鍵欄名
     * @param array  $overrides 覆寫 upload_base、img_file_cols、link_table 等
     */
    function crud_cfg(string $table, string $fk, array $overrides = []): array {
        $base = array_merge([
            'table'          => $table,
            'pk'             => 'PKey',
            'fk'             => $fk,
            'upload_base'    => crud_upload_base(),
            'module'         => crud_module_ctx(),
            'img_table'      => $table . '_img',
            'msg_table'      => $table . '_msg',
            'lang_table'     => $table . '_lang',
            'relation_table' => $table . '_relation',
            'img_folder_col' => 'Forder',
            'img_file_cols'  => ['Photo1'],
            'img_thumb_prefix' => 'thumb_',
            'img_has_webp'   => true,
        ], $overrides);

        if (isset($base['table_relation']) && !isset($base['relation_table'])) {
            $base['relation_table'] = $base['table_relation'];
        }

        return $base;
    }
}

/* ── 讀取與錯誤 ───────────────────────────────────────── */

if (!function_exists('crud_fail_db')) {
    /** 資料庫錯誤：記錄並結束（或僅記錄） */
    function crud_fail_db(
        string $sql,
        string $err,
        array $params = [],
        bool $exit = true,
        ?string $srcFile = null,
        ?int $srcLine = null
    ): void {
        $workFile = $GLOBALS['WorkFile'] ?? '';
        $loginId  = $GLOBALS['Login_ID'] ?? 'system';
        $logSql   = $sql . (empty($params) ? '' : PHP_EOL . (function_exists('array_to_string') ? array_to_string($params) : json_encode($params)));

        if (function_exists('sql_error')) {
            sql_error($logSql, $err, $workFile, $loginId, $srcFile ?? __FILE__, $srcLine ?? __LINE__);
        }

        if ($exit) {
            echo '<pre>';
            print_r(['sql' => $sql, 'error' => $err, 'params' => $params]);
            echo '</pre>';
            exit;
        }
    }
}

if (!function_exists('crud_check_rs')) {
    /** recordset 查詢後檢查錯誤，有錯誤則 crud_fail_db */
    function crud_check_rs(recordset $rs, string $sql, array $params = [], bool $exit = true): void {
        $err = $rs->getErrorMessage();
        if ($err !== '' && $err !== null) {
            crud_fail_db($sql, (string)$err, $params, $exit);
        }
    }
}

if (!function_exists('crud_is_safe_sql_identifier')) {
    /** 表名／欄位名僅允許英數底線 */
    function crud_is_safe_sql_identifier(string $name): bool {
        return $name !== '' && (bool)preg_match('/^[A-Za-z0-9_]+$/', $name);
    }
}

if (!function_exists('crud_sql_guard_enabled')) {
    /** SQL 表存在 guard 是否啟用（預設 true） */
    function crud_sql_guard_enabled(): bool {
        return ($GLOBALS['crud_sql_table_guard'] ?? true) !== false;
    }
}

if (!function_exists('crud_sql_should_skip_guard')) {
    /** SHOW / INFORMATION_SCHEMA 等維護查詢不檢查表是否存在 */
    function crud_sql_should_skip_guard(string $sql): bool {
        $trim = ltrim($sql);
        if ($trim === '') {
            return true;
        }
        if (preg_match('/^(SHOW|DESCRIBE|DESC|EXPLAIN)\s/i', $trim)) {
            return true;
        }
        return stripos($trim, 'INFORMATION_SCHEMA') !== false;
    }
}

if (!function_exists('crud_table_exists')) {
    /**
     * 資料表是否存在（同請求內快取；優先 tableExists / PDO，避免 chkTable→recordset 與 SQL guard 互相干擾）
     */
    function crud_table_exists(string $table): bool {
        static $cache = [];
        $table = trim($table);
        if ($table === '') {
            return false;
        }
        if (!crud_is_safe_sql_identifier($table)) {
            return false;
        }
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }
        if (function_exists('tableExists')) {
            $cache[$table] = tableExists($table);
            return $cache[$table];
        }
        $pdo = function_exists('sql_conn') ? sql_conn() : null;
        if ($pdo instanceof PDO) {
            if (function_exists('db_pdo_table_exists')) {
                $cache[$table] = db_pdo_table_exists($pdo, $table);
                return $cache[$table];
            }
            try {
                $st = $pdo->prepare('SHOW TABLES LIKE ?');
                $st->execute([$table]);
                $cache[$table] = (bool)$st->fetchColumn();
                return $cache[$table];
            } catch (Throwable $e) {
                $cache[$table] = false;
                return false;
            }
        }
        $cache[$table] = false;
        return false;
    }
}

if (!function_exists('crud_extract_sql_tables')) {
    /**
     * 從 SQL 擷取可能操作的實體表名（略過子查詢開頭與維護語句）
     *
     * @return list<string>
     */
    function crud_extract_sql_tables(string $sql): array {
        if (crud_sql_should_skip_guard($sql)) {
            return [];
        }
        $tables = [];
        $pattern = '/\b(?:FROM|JOIN|INTO|UPDATE|DELETE\s+FROM)\s+[`"]?([A-Za-z0-9_]+)[`"]?/i';
        if (preg_match_all($pattern, $sql, $m)) {
            foreach ($m[1] as $name) {
                if (crud_is_safe_sql_identifier($name)) {
                    $tables[$name] = true;
                }
            }
        }
        return array_keys($tables);
    }
}

if (!function_exists('crud_trusted_module_tables')) {
    /**
     * 已由 manage_detail_set_config 註冊的模組表（略過重複的 exists 檢查，避免誤判導致查詢被略過）
     *
     * @return array<string, true> 小寫表名 => true
     */
    function crud_trusted_module_tables(): array {
        $trusted = [];
        $cfg = $GLOBALS['manage_detail_config'] ?? null;
        if (is_array($cfg)) {
            foreach (['master', 'img', 'lang', 'msg', 'link'] as $key) {
                $t = trim((string)($cfg[$key] ?? ''));
                if ($t !== '' && crud_is_safe_sql_identifier($t)) {
                    $trusted[strtolower($t)] = true;
                }
            }
        }
        $fcfg = $GLOBALS['frontend_module_config'] ?? null;
        if (is_array($fcfg)) {
            foreach (['master', 'img', 'lang', 'msg', 'link', 'view'] as $key) {
                $t = trim((string)($fcfg[$key] ?? ''));
                if ($t !== '' && crud_is_safe_sql_identifier($t)) {
                    $trusted[strtolower($t)] = true;
                }
            }
        }
        // 前台／全站 MySQL VIEW 白名單（略過 table exists 誤判）
        foreach ([
            'view_album',
            'view_company',
            'view_dbad',
            'view_dbclass1',
            'view_dbclass2',
            'view_faq',
            'view_filedown',
            'view_investor',
            'view_module_lang',
            'view_news',
            'view_paper',
            'view_product',
            'view_product_realtion',
            'view_question',
            'view_question_class',
            'view_question_item',
            'view_question_report',
            'view_video',
            'view_web',
            // 常用實體表（非 view）
            'dbad',
            'dbad_img',
            'dbad_lang',
            'webset',
            'language',
            // 前台瀏覽記錄
            'frontend_visit_log',
            // 前台模組實體表
            'news',
            'news_img',
            'news_msg',
            'news_link',
            'news_lang',
            // paper 模組實體表
            'paper',
            'paper_img',
            'paper_msg',
            'paper_link',
            'paper_lang',
            // company 模組實體表
            'company',
            'company_img',
            'company_msg',
            'company_lang',
            // faq 模組實體表
            'faq',
            'faq_img',
            'faq_msg',
            'faq_lang',
            // video 模組實體表
            'video',
            'video_img',
            'video_lang',
            // filedown 模組實體表
            'filedown',
            'filedown_img',
            'filedown_lang',
            // weblink 模組實體表
            'dbweb',
            'dbweb_img',
            'dbweb_lang',
            // album 模組實體表
            'album',
            'album_img',
            'album_lang',
            'album_msg',
            // product 模組實體表
            'product',
            'product_img',
            'product_lang',
            'product_msg',
            'product_relation',
            // investor 模組實體表
            'investor',
            'investor_img',
            'investor_lang',
            'investor_msg',
            // question 模組實體表（前台 questionnaire.php 直接查主檔）
            'question',
            'question_lang',
            'question_class',
            'question_class_lang',
            'question_item',
            'question_itme_lang',
            'question_answer',
            'question_answer_lang',
            'question_img',
            'question_msg',
            'question_report_p',
            'question_report_d',
        ] as $t) {
            $trusted[strtolower($t)] = true;
        }
        return $trusted;
    }
}

if (!function_exists('crud_sql_validate_tables')) {
    /** 檢查 SQL 涉及的所有表是否存在于 DB（白名單表略過 exists 查詢） */
    function crud_sql_validate_tables(string $sql): bool {
        $trusted = crud_trusted_module_tables();
        foreach (crud_extract_sql_tables($sql) as $table) {
            if (isset($trusted[strtolower($table)])) {
                continue;
            }
            if (!crud_table_exists($table)) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('crud_guard_sql_or_empty')) {
    /**
     * 執行前檢查 SQL 涉及的表是否皆存在；不存在則記錄並回傳 false（呼叫端應略過查詢）
     */
    function crud_guard_sql_or_empty(string $sql, array $params = []): bool {
        if (!crud_sql_guard_enabled() || crud_sql_validate_tables($sql)) {
            return true;
        }
        if (function_exists('sql_error')) {
            $tables = implode(',', crud_extract_sql_tables($sql));
            sql_error(
                $sql,
                'skipped: referenced table missing (' . $tables . ')',
                (string)($GLOBALS['WorkFile'] ?? ''),
                'system'
            );
        }
        return false;
    }
}

if (!function_exists('crud_pdo_query')) {
    /** @return array<int, array<string, mixed>> */
    function crud_pdo_query(string $sql, array $params = []): array {
        if (!crud_guard_sql_or_empty($sql, $params)) {
            return [];
        }
        $pdo = function_exists('sql_conn') ? sql_conn() : null;
        if (!$pdo instanceof PDO) {
            crud_fail_db($sql, '資料庫連線失敗', $params);
        }
        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            crud_fail_db($sql, $e->getMessage(), $params);
        }
        return [];
    }
}

if (!function_exists('crud_fetch_all')) {
    /**
     * PDO 查詢多筆（含 SQL guard）
     *
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    function crud_fetch_all(string $sql, array $params = []): array {
        return crud_pdo_query($sql, $params);
    }
}

if (!function_exists('crud_fetch_one')) {
    /**
     * PDO 查詢單筆；$skipTableGuard=true 時用 crud_pdo_query_raw
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    function crud_fetch_one(string $sql, array $params = [], bool $skipTableGuard = false): ?array {
        $rows = $skipTableGuard
            ? crud_pdo_query_raw($sql, $params)
            : crud_pdo_query($sql, $params);
        return $rows[0] ?? null;
    }
}

if (!function_exists('crud_pdo_query_raw')) {
    /** 略過表存在檢查（僅供已驗證之模組表名／識別子安全之查詢） */
    function crud_pdo_query_raw(string $sql, array $params = []): array {
        $pdo = function_exists('sql_conn') ? sql_conn() : null;
        if (!$pdo instanceof PDO) {
            crud_fail_db($sql, '資料庫連線失敗', $params);
        }
        try {
            $st = $pdo->prepare($sql);
            if (function_exists('db_pdo_bind_values')) {
                db_pdo_bind_values($st, $params);
                $st->execute();
            } else {
                $st->execute($params);
            }
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            crud_fail_db($sql, $e->getMessage(), $params);
        }
        return [];
    }
}

if (!function_exists('crud_row_val')) {
    /** 從 PDO 列讀欄位（大小寫不敏感） */
    function crud_row_val(array $row, string $col): mixed {
        if (array_key_exists($col, $row)) {
            return $row[$col];
        }
        $lower = strtolower($col);
        foreach ($row as $k => $v) {
            if (is_string($k) && strtolower($k) === $lower) {
                return $v;
            }
        }
        return null;
    }
}

if (!function_exists('crud_row_int')) {
    /** 從列讀整數欄位（經 crud_row_val，無值回 0） */
    function crud_row_int(array $row, string $col): int {
        $v = crud_row_val($row, $col);
        return safe_int(is_scalar($v) ? $v : 0);
    }
}

if (!function_exists('crud_lang_seo_title')) {
    /**
     * 語系 SEO 標題：優先 lang.Title，空白時改用 lang.strName
     *
     * @param array<string,mixed> $row
     */
    function crud_lang_seo_title(array $row): string
    {
        $title = trim((string)crud_row_val($row, 'Title'));
        if ($title !== '') {
            return $title;
        }

        return trim((string)crud_row_val($row, 'strName'));
    }
}

if (!function_exists('frontend_lang_seo_title')) {
    /** @alias crud_lang_seo_title */
    function frontend_lang_seo_title(array $row): string
    {
        return crud_lang_seo_title($row);
    }
}

if (!function_exists('crud_load_row_by_pk')) {
    /**
     * 依主鍵讀取單筆（表名／欄位名須已通過 crud_is_safe_sql_identifier）
     */
    function crud_load_row_by_pk(string $table, int $pkey, string $pkCol = 'PKey'): ?array {
        if ($pkey <= 0 || !crud_is_safe_sql_identifier($table) || !crud_is_safe_sql_identifier($pkCol)) {
            return null;
        }
        $safeTable = str_replace('`', '', $table);
        $safePk    = str_replace('`', '', $pkCol);
        $sql = 'SELECT * FROM `' . $safeTable . '` WHERE `' . $safePk . '` = :pk LIMIT 1';
        $rows = crud_pdo_query_raw($sql, ['pk' => $pkey]);
        return $rows[0] ?? null;
    }
}

if (!function_exists('crud_fetch_master_row')) {
    /**
     * 依主鍵讀取模組主檔一筆（表名須為 manage_detail 已註冊之 master）
     */
    function crud_fetch_master_row(int $pkey, string $pkCol = 'PKey'): ?array {
        if ($pkey <= 0 || !crud_is_safe_sql_identifier($pkCol)) {
            return null;
        }
        $cfg = $GLOBALS['manage_detail_config'] ?? null;
        $table = is_array($cfg) ? trim((string)($cfg['master'] ?? '')) : '';
        if ($table === '' || !crud_is_safe_sql_identifier($table)) {
            return null;
        }
        return crud_load_row_by_pk($table, $pkey, $pkCol);
    }
}

if (!function_exists('crud_fetch_one_rs')) {
    /**
     * 以 recordset 讀單筆（與既有程式相容）
     * @param callable|null $onRow function(recordset $rs): void
     */
    function crud_fetch_one_rs(string $sql, array $params, ?callable $onRow = null): bool {
        if (!crud_guard_sql_or_empty($sql, $params)) {
            return false;
        }
        $rs = new recordset($sql, $params);
        crud_check_rs($rs, $sql, $params);
        if ($rs->eof) {
            $rs->close();
            return false;
        }
        if ($onRow !== null) {
            $onRow($rs);
        }
        $rs->close();
        return true;
    }
}

if (!function_exists('crud_fetch_scalar')) {
    /** recordset 查詢單一數值欄（常用 COUNT AS Total） */
    function crud_fetch_scalar(string $sql, array $params, string $field = 'Total'): int {
        if (!crud_guard_sql_or_empty($sql, $params)) {
            return 0;
        }
        $rs = new recordset($sql, $params);
        crud_check_rs($rs, $sql, $params);
        $val = $rs->eof ? 0 : (int)$rs->field($field);
        $rs->close();
        return $val;
    }
}

if (!function_exists('crud_require_pkey')) {
    /** 編輯頁：PKey 無效則導回列表 */
    function crud_require_pkey(int $pkey, string $listUrl = 'list.php', string $message = '參數錯誤：缺少 PKey'): void {
        if ($pkey > 0) {
            return;
        }
        if (function_exists('manage_alert_script')) {
            manage_alert_script($message, $listUrl);
        } else {
            echo '<script>alert(' . json_encode($message, JSON_UNESCAPED_UNICODE) . ');';
            echo 'location.href=' . json_encode($listUrl, JSON_UNESCAPED_UNICODE) . ';</script>';
        }
        exit;
    }
}

if (!function_exists('crud_load_master_row')) {
    /**
     * 讀取主檔一筆；不存在則 alert 並導回列表
     *
     * @return array<string, mixed>
     */
    function crud_load_master_row(string $table, int $pkey, string $pkCol = 'PKey', string $listUrl = 'list.php'): array {
        $sql = "SELECT * FROM {$table} WHERE {$pkCol} = :pk";
        $row = crud_fetch_one($sql, ['pk' => $pkey]);
        if ($row !== null) {
            return $row;
        }
        if (function_exists('manage_alert_script')) {
            manage_alert_script('查無要修改資料!', $listUrl);
        } else {
            echo "<script>alert('查無要修改資料!');location.href='{$listUrl}';</script>";
        }
        exit;
    }
}

if (!function_exists('crud_load_msg_blocks_data')) {
    /**
     * 讀取內文子表（Sort 1..$blocks）
     *
     * @return array{contents: array<int,string>, isShow: array<int,int>}
     */
    function crud_load_msg_blocks_data(
        string $tableMsg,
        string $fkName,
        int $fkValue,
        int $blocks = 6
    ): array {
        $contents = [];
        $isShow   = [];
        for ($i = 1; $i <= $blocks; $i++) {
            $contents[$i] = '';
            $isShow[$i]   = 1;
        }
        if (!function_exists('chkTable') || !chkTable($tableMsg)) {
            return ['contents' => $contents, 'isShow' => $isShow];
        }

        $cols = 'Sort, Contents';
        $hasIsShow = function_exists('crud_table_has_column')
            && crud_table_has_column($tableMsg, 'isShow');
        if ($hasIsShow) {
            $cols .= ', isShow';
        }

        $sql  = "SELECT {$cols} FROM {$tableMsg} WHERE {$fkName} = :fk ORDER BY Sort";
        $rows = crud_fetch_all($sql, ['fk' => $fkValue]);
        foreach ($rows as $r) {
            $sort = (int)($r['Sort'] ?? 0);
            if ($sort < 1 || $sort > $blocks) {
                continue;
            }
            $contents[$sort] = (string)($r['Contents'] ?? '');
            $isShow[$sort]   = $hasIsShow ? (int)($r['isShow'] ?? 1) : 1;
        }

        return ['contents' => $contents, 'isShow' => $isShow];
    }
}

if (!function_exists('crud_load_img_slots_data')) {
    /**
     * 讀取圖檔/附件子表（路徑 YYYYMM/檔名）
     *
     * @return array{Photo: array<int,string>, PhotoS: array<int,int>, PhotoM: array<int,string>, intType: array<int,int>}
     */
    function crud_load_img_slots_data(string $tableImg, string $fkName, int $fkValue): array {
        $Photo   = [];
        $PhotoS  = [];
        $PhotoM  = [];
        $intType = [];

        if (!function_exists('chkTable') || !chkTable($tableImg)) {
            return ['Photo' => $Photo, 'PhotoS' => $PhotoS, 'PhotoM' => $PhotoM, 'intType' => $intType];
        }

        $cols = 'PKey, Sort, Forder, Photo1, PhotoM';
        $layoutCol = null;
        if (function_exists('crud_table_has_column')) {
            if (crud_table_has_column($tableImg, 'intType')) {
                $layoutCol = 'intType';
            } elseif (crud_table_has_column($tableImg, 'isShow')) {
                $layoutCol = 'isShow';
            }
        }
        if ($layoutCol !== null) {
            $cols .= ', ' . $layoutCol;
        }

        $sql  = "SELECT {$cols} FROM {$tableImg} WHERE {$fkName} = :fk ORDER BY Sort";
        $rows = crud_fetch_all($sql, ['fk' => $fkValue]);

        foreach ($rows as $r) {
            $i      = (int)($r['Sort'] ?? 0);
            if ($i <= 0) {
                continue;
            }
            $forder = rtrim((string)($r['Forder'] ?? ''), "/\\");
            $fname  = basename((string)($r['Photo1'] ?? ''));
            if ($fname !== '') {
                $rel = ($forder !== '' ? $forder . '/' : '') . $fname;
                $Photo[$i]  = $rel;
                $PhotoS[$i] = (int)($r['PKey'] ?? 0);
            }
            $PhotoM[$i] = (string)($r['PhotoM'] ?? '');
            if ($layoutCol !== null) {
                $raw = $r[$layoutCol] ?? null;
                if (!manage_content_img_layout_is_empty($raw)) {
                    $intType[$i] = manage_content_img_layout_normalize($raw);
                }
            }
        }

        $intType = manage_content_img_layout_merge_defaults($intType);

        return ['Photo' => $Photo, 'PhotoS' => $PhotoS, 'PhotoM' => $PhotoM, 'intType' => $intType];
    }
}

if (!function_exists('crud_lang_table_select_meta')) {
    /**
     * 語系子表實際欄位（各站 schema 可能無 Subject，改為 Interview 等）
     *
     * @return array{select:list<string>, subject_col:?string}
     */
    function crud_lang_table_select_meta(string $tableLang): array {
        if (!crud_is_safe_sql_identifier($tableLang) || !crud_table_exists($tableLang)) {
            return ['select' => [], 'subject_col' => null];
        }
        $select = [];
        foreach ([
            'Sort', 'intLang', 'isShow', 'strName', 'Title', 'Description', 'Keywords', 'Movielink', 'Contents',
            'intLink', 'strLink', 'strURL', 'Target', 'FileName', 'FileSize', 'Forder',
        ] as $col) {
            if (crud_table_has_column($tableLang, $col)) {
                $select[] = $col;
            }
        }
        $subjectCol = null;
        foreach (['Interview', 'Subject'] as $col) {
            if (crud_table_has_column($tableLang, $col)) {
                $select[] = $col;
                $subjectCol = $col;
                break;
            }
        }
        return ['select' => $select, 'subject_col' => $subjectCol];
    }
}

if (!function_exists('crud_load_lang_slots_data')) {
    /**
     * 讀取語系子表（dbad_lang 等，依 Sort 索引）
     *
     * @return array{
     *   language: array<int,int>,
     *   isShow: array<int,string>,
     *   strName: array<int,string>,
     *   Subject: array<int,string>,
     *   Description: array<int,string>,
     *   Keywords: array<int,string>,
     *   Movielink: array<int,string>,
     *   Contents: array<int,string>
     * }
     */
    function crud_load_lang_slots_data(string $tableLang, string $fkName, int $fkValue): array {
        $language = [];
        $isShow   = [];
        $strName  = [];
        $Title    = [];
        $Subject  = [];
        $Description = [];
        $Keywords = [];
        $Movielink = [];
        $Contents  = [];
        $intLink   = [];
        $strLink   = [];
        $strURL    = [];
        $Target    = [];
        $FileName  = [];
        $FileSize  = [];
        $Forder    = [];

        $empty = [
            'language'    => $language,
            'isShow'      => $isShow,
            'strName'     => $strName,
            'Title'       => $Title,
            'Subject'     => $Subject,
            'Description' => $Description,
            'Keywords'    => $Keywords,
            'Movielink'   => $Movielink,
            'Contents'    => $Contents,
            'intLink'     => $intLink,
            'strLink'     => $strLink,
            'strURL'      => $strURL,
            'Target'      => $Target,
            'FileName'    => $FileName,
            'FileSize'    => $FileSize,
            'Forder'      => $Forder,
        ];

        if (!crud_is_safe_sql_identifier($tableLang) || !crud_is_safe_sql_identifier($fkName)) {
            return $empty;
        }
        if (!function_exists('chkTable') || !chkTable($tableLang)) {
            return $empty;
        }

        $meta = crud_lang_table_select_meta($tableLang);
        if ($meta['select'] === []) {
            return $empty;
        }

        $sql  = 'SELECT ' . implode(', ', $meta['select']) . ' FROM ' . $tableLang
            . ' WHERE ' . $fkName . ' = :fk ORDER BY Sort';
        $rows = crud_fetch_all($sql, ['fk' => $fkValue]);

        foreach ($rows as $r) {
            $i            = (int)(function_exists('crud_row_int') ? crud_row_int($r, 'Sort') : ($r['Sort'] ?? 0));
            $language[$i] = (int)(function_exists('crud_row_int') ? crud_row_int($r, 'intLang') : ($r['intLang'] ?? 0));
            $isShow[$i]   = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'isShow') : ($r['isShow'] ?? ''));
            $strName[$i]  = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'strName') : ($r['strName'] ?? ''));
            if (crud_table_has_column($tableLang, 'Title')) {
                $Title[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'Title') : ($r['Title'] ?? ''));
            }
            if ($meta['subject_col'] !== null) {
                $col = $meta['subject_col'];
                $Subject[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, $col) : ($r[$col] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'Description')) {
                $Description[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'Description') : ($r['Description'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'Keywords')) {
                $Keywords[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'Keywords') : ($r['Keywords'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'Movielink')) {
                $Movielink[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'Movielink') : ($r['Movielink'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'Contents')) {
                $Contents[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'Contents') : ($r['Contents'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'intLink')) {
                $intLink[$i] = (int)(function_exists('crud_row_int') ? crud_row_int($r, 'intLink') : ($r['intLink'] ?? 0));
            }
            if (crud_table_has_column($tableLang, 'strLink')) {
                $strLink[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'strLink') : ($r['strLink'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'strURL')) {
                $strURL[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'strURL') : ($r['strURL'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'Target')) {
                $Target[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'Target') : ($r['Target'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'FileName')) {
                $FileName[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'FileName') : ($r['FileName'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'FileSize')) {
                $FileSize[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'FileSize') : ($r['FileSize'] ?? ''));
            }
            if (crud_table_has_column($tableLang, 'Forder')) {
                $Forder[$i] = (string)(function_exists('crud_row_val') ? crud_row_val($r, 'Forder') : ($r['Forder'] ?? ''));
            }
        }

        return [
            'language'    => $language,
            'isShow'      => $isShow,
            'strName'     => $strName,
            'Title'       => $Title,
            'Subject'     => $Subject,
            'Description' => $Description,
            'Keywords'    => $Keywords,
            'Movielink'   => $Movielink,
            'Contents'    => $Contents,
            'intLink'     => $intLink,
            'strLink'     => $strLink,
            'strURL'      => $strURL,
            'Target'      => $Target,
            'FileName'    => $FileName,
            'FileSize'    => $FileSize,
            'Forder'      => $Forder,
        ];
    }
}

if (!function_exists('crud_csrf_ensure_page')) {
    /** 編輯頁載入時確保 CSRF token 存在 */
    function crud_csrf_ensure_page(string $key): string {
        return crud_csrf_ensure($key);
    }
}

if (!function_exists('crud_load_program_meta')) {
    /**
     * 讀取 program 設定（module_p 用）
     *
     * @return array{strLink:string,Home:string,intLocal:int,isList:int,isDetail:int,MaxLayer:int,isColum:int}
     */
    function crud_load_program_meta(int $programPKey): array {
        $defaults = [
            'strLink'   => 'none',
            'Home'      => '',
            'intLocal'  => 0,
            'isList'    => 0,
            'isDetail'  => 0,
            'MaxLayer'  => 0,
            'isColum'   => 0,
        ];
        if ($programPKey <= 0) {
            return $defaults;
        }
        $row = crud_fetch_one(
            'SELECT PKey, strLink, Home, isList, isDetail, MaxLayer, isColum FROM program WHERE PKey = :pk',
            ['pk' => $programPKey]
        );
        if ($row === null) {
            return $defaults;
        }
        $home = (string)($row['Home'] ?? '');
        $intLocal = 0;
        if ($home === 'Yes') {
            $intLocal = (int)($row['PKey'] ?? 0) - 1;
        }
        return [
            'strLink'   => (string)($row['strLink'] ?? 'none'),
            'Home'      => $home,
            'intLocal'  => $intLocal,
            'isList'    => (int)($row['isList'] ?? 0),
            'isDetail'  => (int)($row['isDetail'] ?? 0),
            'MaxLayer'  => (int)($row['MaxLayer'] ?? 0),
            'isColum'   => (int)($row['isColum'] ?? 0),
        ];
    }
}

if (!function_exists('crud_module_p_before_delete')) {
    /** module_p 刪除前：清除 module_d、dbad(intLocal) */
    function crud_module_p_before_delete(array $ids): void {
        if ($ids === []) {
            return;
        }
        $ctx = crud_module_ctx();
        $pdo = new dbPDO();
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) {
                continue;
            }
            $pdo->delete('module_d', 'Module_PKey', $id);
            $sqlLog = $pdo->getLastSql() . "\nModule_PKey=" . $id;
            if (function_exists('manage_history')) {
                manage_history($ctx['Module_PKey'], $ctx['Module_Name'] ?: '單元設定', $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], '刪除 module_d');
            }

            if (function_exists('chkTable') && chkTable('module_lang')) {
                $pdo->delete('module_lang', ' Module_PKey = :Module_PKey', ['Module_PKey' => $id]);
                $sqlLog = $pdo->getLastSql() . "\nModule_PKey=" . $id;
                if (function_exists('manage_history')) {
                    manage_history($ctx['Module_PKey'], $ctx['Module_Name'] ?: '單元設定', $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], '刪除 module_lang');
                }
            }

            $pdo->delete('dbad', ' intLocal = :intLocal', ['intLocal' => $id]);
            $sqlLog = $pdo->getLastSql() . "\nintLocal=" . $id;
            if (function_exists('manage_history')) {
                manage_history($ctx['Module_PKey'], $ctx['Module_Name'] ?: '單元設定', $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], '刪除 dbad');
            }
        }
        $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
        $pdo->close();
        if ($err !== '') {
            crud_fail_db('module_p before_delete', $err, $ids, true);
        }
    }
}

if (!function_exists('crud_delete_all_module_d')) {
    /** 清除指定 module_p 的全部 module_d 階層子表 */
    function crud_delete_all_module_d(int $modulePKey): void {
        if ($modulePKey <= 0 || !function_exists('chkTable') || !chkTable('module_d')) {
            return;
        }
        $pdo = new dbPDO();
        $pdo->delete('module_d', 'Module_PKey', $modulePKey);
        $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
        $sqlLog = $pdo->getLastSql() . "\nModule_PKey=" . $modulePKey;
        $pdo->close();
        if ($err !== '') {
            crud_fail_db('DELETE module_d all layers', $err, ['Module_PKey' => $modulePKey], true);
        }
        if (function_exists('manage_history')) {
            $ctx = crud_module_ctx();
            manage_history(
                $ctx['Module_PKey'],
                $ctx['Module_Name'] ?: '單元設定',
                $sqlLog,
                $ctx['WorkFile'],
                $ctx['Login_ID'],
                '清除 module_d'
            );
        }
    }
}

if (!function_exists('crud_sync_module_d_layers')) {
    /** 依層級維護 module_d 子表 */
    function crud_sync_module_d_layers(
        int $modulePKey,
        int $intLayer,
        int $oldLayer,
        int $intUse,
        string $strLink,
        array $filter
    ): void {
        $maxLayer = 0;
        if ($intUse > 0) {
            $maxLayer = (int)(crud_load_program_meta($intUse)['MaxLayer'] ?? 0);
        }
        if ($maxLayer <= 0 || $intLayer <= 1 || ($oldLayer > 1 && $intLayer < 2)) {
            crud_delete_all_module_d($modulePKey);
            return;
        }

        $subLink = [];
        switch ($intLayer) {
            case 2:
                $subLink = ['class1', $strLink];
                if ($intUse === 20) {
                    $subLink = ['project', 'donation'];
                }
                break;
            case 3:
                $subLink = ['class1', 'class2', $strLink];
                break;
            case 4:
                $subLink = ['class1', 'class2', 'class3', $strLink];
                break;
            case 5:
                $subLink = ['class1', 'class2', 'class3', 'class4', $strLink];
                break;
        }

        $strv = static function ($v, int $len = 50): string {
            $v = (string)$v;
            return $len > 0 ? mb_substr($v, 0, $len, 'UTF-8') : $v;
        };

        if ($subLink !== []) {
            for ($i = 0; $i < $intLayer; $i++) {
                $j = $i + 1;
                $row = [
                    'Sort'        => SqlFilter($j, 'int'),
                    'Module_PKey' => SqlFilter($modulePKey, 'int'),
                    'strName'     => SqlFilter($strv($filter['subName' . $j] ?? $filter['strName' . $j] ?? ''), 'tab'),
                    'strLink'     => SqlFilter($subLink[$i], 'tab'),
                    'dtDate'      => date('Y-m-d H:i:s'),
                    'dtUDate'     => date('Y-m-d H:i:s'),
                    'UserID'      => SqlFilter($GLOBALS['Login_ID'] ?? '', 'tab'),
                ];
                crud_upsert_by_fk_sort('module_d', 'Module_PKey', $modulePKey, $j, $row, '子層' . $j);
            }
        }

        if ($oldLayer > $intLayer) {
            $pdo = new dbPDO();
            $extraRows = crud_fetch_all(
                'SELECT PKey FROM module_d WHERE Module_PKey = :fk AND Sort > :sort',
                ['fk' => $modulePKey, 'sort' => $intLayer]
            );
            foreach ($extraRows as $r) {
                $childPk = (int)($r['PKey'] ?? 0);
                if ($childPk > 0) {
                    $pdo->delete('module_d', 'PKey', $childPk);
                }
            }
            $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
            $pdo->close();
            if ($err !== '') {
                crud_fail_db('DELETE module_d extra layers', $err, ['Module_PKey' => $modulePKey], true);
            }
        }
    }
}

if (!function_exists('crud_save_ad_lang_slots')) {
    /** 儲存 dbad_lang 語系子表 */
    function crud_save_ad_lang_slots(
        string $tableLang,
        string $fkName,
        int $adPKey,
        array $filter,
        ?array $langIndexes = null
    ): void {
        if (!function_exists('chkTable') || !chkTable($tableLang)) {
            return;
        }

        $langCount = $langIndexes !== null
            ? count($langIndexes)
            : count((array)($GLOBALS['array_lang'] ?? []));
        if ($langCount <= 0) {
            $langCount = 6;
        }

        for ($n = 1; $n <= $langCount; $n++) {
            $showVal = (($filter['Show' . $n] ?? '') === 'Y') ? 'Y' : '';
            $row = [
                $fkName    => SqlFilter($adPKey, 'int'),
                'Sort'     => SqlFilter($n, 'int'),
                'intLang'  => SqlFilter($n, 'int'),
                'isShow'   => SqlFilter($showVal, 'tab'),
                'strName'  => SqlFilter((string)($filter['strName' . $n] ?? ''), 'tab'),
                'Subject'  => SqlFilter((string)($filter['Subject' . $n] ?? ''), 'tab'),
                'dtDate'   => date('Y-m-d H:i:s'),
            ];
            if (isset($filter['Interview' . $n]) && crud_table_has_column($tableLang, 'Interview')) {
                $row['Interview'] = SqlFilter((string)$filter['Interview' . $n], 'tab');
            }
            if (isset($filter['Movielink' . $n]) && crud_table_has_column($tableLang, 'Movielink')) {
                $row['Movielink'] = SqlFilter((string)$filter['Movielink' . $n], 'tab');
            }

            $existing = crud_fetch_one(
                "SELECT PKey FROM {$tableLang} WHERE {$fkName} = :fk AND intLang = :lang",
                ['fk' => $adPKey, 'lang' => $n]
            );

            $pdo = new dbPDO();
            if ($existing !== null) {
                $childPk = (int)$existing['PKey'];
                $pdo->update($tableLang, $row, 'PKey', $childPk);
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '語系' . $n . '修改成功';
            } else {
                $pdo->insert($tableLang, $row);
                $childPk = (int)$pdo->getLastId();
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '語系' . $n . '新增成功';
            }
            $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
            $pdo->close();
            if ($err !== '') {
                crud_fail_db($sqlLog, $err, $row, true);
            }

            $ctx = crud_module_ctx();
            if (function_exists('manage_history')) {
                manage_history($ctx['Module_PKey'], $ctx['Module_Name'], $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], $action);
            }
        }
    }
}

if (!function_exists('crud_hash_password')) {
    /** 後台密碼雜湊（優先 host.php hash_password + PASSWORD_PEPPER） */
    function crud_hash_password(string $plain): string {
        if (function_exists('hash_password')) {
            return hash_password($plain);
        }
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($plain, PASSWORD_ARGON2ID);
        }
        return password_hash($plain, PASSWORD_DEFAULT);
    }
}

if (!function_exists('crud_validate_password_complexity')) {
    /** 驗證密碼複雜度；通過回傳空字串，否則回傳錯誤訊息 */
    function crud_validate_password_complexity(string $pw, ?int $policy = null): string {
        $policy = $policy ?? (is_numeric($GLOBALS['PW_Match'] ?? null) ? (int)$GLOBALS['PW_Match'] : 2);
        $minLen = 8;
        $maxLen = 20;
        $need   = max(2, min(4, $policy));
        $len    = strlen($pw);

        if ($len < $minLen || $len > $maxLen) {
            return "【密碼】長度需為 {$minLen}~{$maxLen} 碼\n";
        }

        $kinds = 0;
        $kinds += (int)preg_match('/[a-z]/', $pw);
        $kinds += (int)preg_match('/[A-Z]/', $pw);
        $kinds += (int)preg_match('/[0-9]/', $pw);
        $kinds += (int)preg_match('/[~!@#$%^&*()\-_=+{};:<,.>?]/', $pw);

        if ($kinds >= $need) {
            return '';
        }

        $lack = [];
        if (!preg_match('/[a-z]/', $pw)) {
            $lack[] = '小寫';
        }
        if (!preg_match('/[A-Z]/', $pw)) {
            $lack[] = '大寫';
        }
        if (!preg_match('/[0-9]/', $pw)) {
            $lack[] = '數字';
        }
        if (!preg_match('/[~!@#$%^&*()\-_=+{};:<,.>?]/', $pw)) {
            $lack[] = '符號';
        }

        return '【密碼】需至少包含 ' . $need . ' 類：' . implode('、', $lack) . "\n";
    }
}

if (!function_exists('crud_change_own_password')) {
    /** 登入者變更自己的 webcontrol 密碼並寫入 password_log */
    function crud_change_own_password(string $loginId, string $plainPassword, ?string $userName = null): void {
        $loginId   = trim($loginId);
        $userName  = $userName ?? (string)($_SESSION['UserName'] ?? '');
        $hash      = crud_hash_password($plainPassword);
        $ctx       = crud_module_ctx();

        $pdo = new dbPDO();
        $pdo->update('webcontrol', [
            'strPW'   => $hash,
            'dtUDate' => date('Y-m-d H:i:s'),
            'UserID'  => $loginId,
        ], 'strID', $loginId);
        $sqlLog = $pdo->getLastSql() . "\nstrID=" . $loginId;
        $err    = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
        $pdo->close();
        if ($err !== '') {
            crud_fail_db($sqlLog, $err, ['strID' => $loginId], true);
        }
        if (function_exists('manage_history')) {
            manage_history($ctx['Module_PKey'], '變更密碼', $sqlLog, $ctx['WorkFile'], $loginId, '更新密碼');
        }

        $pdo = new dbPDO();
        $logRow = [
            'strID'   => $loginId,
            'strName' => $userName,
            'strPW'   => $hash,
            'dtDate'  => date('Y-m-d H:i:s'),
        ];
        $pdo->insert('password_log', $logRow);
        $sqlLog = $pdo->getLastSql() . "\nPKey=" . $pdo->getLastId();
        $err    = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
        $pdo->close();
        if ($err !== '') {
            crud_fail_db($sqlLog, $err, $logRow, true);
        }
        if (function_exists('manage_history')) {
            manage_history($ctx['Module_PKey'], '變更密碼', $sqlLog, $ctx['WorkFile'], $loginId, '更新密碼');
        }
    }
}

if (!function_exists('crud_load_webset_form_data')) {
    /**
     * 載入 webset 表單資料（依 intLang 索引）
     *
     * @return array<string,mixed>
     */
    function crud_load_webset_form_data(): array {
        $out = [
            'strName'     => [],
            'Description' => [],
            'Keywords'    => [],
            'Tel'         => [],
            'Fax'         => [],
            'PostCode'    => [],
            'County'      => [],
            'City'        => [],
            'Address'     => [],
            'Facebook'    => [],
            'Line'        => [],
            'IG'          => [],
            'Youtube'     => [],
            'Twitter'     => [],
            'FromMail'    => '',
            'ToMail'      => '',
            'gaCode'      => '',
            'gtmCode'     => '',
        ];

        $rows = crud_fetch_all('SELECT * FROM webset ORDER BY intLang');
        foreach ($rows as $r) {
            $i = (int)($r['intLang'] ?? 0);
            if ($i <= 0) {
                continue;
            }
            $out['strName'][$i]     = (string)($r['strName'] ?? '');
            $out['Description'][$i] = (string)($r['Description'] ?? '');
            $out['Tel'][$i]         = (string)($r['Tel'] ?? '');
            $out['Fax'][$i]         = (string)($r['Fax'] ?? '');
            $out['PostCode'][$i]   = (string)($r['PostCode'] ?? '');
            $out['County'][$i]     = (string)($r['strCounty'] ?? '');
            $out['City'][$i]       = (string)($r['strCity'] ?? '');
            $out['Address'][$i]    = (string)($r['Address'] ?? '');
            $out['Facebook'][$i]   = (string)($r['Facebook'] ?? '');
            $out['Line'][$i]       = (string)($r['Line'] ?? '');
            $out['IG'][$i]         = (string)($r['IG'] ?? '');
            $out['Youtube'][$i]    = (string)($r['Youtube'] ?? '');
            $out['Twitter'][$i]    = (string)($r['Twitter'] ?? '');
            $out['FromMail']       = (string)($r['FromMail'] ?? $out['FromMail']);
            $out['ToMail']         = (string)($r['ToMail'] ?? $out['ToMail']);
            $out['gaCode']         = (string)($r['gaCode'] ?? $out['gaCode']);
            $out['gtmCode']        = (string)($r['gtmCode'] ?? $out['gtmCode']);

            $kwRaw = (string)($r['Keywords'] ?? '');
            if ($kwRaw !== '') {
                $parts = explode(',', $kwRaw);
                foreach ($parts as $idx => $word) {
                    $out['Keywords'][$i][$idx + 1] = $word;
                }
            }
        }

        return $out;
    }
}

if (!function_exists('crud_save_webset_from_filter')) {
    /** 儲存 webset（多語系 + 全域信箱/追蹤碼） */
    function crud_save_webset_from_filter(array $filter, ?array $langIndexes = null): void {
        if ($langIndexes !== null) {
            $langs = array_values(array_filter(array_map('intval', $langIndexes), static fn(int $n): bool => $n > 0));
        } else {
            $al = (array)($GLOBALS['array_lang'] ?? []);
            $langs = range(1, max(1, count($al)));
        }

        $ctx = crud_module_ctx();
        $table = 'webset';

        foreach ($langs as $n) {
            $keywords = [];
            for ($i = 1; $i <= 5; $i++) {
                $k = 'Keyword' . $n . '_' . $i;
                if (!empty($filter[$k])) {
                    $keywords[] = trim((string)$filter[$k]);
                }
            }

            $row = [
                'intLang'   => SqlFilter($n, 'int'),
                'dtDate'    => date('Y-m-d H:i:s'),
            ];
            if (isset($filter['strName' . $n])) {
                $row['strName'] = SqlFilter($filter['strName' . $n], 'tab');
            }
            if (isset($filter['Description' . $n])) {
                $row['Description'] = SqlFilter($filter['Description' . $n], 'tab');
            }
            $row['Keywords'] = SqlFilter(implode(',', $keywords), 'tab');
            if (isset($filter['PostCode' . $n])) {
                $row['PostCode'] = SqlFilter($filter['PostCode' . $n], 'tab');
            }
            if (isset($filter['strCounty' . $n]) && ($filter['strCity' . $n] ?? '') !== '請選擇') {
                $row['strCounty'] = SqlFilter($filter['strCounty' . $n], 'tab');
            }
            if (isset($filter['strCity' . $n]) && ($filter['strCity' . $n] ?? '') !== '請選擇') {
                $row['strCity'] = SqlFilter($filter['strCity' . $n], 'tab');
            }
            if (isset($filter['Address' . $n])) {
                $row['Address'] = SqlFilter($filter['Address' . $n], 'tab');
            }
            if (isset($filter['Tel' . $n])) {
                $row['Tel'] = SqlFilter($filter['Tel' . $n], 'tab');
            }
            if (isset($filter['Fax' . $n])) {
                $row['Fax'] = SqlFilter($filter['Fax' . $n], 'tab');
            }
            if (isset($filter['FromMail'])) {
                $row['FromMail'] = SqlFilter($filter['FromMail'], 'tab');
            }
            if (isset($filter['ToMail'])) {
                $row['ToMail'] = SqlFilter($filter['ToMail'], 'tab');
            }
            if (isset($filter['Facebook' . $n])) {
                $row['Facebook'] = SqlFilter($filter['Facebook' . $n], 'tab');
            }
            if (isset($filter['Line' . $n])) {
                $row['Line'] = SqlFilter($filter['Line' . $n], 'tab');
            }
            if (isset($filter['Youtube' . $n])) {
                $row['Youtube'] = SqlFilter($filter['Youtube' . $n], 'tab');
            }
            if (isset($filter['IG' . $n])) {
                $row['IG'] = SqlFilter($filter['IG' . $n], 'tab');
            }
            if (isset($filter['Twitter' . $n])) {
                $row['Twitter'] = SqlFilter($filter['Twitter' . $n], 'tab');
            }
            if (isset($filter['gaCode'])) {
                $row['gaCode'] = SqlFilter((string)$filter['gaCode'], 'tab');
            }
            if (isset($filter['gtmCode'])) {
                $row['gtmCode'] = SqlFilter((string)$filter['gtmCode'], 'tab');
            }

            $existing = crud_fetch_one('SELECT PKey FROM webset WHERE intLang = :lang', ['lang' => $n]);
            $pdo = new dbPDO();
            if ($existing !== null) {
                $childPk = (int)$existing['PKey'];
                $pdo->update($table, $row, 'PKey', $childPk);
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '修改成功!';
            } else {
                $pdo->insert($table, $row);
                $childPk = (int)$pdo->getLastId();
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '新增成功!';
            }
            $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
            $pdo->close();
            if ($err !== '') {
                crud_fail_db($sqlLog, $err, $row, true);
            }
            if (function_exists('manage_history')) {
                manage_history($ctx['Module_PKey'], '網站SEO設定', $sqlLog, $_SERVER['PHP_SELF'] ?? $ctx['WorkFile'], $ctx['Login_ID'], $action);
            }
        }
    }
}

if (!function_exists('crud_validate_webset_from_filter')) {
    /** webset 表單驗證（多語系網站名稱、寄件信箱） */
    function crud_validate_webset_from_filter(array $filter, ?array $langIndexes = null): string {
        $msg = '';
        if ($langIndexes !== null) {
            $langs = array_values(array_filter(array_map('intval', $langIndexes), static fn(int $n): bool => $n > 0));
        } else {
            $al = (array)($GLOBALS['array_lang'] ?? []);
            $langs = range(1, max(1, count($al)));
        }
        foreach ($langs as $n) {
            $name = trim((string)($filter['strName' . $n] ?? ''));
            if ($name === '') {
                $label = (string)($GLOBALS['array_lang'][$n] ?? ('語系 ' . $n));
                $msg .= '【' . $label . '】網站名稱為空白' . "\n";
            }
        }
        $fromMail = trim((string)($filter['FromMail'] ?? ''));
        if ($fromMail === '') {
            $msg .= "【寄件信箱】為空白\n";
        } elseif (function_exists('CheckMail') && !CheckMail($fromMail)) {
            $msg .= "【寄件信箱】格式錯誤\n";
        }
        $toMail = trim((string)($filter['ToMail'] ?? ''));
        if ($toMail !== '' && function_exists('CheckMail')) {
            foreach (preg_split('/\s*;\s*/', $toMail) ?: [] as $em) {
                $em = trim((string)$em);
                if ($em !== '' && !CheckMail($em)) {
                    $msg .= "【收件信箱】格式錯誤：" . $em . "\n";
                    break;
                }
            }
        }
        return $msg;
    }
}

if (!function_exists('crud_paginate')) {
    /**
     * @return array{tPage:int,tPageTotal:int,offset:int,pageSize:int,total:int}
     */
    function crud_paginate(int $total, int $pageSize, $page = null): array {
        $pageSize = max(1, $pageSize);
        $tPageTotal = max(1, (int)ceil($total / $pageSize));
        $tPage = 1;
        if ($page !== null && $page !== '' && is_numeric($page)) {
            $tPage = max(1, (int)$page);
        }
        if ($tPage > $tPageTotal) {
            $tPage = $tPageTotal;
        }
        return [
            'total'      => $total,
            'tPage'      => $tPage,
            'tPageTotal' => $tPageTotal,
            'pageSize'   => $pageSize,
            'offset'     => ($tPage - 1) * $pageSize,
        ];
    }
}

if (!function_exists('crud_module_where')) {
    /**
     * 依 Module_PKey 的 WHERE 片段與參數
     *
     * @param string|null $tableAlias 列表 JOIN 時的主表別名（如 t2、t3），僅修飾欄位，不影響 :Module_PKey
     */
    function crud_module_where(?string $tableAlias = null): array {
        $mpk = SqlFilter($GLOBALS['Module_PKey'] ?? 0, 'int');
        $col = 'Module_PKey';
        if ($tableAlias !== null && $tableAlias !== '') {
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $tableAlias);
            if ($alias !== '') {
                $col = $alias . '.Module_PKey';
            }
        }
        return [" WHERE {$col} = :Module_PKey", ['Module_PKey' => $mpk]];
    }
}

if (!function_exists('crud_list_page_size')) {
    /** 列表每頁筆數（filter PageSize 或 $default） */
    function crud_list_page_size(array $filter, int $default = 15): int {
        if (!empty($filter['PageSize']) && is_numeric($filter['PageSize'])) {
            return max(1, (int)$filter['PageSize']);
        }
        return $default;
    }
}

if (!function_exists('manage_list_search_filter_value')) {
    /**
     * 列表搜尋條件：優先 Q_*（編輯頁帶回），其次同名欄位（列表 GET/POST）
     */
    function manage_list_search_filter_value(array $filter, string $key): string
    {
        $qKey = 'Q_' . $key;
        if (isset($filter[$qKey]) && (string)$filter[$qKey] !== '') {
            return (string)$filter[$qKey];
        }

        return (string)($filter[$key] ?? '');
    }
}

if (!function_exists('crud_list_apply_keyword_search')) {
    /**
     * 列表智慧語意搜尋（Gemini 擴詞 + 可選向量相似度；回傳供畫面顯示的字串）
     *
     * @param string|list<string> $column
     * @param array<string, mixed>|null $semanticContext 可傳 table、pk；未傳 base_where 時自動以目前條件為候選範圍
     */
    function crud_list_apply_keyword_search(
        string &$where,
        array &$params,
        array $filter,
        string|array $column = 'strName',
        string $placeholder = '請輸入關鍵字搜尋',
        ?array $semanticContext = null,
    ): string {
        $kw = trim(manage_list_search_filter_value($filter, 'Keywords'));
        $kw = mb_substr($kw, 0, 50);

        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        $hasKeyword = $kw !== '' && $kw !== $placeholder;

        if ((!$submitted && !$hasKeyword) || !$hasKeyword) {
            return $placeholder;
        }

        if (!function_exists('manage_semantic_apply_keyword_filter')) {
            require_once __DIR__ . '/manage_semantic_search_helpers.php';
        }

        $context = is_array($semanticContext) ? $semanticContext : [];
        if (!isset($context['base_where'])) {
            $context['base_where'] = $where;
        }
        if (!isset($context['base_params'])) {
            $context['base_params'] = $params;
        }

        manage_semantic_apply_keyword_filter($where, $params, $kw, $column, $context);

        return $kw;
    }
}

if (!function_exists('crud_list_validate_opendate_range')) {
    /** 列表刊登日期起迄：迄日不可小於起日；僅在兩者皆有有效日期時檢查 */
    function crud_list_validate_opendate_range(string $dateFrom, string $dateTo): ?string
    {
        $dateFrom = trim($dateFrom);
        $dateTo   = trim($dateTo);
        if ($dateFrom === '' || $dateTo === '') {
            return null;
        }
        if (!function_exists('chkDate') || !chkDate($dateFrom) || !chkDate($dateTo)) {
            return null;
        }
        $tsFrom = strtotime($dateFrom);
        $tsTo   = strtotime($dateTo);
        if ($tsFrom === false || $tsTo === false) {
            return null;
        }
        if ($tsTo < $tsFrom) {
            return '刊登日期（迄）不可小於刊登日期（起）';
        }
        return null;
    }
}

if (!function_exists('crud_list_apply_opendate_range')) {
    /**
     * 列表刊登日期區間：比對 OpenDate，起日 00:00:00、迄日 23:59:59
     *
     * @return array{OpenDate:string, EndDate:string, error:string} 供表單顯示
     */
    function crud_list_apply_opendate_range(
        string &$where,
        array &$params,
        array $filter,
        string $column = 'OpenDate',
        ?string $tableAlias = null
    ): array {
        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        $display = ['OpenDate' => '', 'EndDate' => '', 'error' => ''];
        if (!$submitted) {
            return $display;
        }

        $dateFrom = function_exists('manage_list_search_filter_value')
            ? trim(manage_list_search_filter_value($filter, 'OpenDate'))
            : trim((string)($filter['Q_OpenDate'] ?? $filter['OpenDate'] ?? ''));
        $dateTo = function_exists('manage_list_search_filter_value')
            ? trim(manage_list_search_filter_value($filter, 'EndDate'))
            : trim((string)($filter['Q_EndDate'] ?? $filter['EndDate'] ?? ''));

        if (function_exists('chkDate') && chkDate($dateFrom)) {
            $ts = strtotime($dateFrom);
            if ($ts !== false) {
                $display['OpenDate'] = date('Y-m-d', $ts);
            }
        }
        if (function_exists('chkDate') && chkDate($dateTo)) {
            $ts = strtotime($dateTo);
            if ($ts !== false) {
                $display['EndDate'] = date('Y-m-d', $ts);
            }
        }

        $rangeError = crud_list_validate_opendate_range($display['OpenDate'], $display['EndDate']);
        if ($rangeError !== null) {
            $display['error'] = $rangeError;
            return $display;
        }

        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        if ($col === '') {
            $col = 'OpenDate';
        }
        if ($tableAlias !== null && $tableAlias !== '') {
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $tableAlias);
            if ($alias !== '') {
                $col = $alias . '.' . $col;
            }
        }

        if ($display['OpenDate'] !== '') {
            $params['OpenDateFrom'] = $display['OpenDate'] . ' 00:00:00';
            $where .= " AND {$col} >= :OpenDateFrom";
        }
        if ($display['EndDate'] !== '') {
            $params['OpenDateTo'] = $display['EndDate'] . ' 23:59:59';
            $where .= " AND {$col} <= :OpenDateTo";
        }

        return $display;
    }
}

if (!function_exists('crud_list_apply_class_filters')) {
    /**
     * 列表分類篩選：搜尋提交時更新 Class1~4，並寫入 WHERE
     */
    function crud_list_apply_class_filters(
        string &$where,
        array &$params,
        array $filter,
        int $layer,
        int &$class1,
        int &$class2,
        int &$class3,
        int &$class4,
        ?string $tableAlias = null
    ): void {
        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        if ($submitted) {
            if (isset($filter['Class1'])) {
                $class1 = (int)SqlFilter($filter['Class1'], 'int');
            }
            if (isset($filter['Class2'])) {
                $class2 = (int)SqlFilter($filter['Class2'], 'int');
            }
            if (isset($filter['Class3'])) {
                $class3 = (int)SqlFilter($filter['Class3'], 'int');
            }
            if (isset($filter['Class4'])) {
                $class4 = (int)SqlFilter($filter['Class4'], 'int');
            }
        }
        $colPrefix = '';
        if ($tableAlias !== null && $tableAlias !== '') {
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $tableAlias);
            if ($alias !== '') {
                $colPrefix = $alias . '.';
            }
        }
        if ($layer > 1 && $class1 > 0) {
            $params['Class1_PKey'] = $class1;
            $where .= ' AND ' . $colPrefix . 'Class1_PKey = :Class1_PKey';
        }
        if ($layer > 2 && $class2 > 0) {
            $params['Class2_PKey'] = $class2;
            $where .= ' AND ' . $colPrefix . 'Class2_PKey = :Class2_PKey';
        }
        if ($layer > 3 && $class3 > 0) {
            $params['Class3_PKey'] = $class3;
            $where .= ' AND ' . $colPrefix . 'Class3_PKey = :Class3_PKey';
        }
        if ($layer > 4 && $class4 > 0) {
            $params['Class4_PKey'] = $class4;
            $where .= ' AND ' . $colPrefix . 'Class4_PKey = :Class4_PKey';
        }
    }
}

if (!function_exists('crud_module_child_table_ok')) {
    /** 子表存在且含指定外鍵欄位 */
    function crud_module_child_table_ok(string $table, string $fkCol): bool {
        return crud_table_exists($table) && crud_table_has_column($table, $fkCol);
    }
}

if (!function_exists('crud_normalize_module_config')) {
    /**
     * 過濾模組設定：master 必須存在；子表不存在或無 fk 欄位則清空；delete_lock_tables 正規化
     *
     * @param array<string, mixed> $config
     * @return array{master:string,img:string,lang:string,msg:string,link:string,fk:string,csrf:string,delete_lock_tables:array<string,string>}
     */
    function crud_normalize_module_config(array $config): array {
        $master = trim((string)($config['master'] ?? ''));
        $fk     = trim((string)($config['fk'] ?? ''));
        $out = [
            'master' => ($master !== '' && crud_is_safe_sql_identifier($master)) ? $master : '',
            'img'    => '',
            'lang'   => '',
            'msg'    => '',
            'link'   => '',
            'fk'     => crud_is_safe_sql_identifier($fk) ? $fk : '',
            'csrf'   => trim((string)($config['csrf'] ?? '')),
            'delete_lock_tables' => [],
        ];
        if ($out['fk'] === '') {
            return $out;
        }
        foreach (['img', 'lang', 'msg', 'link'] as $key) {
            $table = trim((string)($config[$key] ?? ''));
            $out[$key] = ($table !== '' && crud_module_child_table_ok($table, $out['fk'])) ? $table : '';
        }
        $locks = is_array($config['delete_lock_tables'] ?? null) ? $config['delete_lock_tables'] : [];
        $out['delete_lock_tables'] = crud_normalize_delete_lock_tables($locks);
        return $out;
    }
}

if (!function_exists('manage_detail_set_config')) {
    /**
     * 註冊目前模組的資料表設定（在 add.php / update.php / addin.php 最前面呼叫）
     *
     * @param array{master:string,img?:string,lang?:string,msg?:string,link?:string,fk:string,csrf?:string,module_pk_col?:string,delete_lock_tables?:array<string,string>} $config
     * @param bool $masterOnly true=編輯頁僅註冊主檔設定，不檢查 lang/img 等子表是否存在
     */
    function manage_detail_set_config(array $config, bool $masterOnly = false): void {
        $master = trim((string)($config['master'] ?? ''));
        $fk     = trim((string)($config['fk'] ?? ''));
        if ($master === '' || !crud_is_safe_sql_identifier($master)) {
            throw new RuntimeException('manage_detail_set_config: master 表名無效');
        }
        if ($fk === '' || !crud_is_safe_sql_identifier($fk)) {
            throw new RuntimeException('manage_detail_set_config: fk 欄位名無效');
        }

        if ($masterOnly) {
            $locks = is_array($config['delete_lock_tables'] ?? null) ? $config['delete_lock_tables'] : [];
            $GLOBALS['manage_detail_config'] = [
                'master'             => $master,
                'img'                => trim((string)($config['img'] ?? '')),
                'lang'               => trim((string)($config['lang'] ?? '')),
                'msg'                => trim((string)($config['msg'] ?? '')),
                'link'               => trim((string)($config['link'] ?? '')),
                'fk'                 => $fk,
                'csrf'               => trim((string)($config['csrf'] ?? '')),
                'module_pk_col'      => trim((string)($config['module_pk_col'] ?? 'Module_PKey')),
                'delete_lock_tables' => $locks,
            ];
            foreach (['content_in_lang', 'content_blocks', 'photo_slots', 'list_csrf', 'list_file', 'forder_prefix', 'has_sort'] as $optKey) {
                if (array_key_exists($optKey, $config)) {
                    $GLOBALS['manage_detail_config'][$optKey] = $config[$optKey];
                }
            }
            return;
        }

        $normalized = crud_normalize_module_config($config);
        if (($normalized['master'] ?? '') === '') {
            $normalized['master'] = $master;
        }
        if (($normalized['fk'] ?? '') === '') {
            $normalized['fk'] = $fk;
        }
        $normalized['module_pk_col'] = trim((string)($config['module_pk_col'] ?? 'Module_PKey'));
        $GLOBALS['manage_detail_config'] = $normalized;
    }
}

if (!function_exists('manage_detail_tables')) {
    /** 取得目前模組資料表設定（需先 manage_detail_set_config） */
    function manage_detail_tables(): array {
        $cfg = $GLOBALS['manage_detail_config'] ?? null;
        if (!is_array($cfg) || ($cfg['master'] ?? '') === '' || ($cfg['fk'] ?? '') === '') {
            throw new RuntimeException('manage_detail_set_config() 尚未設定或缺少 master / fk');
        }
        return $cfg;
    }
}

if (!function_exists('manage_return_list_sanitize')) {
    /**
     * 關閉／儲存後導回列表的相對路徑（例：product/list.php）
     */
    function manage_return_list_sanitize(string $list, string $listModule = ''): string
    {
        $list = str_replace('\\', '/', trim($list));
        if (
            $list !== ''
            && strpos($list, '..') === false
            && !preg_match('#^(?:[a-z]+:)?//#i', $list)
            && preg_match('/^(?:[A-Za-z0-9_-]+\/)+[A-Za-z0-9_-]+\.php$/', $list)
        ) {
            return $list;
        }
        $file = basename($list);
        if (!preg_match('/^[A-Za-z0-9_-]+\.php$/', $file)) {
            $file = 'list.php';
        }
        $mod = basename(str_replace('\\', '/', $listModule));
        if ($mod !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $mod)) {
            return $mod . '/' . $file;
        }
        return $file;
    }
}

if (!function_exists('manage_return_list_module_from_path')) {
    /** 從 URL 路徑取出 manage 底下一層模組目錄（例 product） */
    function manage_return_list_module_from_path(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '') {
            return '';
        }
        if (preg_match('#/manage/([A-Za-z0-9_-]+)/#', $path, $m)) {
            return $m[1];
        }
        $parts = array_values(array_filter(explode('/', trim($path, '/')), static fn($p) => $p !== ''));
        $n = count($parts);
        if ($n >= 2) {
            $last = $parts[$n - 1];
            if (str_contains($last, '.') && preg_match('/^[A-Za-z0-9_-]+$/', $parts[$n - 2])) {
                return $parts[$n - 2];
            }
        }
        return '';
    }
}

if (!function_exists('manage_return_list_module_dir')) {
    /** 目前頁所屬模組目錄名（例 product），供 list_module hidden 使用 */
    function manage_return_list_module_dir(): string
    {
        foreach (
            [
                (string)($_SERVER['SCRIPT_NAME'] ?? ''),
                (string)($GLOBALS['PHP_SELF'] ?? ''),
                (string)($GLOBALS['REQUEST_URI_PATH'] ?? ''),
                (string)($GLOBALS['WorkFile'] ?? ''),
            ] as $path
        ) {
            $mod = manage_return_list_module_from_path($path);
            if ($mod !== '' && $mod !== 'manage') {
                return $mod;
            }
        }
        return '';
    }
}

if (!function_exists('manage_return_list_path')) {
    /**
     * 表單 hidden list：僅檔名（list.php），與 add/update 同目錄相對導向，避免 product/list.php 變成 product/product/list.php
     */
    function manage_return_list_path(string $listFile = 'list.php'): string
    {
        $file = basename(str_replace('\\', '/', $listFile));
        if (!preg_match('/^[A-Za-z0-9_-]+\.php$/', $file)) {
            $file = 'list.php';
        }
        return $file;
    }
}

if (!function_exists('manage_return_list_redirect_target')) {
    /**
     * 導回列表的相對路徑：已在模組目錄執行（addin.php）僅 list.php；在 manage/ 則為 product/list.php
     */
    function manage_return_list_redirect_target(string $listFile = 'list.php', string $moduleDir = ''): string
    {
        $file = manage_return_list_path($listFile);
        $mod = basename(str_replace('\\', '/', trim($moduleDir)));
        if ($mod === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $mod) || $mod === 'manage') {
            $mod = manage_return_list_module_dir();
        }
        if ($mod === '' || $mod === 'manage') {
            return $file;
        }
        $scriptMod = manage_return_list_module_from_path((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptMod !== '' && $scriptMod === $mod) {
            return $file;
        }
        return $mod . '/' . $file;
    }
}

if (!function_exists('manage_return_list_resolve_path')) {
    /**
     * _return_list.php：解析表單 list + list_module，並依執行位置決定相對路徑
     */
    function manage_return_list_resolve_path(string $listRaw, string $listModule = ''): string
    {
        $listRaw = str_replace('\\', '/', trim($listRaw));
        if (preg_match('#^([A-Za-z0-9_-]+)/\1/list\.php$#', $listRaw)) {
            $listRaw = 'list.php';
        }
        $mod = basename(str_replace('\\', '/', trim($listModule)));
        if ($mod === '' && str_contains($listRaw, '/')) {
            $mod = basename(dirname($listRaw));
            $listRaw = basename($listRaw);
        } else {
            $listRaw = basename($listRaw);
        }
        if ($mod === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $mod)) {
            $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
            $refPath = is_string($ref) ? (string)(parse_url($ref, PHP_URL_PATH) ?? '') : '';
            $mod = manage_return_list_module_from_path($refPath);
        }
        return manage_return_list_redirect_target($listRaw, $mod);
    }
}

if (!function_exists('manage_breadcrumbs_list_href')) {
    /** 列表頁麵包屑連結（含 manNo / subNo） */
    function manage_breadcrumbs_list_href(string $listFile = 'list.php'): string {
        $href = $listFile;
        $manNo = (int)($GLOBALS['manNo'] ?? 0);
        $subNo = (int)($GLOBALS['subNo'] ?? 0);
        if ($manNo > 0) {
            $href .= '?manNo=' . $manNo;
            if ($subNo > 0) {
                $href .= '&subNo=' . $subNo;
            }
        }
        return $href;
    }
}

if (!function_exists('manage_breadcrumb_unit_label')) {
    /**
     * 目前子單元顯示名（例：類別管理、文章管理）
     * 優先 module_d 分類名稱 + mgw1，否則由 subname 推算
     */
    function manage_breadcrumb_unit_label(): string {
        $mgw1  = (string)($GLOBALS['mgw1'] ?? '管理');
        $subNo = (int)($GLOBALS['subNo'] ?? 0);

        if ($subNo > 0) {
            $row = crud_fetch_one(
                'SELECT strName FROM module_d WHERE PKey = :pk LIMIT 1',
                ['pk' => $subNo]
            );
            if ($row !== null) {
                $unitName = trim((string)($row['strName'] ?? ''));
                if ($unitName !== '') {
                    return $unitName . $mgw1;
                }
            }
            $subname = trim((string)($GLOBALS['subname'] ?? ''));
            if ($subname !== '') {
                return $subname;
            }
        }

        $layer = (int)($GLOBALS['Layer'] ?? 0);
        $classNames = $GLOBALS['Class_Name'] ?? [];
        if ($layer > 0 && is_array($classNames) && !empty($classNames[$layer])) {
            return (string)$classNames[$layer] . $mgw1;
        }

        $moduleName = (string)($GLOBALS['Module_Name'] ?? '');
        $subname    = trim((string)($GLOBALS['subname'] ?? ''));
        if ($subname !== '' && $moduleName !== '' && str_starts_with($subname, $moduleName)) {
            $tail = trim(substr($subname, strlen($moduleName)), " ：\t\n\r\0\x0B");
            if ($tail !== '') {
                return $tail;
            }
        }
        if ($subname !== '') {
            return $subname;
        }
        if ($moduleName !== '') {
            return $moduleName . $mgw1;
        }
        return $mgw1;
    }
}

if (!function_exists('manage_breadcrumbs_default')) {
    /**
     * 列表頁預設麵包屑：單元管理 → 主單元 → 功能單元（例：類別管理）
     * @return list<array{label:string, href?:string}>
     */
    function manage_breadcrumbs_default(): array {
        $moduleName = (string)($GLOBALS['Module_Name'] ?? '');
        return [
            ['label' => '單元管理'],
            ['label' => $moduleName],
            ['label' => manage_breadcrumb_unit_label()],
        ];
    }
}

if (!function_exists('manage_breadcrumb_form_action_label')) {
    /** add.php / update.php 麵包屑最後一層固定文字 */
    function manage_breadcrumb_form_action_label(): string {
        return '內容編輯';
    }
}

if (!function_exists('manage_breadcrumbs_for_form')) {
    /**
     * 表單頁麵包屑：單元管理 → 主單元 → 功能單元 → 內容編輯
     * @return list<array{label:string, href?:string}>
     */
    function manage_breadcrumbs_for_form(?string $actionLabel = null): array {
        $moduleName = (string)($GLOBALS['Module_Name'] ?? '');
        $actionLabel = manage_breadcrumb_form_action_label();
        return [
            ['label' => '單元管理'],
            ['label' => $moduleName],
            ['label' => manage_breadcrumb_unit_label(), 'href' => manage_breadcrumbs_list_href()],
            ['label' => $actionLabel],
        ];
    }
}

if (!function_exists('manage_breadcrumbs_page_title')) {
    /**
     * 依麵包屑產生頁面標題
     * 列表 + subNo：僅功能單元（例：類別管理）
     * 列表無 subNo：模組名 + 功能單元
     * 表單（4 層）：最後一層（內容編輯）
     */
    function manage_breadcrumbs_page_title(array $breadcrumbs): string {
        $formAction = manage_breadcrumb_form_action_label();
        $labels = [];
        foreach ($breadcrumbs as $crumb) {
            if (!is_array($crumb)) {
                continue;
            }
            $label = trim((string)($crumb['label'] ?? ''));
            if ($label === '' || $label === '單元管理') {
                continue;
            }
            $labels[] = $label;
        }

        $n = count($labels);
        if ($n === 0) {
            return '';
        }
        if ($n >= 1 && $labels[$n - 1] === $formAction) {
            return $formAction;
        }
        if ($n === 2) {
            if ((int)($GLOBALS['subNo'] ?? 0) > 0) {
                return $labels[1];
            }
            if (str_starts_with($labels[1], $labels[0])) {
                return $labels[1];
            }
            return $labels[0] . $labels[1];
        }
        return $labels[$n - 1];
    }
}

if (!function_exists('manage_list_expand_enabled')) {
    /**
     * 列表是否顯示「開合」欄（預設 false）
     * 各 list.php 可設：manage_list_expand_enabled($list_show_expand_row ?? false);
     */
    function manage_list_expand_enabled(?bool $enabled = null): bool {
        if ($enabled !== null) {
            $GLOBALS['manage_list_expand_row'] = $enabled;
        }
        return (bool)($GLOBALS['manage_list_expand_row'] ?? false);
    }
}

if (!function_exists('manage_content_img_layout_defaults')) {
    /** 內容區塊 2–7 呈現方式預設（intType：1上圖下文 2左圖右文 3右圖左文 4下圖上文） */
    function manage_content_img_layout_defaults(): array {
        return [2 => 1, 3 => 2, 4 => 3, 5 => 2, 6 => 3, 7 => 4];
    }
}

if (!function_exists('manage_content_img_layout_options')) {
    /** @return array<int, string> */
    function manage_content_img_layout_options(): array {
        return [
            1 => '上圖下文',
            2 => '左圖右文',
            3 => '右圖左文',
            4 => '下圖上文',
        ];
    }
}

if (!function_exists('manage_content_img_layout_is_empty')) {
    /** DB 欄位視為「未設定」：NULL、空字串、0 */
    function manage_content_img_layout_is_empty(mixed $raw): bool {
        if ($raw === null) {
            return true;
        }
        if (is_string($raw) && trim($raw) === '') {
            return true;
        }
        return is_numeric($raw) && (int)$raw === 0;
    }
}

if (!function_exists('manage_content_img_layout_normalize')) {
    /** 將 DB 值（1–4 或舊版 img_top 等）轉成 1–4；空值回傳 0 */
    function manage_content_img_layout_normalize(mixed $raw): int {
        if (manage_content_img_layout_is_empty($raw)) {
            return 0;
        }
        if (is_numeric($raw)) {
            $n = (int)$raw;
            return ($n >= 1 && $n <= 4) ? $n : 0;
        }
        $map = [
            'img_top'    => 1,
            'img_left'   => 2,
            'img_right'  => 3,
            'img_bottom' => 4,
        ];
        $key = strtolower(trim((string)$raw));
        return $map[$key] ?? 0;
    }
}

if (!function_exists('manage_content_img_layout_merge_defaults')) {
    /**
     * 合併 DB 讀到的 intType：有值用 DB，空值用預設 [1,2,3,2,3,4]（Sort 2–7）
     *
     * @param array<int, int> $fromDb
     * @return array<int, int>
     */
    function manage_content_img_layout_merge_defaults(array $fromDb): array {
        $out = manage_content_img_layout_defaults();
        foreach ($fromDb as $slot => $val) {
            $slot = (int)$slot;
            if ($slot < 2 || $slot > 7) {
                continue;
            }
            if (!manage_content_img_layout_is_empty($val)) {
                $out[$slot] = manage_content_img_layout_normalize($val);
            }
        }
        return $out;
    }
}

if (!function_exists('manage_content_img_layout_slot_value')) {
    /** 單一 Sort 槽位的呈現方式（DB 空值 → 預設表） */
    function manage_content_img_layout_slot_value(int $slot, mixed $fromDb = null): int {
        if (!manage_content_img_layout_is_empty($fromDb)) {
            $n = manage_content_img_layout_normalize($fromDb);
            if ($n >= 1 && $n <= 4) {
                return $n;
            }
        }
        return (int)(manage_content_img_layout_defaults()[$slot] ?? 1);
    }
}

if (!function_exists('manage_render_content_layout_select')) {
    /** 輸出內容區塊呈現方式下拉（name=intType{n}） */
    function manage_render_content_layout_select(int $slot): void {
        $cur = manage_content_img_layout_slot_value(
            $slot,
            $GLOBALS['ImgType'][$slot] ?? null
        );
        echo '<select name="intType' . $slot . '" id="intType' . $slot . '" class="formSelect w--auto">';
        foreach (manage_content_img_layout_options() as $val => $label) {
            $v = (int)$val;
            echo '<option value="' . $v . '"';
            if ($cur === $v) {
                echo ' selected';
            }
            echo '>' . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        echo '</select>';
    }
}

if (!function_exists('manage_detail_img_slot_config')) {
    /**
     * 自模組 _config.php 解析 img_slot_max / img_file_from
     *
     * @return array{max:int,file_from:int,image_end:int}
     */
    function manage_detail_img_slot_config(?array $detailConfig = null, int $fallbackMax = 7, bool $imageOnly = false): array
    {
        $cfg = is_array($detailConfig) ? $detailConfig : [];
        if ($cfg === [] && isset($GLOBALS['manage_detail_config']) && is_array($GLOBALS['manage_detail_config'])) {
            $cfg = $GLOBALS['manage_detail_config'];
        }

        $max = max(1, (int)($cfg['img_slot_max'] ?? $fallbackMax));
        $fileFrom = (int)($cfg['img_file_from'] ?? ($max + 1));
        if ($fileFrom < 1) {
            $fileFrom = 1;
        }
        if ($fileFrom > ($max + 1)) {
            $fileFrom = $max + 1;
        }
        $imageEnd = $imageOnly ? $max : min($max, $fileFrom - 1);

        return [
            'max'       => $max,
            'file_from' => $fileFrom,
            'image_end' => $imageEnd,
        ];
    }
}

if (!function_exists('manage_detail_init_img_slot_view')) {
    /**
     * 表單頁欄位變數（對應 product/_detail.php）
     *
     * @return array<string,mixed>
     */
    function manage_detail_init_img_slot_view(
        ?array $detailConfig = null,
        int $fallbackMax = 7,
        bool $imageOnly = false,
        ?bool $showListField = null
    ): array {
        if ($showListField === null) {
            $showListField = manage_module_show_detail_field('list');
        }
        $cfg = manage_detail_img_slot_config($detailConfig, $fallbackMax, $imageOnly);

        return [
            'managePhotoSlotMax'        => $cfg['max'],
            'manageFileSlotFrom'        => $cfg['file_from'],
            'manageImageSlotEnd'        => $cfg['image_end'],
            'managePhotoContentSlotEnd' => $cfg['image_end'],
            'managePhotoSlotStart'      => $showListField ? 1 : 2,
            'manageHasFileSlots'        => !$imageOnly && $cfg['file_from'] <= $cfg['max'],
        ];
    }
}

if (!function_exists('manage_echo_detail_img_slot_delete_scripts')) {
    /**
     * 輸出詳情頁圖片/檔案槽位刪除的 jQuery init script
     *
     * @param array<int, string> $photoS 各槽 PhotoS 預覽路徑
     */
    function manage_echo_detail_img_slot_delete_scripts(
        array $photoS,
        int $imageStart,
        int $imageEnd,
        int $fileFrom,
        int $slotMax,
        bool $includeFileSlots = true
    ): void {
        if ($imageEnd >= $imageStart) {
            manage_echo_photo_delete_init_script(
                manage_photo_delete_slots_for_range($photoS, $imageStart, $imageEnd)
            );
        }
        if ($includeFileSlots && $fileFrom <= $slotMax) {
            manage_echo_photo_delete_init_script(
                manage_photo_delete_slots_for_range($photoS, $fileFrom, $slotMax),
                'file'
            );
        }
    }
}

if (!function_exists('crud_addin_process_img_file_uploads')) {
    /**
     * addin 圖片／檔案欄上傳（對應 product/addin.php）
     *
     * @return array{
     *   photoSlots:array,
     *   maxSlots:int,
     *   Photo:array<int,string>,
     *   PhotoW:array<int,int>,
     *   PhotoH:array<int,int>,
     *   PhotoM:array<int,string>,
     *   messages:string,
     *   forderVal:string,
     *   upload_folder:string
     * }
     */
    function crud_addin_process_img_file_uploads(
        array $detailConfig,
        array $filter_array,
        array $file_array,
        int $fallbackMax = 7,
        bool $imageOnly = false
    ): array {
        $cfg = manage_detail_img_slot_config($detailConfig, $fallbackMax, $imageOnly);
        $maxSlots = $cfg['max'];
        $fileFrom = $cfg['file_from'];
        $photoSlots = crud_resolve_photo_upload_slots($filter_array, $file_array, $maxSlots);

        $uploadDirInfo = crud_upload_dir();
        $uploadFolder  = $uploadDirInfo['dir'];
        $messages      = $uploadDirInfo['error'];

        $forderName  = (string)($detailConfig['forder_prefix'] ?? '');
        $sizeBytes   = (int)($GLOBALS['size_bytes'] ?? 2000 * 1024);
        $fileSize    = (int)($GLOBALS['file_size'] ?? 6000 * 1024);

        $photoM = [];
        for ($i = 1; $i <= $maxSlots; $i++) {
            if (isset($filter_array['PhotoM' . $i])) {
                $photoM[$i] = (string)$filter_array['PhotoM' . $i];
            }
        }

        $photo  = [];
        $photoW = [];
        $photoH = [];

        $imageEnd = $cfg['image_end'];
        $imageIndices = $imageEnd >= 1 ? range(1, $imageEnd) : [];
        $fileIndices  = (!$imageOnly && $fileFrom <= $maxSlots) ? range($fileFrom, $maxSlots) : [];

        $uploadImg = crud_upload_file_slots($file_array, $uploadFolder, $imageIndices, [
            'forder_prefix' => $forderName,
            'size_bytes'    => $sizeBytes,
            'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'webp'],
            'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
            'field_prefix'  => 'Photo',
            'resize_thumb'  => true,
        ]);

        $uploadFile = ['photos' => [], 'photoW' => [], 'photoH' => [], 'messages' => '', 'monthdir' => ''];
        if ($fileIndices !== []) {
            $uploadFile = crud_upload_file_slots($file_array, $uploadFolder, $fileIndices, [
                'forder_prefix' => $forderName,
                'size_bytes'    => $fileSize,
                'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt'],
                'allowed_mimes' => [],
                'field_prefix'  => 'Photo',
                'resize_thumb'  => false,
            ]);
        }

        foreach ([$uploadImg, $uploadFile] as $uploadResult) {
            foreach ((array)($uploadResult['photos'] ?? []) as $idx => $filename) {
                $photo[(int)$idx] = (string)$filename;
            }
            foreach ((array)($uploadResult['photoW'] ?? []) as $idx => $w) {
                $photoW[(int)$idx] = (int)$w;
            }
            foreach ((array)($uploadResult['photoH'] ?? []) as $idx => $h) {
                $photoH[(int)$idx] = (int)$h;
            }
            $messages .= (string)($uploadResult['messages'] ?? '');
        }

        $forderVal = crud_resolve_upload_monthdir($uploadImg, $uploadFile);

        return [
            'photoSlots'     => $photoSlots,
            'maxSlots'       => $maxSlots,
            'Photo'          => $photo,
            'PhotoW'         => $photoW,
            'PhotoH'         => $photoH,
            'PhotoM'         => $photoM,
            'messages'       => $messages,
            'forderVal'      => $forderVal,
            'upload_folder'  => $uploadFolder,
        ];
    }
}

if (!function_exists('manage_photo_slot_show_delete')) {
    /** 編輯頁且已有圖檔路徑時顯示刪除按鈕 */
    function manage_photo_slot_show_delete(bool $isAdd, string $photoPath): bool
    {
        return !$isAdd && trim($photoPath) !== '';
    }
}

if (!function_exists('manage_render_photo_delete_button')) {
    /** 圖檔預覽區刪除按鈕（id=delete{n}，搭配 filesize.js del_file） */
    function manage_render_photo_delete_button(int $slot): void
    {
        $slot = max(1, $slot);
        echo '<button type="button" id="delete' . $slot . '" class="uploadBox__delBtn">刪除圖片</button>';
    }
}

if (!function_exists('manage_file_ext_from_path')) {
    /** 從路徑取小寫副檔名（basename + pathinfo，空路徑回 ''） */
    function manage_file_ext_from_path(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        return strtolower(pathinfo(basename(str_replace('\\', '/', $path)), PATHINFO_EXTENSION));
    }
}

if (!function_exists('manage_file_icon_fa_class')) {
    /** 依副檔名回傳 Font Awesome 類別（不含 fas 前綴，例：fa-file-pdf） */
    function manage_file_icon_fa_class(string $ext): string
    {
        switch (strtolower(trim($ext))) {
            case 'pdf':
                return 'fa-file-pdf';
            case 'doc':
            case 'docx':
                return 'fa-file-word';
            case 'xls':
            case 'xlsx':
                return 'fa-file-excel';
            case 'ppt':
            case 'pptx':
                return 'fa-file-powerpoint';
            case 'zip':
            case 'rar':
            case '7z':
                return 'fa-file-archive';
            case 'txt':
                return 'fa-file-alt';
            default:
                return 'fa-file-alt';
        }
    }
}

if (!function_exists('manage_file_icon_bi_class')) {
    /** @deprecated 請改用 manage_file_icon_fa_class */
    function manage_file_icon_bi_class(string $ext): string
    {
        return manage_file_icon_fa_class($ext);
    }
}

if (!function_exists('manage_render_upload_file_prefile')) {
    /**
     * 非圖片檔案預覽（#prefile{n}，class="fas fa-file-*"；搭配 file_preview.js）
     */
    function manage_render_upload_file_prefile(int $slot, string $relativePath, string $ext = ''): void
    {
        $slot = max(1, $slot);
        $relativePath = trim($relativePath);
        if ($relativePath === '') {
            echo '<span id="prefile' . $slot . '" style="display:none;"></span>';
            return;
        }
        $ext = $ext !== '' ? strtolower($ext) : manage_file_ext_from_path($relativePath);
        $icon = manage_file_icon_fa_class($ext);
        $name = basename(str_replace('\\', '/', $relativePath));
        echo '<span id="prefile' . $slot . '" class="fas ' . e($icon) . '">' . e($name) . '</span>';
    }
}

if (!function_exists('manage_render_upload_image_slot')) {
    /** 圖片欄位（Sort 對應 Photo{n}） */
    function manage_render_upload_image_slot(
        int $slot,
        bool $isAdd,
        string $photoPath,
        int $photoRowPkey = 0,
        string $checkType = 'img',
        int $maxKb = 2000
    ): void {
        $slot = max(1, $slot);
        $photoPath = trim($photoPath);
        ?>
        <div class="uploadBox w--auto">
            <p class="inputLabel">圖片上傳</p>
            <div class="uploadBox__picBx">
                <img id="preview<?php echo $slot; ?>" alt=""
                    style="max-width:150px;max-height:150px;<?php echo $photoPath === '' ? 'display:none;' : ''; ?>"
                    <?php if ($photoPath !== '') { ?>
                    src="../../Upload/<?php echo e($photoPath); ?>?<?php echo time(); ?>"
                    <?php } ?>>
                <div id="size<?php echo $slot; ?>"></div>
                <?php if (manage_photo_slot_show_delete($isAdd, $photoPath)) {
                    manage_render_photo_delete_button($slot);
                } ?>
            </div>
            <div class="uploadBox__fileBx">
                <label for="Photo<?php echo $slot; ?>">
                    選擇檔案
                    <input name="Photo<?php echo $slot; ?>" type="file"
                        accept="image/jpeg,image/gif,image/png,image/webp"
                        id="Photo<?php echo $slot; ?>" size="30"
                        data-check-file="Photo<?php echo $slot; ?>,<?php echo (int)$maxKb; ?>,<?php echo e($checkType); ?>">
                    <input name="intType<?php echo $slot; ?>" type="hidden" id="intType<?php echo $slot; ?>" value="1">
                </label>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('manage_render_upload_document_slot')) {
    /** 檔案欄位（product_img.intType=2）；選檔後依副檔名顯示圖示 */
    function manage_render_upload_document_slot(
        int $slot,
        bool $isAdd,
        string $filePath,
        int $photoRowPkey = 0,
        string $ext = '',
        int $maxKb = 6000
    ): void {
        $slot = max(1, $slot);
        $filePath = trim($filePath);
        $ext = $ext !== '' ? strtolower($ext) : manage_file_ext_from_path($filePath);
        $isImageExt = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
        ?>
        <div class="uploadBox w--auto">
            <p class="inputLabel">檔案上傳</p>
            <div class="uploadBox__picBx uploadBox__picBx--file">
                <?php if ($filePath !== '' && $isImageExt) { ?>
                <img id="preview<?php echo $slot; ?>" alt=""
                    style="max-width:150px;max-height:150px;"
                    src="../../Upload/<?php echo e($filePath); ?>?<?php echo time(); ?>">
                <?php manage_render_upload_file_prefile($slot, '', ''); ?>
                <?php } elseif ($filePath !== '') { ?>
                <img id="preview<?php echo $slot; ?>" alt="" style="max-width:150px;max-height:150px;display:none;">
                <?php manage_render_upload_file_prefile($slot, $filePath, $ext); ?>
                <?php } else { ?>
                <img id="preview<?php echo $slot; ?>" alt="" style="max-width:150px;max-height:150px;display:none;">
                <?php manage_render_upload_file_prefile($slot, '', ''); ?>
                <?php } ?>
                <div id="size<?php echo $slot; ?>"></div>
                <?php
                if (manage_photo_slot_show_delete($isAdd, $filePath)) {
                    manage_render_photo_delete_button($slot);
                }
                ?>
            </div>
            <div class="uploadBox__fileBx">
                <label for="Photo<?php echo $slot; ?>">
                    選擇檔案
                    <input name="Photo<?php echo $slot; ?>" type="file" id="Photo<?php echo $slot; ?>" size="30"
                        data-check-file="Photo<?php echo $slot; ?>,<?php echo (int)$maxKb; ?>,file">
                    <input name="intType<?php echo $slot; ?>" type="hidden" id="intType<?php echo $slot; ?>" value="2">
                </label>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('manage_render_strdate_field')) {
    /** 基本設定：發佈日期 strDate（jQuery UI datepicker） */
    function manage_render_strdate_field(?string $value = null): void {
        $display = function_exists('crud_strdate_for_form')
            ? crud_strdate_for_form($value ?? '')
            : date('Y/m/d');
        ?>
        <div class="formGrid">
            <label class="col--2 inputLabel editView__formLabel" for="strDate">發佈日期 <span class="inputLabel__required">*</span></label>
            <div class="col--10 inputGroup">
                <input type="text" name="strDate" id="strDate" class="formInput editView__dateInput"
                    value="<?php echo e($display); ?>" autocomplete="off" maxlength="10">
                <span id="strDate_txt" class="input__errorTxt"></span>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('manage_strdate_picker_bounds')) {
    /**
     * 發佈日期萬年曆選取範圍：預設今天 ±365 天；若舊值超出範圍則一併納入
     *
     * @return array{min: string, max: string, minYear: int, maxYear: int}
     */
    function manage_strdate_picker_bounds(?string $value = null, int $dayWindow = 365): array {
        $today = new DateTime('today');
        $min   = (clone $today)->modify('-' . $dayWindow . ' days');
        $max   = (clone $today)->modify('+' . $dayWindow . ' days');

        $raw = trim((string)$value);
        if ($raw !== '' && function_exists('chkDate') && chkDate($raw)) {
            $existing = new DateTime($raw);
            if ($existing < $min) {
                $min = $existing;
            }
            if ($existing > $max) {
                $max = $existing;
            }
        }

        return [
            'min'     => $min->format('Y/m/d'),
            'max'     => $max->format('Y/m/d'),
            'minYear' => (int)$min->format('Y'),
            'maxYear' => (int)$max->format('Y'),
        ];
    }
}

if (!function_exists('manage_echo_strdate_picker_init')) {
    /** 初始化 strDate 萬年曆（須已載入 jQuery UI datepicker，繁體中文介面） */
    function manage_echo_strdate_picker_init(string $inputId = 'strDate', ?string $value = null): void {
        $id     = e($inputId);
        $bounds = manage_strdate_picker_bounds($value);
        ?>
if ($.fn && typeof $.fn.datepicker === 'function') {
	if (!$.datepicker.regional['zh-TW']) {
		$.datepicker.regional['zh-TW'] = {
			closeText: '關閉',
			prevText: '上個月',
			nextText: '下個月',
			currentText: '今天',
			monthNames: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
			monthNamesShort: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
			dayNames: ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'],
			dayNamesShort: ['週日', '週一', '週二', '週三', '週四', '週五', '週六'],
			dayNamesMin: ['日', '一', '二', '三', '四', '五', '六'],
			weekHeader: '週',
			dateFormat: 'yy/mm/dd',
			firstDay: 0,
			isRTL: false,
			showMonthAfterYear: true,
			yearSuffix: '年'
		};
	}
	$('#<?php echo $id; ?>').datepicker($.extend({}, $.datepicker.regional['zh-TW'], {
		changeMonth: true,
		changeYear: true,
		minDate: '<?php echo e($bounds['min']); ?>',
		maxDate: '<?php echo e($bounds['max']); ?>',
		yearRange: '<?php echo (int)$bounds['minYear']; ?>:<?php echo (int)$bounds['maxYear']; ?>'
	}));
}
        <?php
    }
}

if (!function_exists('manage_render_field_help')) {
    /**
     * 表單欄位說明問號（點選顯示 ttpShow 燈箱，同 news 列表「預覽」說明）
     */
    function manage_render_field_help(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $body = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = e($body);

        return ' <div class="ttpShowZone ttpShowZone--field">'
            . '<button type="button" class="ttpShowZone__trigger" data-manage-action="preview-help-toggle"'
            . ' aria-label="欄位說明" aria-expanded="false">'
            . '<i class="bi bi-question-circle-fill" aria-hidden="true"></i>'
            . '</button>'
            . '<div class="ttpShow ttpShow--field" role="dialog" aria-hidden="true" aria-label="欄位說明">'
            . '<div class="ttpShow__body">' . $body . '</div>'
            . '<button type="button" class="ttpShow__closeBtn" data-manage-action="preview-help-close" aria-label="關閉">X</button>'
            . '</div></div>';
    }
}

if (!function_exists('manage_photo_delete_slots_for_range')) {
    /**
     * 組出可綁定刪除事件的欄位（PhotoS 子表 PKey）
     *
     * @return array<int,int>
     */
    function manage_photo_delete_slots_for_range(array $photoS, int $fromSlot, int $toSlot): array
    {
        $out = [];
        for ($i = max(1, $fromSlot); $i <= max($fromSlot, $toSlot); $i++) {
            $pk = (int)($photoS[$i] ?? 0);
            if ($pk > 0) {
                $out[$i] = $pk;
            }
        }
        return $out;
    }
}

if (!function_exists('manage_echo_photo_delete_init_script')) {
    /**
     * 輸出 $(function(){ ... }) 內的刪除圖檔 click 綁定（需已載入 filesize.js）
     *
     * @param array<int,int> $slots 欄位 => 子表 PKey
     */
    function manage_echo_photo_delete_init_script(array $slots, string $fileType = 'img'): void
    {
        if ($slots === []) {
            return;
        }
        $typeJson = json_encode($fileType, JSON_UNESCAPED_UNICODE);
        foreach ($slots as $num => $pkey) {
            $n  = (int)$num;
            $pk = (int)$pkey;
            if ($n < 1 || $pk <= 0) {
                continue;
            }
            echo "\t$('#delete{$n}').on('click', function (e) {\n";
            echo "\t\te.preventDefault();\n";
            echo "\t\tif (!confirm('確定刪除嗎？')) {\n";
            echo "\t\t\treturn;\n";
            echo "\t\t}\n";
            echo "\t\tif (typeof del_file === 'function') {\n";
            echo "\t\t\tdel_file({$pk}, {$n}, {$typeJson});\n";
            echo "\t\t}\n";
            echo "\t});\n";
        }
    }
}

if (!function_exists('manage_list_grid_class')) {
    /** 依開合欄是否啟用，回傳對應 tableGrid CSS class */
    function manage_list_grid_class(string $profile): string {
        $profile = preg_replace('/[^a-z0-9_-]/i', '', $profile);
        if ($profile === '') {
            $profile = 'class1';
        }
        return manage_list_expand_enabled()
            ? ('tableGrid--' . $profile)
            : ('tableGrid--' . $profile . '-compact');
    }
}

if (!function_exists('crud_lang_row_is_show_on')) {
    /** 語系子表 isShow 是否視為啟用（Y/1/Yes 等） */
    function crud_lang_row_is_show_on($value): bool {
        if (function_exists('class1_lang_is_show_on')) {
            return class1_lang_is_show_on($value);
        }
        $v = strtolower(trim((string)$value));
        return in_array($v, ['y', 'yes', '1', 'true', 'on'], true);
    }
}

if (!function_exists('crud_list_preload_visible_langs')) {
    /**
     * 批次載入列表列的「已顯示語系」intLang（依 Sort 排序）
     *
     * @return array<int, list<int>>
     */
    function crud_list_preload_visible_langs(
        string $tableLang,
        string $fkCol,
        array $listRows,
        string $pkCol = 'PKey'
    ): array {
        if ($tableLang === '' || $fkCol === ''
            || !crud_is_safe_sql_identifier($tableLang)
            || !crud_is_safe_sql_identifier($fkCol)
            || !crud_is_safe_sql_identifier($pkCol)
            || !function_exists('chkTable') || !chkTable($tableLang)
        ) {
            return [];
        }

        $pkeys = [];
        foreach ($listRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row[$pkCol] ?? $row['PKey'] ?? 0);
            if ($id > 0) {
                $pkeys[$id] = $id;
            }
        }
        if ($pkeys === []) {
            return [];
        }

        $ph = [];
        $params = [];
        foreach (array_values($pkeys) as $idx => $id) {
            $k = 'pk' . $idx;
            $ph[] = ':' . $k;
            $params[$k] = $id;
        }

        $sql = 'SELECT `' . $fkCol . '`, `intLang`, `isShow`, `Sort` FROM `' . $tableLang
            . '` WHERE `' . $fkCol . '` IN (' . implode(',', $ph) . ') ORDER BY `' . $fkCol . '`, `Sort`';
        $rows = crud_fetch_all($sql, $params);

        $map = [];
        foreach ($rows as $r) {
            if (!crud_lang_row_is_show_on($r['isShow'] ?? '')) {
                continue;
            }
            $parentId = (int)($r[$fkCol] ?? 0);
            $langInt  = (int)($r['intLang'] ?? 0);
            if ($parentId <= 0 || $langInt <= 0) {
                continue;
            }
            if (!isset($map[$parentId])) {
                $map[$parentId] = [];
            }
            if (!in_array($langInt, $map[$parentId], true)) {
                $map[$parentId][] = $langInt;
            }
        }

        return $map;
    }
}

if (!function_exists('crud_list_lang_column_init')) {
    /**
     * 列表語系欄初始化（需模組 _config 的 lang / fk）
     *
     * @return array{show:bool, map:array<int, list<int>>}
     */
    function crud_list_lang_column_init(
        array $detailConfig,
        array $listRows,
        string $pkCol = 'PKey'
    ): array {
        $tableLang = trim((string)($detailConfig['lang'] ?? ''));
        $fkCol     = trim((string)($detailConfig['fk'] ?? ''));
        if ($tableLang === '' || $fkCol === '') {
            return ['show' => false, 'map' => []];
        }

        return [
            'show' => true,
            'map'  => crud_list_preload_visible_langs($tableLang, $fkCol, $listRows, $pkCol),
        ];
    }
}

if (!function_exists('manage_list_grid_with_lang')) {
    /** 列表 tableGrid 加上語系欄時追加 tableGrid--has-lang */
    function manage_list_grid_with_lang(string $gridClass, bool $showLangColumn): string {
        return $showLangColumn ? (trim($gridClass) . ' tableGrid--has-lang') : trim($gridClass);
    }
}

if (!function_exists('manage_list_render_lang_header')) {
    /** 列表表頭「語系」欄（$showLangColumn 為 false 不輸出） */
    function manage_list_render_lang_header(bool $showLangColumn): void {
        if (!$showLangColumn) {
            return;
        }
        echo '<div class="textCenter">語系</div>';
    }
}

if (!function_exists('manage_render_list_lang_tabs')) {
    /** 列表語系欄：色塊標籤（僅背景色 + 文字，與 editView 語系色票一致） */
    function manage_render_list_lang_tabs(int $parentPKey, array $langMap): void {
        global $array_lang;

        $langs = $langMap[$parentPKey] ?? [];
        if ($langs === []) {
            echo '<span class="listLangCell__empty">—</span>';
            return;
        }

        echo '<div class="listLangTags">';
        foreach ($langs as $langInt) {
            $langInt = (int)$langInt;
            if ($langInt <= 0) {
                continue;
            }
            $label = (string)($array_lang[$langInt] ?? ('語系' . $langInt));
            echo '<span class="listLangTag listLangTag--color' . $langInt . '">' . e($label) . '</span>';
        }
        echo '</div>';
    }
}

if (!function_exists('manage_list_render_lang_cell')) {
    /** 列表列：語系色塊標籤儲存格（委派 manage_render_list_lang_tabs） */
    function manage_list_render_lang_cell(int $rowPKey, bool $showLangColumn, array $langMap): void {
        if (!$showLangColumn) {
            return;
        }
        echo '<div class="textCenter listLangCell">';
        manage_render_list_lang_tabs($rowPKey, $langMap);
        echo '</div>';
    }
}

if (!function_exists('manage_render_ckeditor_html')) {
    /**
     * 後台列表／預覽：輸出 CKEditor 儲存的 HTML（非 escape）
     * 會移除 script、並將 Upload 路徑正規化為 manage 子目錄可載入的相對路徑
     */
    function manage_render_ckeditor_html(string $html, string $uploadRel = '../../Upload/'): string {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/iu', '', $html) ?? $html;

        $uploadRel = rtrim(str_replace('\\', '/', $uploadRel), '/') . '/';
        $html = preg_replace(
            '#(src|href)\s*=\s*(["\'])(?:\.\./\.\./|\.\./|/)?Upload/#iu',
            '$1=$2' . $uploadRel,
            $html
        ) ?? $html;

        global $web_root;
        $base = rtrim((string)($web_root ?? ''), '/');
        if ($base !== '') {
            $uploadUrl = $base . '/Upload/';
            $html = preg_replace(
                '#(src|href)\s*=\s*(["\'])/Upload/#iu',
                '$1=$2' . $uploadUrl,
                $html
            ) ?? $html;
        }

        return $html;
    }
}

if (!function_exists('manage_enhance_content_tables')) {
    /**
     * CKEditor 內文表格：RWD 外層 + ai-table 框線／隔行變色（AI 產文與前台 rwd_table 共用）
     */
    function manage_enhance_content_tables(string $html): string {
        if ($html === '' || stripos($html, '<table') === false) {
            return $html;
        }

        $html = preg_replace(
            '#<div\s+class=["\']tableContainer["\']>\s*(<table\b.*?</table>)\s*</div>#is',
            '$1',
            $html
        ) ?? $html;

        return preg_replace_callback(
            '#<table\b([^>]*)>(.*?)</table>#is',
            static function (array $m): string {
                $attrs = $m[1];
                $inner = $m[2];

                if (preg_match('/\bclass=(["\'])([^"\']*)\1/i', $attrs, $classMatch)) {
                    $classValue = trim((string)$classMatch[2]);
                    if (!preg_match('/\bai-table\b/i', $classValue)) {
                        $classValue = trim($classValue . ' ai-table');
                    }
                    $attrs = (string)preg_replace(
                        '/\bclass=(["\'])[^"\']*\1/i',
                        'class="' . $classValue . '"',
                        $attrs
                    );
                } else {
                    $attrs = ' class="ai-table"' . $attrs;
                }

                return '<div class="tableContainer"><table' . $attrs . '>' . $inner . '</table></div>';
            },
            $html
        ) ?? $html;
    }
}

if (!function_exists('crud_class_table_name')) {
    /** 分類層級對應資料表（第 4 層自動偵測 dbclass34 / dbclass4） */
    function crud_class_table_name(int $level): ?string {
        static $level4Table = null;
        if ($level < 1 || $level > 4) {
            return null;
        }
        if ($level === 4) {
            if ($level4Table === null) {
                $level4Table = '';
                if (function_exists('chkTable')) {
                    if (chkTable('dbclass34')) {
                        $level4Table = 'dbclass34';
                    } elseif (chkTable('dbclass4')) {
                        $level4Table = 'dbclass4';
                    }
                }
            }
            return $level4Table !== '' ? $level4Table : null;
        }
        $map = [1 => 'dbclass1', 2 => 'dbclass2', 3 => 'dbclass3'];
        $table = $map[$level] ?? null;
        if ($table && function_exists('chkTable') && !chkTable($table)) {
            return null;
        }
        return $table;
    }
}

if (!function_exists('crud_dedupe_class_option_rows')) {
    /**
     * 分類選項依 PKey 去重（避免語系子表或多段載入造成重複）
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array{PKey:int, strName:string}>
     */
    function crud_dedupe_class_option_rows(array $rows): array {
        $seen = [];
        $out  = [];
        foreach ($rows as $row) {
            $pk = (int)(function_exists('crud_row_int')
                ? crud_row_int($row, 'PKey')
                : ($row['PKey'] ?? $row['ID'] ?? 0));
            if ($pk <= 0 || isset($seen[$pk])) {
                continue;
            }
            $seen[$pk] = true;
            $out[]     = [
                'PKey'    => $pk,
                'strName' => (string)(function_exists('crud_row_val')
                    ? crud_row_val($row, 'strName')
                    : ($row['strName'] ?? $row['Name'] ?? '')),
            ];
        }
        return $out;
    }
}

if (!function_exists('crud_fetch_class_options')) {
    /**
     * 列表／下拉用分類選項（僅主檔 dbclass1~3，不含 *_lang）
     *
     * @return list<array{PKey:int, strName:string}>
     */
    function crud_fetch_class_options(int $level, int $modulePKey, int $parentPKey = 0): array {
        $table = crud_class_table_name($level);
        if ($table === null || !crud_is_safe_sql_identifier($table) || str_ends_with($table, '_lang')) {
            return [];
        }
        if ($level === 1) {
            $sql = 'SELECT PKey, strName FROM `' . $table . '` WHERE Module_PKey = :Module_PKey'
                . ' ORDER BY Sort ASC, dtUDate DESC';
            $params = ['Module_PKey' => SqlFilter($modulePKey, 'int')];
        } else {
            if ($parentPKey <= 0) {
                return [];
            }
            $parentCol = 'Class' . ($level - 1) . '_PKey';
            if (!crud_is_safe_sql_identifier($parentCol)) {
                return [];
            }
            $sql = 'SELECT PKey, strName FROM `' . $table . '` WHERE Module_PKey = :Module_PKey'
                . ' AND `' . $parentCol . '` = :parent'
                . ' ORDER BY Sort ASC, dtUDate DESC';
            $params = [
                'Module_PKey' => SqlFilter($modulePKey, 'int'),
                'parent'      => SqlFilter($parentPKey, 'int'),
            ];
        }
        return crud_dedupe_class_option_rows(crud_fetch_all($sql, $params));
    }
}

if (!function_exists('crud_filter_row_for_table')) {
    /**
     * 僅保留資料表實際存在的欄位（避免寫入 OpenDate 等 schema 沒有的欄位）
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    function crud_filter_row_for_table(string $table, array $row): array
    {
        $out = [];
        foreach ($row as $col => $val) {
            if (!is_string($col) || !crud_is_safe_sql_identifier($col)) {
                continue;
            }
            if (crud_table_has_column($table, $col)) {
                $out[$col] = $val;
            }
        }
        return $out;
    }
}

if (!function_exists('crud_collect_lang_keywords_from_filter')) {
    /** 表單 Keyword1_{lang}…Keyword5_{lang} → 逗號分隔字串（paper_lang.Keywords） */
    function crud_collect_lang_keywords_from_filter(array $filter, int $langIndex, int $keywordCount = 5): string
    {
        $parts = [];
        for ($k = 1; $k <= $keywordCount; $k++) {
            $key = 'Keyword' . $k . '_' . $langIndex;
            if (!isset($filter[$key])) {
                continue;
            }
            $word = trim((string)$filter[$key]);
            if ($word !== '') {
                $parts[] = $word;
            }
        }
        return implode(',', $parts);
    }
}

if (!function_exists('crud_table_has_column')) {
    /** 資料表是否存在指定欄位（表名須已通過 crud_is_safe_sql_identifier） */
    function crud_table_has_column(string $table, string $column): bool {
        if (!crud_is_safe_sql_identifier($table) || !crud_is_safe_sql_identifier($column)) {
            return false;
        }
        if (!function_exists('chkTable') || !chkTable($table)) {
            return false;
        }
        $safeTable = str_replace('`', '', $table);
        foreach (crud_fetch_all('SHOW COLUMNS FROM `' . $safeTable . '`') as $row) {
            if (strcasecmp((string)($row['Field'] ?? ''), $column) === 0) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('crud_normalize_delete_lock_tables')) {
    /**
     * 過濾 delete_lock_tables：略過不存在表、非法名稱、不存在外鍵欄位
     *
     * @param array<string, string> $childChecks 子表 => 外鍵欄名
     * @return array<string, string>
     */
    function crud_normalize_delete_lock_tables(array $childChecks): array {
        $out = [];
        foreach ($childChecks as $table => $fkCol) {
            $table = trim((string)$table);
            $fkCol = trim((string)$fkCol);
            if ($table === '' || $fkCol === '') {
                continue;
            }
            if (!crud_is_safe_sql_identifier($table) || !crud_is_safe_sql_identifier($fkCol)) {
                continue;
            }
            if (!crud_table_exists($table)) {
                continue;
            }
            if (!crud_table_has_column($table, $fkCol)) {
                continue;
            }
            $out[$table] = $fkCol;
        }
        return $out;
    }
}

if (!function_exists('crud_ids_referenced_in_tables')) {
    /**
     * 批次查詢：哪些父鍵在子表仍有資料
     *
     * @param int[]              $parentIds
     * @param array<string,string> $childChecks 子表 => 外鍵欄名
     * @return array<int,true>   被鎖定（不可刪）的父鍵
     */
    function crud_ids_referenced_in_tables(array $parentIds, array $childChecks): array {
        $parentIds = array_values(array_unique(array_map('intval', $parentIds)));
        $parentIds = array_filter($parentIds, static fn(int $id): bool => $id > 0);
        if ($parentIds === []) {
            return [];
        }

        $childChecks = crud_normalize_delete_lock_tables($childChecks);
        if ($childChecks === []) {
            return [];
        }

        $locked = [];
        foreach ($childChecks as $table => $fkCol) {
            $ph = [];
            $params = [];
            foreach ($parentIds as $idx => $id) {
                $k = 'pid' . $idx;
                $ph[] = ':' . $k;
                $params[$k] = $id;
            }
            $sql = 'SELECT DISTINCT ' . $fkCol . ' AS pid FROM ' . $table
                . ' WHERE ' . $fkCol . ' IN (' . implode(',', $ph) . ')';
            foreach (crud_fetch_all($sql, $params) as $row) {
                $locked[(int)($row['pid'] ?? 0)] = true;
            }
        }
        return $locked;
    }
}

/* ── CSRF ─────────────────────────────────────────────── */

if (!function_exists('crud_csrf_normalize')) {
    /** 將舊版字串型 $_SESSION['csrf'] 轉為以 key 為索引的陣列 */
    function crud_csrf_normalize(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = [];
            return;
        }
        if (is_array($_SESSION['csrf'])) {
            return;
        }
        $legacy = is_string($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
        $_SESSION['csrf'] = [];
        if ($legacy !== '') {
            $_SESSION['csrf']['_legacy'] = $legacy;
        }
    }
}

if (!function_exists('crud_csrf_fail_redirect_url')) {
    /** addin 驗證失敗時導回 add/update（避免 history.back 留在過期表單） */
    function crud_csrf_fail_redirect_url(): ?string {
        $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
        if ($script !== 'addin.php' || !function_exists('crud_addin_return_url')) {
            return null;
        }
        $fa   = $GLOBALS['filter_array'] ?? [];
        $pkey = function_exists('safe_int')
            ? safe_int($fa['PKey'] ?? 0)
            : (int)($fa['PKey'] ?? 0);
        return crud_addin_return_url($pkey);
    }
}

if (!function_exists('crud_csrf_ensure')) {
    /** 確保 session 內指定 key 的 CSRF token 存在並回傳 */
    function crud_csrf_ensure(string $key): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        crud_csrf_normalize();
        if (empty($_SESSION['csrf'][$key])) {
            $_SESSION['csrf'][$key] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf'][$key];
    }
}

if (!function_exists('crud_csrf_rotate')) {
    /** 強制換發新 CSRF token（少用；成功後 rotate 會使「上一頁」表單 hidden 失效） */
    function crud_csrf_rotate(string $key): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        crud_csrf_normalize();
        $_SESSION['csrf'][$key] = bin2hex(random_bytes(32));
        return (string)$_SESSION['csrf'][$key];
    }
}

if (!function_exists('crud_csrf_verify')) {
    /**
     * 驗證 CSRF；失敗則 exit
     * $consume 預設 false：同頁可重送（導回編輯頁未重整時，hidden 仍有效）
     */
    function crud_csrf_verify(string $key, ?string $posted = null, bool $consume = false): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        crud_csrf_normalize();
        $posted  = $posted ?? (string)($GLOBALS['filter_array']['csrf_token'] ?? '');
        $session = (string)($_SESSION['csrf'][$key] ?? '');
        if ($posted === '' || $session === '' || !hash_equals($session, $posted)) {
            http_response_code(403);
            $failUrl = crud_csrf_fail_redirect_url();
            $failMsg = 'Invalid CSRF token';
            if ($posted === '' && strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                $failMsg = 'Invalid CSRF token（表單資料可能過大，請縮小圖片後再試）';
            }
            if (function_exists('manage_alert_script')) {
                manage_alert_script(
                    $failMsg,
                    $failUrl !== null && $failUrl !== '' ? $failUrl : null,
                    $failUrl === null || $failUrl === ''
                );
            }
            if ($failUrl !== null && $failUrl !== '') {
                echo '<script>alert(' . json_encode($failMsg, JSON_UNESCAPED_UNICODE) . ');';
                echo 'location.replace(' . json_encode($failUrl, JSON_UNESCAPED_UNICODE) . ');</script>';
            } else {
                echo '<script>alert(' . json_encode($failMsg, JSON_UNESCAPED_UNICODE) . ');history.back();</script>';
            }
            exit;
        }
        if ($consume) {
            unset($_SESSION['csrf'][$key]);
        }
    }
}

if (!function_exists('crud_csrf_verify_form')) {
    /** add/update 表單送出：驗證但不消耗 token */
    function crud_csrf_verify_form(string $key, ?string $posted = null): void {
        crud_csrf_verify($key, $posted, false);
    }
}

if (!function_exists('crud_csrf_guard_list')) {
    /** list 頁：刪除/排序前驗證 CSRF */
    function crud_csrf_guard_list(string $key): void {
        global $filter_array;
        $need = (isset($filter_array['Action']) && $filter_array['Action'] === 'del')
            || (isset($filter_array['SortUpdate']) && $filter_array['SortUpdate'] === '更新順序');
        if ($need) {
            crud_csrf_verify($key);
        }
        crud_csrf_ensure($key);
    }
}

/* ── 寫入 ─────────────────────────────────────────────── */

if (!function_exists('crud_next_sort')) {
    /**
     * @param list<int> $excludeSorts 自動遞增時略過的 Sort（如 module_p 保留 50 給權限用）
     */
    function crud_next_sort(
        string $table,
        array $whereParams,
        string $orderCol = 'Sort',
        array $excludeSorts = []
    ): int {
        $conds  = [];
        $params = [];
        foreach ($whereParams as $col => $val) {
            $conds[] = $col . ' = :' . $col;
            $params[$col] = $val;
        }

        $exclude = array_values(array_unique(array_map('intval', $excludeSorts)));
        if ($exclude !== []) {
            $ph = [];
            foreach ($exclude as $idx => $ex) {
                $k = 'ex' . $idx;
                $ph[] = ':' . $k;
                $params[$k] = $ex;
            }
            $conds[] = $orderCol . ' NOT IN (' . implode(',', $ph) . ')';
        }

        $where = $conds !== [] ? (' WHERE ' . implode(' AND ', $conds)) : '';
        $sql   = 'SELECT ' . $orderCol . ' FROM ' . $table . $where
            . ' ORDER BY ' . $orderCol . ' DESC LIMIT 1';
        $rs = new recordset($sql, $params);
        crud_check_rs($rs, $sql, $params);
        $next = $rs->eof ? 1 : ((int)$rs->field($orderCol) + 1);
        $rs->close();

        while ($exclude !== [] && in_array($next, $exclude, true)) {
            $next++;
        }

        return $next;
    }
}

if (!function_exists('crud_upsert_master')) {
    /**
     * 主檔新增或更新
     *
     * @return array{pkey:int, action:string, sql_log:string}
     */
    function crud_upsert_master(
        string $table,
        int $pkey,
        array $data,
        string $pkCol = 'PKey',
        ?string $insertDateCol = 'dtDate'
    ): array {
        $workFile = $GLOBALS['WorkFile'] ?? __FILE__;
        $loginId  = $GLOBALS['Login_ID'] ?? '';

        $sql = 'SELECT ' . $pkCol . ' FROM ' . $table . ' WHERE ' . $pkCol . ' = :' . $pkCol;
        $rs  = new recordset($sql, [$pkCol => $pkey]);
        crud_check_rs($rs, $sql, [$pkCol => $pkey]);

        $pdo = new dbPDO();
        $isUpdate = !$rs->eof;
        $rs->close();

        if ($isUpdate) {
            $realPk = (int)$pkey;
            $pdo->update($table, $data, $pkCol, $realPk);
            $action = '修改成功!';
        } else {
            if ($insertDateCol !== null && !isset($data[$insertDateCol])) {
                $data[$insertDateCol] = date('Y-m-d H:i:s');
            }
            $pdo->insert($table, $data);
            $realPk = (int)$pdo->getLastId();
            $action = '新增成功!';
        }

        $sqlLog = $pdo->getLastSql();
        $err = method_exists($pdo, 'getErrorMessage') ? $pdo->getErrorMessage() : '';
        if ($err !== '' && $err !== null) {
            crud_fail_db($sqlLog, (string)$err, $data, true);
        }
        $pdo->close();

        if (function_exists('manage_history')) {
            $ctx = crud_module_ctx();
            manage_history(
                $ctx['Module_PKey'],
                $ctx['Module_Name'],
                $sqlLog . "\n" . (function_exists('array_to_string') ? array_to_string($data) : '') . $pkCol . '=' . $realPk,
                $ctx['WorkFile'],
                $ctx['Login_ID'],
                $action
            );
        }

        if (!function_exists('vector_sync_after_master_save')) {
            require_once __DIR__ . '/vector_sync_helpers.php';
        }
        vector_sync_after_master_save($table, $realPk);

        return ['pkey' => $realPk, 'action' => $action, 'sql_log' => $sqlLog];
    }
}

/* ── list.php：刪除 / 排序 ───────────────────────────── */

if (!function_exists('crud_collect_sort_items')) {
    /**
     * 從 list 表單 filter 收集 Sort 有變更的列
     *
     * @return array{items: list<array<string, int>>, skipped: list<string>}
     */
    function crud_collect_sort_items(array $filter, string $pkName = 'PKey'): array {
        $total = (int)SqlFilter($filter['Total'] ?? 0, 'int');
        $items = [];
        $skipped = [];
        for ($i = 1; $i <= $total; $i++) {
            $pk   = (int)SqlFilter($filter["PKey{$i}"] ?? 0, 'int');
            $sort = (int)SqlFilter($filter["Sort{$i}"] ?? 0, 'int');
            $old  = (int)SqlFilter($filter["O_Sort{$i}"] ?? 0, 'int');
            if ($pk <= 0) {
                $skipped[] = "PKey{$i}無效";
                continue;
            }
            if ($sort !== $old) {
                $items[] = [$pkName => $pk, 'Sort' => $sort];
            } else {
                $skipped[] = (string)$pk;
            }
        }
        return ['items' => $items, 'skipped' => $skipped];
    }
}

if (!function_exists('crud_handle_list_delete')) {
    /**
     * 批次刪除並導回列表
     *
     * @param callable|null $beforeDelete function(array $ids): void — 可 throw/exit 阻擋刪除
     */
    function crud_handle_list_delete(array $cfg, ?callable $beforeDelete = null, ?string $backUrl = null): void {
        global $filter_array;

        $ids = array_values(array_filter(
            array_map('intval', $filter_array['nid'] ?? []),
            fn($x) => $x > 0
        ));

        if ($beforeDelete !== null && !empty($ids)) {
            $beforeDelete($ids);
        }

        if (!function_exists('delete_cascade_by_ids')) {
            crud_fail_db('delete_cascade_by_ids', '函式不存在', [], true);
        }

        $result = delete_cascade_by_ids($ids, $cfg);

        if (!empty($ids)) {
            if (!function_exists('vector_sync_after_master_delete')) {
                require_once __DIR__ . '/vector_sync_helpers.php';
            }
            vector_sync_after_master_delete((string)($cfg['table'] ?? ''), $ids);
        }

        $total   = count($ids);
        $success = (int)($result['deleted'] ?? 0);
        $fail    = max(0, $total - $success);
        $warn    = isset($result['miss_files']) ? count(array_unique($result['miss_files'])) : 0;

        $msg = "刪除完成：成功 {$success} 筆";
        if ($fail > 0) {
            $msg .= "，失敗 {$fail} 筆";
        }
        if ($warn > 0) {
            $msg .= "（檔案遺失 {$warn} 筆）";
        }

        if (function_exists('manage_history')) {
            $ctx = $cfg['module'] ?? crud_module_ctx();
            $summary = "[delete_summary] total={$total}, success={$success}, fail={$fail}, miss_files={$warn}";
            manage_history($ctx['Module_PKey'], $ctx['Module_Name'], $summary, $ctx['WorkFile'], $ctx['Login_ID'], '刪除彙總');
        }

        if ($backUrl !== null && $backUrl !== '') {
            if (function_exists('manage_alert_script')) {
                manage_alert_script($msg, $backUrl);
            }
            echo '<script>alert(' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ');location.href='
                . json_encode($backUrl, JSON_UNESCAPED_SLASHES) . ';</script>';
            exit;
        }

        $GLOBALS['show'] = $msg;
        require_once dirname(__DIR__) . '/manage/_search_list.php';
        exit;
    }
}

if (!function_exists('crud_handle_list_sort')) {
    /** list.php「更新順序」：批次 UpdateSortBatch 後 alert 導回 */
    function crud_handle_list_sort(string $table, string $pkName = 'PKey', ?string $backUrl = null): void {
        global $filter_array;

        $collected = crud_collect_sort_items($filter_array, $pkName);
        $res = UpdateSortBatch($table, $collected['items'], $pkName);

        $okCnt   = count($res['ok'] ?? []);
        $failCnt = count($res['fail'] ?? []);
        $skipCnt = count($collected['skipped']);

        $msg = "更新完成：成功 {$okCnt} 筆";
        if ($skipCnt > 0) {
            $msg .= "，未變更 {$skipCnt} 筆";
        }
        if ($failCnt > 0) {
            $msg .= '，失敗 ' . $failCnt . ' 筆（PKey: ' . implode(',', array_keys($res['fail'])) . '）';
        }

        $backUrl = $backUrl ?? ($GLOBALS['WorkFile'] ?? 'list.php');
        if (function_exists('manage_alert_script')) {
            manage_alert_script($msg, $backUrl);
        }
        echo '<script>alert(' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ');location.href='
            . json_encode($backUrl, JSON_UNESCAPED_SLASHES) . ';</script>';
        exit;
    }
}

if (!function_exists('crud_process_list_actions')) {
    /**
     * 處理 list 的刪除與排序（有處理則 exit）
     *
     * @param array         $cfg           crud_cfg() 結果
     * @param callable|null $beforeDelete  刪除前檢查
     * @param string|null   $listBackUrl   刪除／排序完成後導回網址（預設 WorkFile 或 list.php）
     */
    function crud_process_list_actions(array $cfg, ?callable $beforeDelete = null, ?string $listBackUrl = null): void {
        global $filter_array;

        if (isset($filter_array['Action']) && $filter_array['Action'] === 'del') {
            crud_handle_list_delete($cfg, $beforeDelete, $listBackUrl);
        }

        if (isset($filter_array['SortUpdate']) && $filter_array['SortUpdate'] === '更新順序') {
            crud_handle_list_sort(
                (string)$cfg['table'],
                (string)($cfg['pk'] ?? 'PKey'),
                $listBackUrl
            );
        }
    }
}

if (!function_exists('crud_validation_alert_back')) {
    /** 驗證失敗：alert 訊息後 history.back() 並 exit */
    function crud_validation_alert_back(string $message): never {
        if (function_exists('manage_alert_script')) {
            manage_alert_script($message, null, true);
        }
        echo '<script>alert(' . json_encode($message, JSON_UNESCAPED_UNICODE) . ');history.back();</script>';
        exit;
    }
}

if (!function_exists('crud_url_with_cache_buster')) {
    /** 導回編輯頁時避免瀏覽器 bfcache 沿用舊 CSRF hidden */
    function crud_url_with_cache_buster(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }
        $sep = (str_contains($url, '?')) ? '&' : '?';
        return $url . $sep . '_t=' . time();
    }
}

if (!function_exists('manage_pull_form_flash_errors')) {
    /** 讀取 addin 導回後要顯示的錯誤（讀取後即清除 session） */
    function manage_pull_form_flash_errors(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $errors = [];
        if (!empty($_SESSION['form_error'])) {
            $text = trim((string)$_SESSION['form_error']);
            unset($_SESSION['form_error']);
            foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line === '發生錯誤，請填寫下列欄位') {
                    continue;
                }
                $errors[] = $line;
            }
        }
        if (!empty($_SESSION['error_msg'])) {
            $msg = trim((string)$_SESSION['error_msg']);
            unset($_SESSION['error_msg']);
            if ($msg !== '') {
                $errors[] = $msg;
            }
        }
        return array_values(array_unique($errors));
    }
}

if (!function_exists('crud_addin_resolve_man_sub_no')) {
    /** addin / 導回網址用：manNo、subNo（GLOBALS → 表單 POST） */
    function crud_addin_resolve_man_sub_no(): array
    {
        global $filter_array;
        $fa = is_array($filter_array ?? null) ? $filter_array : [];
        $manNo = (int)($GLOBALS['manNo'] ?? $fa['manNo'] ?? 0);
        $subNo = (int)($GLOBALS['subNo'] ?? $fa['subNo'] ?? 0);
        return [$manNo, $subNo];
    }
}

if (!function_exists('crud_addin_verify_master_module')) {
    /**
     * 編輯時確認主檔屬於目前單元；通過回傳 null，失敗回傳錯誤訊息
     */
    function crud_addin_verify_master_module(
        string $table,
        int $formPKey,
        int $modulePKey,
        string $moduleCol = 'Module_PKey'
    ): ?string {
        if ($formPKey <= 0 || $modulePKey <= 0 || !crud_is_safe_sql_identifier($table)
            || !crud_is_safe_sql_identifier($moduleCol)) {
            return null;
        }
        $row = crud_fetch_one(
            'SELECT `' . $moduleCol . '` FROM `' . $table . '` WHERE `PKey` = :pk LIMIT 1',
            ['pk' => $formPKey]
        );
        if ($row === null) {
            return '查無要修改資料或無權限';
        }
        $rowModule = function_exists('crud_row_int')
            ? crud_row_int($row, $moduleCol)
            : (int)($row[$moduleCol] ?? 0);
        global $filter_array;
        $manNoFromForm = safe_int(is_array($filter_array ?? null) ? ($filter_array['manNo'] ?? 0) : 0);
        $moduleOk = ($rowModule === $modulePKey)
            || ($manNoFromForm > 0 && $rowModule === $manNoFromForm);
        if ($moduleOk) {
            return null;
        }
        return '查無要修改資料或無權限（單元 Module_PKey=' . $rowModule . '，目前=' . $modulePKey . '）';
    }
}

if (!function_exists('crud_addin_return_url')) {
    /**
     * addin 驗證失敗或錯誤時導回 add.php / update.php（含 manNo、subNo）
     */
    function crud_addin_return_url(int $pkey = 0, string $addFile = 'add.php', string $updateFile = 'update.php'): string {
        if ($pkey > 0) {
            $url = $updateFile . '?PKey=' . $pkey;
            $sep = '&';
        } else {
            $url = $addFile;
            $sep = '?';
        }
        [$manNo, $subNo] = crud_addin_resolve_man_sub_no();
        if ($manNo > 0) {
            $url .= $sep . 'manNo=' . $manNo;
            $sep = '&';
            if ($subNo > 0) {
                $url .= '&subNo=' . $subNo;
            }
        }
        return crud_url_with_cache_buster($url);
    }
}

if (!function_exists('crud_addin_flag01')) {
    /** Y/N、Yes/No、1/0 → '1' / '0'（刊登日期等旗標） */
    function crud_addin_flag01($value): string {
        if (function_exists('class1_detail_flag01')) {
            return class1_detail_flag01($value);
        }
        $v = strtolower(trim((string)$value));
        return in_array($v, ['1', 'y', 'yes', 'true', 'on'], true) ? '1' : '0';
    }
}

if (!function_exists('crud_addin_validate_layer_classes')) {
    /** 依 Layer 驗證 Class1～Class3 是否已選 */
    function crud_addin_validate_layer_classes(array $filter, int $layer): string {
        global $Class_Name;

        $msg = '';
        if ($layer > 1 && safe_int($filter['Class1'] ?? 0) <= 0) {
            $msg .= '【' . (string)($Class_Name[1] ?? '上層類別') . "】請選擇\n";
        }
        if ($layer > 2 && safe_int($filter['Class2'] ?? 0) <= 0) {
            $msg .= '【' . (string)($Class_Name[2] ?? '上層類別') . "】請選擇\n";
        }
        if ($layer > 3 && safe_int($filter['Class3'] ?? 0) <= 0) {
            $msg .= '【' . (string)($Class_Name[3] ?? '上層類別') . "】請選擇\n";
        }
        return $msg;
    }
}

if (!function_exists('crud_addin_validate_publish_dates')) {
    /**
     * 刊登／下架日期：馬上刊登、無下架期限時自動帶入；並檢查起迄
     *
     * @return string 錯誤訊息（空字串表示通過）
     */
    function crud_addin_validate_publish_dates(array $filter): string {
        $noOpenSoon = crud_addin_flag01($filter['NoOpenDate'] ?? '0') !== '1';
        $noEndLimit = crud_addin_flag01($filter['NoEndDate'] ?? '0') !== '1';

        if ($noOpenSoon) {
            $openDate = date('Y-m-d');
        } else {
            $openDate = trim((string)($filter['OpenDate'] ?? ''));
            if ($openDate === '') {
                return "【刊登日期】為空白\n";
            }
        }

        if ($noEndLimit) {
            $endDate = date('Y-m-d', mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y') + 10));
        } else {
            $endDate = trim((string)($filter['EndDate'] ?? ''));
            if ($endDate === '') {
                return "【下架日期】為空白\n";
            }
        }

        $openTs = strtotime($openDate);
        $endTs  = strtotime($endDate);
        if ($openTs === false || $endTs === false) {
            return "【刊登日期】或【下架日期】格式不正確\n";
        }
        if ($endTs < $openTs) {
            return "【刊登日期大於下架日期】\n";
        }

        return '';
    }
}

if (!function_exists('crud_strdate_for_form')) {
    /** 發佈日期 strDate：表單顯示用 Y/m/d，空白或無效時為今天 */
    function crud_strdate_for_form($value): string {
        $value = trim((string)$value);
        if ($value === '' || !function_exists('chkDate') || !chkDate($value)) {
            return date('Y/m/d');
        }
        return (string)Date_EN($value, 1);
    }
}

if (!function_exists('crud_addin_validate_strdate')) {
    /** 驗證發佈日期 strDate */
    function crud_addin_validate_strdate(array $filter): string {
        $raw = trim((string)($filter['strDate'] ?? ''));
        if ($raw === '') {
            return "【發佈日期】為空白\n";
        }
        if (!function_exists('chkDate') || !chkDate($raw)) {
            return "【發佈日期】格式不正確\n";
        }
        return '';
    }
}

if (!function_exists('crud_addin_resolve_strdate')) {
    /** 正規化發佈日期 strDate 為 Y-m-d，供寫入 DB */
    function crud_addin_resolve_strdate(array &$filter): void {
        $raw = trim((string)($filter['strDate'] ?? ''));
        if ($raw === '' || !function_exists('chkDate') || !chkDate($raw)) {
            $filter['strDate'] = date('Y-m-d');
        } else {
            $filter['strDate'] = (string)Date_EN($raw, 2);
        }
    }
}

if (!function_exists('crud_addin_resolve_publish_dates')) {
    /** 依表單選項算出實際 OpenDate / EndDate，供寫入 DB */
    function crud_addin_resolve_publish_dates(array &$filter): void {
        if (crud_addin_flag01($filter['NoOpenDate'] ?? '0') !== '1') {
            $filter['OpenDate'] = date('Y-m-d');
        } else {
            $filter['OpenDate'] = trim((string)($filter['OpenDate'] ?? ''));
        }

        if (crud_addin_flag01($filter['NoEndDate'] ?? '0') !== '1') {
            $filter['EndDate'] = date('Y-m-d', mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y') + 10));
        } else {
            $filter['EndDate'] = trim((string)($filter['EndDate'] ?? ''));
        }
    }
}

if (!function_exists('crud_detail_resolve_publish_dates_for_form')) {
    /**
     * 編輯表單：還原刊登／下架日期與 NoOpenDate、NoEndDate 選項
     * - 有欄位時以 DB 旗標為準；旗標為 0 但日期明顯為自訂值時改視為自訂
     * - 無 NoOpenDate 欄位時（舊資料）依 OpenDate 是否有值判斷
     */
    function crud_detail_resolve_publish_dates_for_form(array &$v, array $row, ?string $masterTable = null): void {
        $masterTable = $masterTable ?? (string)(function_exists('manage_detail_tables')
            ? (manage_detail_tables()['master'] ?? '')
            : '');
        $hasNoOpen = $masterTable !== '' && function_exists('crud_table_has_column')
            && crud_table_has_column($masterTable, 'NoOpenDate');
        $hasNoEnd  = $masterTable !== '' && function_exists('crud_table_has_column')
            && crud_table_has_column($masterTable, 'NoEndDate');

        $rawOpen = trim((string)($row['OpenDate'] ?? ''));
        $rawEnd  = trim((string)($row['EndDate'] ?? ''));
        $todayYmd = date('Y-m-d');
        $defaultEndYmd = date('Y-m-d', mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y') + 10));

        if ($hasNoOpen && array_key_exists('NoOpenDate', $row)) {
            $v['NoOpenDate'] = crud_addin_flag01($row['NoOpenDate']);
        } else {
            $v['NoOpenDate'] = '0';
        }

        if ($v['NoOpenDate'] !== '1' && $rawOpen !== '' && function_exists('chkDate') && chkDate($rawOpen)) {
            $openYmd = function_exists('Date_EN') ? Date_EN($rawOpen, 2) : null;
            if ($openYmd !== null && ($openYmd !== $todayYmd || !$hasNoOpen)) {
                $v['NoOpenDate'] = '1';
            }
        }

        if ($v['NoOpenDate'] === '1') {
            $v['OpenDate'] = '';
            if ($rawOpen !== '' && function_exists('Date_EN')) {
                $fmt = Date_EN($rawOpen, 2);
                if ($fmt !== null) {
                    $v['OpenDate'] = $fmt;
                }
            }
        } else {
            $v['OpenDate']    = '';
            $v['NoOpenDate']  = '0';
        }

        if ($hasNoEnd && array_key_exists('NoEndDate', $row)) {
            $v['NoEndDate'] = crud_addin_flag01($row['NoEndDate']);
        } else {
            $v['NoEndDate'] = '0';
        }

        if ($v['NoEndDate'] !== '1' && $rawEnd !== '' && function_exists('chkDate') && chkDate($rawEnd)) {
            $endYmd = function_exists('Date_EN') ? Date_EN($rawEnd, 2) : null;
            if ($endYmd !== null && ($endYmd !== $defaultEndYmd || !$hasNoEnd)) {
                $v['NoEndDate'] = '1';
            }
        }

        if ($v['NoEndDate'] === '1') {
            $v['EndDate'] = '';
            if ($rawEnd !== '' && function_exists('Date_EN')) {
                $fmt = Date_EN($rawEnd, 2);
                if ($fmt !== null) {
                    $v['EndDate'] = $fmt;
                }
            }
        } else {
            $v['EndDate']   = '';
            $v['NoEndDate'] = '0';
        }
    }
}

if (!function_exists('crud_addin_append_publish_master_fields')) {
    /**
     * 內容單元主檔：分類、刊登區間、Home 等（news / paper 等）
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    function crud_addin_append_publish_master_fields(array $data, array $filter, int $layer, ?string $masterTable = null): array {
        $masterTable = $masterTable ?? (string)(manage_detail_tables()['master'] ?? '');

        if ($layer > 1 && isset($filter['Class1'])) {
            $data['Class1_PKey'] = SqlFilter($filter['Class1'], 'int');
        }
        if ($layer > 2 && isset($filter['Class2'])) {
            $data['Class2_PKey'] = SqlFilter($filter['Class2'], 'int');
        }
        if ($layer > 3 && isset($filter['Class3'])) {
            $data['Class3_PKey'] = SqlFilter($filter['Class3'], 'int');
        }
        if (isset($filter['OpenDate']) && ($masterTable === '' || crud_table_has_column($masterTable, 'OpenDate'))) {
            $data['OpenDate'] = SqlFilter((string)$filter['OpenDate'], 'tab');
        }
        if (isset($filter['EndDate']) && ($masterTable === '' || crud_table_has_column($masterTable, 'EndDate'))) {
            $data['EndDate'] = SqlFilter((string)$filter['EndDate'], 'tab');
        }
        if (isset($filter['strDate']) && ($masterTable === '' || crud_table_has_column($masterTable, 'strDate'))) {
            $data['strDate'] = SqlFilter((string)$filter['strDate'], 'tab');
        }
        if ($masterTable === '' || crud_table_has_column($masterTable, 'NoOpenDate')) {
            $data['NoOpenDate'] = SqlFilter(crud_addin_flag01($filter['NoOpenDate'] ?? '0'), 'tab');
        }
        if ($masterTable === '' || crud_table_has_column($masterTable, 'NoEndDate')) {
            $data['NoEndDate'] = SqlFilter(crud_addin_flag01($filter['NoEndDate'] ?? '0'), 'tab');
        }
        if ($masterTable === '' || crud_table_has_column($masterTable, 'Home')) {
            $data['Home'] = SqlFilter(!empty($filter['Home']) ? 'Yes' : 'No', 'tab');
        }
        if (isset($filter['intLink']) && ($masterTable === '' || crud_table_has_column($masterTable, 'intLink'))) {
            $data['intLink'] = SqlFilter($filter['intLink'], 'int');
        }
        if (isset($filter['strLink']) && ($masterTable === '' || crud_table_has_column($masterTable, 'strLink'))) {
            $safeLink = function_exists('safe_url')
                ? safe_url((string)$filter['strLink'])
                : (string)$filter['strLink'];
            if ($safeLink !== null && $safeLink !== '') {
                $data['strLink'] = $safeLink;
            }
        }
        return $data;
    }
}

if (!function_exists('crud_validate_lang_show_strname')) {
    /**
     * 多語系表單：至少勾選一個 Show{n}，且已勾選者 strName{n} 不可空白
     */
    function crud_validate_lang_show_strname(array $filter, int $langCount = 0): string {
        global $array_lang;

        $labels = (array)($array_lang ?? []);
        $n      = $langCount > 0 ? $langCount : (count($labels) > 0 ? count($labels) : 6);
        $msg    = '';
        $anyOn  = false;

        for ($i = 1; $i <= $n; $i++) {
            if (($filter['Show' . $i] ?? '') !== 'Y') {
                continue;
            }
            $anyOn = true;
            if (trim((string)($filter['strName' . $i] ?? '')) === '') {
                $label = $labels[$i] ?? ('語系' . $i);
                $msg  .= '【' . $label . "】標題為空白\n";
            }
        }
        if (!$anyOn) {
            $msg .= "【顯示語系】請選擇\n";
        }
        return $msg;
    }
}

if (!function_exists('crud_remove_dangerous_html_embeds')) {
    /**
     * 移除常見 XSS 載體標籤（保留 img 等 CKEditor 圖文內容）
     */
    function crud_remove_dangerous_html_embeds(string $html): string {
        if (trim($html) === '' || !class_exists(DOMDocument::class)) {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousLibxmlState = libxml_use_internal_errors(true);
        $wrapperId = 'crud_html_root_' . bin2hex(random_bytes(4));
        $document->loadHTML(
            '<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . '<div id="' . $wrapperId . '">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        $xpath = new DOMXPath($document);
        $wrapper = $xpath->query('//*[@id="' . $wrapperId . '"]')->item(0);
        if (!$wrapper instanceof DOMElement) {
            return $html;
        }

        $dangerousTags = [
            'script', 'iframe', 'object', 'embed', 'form', 'input', 'button',
            'textarea', 'select', 'option', 'meta', 'link', 'base', 'style', 'noscript',
        ];
        $dangerQuery = './/' . implode('|.//', $dangerousTags);
        foreach ($xpath->query($dangerQuery, $wrapper) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('.//*[@href or @src or @style]', $wrapper) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                $name = strtolower($attribute->nodeName);
                if (str_starts_with($name, 'on')) {
                    $node->removeAttribute($attribute->nodeName);
                    continue;
                }
                if (in_array($name, ['href', 'src', 'xlink:href'], true)) {
                    $value = html_entity_decode((string)$attribute->nodeValue, ENT_QUOTES, 'UTF-8');
                    if (preg_match('/^(javascript|data|vbscript):/i', trim($value))) {
                        $node->removeAttribute($attribute->nodeName);
                    }
                }
            }
        }

        $output = '';
        foreach ($wrapper->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }
}

if (!function_exists('crud_sanitize_ckeditor_html_for_storage')) {
    /**
     * CKEditor / AI 產文 HTML 寫入 DB 前清理，防 XSS
     */
    function crud_sanitize_ckeditor_html_for_storage(string $html): string {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (!function_exists('gemini_sanitize_editor_html')) {
            $helper = __DIR__ . '/gemini_editor_helpers.php';
            if (is_file($helper)) {
                require_once $helper;
            }
        }

        $hasRichMedia = (bool)preg_match('/<(img|iframe|video|audio|embed|object)\b/i', $html);

        if (!$hasRichMedia && function_exists('gemini_sanitize_editor_html')) {
            return gemini_sanitize_editor_html($html);
        }

        if (function_exists('filter_html')) {
            $html = filter_html($html, 'tab');
        }

        return crud_remove_dangerous_html_embeds($html);
    }
}

if (!function_exists('crud_decode_b64_content_multilang')) {
    /**
     * 解碼 CKEditor 欄位 Contents{段}_{語系}_b64 → [語系][段] => HTML
     *
     * @return array{contents: array<int, array<int, string>>, error: string}
     */
    function crud_decode_b64_content_multilang(
        array $filter,
        int $maxBytes = 2097152,
        int $maxBlock = 6
    ): array {
        $contents = [];
        $error    = '';

        foreach ($filter as $k => $v) {
            if (preg_match('/^Contents(\d+)_(\d+)_b64$/', (string)$k, $m)) {
                $block = (int)$m[1];
                $lang  = (int)$m[2];
                if ($block < 1 || $block > $maxBlock || $lang < 1) {
                    continue;
                }
                if (!function_exists('strict_b64url_decode_to_utf8')) {
                    $error .= "【{$k}】解碼函式不存在\n";
                    continue;
                }
                $html = strict_b64url_decode_to_utf8((string)$v, $maxBytes);
                if ($html === null) {
                    $error .= "【{$k}】解碼失敗或格式不合法\n";
                    continue;
                }
                $contents[$lang][$block] = crud_sanitize_ckeditor_html_for_storage($html);
                continue;
            }
            if (!preg_match('/^Contents(\d+)_(\d+)$/', (string)$k, $m)) {
                continue;
            }
            $block = (int)$m[1];
            $lang  = (int)$m[2];
            if ($block < 1 || $block > $maxBlock || $lang < 1) {
                continue;
            }
            if (isset($contents[$lang][$block]) && $contents[$lang][$block] !== '') {
                continue;
            }
            $html = (string)$v;
            if ($html === '') {
                continue;
            }
            $contents[$lang][$block] = crud_sanitize_ckeditor_html_for_storage($html);
        }

        return ['contents' => $contents, 'error' => $error];
    }
}

if (!function_exists('crud_save_lang_slots')) {
    /**
     * 儲存語系子表（Show{n}、strName{n}；Interview/Subject 依表結構）
     */
    function crud_save_lang_slots(
        string $tableLang,
        string $fkName,
        int $parentPKey,
        array $filter,
        ?array $langIndexes = null,
        ?array $contentsByLang = null
    ): void {
        if (!function_exists('chkTable') || !chkTable($tableLang)) {
            return;
        }

        $langs = $langIndexes ?? array_keys((array)($GLOBALS['array_lang'] ?? []));
        if ($langs === []) {
            $langs = range(1, 6);
        }

        $meta       = crud_lang_table_select_meta($tableLang);
        $subjectCol = $meta['subject_col'];

        foreach ($langs as $n) {
            $n = (int)$n;
            if ($n <= 0) {
                continue;
            }

            $row = [
                $fkName   => SqlFilter($parentPKey, 'int'),
                'Sort'    => SqlFilter($n, 'int'),
                'intLang' => SqlFilter($n, 'int'),
                'isShow'  => SqlFilter((string)($filter['Show' . $n] ?? ''), 'tab'),
                'strName' => SqlFilter((string)($filter['strName' . $n] ?? ''), 'tab'),
                'dtDate'  => date('Y-m-d H:i:s'),
            ];

            if ($subjectCol !== null) {
                $filterKey = $subjectCol === 'Interview' ? 'Interview' . $n : 'Subject' . $n;
                if (isset($filter[$filterKey])) {
                    $row[$subjectCol] = SqlFilter((string)$filter[$filterKey], 'tab');
                }
            }
            if (isset($filter['Title' . $n]) && crud_table_has_column($tableLang, 'Title')) {
                $row['Title'] = SqlFilter((string)$filter['Title' . $n], 'tab');
            }
            if (isset($filter['Description' . $n]) && crud_table_has_column($tableLang, 'Description')) {
                $row['Description'] = SqlFilter((string)$filter['Description' . $n], 'tab');
            }
            if (crud_table_has_column($tableLang, 'Keywords')) {
                $row['Keywords'] = SqlFilter(crud_collect_lang_keywords_from_filter($filter, $n), 'tab');
            }
            if (isset($filter['Movielink' . $n]) && crud_table_has_column($tableLang, 'Movielink')) {
                $row['Movielink'] = SqlFilter((string)$filter['Movielink' . $n], 'tab');
            }
            if (crud_table_has_column($tableLang, 'Contents')) {
                $html = '';
                if (is_array($contentsByLang) && isset($contentsByLang[$n])) {
                    if (is_array($contentsByLang[$n])) {
                        $html = (string)($contentsByLang[$n][1] ?? reset($contentsByLang[$n]) ?: '');
                    } else {
                        $html = (string)$contentsByLang[$n];
                    }
                }
                if ($html === '') {
                    $plainKey = 'Contents1_' . $n;
                    if (isset($filter[$plainKey]) && (string)$filter[$plainKey] !== '') {
                        $html = (string)$filter[$plainKey];
                    }
                }
                if ($html === '' && isset($filter['Contents' . $n])) {
                    $html = (string)$filter['Contents' . $n];
                }
                $row['Contents'] = SqlFilter(crud_sanitize_ckeditor_html_for_storage($html), 'tab');
            }
            if (isset($filter['intLink' . $n]) && crud_table_has_column($tableLang, 'intLink')) {
                $row['intLink'] = SqlFilter($filter['intLink' . $n], 'int');
            }
            if (crud_table_has_column($tableLang, 'strLink')) {
                if (!crud_table_has_column($tableLang, 'intLink') && isset($filter['strLink' . $n])) {
                    $safeLink = function_exists('safe_url')
                        ? safe_url((string)$filter['strLink' . $n])
                        : (string)$filter['strLink' . $n];
                    $row['strLink'] = SqlFilter($safeLink !== null && $safeLink !== '' ? $safeLink : (string)$filter['strLink' . $n], 'tab');
                } else {
                    $linkMode = (int)($filter['intLink' . $n] ?? 2);
                    if ($linkMode === 1 && isset($filter['strLink' . $n])) {
                        $safeLink = function_exists('safe_url')
                            ? safe_url((string)$filter['strLink' . $n])
                            : (string)$filter['strLink' . $n];
                        $row['strLink'] = SqlFilter($safeLink !== null && $safeLink !== '' ? $safeLink : (string)$filter['strLink' . $n], 'tab');
                    } else {
                        $row['strLink'] = SqlFilter('', 'tab');
                    }
                }
            }
            if (isset($filter['Target' . $n]) && crud_table_has_column($tableLang, 'Target')) {
                $targetVal = (string)$filter['Target' . $n];
                $row['Target'] = SqlFilter($targetVal === '_self' ? '_self' : '_blank', 'tab');
            }
            if (crud_table_has_column($tableLang, 'strURL')) {
                $urlVal = '';
                if (isset($filter['strURL' . $n]) && trim((string)$filter['strURL' . $n]) !== '') {
                    $urlVal = (string)$filter['strURL' . $n];
                } elseif (isset($filter['strLinkUrl' . $n]) && trim((string)$filter['strLinkUrl' . $n]) !== '') {
                    $urlVal = (string)$filter['strLinkUrl' . $n];
                } elseif ((int)($filter['intLink' . $n] ?? 2) === 1 && isset($filter['strLink' . $n])) {
                    $urlVal = (string)$filter['strLink' . $n];
                }
                if ($urlVal !== '') {
                    $safeUrl = function_exists('safe_url') ? safe_url($urlVal) : $urlVal;
                    $urlVal = ($safeUrl !== null && $safeUrl !== '') ? $safeUrl : $urlVal;
                }
                $row['strURL'] = SqlFilter($urlVal, 'tab');
            }
            if (crud_table_has_column($tableLang, 'FileName') && (int)($filter['intLink' . $n] ?? 2) === 1) {
                $row['FileName'] = SqlFilter('', 'tab');
                if (crud_table_has_column($tableLang, 'FileSize')) {
                    $row['FileSize'] = SqlFilter('', 'tab');
                }
                if (crud_table_has_column($tableLang, 'Forder')) {
                    $row['Forder'] = SqlFilter('', 'tab');
                }
            }

            $row = crud_filter_row_for_table($tableLang, $row);

            $existing = crud_fetch_one(
                "SELECT PKey FROM {$tableLang} WHERE {$fkName} = :fk AND intLang = :lang",
                ['fk' => $parentPKey, 'lang' => $n]
            );

            $pdo = new dbPDO();
            if ($existing !== null) {
                $childPk = (int)$existing['PKey'];
                $pdo->update($tableLang, $row, 'PKey', $childPk);
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '語系' . $n . '修改成功';
            } else {
                $pdo->insert($tableLang, $row);
                $childPk = (int)$pdo->getLastId();
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '語系' . $n . '新增成功';
            }
            $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
            $pdo->close();

            if ($err !== '') {
                crud_fail_db($sqlLog, $err, $row, true);
            }

            $ctx = crud_module_ctx();
            if (function_exists('manage_history')) {
                manage_history($ctx['Module_PKey'], $ctx['Module_Name'], $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], $action);
            }
        }
    }
}

if (!function_exists('crud_save_module_lang_slots')) {
    /**
     * 儲存 module_lang（Description{n}、Keywords{n}、Show{n}）
     */
    function crud_save_module_lang_slots(
        string $tableLang,
        int $modulePKey,
        array $filter,
        ?array $langIndexes = null
    ): void {
        if ($modulePKey <= 0 || !function_exists('chkTable') || !chkTable($tableLang)) {
            return;
        }

        $langs = $langIndexes ?? array_keys((array)($GLOBALS['array_lang'] ?? []));
        if ($langs === []) {
            $langs = range(1, 6);
        }

        foreach ($langs as $n) {
            $n = (int)$n;
            if ($n <= 0) {
                continue;
            }

            $descKey = 'Description' . $n;
            $kwKey   = 'Keywords' . $n;
            $nameKey = 'strName' . $n;
            $hasShow = isset($filter['Show' . $n]);
            $hasDesc = isset($filter[$descKey]);
            $hasKw   = isset($filter[$kwKey]);
            $hasName = isset($filter[$nameKey]);
            if (!$hasShow && !$hasDesc && !$hasKw && !$hasName) {
                continue;
            }

            $row = [
                'Module_PKey' => SqlFilter($modulePKey, 'int'),
                'Sort'        => SqlFilter($n, 'int'),
                'intLang'     => SqlFilter($n, 'int'),
                'isShow'      => SqlFilter((string)($filter['Show' . $n] ?? ''), 'tab'),
                'strName'     => SqlFilter((string)($filter[$nameKey] ?? ''), 'tab'),
                'Description' => SqlFilter((string)($filter[$descKey] ?? ''), 'tab'),
                'Keywords'    => SqlFilter((string)($filter[$kwKey] ?? ''), 'tab'),
                'dtDate'      => date('Y-m-d H:i:s'),
            ];

            $existing = crud_fetch_one(
                "SELECT PKey FROM {$tableLang} WHERE Module_PKey = :fk AND intLang = :lang",
                ['fk' => $modulePKey, 'lang' => $n]
            );

            $pdo = new dbPDO();
            if ($existing !== null) {
                $childPk = (int)$existing['PKey'];
                $pdo->update($tableLang, $row, 'PKey', $childPk);
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '語系' . $n . '修改成功';
            } else {
                $pdo->insert($tableLang, $row);
                $childPk = (int)$pdo->getLastId();
                $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                $action = '語系' . $n . '新增成功';
            }
            $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
            $pdo->close();

            if ($err !== '') {
                crud_fail_db($sqlLog, $err, $row, true);
            }

            $ctx = crud_module_ctx();
            if (function_exists('manage_history')) {
                manage_history(
                    $ctx['Module_PKey'],
                    $ctx['Module_Name'] ?: '單元設定',
                    $sqlLog,
                    $ctx['WorkFile'],
                    $ctx['Login_ID'],
                    $action
                );
            }
        }
    }
}

if (!function_exists('crud_save_msg_blocks_multilang')) {
    /**
     * 儲存多語系內文子表（Contents{段}_{語系}、isShow{段}_{語系}）
     *
     * @param array<int, array<int, string>> $contentsByLangBlock [語系][段] => HTML
     */
    function crud_save_msg_blocks_multilang(
        string $tableMsg,
        string $fkName,
        int $parentPKey,
        array $contentsByLangBlock,
        array $filter,
        int $blockCount = 6,
        ?array $langIndexes = null
    ): void {
        if (!function_exists('chkTable') || !chkTable($tableMsg)) {
            return;
        }

        $langs = $langIndexes ?? array_keys((array)($GLOBALS['array_lang'] ?? []));
        if ($langs === []) {
            $langs = range(1, 6);
        }

        foreach ($langs as $n) {
            $n = (int)$n;
            if ($n <= 0) {
                continue;
            }
            for ($i = 1; $i <= $blockCount; $i++) {
                $showKey = 'isShow' . $i . '_' . $n;
                $html    = crud_sanitize_ckeditor_html_for_storage(
                    (string)($contentsByLangBlock[$n][$i] ?? '')
                );
                if ($html === '' && !isset($filter[$showKey])) {
                    continue;
                }

                $row = [
                    $fkName    => SqlFilter($parentPKey, 'int'),
                    'intLang'  => SqlFilter($n, 'int'),
                    'Sort'     => SqlFilter($i, 'int'),
                    'Contents' => $html,
                    'dtDate'   => date('Y-m-d H:i:s'),
                ];
                if (isset($filter[$showKey])) {
                    $row['isShow'] = SqlFilter((string)$filter[$showKey], 'tab');
                }

                $row = crud_filter_row_for_table($tableMsg, $row);

                $existing = crud_fetch_one(
                    "SELECT PKey FROM {$tableMsg} WHERE {$fkName} = :fk AND intLang = :lang AND Sort = :sort",
                    ['fk' => $parentPKey, 'lang' => $n, 'sort' => $i]
                );

                $pdo = new dbPDO();
                if ($existing !== null) {
                    $childPk = (int)$existing['PKey'];
                    $pdo->update($tableMsg, $row, 'PKey', $childPk);
                    $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                    $action = '內文' . $n . '-' . $i . '修改成功';
                } else {
                    $pdo->insert($tableMsg, $row);
                    $childPk = (int)$pdo->getLastId();
                    $sqlLog = $pdo->getLastSql() . "\nPKey=" . $childPk;
                    $action = '內文' . $n . '-' . $i . '新增成功';
                }
                $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
                $pdo->close();

                if ($err !== '') {
                    crud_fail_db($sqlLog, $err, $row, true);
                }

                $ctx = crud_module_ctx();
                if (function_exists('manage_history')) {
                    manage_history($ctx['Module_PKey'], $ctx['Module_Name'], $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], $action);
                }
            }
        }
    }
}

if (!function_exists('crud_form_error_redirect')) {
    /** 驗證失敗：寫入 session 並導回 add/update */
    function crud_form_error_redirect(string $message, string $returnUrl, bool $useSession = true): never {
        $prefix = "發生錯誤，請填寫下列欄位\n";
        $alertText = $prefix . $message;
        $returnUrl = crud_url_with_cache_buster($returnUrl);
        if ($useSession) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $_SESSION['form_error'] = $alertText;
        }
        if (function_exists('manage_alert_script')) {
            manage_alert_script($alertText, $returnUrl);
        }
        if (function_exists('manage_inline_script')) {
            echo manage_inline_script(
                'alert(' . json_encode($alertText, JSON_UNESCAPED_UNICODE) . ');'
                . 'location.replace(' . json_encode($returnUrl, JSON_UNESCAPED_UNICODE) . ');'
            );
        } else {
            echo '<script>alert(' . json_encode($alertText, JSON_UNESCAPED_UNICODE) . ');';
            echo 'location.replace(' . json_encode($returnUrl, JSON_UNESCAPED_UNICODE) . ');</script>';
        }
        exit;
    }
}

if (!function_exists('crud_resolve_upload_monthdir')) {
    /**
     * 從上傳結果取 YYYYMM 子目錄（空字串視為未設定；?? 無法略過 ''）
     *
     * @param array<string,mixed> ...$uploadResults crud_upload_file_slots 回傳陣列
     */
    function crud_resolve_upload_monthdir(array ...$uploadResults): string
    {
        foreach ($uploadResults as $result) {
            $monthdir = rtrim((string)($result['monthdir'] ?? ''), "\\/");
            if ($monthdir !== '') {
                return $monthdir;
            }
        }

        return date('Ym');
    }
}

if (!function_exists('crud_upload_dir')) {
    /** 上傳目錄（結尾含 DIRECTORY_SEPARATOR），不可寫入時回傳錯誤訊息 */
    function crud_upload_dir(): array {
        $base = crud_upload_base();
        if ($base === '') {
            $base = dirname(__DIR__, 2) . '/Upload/';
        }
        $dir = rtrim($base, "/\\") . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            return ['dir' => $dir, 'error' => "【上傳目錄不可寫入】\n"];
        }
        return ['dir' => $dir, 'error' => ''];
    }
}

if (!function_exists('crud_decode_b64_content_fields')) {
    /**
     * 解碼 CKEditor 欄位 Contents{n}_b64
     *
     * @return array{decoded: array<string,string>, indexes: array<int,true>, error: string}
     */
    function crud_decode_b64_content_fields(array $filter, int $maxBytes = 5242880): array {
        $decoded = [];
        $indexes = [];
        $errors  = '';
        $regex   = '/^Contents(\d+)_b64$/';

        foreach ($filter as $k => $v) {
            if (!preg_match($regex, (string)$k, $m)) {
                continue;
            }
            $idx = (int)$m[1];
            if (!function_exists('strict_b64url_decode_to_utf8')) {
                $errors .= "【{$k}】解碼函式不存在\n";
                continue;
            }
            $html = strict_b64url_decode_to_utf8((string)$v, $maxBytes);
            if ($html === null) {
                $errors .= "【{$k}】解碼失敗或格式不合法\n";
                continue;
            }
            $decoded[(string)$k] = crud_sanitize_ckeditor_html_for_storage($html);
            $indexes[$idx] = true;
        }

        return ['decoded' => $decoded, 'indexes' => $indexes, 'error' => $errors];
    }
}

if (!function_exists('crud_upsert_by_fk_sort')) {
    /**
     * 依外鍵 + Sort 新增或更新子表一列，並寫入 manage_history
     */
    function crud_upsert_by_fk_sort(
        string $table,
        string $fkName,
        int $fkValue,
        int $sort,
        array $row,
        string $historyLabel = '儲存'
    ): void {
        $sql = "SELECT PKey FROM {$table} WHERE {$fkName} = :fk AND Sort = :sort";
        $existing = crud_fetch_one($sql, ['fk' => $fkValue, 'sort' => $sort]);

        $pdo = new dbPDO();
        if ($existing !== null) {
            $childPk = (int)$existing['PKey'];
            $pdo->update($table, $row, 'PKey', $childPk);
            $sqlLog = $pdo->getLastSql() . "\n" . (function_exists('array_to_string') ? array_to_string($row) : '') . 'PKey=' . $childPk;
            $action = $historyLabel . '修改成功';
        } else {
            $pdo->insert($table, $row);
            $childPk = (int)$pdo->getLastId();
            $sqlLog = $pdo->getLastSql() . "\n" . (function_exists('array_to_string') ? array_to_string($row) : '') . 'PKey=' . $childPk;
            $action = $historyLabel . '新增成功';
        }

        $err = method_exists($pdo, 'getErrorMessage') ? (string)$pdo->getErrorMessage() : '';
        $pdo->close();

        if ($err !== '') {
            crud_fail_db($sqlLog, $err, $row, true);
        }

        $ctx = crud_module_ctx();
        if (function_exists('manage_history')) {
            manage_history($ctx['Module_PKey'], $ctx['Module_Name'], $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], $action);
        }
    }
}

if (!function_exists('crud_save_msg_blocks')) {
    /**
     * 儲存 paper/knowledge 內文子表（固定 Sort 1..$blockCount）
     *
     * @param array<string,string> $decodedByKey  key 為 Contents{n}_b64
     */
    function crud_save_msg_blocks(
        string $tableMsg,
        string $fkName,
        int $parentPKey,
        array $decodedByKey,
        array $filter,
        int $blockCount = 6
    ): void {
        if (!function_exists('chkTable') || !chkTable($tableMsg)) {
            return;
        }

        for ($i = 1; $i <= $blockCount; $i++) {
            $b64Key = 'Contents' . $i . '_b64';
            if (!array_key_exists($b64Key, $decodedByKey)) {
                if (!isset($filter['isShow' . $i])) {
                    continue;
                }
                $decodedHtml = '';
            } else {
                $decodedHtml = crud_sanitize_ckeditor_html_for_storage($decodedByKey[$b64Key]);
            }

            $row = [
                $fkName    => SqlFilter($parentPKey, 'int'),
                'Sort'     => SqlFilter($i, 'int'),
                'Contents' => SqlFilter($decodedHtml, 'tab'),
                'dtDate'   => date('Y-m-d H:i:s'),
            ];
            if (isset($filter['isShow' . $i])) {
                $row['isShow'] = SqlFilter($filter['isShow' . $i], 'int');
            }

            crud_upsert_by_fk_sort($tableMsg, $fkName, $parentPKey, $i, $row, '內文' . $i);
        }

        if (!function_exists('vector_sync_after_msg_save')) {
            require_once __DIR__ . '/vector_sync_helpers.php';
        }
        vector_sync_after_msg_save($tableMsg, $parentPKey);
    }
}

if (!function_exists('crud_delete_image_variants')) {
    /** 刪除原圖、縮圖、webp 等衍生檔 */
    function crud_delete_image_variants(string $uploadBase, string $folder, string $filename): void {
        if ($filename === '' || !function_exists('DelFile')) {
            return;
        }
        $dir = rtrim($uploadBase, "/\\") . DIRECTORY_SEPARATOR . trim($folder, "/\\") . DIRECTORY_SEPARATOR;
        DelFile($dir . $filename);
        DelFile($dir . 's_' . $filename);
        DelFile($dir . 'thumb_' . $filename);
        $webp = preg_replace('/\.[a-z0-9]+$/i', '.webp', $filename);
        if ($webp !== '' && $webp !== $filename) {
            DelFile($dir . $webp);
        }
    }
}

if (!function_exists('crud_json_response')) {
    /** AJAX 端點：輸出 JSON 並結束（ok: 1|0） */
    function crud_json_response(bool $ok, string $msg = '', array $extra = []): never {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        $payload = array_merge(['ok' => $ok ? 1 : 0, 'msg' => $msg], $extra);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}

if (!function_exists('crud_delete_img_row')) {
    /**
     * 刪除圖檔/附件子表一列（含實體檔與 manage_history）
     * 封裝 common.php DeleteImageRow，並補齊上傳路徑全域變數
     */
    function crud_delete_img_row(string $tableImg, int $pkey, string $pkCol = 'PKey'): bool {
        if ($pkey <= 0 || $tableImg === '') {
            return false;
        }

        if (empty($GLOBALS['upload_foder']) && empty($GLOBALS['upload_folder'])) {
            $dirInfo = crud_upload_dir();
            if ($dirInfo['dir'] !== '') {
                $GLOBALS['upload_foder'] = $dirInfo['dir'];
            }
        }

        if (!function_exists('DeleteImageRow')) {
            return false;
        }

        return DeleteImageRow($tableImg, $pkey, $pkCol);
    }
}

if (!function_exists('crud_save_img_slots')) {
    /**
     * 儲存多欄位圖檔子表（paper_img 等）
     *
     * @param array<int,string> $photos   Sort => 檔名
     * @param array<int,int>    $photoW
     * @param array<int,int>    $photoH
     * @param array<int,string> $photoM   圖說
     */
    function crud_save_img_slots(
        string $tableImg,
        string $fkName,
        int $parentPKey,
        string $forderVal,
        array $photos,
        array $photoW,
        array $photoH,
        array $photoM,
        array $filter,
        int $maxSlots,
        string $uploadBase
    ): void {
        if (!function_exists('chkTable') || !chkTable($tableImg)) {
            return;
        }

        $forderVal = rtrim(trim($forderVal), "\\/");
        if ($forderVal === '') {
            $forderVal = date('Ym');
        }

        for ($i = 1; $i <= $maxSlots; $i++) {
            if (array_key_exists($i, $photoM)) {
                $pdo = new dbPDO();
                $sqlU = "UPDATE {$tableImg} SET PhotoM = :PhotoM WHERE {$fkName} = :fk AND Sort = :sort";
                $bind = [
                    'PhotoM' => SqlFilter($photoM[$i], 'tab'),
                    'fk'     => SqlFilter($parentPKey, 'int'),
                    'sort'   => SqlFilter($i, 'int'),
                ];
                $pdo->execute($sqlU, $bind);
                $err = $pdo->getErrorMessage();
                $pdo->close();
                if ($err !== '') {
                    crud_fail_db($sqlU . PHP_EOL . (function_exists('array_to_string') ? array_to_string($bind) : ''), $err, $bind, true);
                }
            }

            $layoutFilter = $filter['intType' . $i] ?? $filter['imgShow' . $i] ?? null;

            if (empty($photos[$i])) {
                if ($layoutFilter !== null) {
                    $sqlExist = "SELECT PKey FROM {$tableImg} WHERE {$fkName} = :fk AND Sort = :sort";
                    $existingLayout = crud_fetch_one($sqlExist, ['fk' => $parentPKey, 'sort' => $i]);
                    if ($existingLayout !== null) {
                        $layoutVal = manage_content_img_layout_slot_value($i, $layoutFilter);
                        $layoutRow = [];
                        if (function_exists('crud_table_has_column') && crud_table_has_column($tableImg, 'intType')) {
                            $layoutRow['intType'] = SqlFilter($layoutVal, 'int');
                        } elseif (function_exists('crud_table_has_column') && crud_table_has_column($tableImg, 'isShow')) {
                            $layoutRow['isShow'] = SqlFilter((string)$layoutVal, 'tab');
                        }
                        if ($layoutRow !== []) {
                            $pdo = new dbPDO();
                            $pdo->update($tableImg, $layoutRow, 'PKey', (int)$existingLayout['PKey']);
                            $pdo->close();
                        }
                    }
                }
                continue;
            }

            $sql = "SELECT PKey, Forder, Photo1 FROM {$tableImg} WHERE {$fkName} = :fk AND Sort = :sort";
            $existing = crud_fetch_one($sql, ['fk' => $parentPKey, 'sort' => $i]);

            $row = [
                $fkName  => SqlFilter($parentPKey, 'int'),
                'Sort'   => SqlFilter($i, 'int'),
                'Forder' => $forderVal,
                'Photo1' => $photos[$i],
                'dtDate' => date('Y-m-d H:i:s'),
            ];
            if (isset($photoW[$i])) {
                $row['PhotoW1'] = SqlFilter($photoW[$i], 'int');
            }
            if (isset($photoH[$i])) {
                $row['PhotoH1'] = SqlFilter($photoH[$i], 'int');
            }
            if (isset($photoM[$i])) {
                $row['PhotoM'] = SqlFilter($photoM[$i], 'tab');
            }
            if (isset($filter['intType' . $i]) && (int)$filter['intType' . $i] === 2
                && function_exists('crud_table_has_column') && crud_table_has_column($tableImg, 'intType')) {
                $row['intType'] = SqlFilter(2, 'int');
            } elseif (isset($filter['intType' . $i])) {
                $layoutVal = manage_content_img_layout_slot_value(
                    $i,
                    $filter['intType' . $i]
                );
                $row['intType'] = SqlFilter($layoutVal, 'int');
            } elseif (isset($filter['imgShow' . $i])) {
                $layoutVal = manage_content_img_layout_slot_value(
                    $i,
                    $filter['imgShow' . $i]
                );
                if (function_exists('crud_table_has_column') && crud_table_has_column($tableImg, 'intType')) {
                    $row['intType'] = SqlFilter($layoutVal, 'int');
                } elseif (function_exists('crud_table_has_column') && crud_table_has_column($tableImg, 'isShow')) {
                    $row['isShow'] = SqlFilter((string)$layoutVal, 'tab');
                }
            }

            if ($existing !== null) {
                crud_delete_image_variants($uploadBase, (string)$existing['Forder'], (string)$existing['Photo1']);
                $pdo = new dbPDO();
                $pdo->update($tableImg, $row, 'PKey', (int)$existing['PKey']);
                $sqlLog = $pdo->getLastSql() . "\n" . (function_exists('array_to_string') ? array_to_string($row) : '') . 'PKey=' . $existing['PKey'];
                $err = $pdo->getErrorMessage();
                $pdo->close();
                $action = '圖檔' . $i . '修改成功';
            } else {
                $pdo = new dbPDO();
                $pdo->insert($tableImg, $row);
                $childPk = (int)$pdo->getLastId();
                $sqlLog = $pdo->getLastSql() . "\n" . (function_exists('array_to_string') ? array_to_string($row) : '') . 'PKey=' . $childPk;
                $err = $pdo->getErrorMessage();
                $pdo->close();
                $action = '圖檔' . $i . '新增成功';
            }

            if ($err !== '') {
                crud_fail_db($sqlLog, $err, $row, true);
            }

            $ctx = crud_module_ctx();
            if (function_exists('manage_history')) {
                manage_history($ctx['Module_PKey'], $ctx['Module_Name'], $sqlLog, $ctx['WorkFile'], $ctx['Login_ID'], $action);
            }
        }
    }
}

if (!function_exists('crud_photo_slot_max_from_filter')) {
    /**
     * 讀取表單 PhotoSlotMax（由各模組 _detail.php 的 file 欄位數一致）
     */
    function crud_photo_slot_max_from_filter(array $filter, int $fallbackMax = 1): int
    {
        $max = (int)($filter['PhotoSlotMax'] ?? 0);
        if ($max > 0) {
            return $max;
        }

        return max(1, $fallbackMax);
    }
}

if (!function_exists('crud_resolve_photo_upload_slots')) {
    /**
     * 依 PhotoSlotMax 與 $_FILES 解析圖檔上傳欄位
     *
     * @return array{config_total:int,indices:list<int>,max_slots:int,slot_max:int}
     */
    function crud_resolve_photo_upload_slots(
        array $filter,
        array $file_array,
        int $fallbackMax = 1,
        string $fieldPrefix = 'Photo'
    ): array {
        $slotMax = crud_photo_slot_max_from_filter($filter, $fallbackMax);
        $indices = crud_detect_upload_indices($file_array, $fieldPrefix, $slotMax, $fallbackMax);
        $maxSlots = $indices !== [] ? max($indices) : $slotMax;

        return [
            'config_total' => $slotMax,
            'indices'      => $indices,
            'max_slots'    => $maxSlots,
            'slot_max'     => $slotMax,
        ];
    }
}

if (!function_exists('crud_detect_upload_indices')) {
    /**
     * 從 $_FILES 鍵名偵測上傳欄位（Photo1、Photo2…）
     *
     * @return list<int>
     */
    function crud_detect_upload_indices(
        array $file_array,
        string $fieldPrefix = 'Photo',
        int $configTotal = 0,
        int $fallbackTotal = 1
    ): array {
        $pattern = '/^' . preg_quote($fieldPrefix, '/') . '(\d+)$/';
        $indices = [];
        foreach (array_keys($file_array) as $k) {
            if (preg_match($pattern, (string)$k, $m)) {
                $indices[] = (int)$m[1];
            }
        }
        sort($indices);
        if ($indices === []) {
            $n = max(1, $configTotal > 0 ? $configTotal : $fallbackTotal);
            $indices = range(1, $n);
        }
        if ($configTotal > 0) {
            $indices = array_values(array_filter($indices, static fn(int $i): bool => $i >= 1 && $i <= $configTotal));
            if ($indices === []) {
                $indices = range(1, $configTotal);
            }
        }
        return $indices;
    }
}

if (!function_exists('crud_upload_file_slots')) {
    /**
     * 多欄位檔案上傳（圖片＋文件，knowledge 等模組）
     *
     * @param list<int> $indices
     * @return array{photos: array<int,string>, photoW: array<int,int>, photoH: array<int,int>, messages: string, monthdir: string}
     */
    function crud_upload_file_slots(
        array $file_array,
        string $uploadFolder,
        array $indices,
        array $options = []
    ): array {
        $forderPrefix  = (string)($options['forder_prefix'] ?? 'file_');
        $sizeBytes     = (int)($options['size_bytes'] ?? 6000 * 1024);
        $allowedExts   = (array)($options['allowed_exts'] ?? []);
        $allowedMimes  = (array)($options['allowed_mimes'] ?? []);
        $fieldPrefix   = (string)($options['field_prefix'] ?? 'Photo');
        $resizeThumb   = (bool)($options['resize_thumb'] ?? true);

        $forderVal = date('Ym');
        $absDir    = rtrim($uploadFolder, "/\\") . DIRECTORY_SEPARATOR . $forderVal . DIRECTORY_SEPARATOR;

        if (function_exists('makedirs')) {
            makedirs($absDir);
        } elseif (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        $photos   = [];
        $photoW   = [];
        $photoH   = [];
        $messages = '';

        foreach ($indices as $i) {
            $key = $fieldPrefix . $i;
            if (!isset($file_array[$key]) || !is_array($file_array[$key])) {
                continue;
            }

            $f         = $file_array[$key];
            $origName  = (string)($f['name'] ?? '');
            $tmpName   = (string)($f['tmp_name'] ?? '');
            $size      = (int)($f['size'] ?? 0);

            if ($origName === '' || !is_uploaded_file($tmpName)) {
                continue;
            }

            $ext = strtolower((string)pathinfo($origName, PATHINFO_EXTENSION));
            if ($ext === '' || ($allowedExts !== [] && !in_array($ext, $allowedExts, true))) {
                $messages .= "檔案 {$i}: ({$origName}) 的副檔名不允許\n";
                continue;
            }

            $mime = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = (string)finfo_file($finfo, $tmpName);
                }
            }
            if ($mime === '') {
                $mime = (string)($f['type'] ?? '');
            }
            if ($allowedMimes !== [] && ($mime === '' || !in_array($mime, $allowedMimes, true))) {
                $messages .= "檔案 {$i}: ({$origName}) 的檔案格式有誤\n";
                continue;
            }

            if ($size > $sizeBytes) {
                $messages .= '檔案 ' . $i . ': (' . $origName . ') 無法上傳，請檢查檔案是否小於 '
                    . (int)($sizeBytes / 1024) . "KB\n";
                continue;
            }

            $rand    = bin2hex(random_bytes(3));
            $newName = $forderPrefix . date('YmdHis') . $rand . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . '.' . $ext;
            $destAbs = $absDir . $newName;

            if (!move_uploaded_file($tmpName, $destAbs)) {
                $messages .= "檔案 {$i}: ({$origName}) 搬移失敗\n";
                continue;
            }
            @chmod($destAbs, 0666);

            $photos[$i] = $newName;

            $imgInfo = @getimagesize($destAbs);
            if (is_array($imgInfo)) {
                $photoW[$i] = (int)($imgInfo[0] ?? 0);
                $photoH[$i] = (int)($imgInfo[1] ?? 0);
            }

            if (function_exists('convert_webp')) {
                convert_webp($destAbs);
            } elseif (function_exists('covnert_webp')) {
                covnert_webp($destAbs);
            }

            $webpAbs = $absDir . preg_replace('/\.[^.]+$/', '.webp', $newName);
            if (is_file($webpAbs)) {
                @chmod($webpAbs, 0666);
            }

            if ($resizeThumb) {
                if (function_exists('create_image_list_thumb')) {
                    create_image_list_thumb($absDir, $newName, 150);
                } elseif (function_exists('ReSizeImg')) {
                    ReSizeImg($absDir, $newName, 150);
                }
            }
        }

        return [
            'photos'   => $photos,
            'photoW'   => $photoW,
            'photoH'   => $photoH,
            'messages' => $messages,
            'monthdir' => $forderVal,
        ];
    }
}
