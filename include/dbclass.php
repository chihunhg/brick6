<?php
declare(strict_types=1);

/**
 * PDO 單一參數綁定（recordset / dbPDO / execute_sql 共用）
 */
function db_pdo_bind_one(PDOStatement $stmt, string|int $param, mixed $val): void {
    if (is_int($val)) {
        $stmt->bindValue($param, $val, PDO::PARAM_INT);
    } elseif (is_bool($val)) {
        $stmt->bindValue($param, $val, PDO::PARAM_BOOL);
    } elseif ($val === null) {
        $stmt->bindValue($param, null, PDO::PARAM_NULL);
    } elseif (is_float($val)) {
        $stmt->bindValue($param, rtrim(rtrim(sprintf('%.15F', $val), '0'), '.'), PDO::PARAM_STR);
    } else {
        $stmt->bindValue($param, (string)$val, PDO::PARAM_STR);
    }
}

/**
 * 批次綁定預備陳述式參數（具名 :key 或位置 ? 索引）
 *
 * @param PDOStatement $stmt  已 prepare 的陳述式
 * @param array<int|string, mixed> $data  參數鍵值（整數鍵為 1-based 位置）
 */
function db_pdo_bind_values(PDOStatement $stmt, array $data): void {
    foreach ($data as $key => $val) {
        $param = is_int($key) ? ($key + 1) : (':' . ltrim((string)$key, ':'));
        db_pdo_bind_one($stmt, $param, $val);
    }
}

/**
 * SHOW … LIKE 改為 quote 內嵌（與 recordset::expandShowLike 行為一致）
 */
function db_expand_show_like_sql(PDO $db, string $sql, array &$data): string {
    if (preg_match('/^\s*SHOW\s+.+?\s+LIKE\s+:(\w+)\s*$/i', $sql, $m)) {
        $key = $m[1];
        if (array_key_exists($key, $data)) {
            $quoted = $db->quote((string)$data[$key]);
            $sql = preg_replace('/:' . preg_quote($key, '/') . '\b/', $quoted, $sql, 1);
            unset($data[$key]);
            return $sql;
        }
    }

    if (preg_match('/^\s*SHOW\s+.+?\s+LIKE\s+\?\s*$/i', $sql)) {
        if (array_key_exists(0, $data)) {
            $quoted = $db->quote((string)$data[0]);
            $sql = preg_replace('/\?/', $quoted, $sql, 1);
            unset($data[0]);
            $data = array_values($data);
            return $sql;
        }
    }

    return $sql;
}

/**
 * 資料表是否存在（information_schema；MySQL 不支援 SHOW … LIKE ? 預備陳述式）
 */
function db_pdo_table_exists(PDO $pdo, string $table): bool
{
    $table = trim($table);
    if ($table === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    try {
        $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!$db) {
            return false;
        }
        $st = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables'
            . ' WHERE table_schema = :db AND LOWER(table_name) = LOWER(:tb)'
            . ' LIMIT 1'
        );
        $st->execute([':db' => $db, ':tb' => $table]);

        return (bool)$st->fetchColumn();
    } catch (Throwable) {
        try {
            $params = [$table];
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

            return (bool)$st->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}

/**
 * SHOW TABLES LIKE 查詢（回傳表名列表）
 *
 * @return list<string>
 */
function db_pdo_show_tables_like(PDO $pdo, string $likePattern): array
{
    $likePattern = trim($likePattern);
    if ($likePattern === '') {
        return [];
    }

    try {
        $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!$db) {
            return [];
        }
        $st = $pdo->prepare(
            'SELECT table_name FROM information_schema.tables'
            . ' WHERE table_schema = :db AND table_name LIKE :pat'
        );
        $st->execute([':db' => $db, ':pat' => $likePattern]);
        $rows = $st->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_filter(array_map('strval', $rows ?: [])));
    } catch (Throwable) {
        try {
            $params = [$likePattern];
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
            $out = [];
            foreach ($rows as $row) {
                $name = (string)($row[0] ?? '');
                if ($name !== '') {
                    $out[] = $name;
                }
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }
}

/**
 * 共用：型別安全的 bindOne / bindValuesWithType
 */
trait PdoBindHelpers {
    /**
     * 綁定單一參數（依 PHP 型別選 PDO 參數型別）
     *
     * @param PDOStatement $stmt
     * @param string|int $param  占位符（:name 或 1-based 索引）
     * @param mixed $val
     */
    private function bindOne(PDOStatement $stmt, string|int $param, mixed $val): void {
        db_pdo_bind_one($stmt, $param, $val);
    }

    /**
     * 批次綁定參數陣列
     *
     * @param PDOStatement $stmt
     * @param array<int|string, mixed> $data
     */
    private function bindValuesWithType(PDOStatement $stmt, array $data): void {
        db_pdo_bind_values($stmt, $data);
    }
}

/**
 * 記憶體安全的 Recordset：支援伺服器端分頁（LIMIT/OFFSET）
 */
class recordset {
    use PdoBindHelpers;

    private ?PDO $db = null;
    private array $result = [];

    public int $record_count = 0;
    public int $position = 0;
    public bool $eof = true;
    public int $page_size = -1;

    private string $error_message = '';

    /** field() 快取：小寫欄名 => 值（與 field_cache_position 同列） */
    private array $field_cache_lower = [];
    private ?int $field_cache_position = null;

    /**
     * 執行 SQL 並載入結果集（可選分頁）
     *
     * @param string|null $sql         SELECT 等查詢語句
     * @param array<int|string, mixed> $data_array  綁定參數
     * @param int|null $page_size      每頁筆數；null 或 ≤0 表示不分頁
     * @param int $page                 頁碼（1-based）
     */
    public function __construct(?string $sql = null, array $data_array = [], ?int $page_size = null, int $page = 1) {
        try {
            $this->db = sql_conn();
            if ($this->db === null) {
                $this->setError('資料庫連線失敗，無法執行查詢。');
                error_log("[DB ERROR] recordset 建構時無法取得資料庫連線。SQL: " . ($sql ?? 'NULL'));
                $this->resetState();
                return;
            }

            $this->page_size = ($page_size !== null && $page_size > 0) ? $page_size : -1;
            $page = max(1, $page);

            if ($sql) {
                if (function_exists('crud_guard_sql_or_empty') && !crud_guard_sql_or_empty($sql, $data_array)) {
                    $this->resetState();
                    return;
                }

                $sql = $this->expandShowLike($sql, $data_array);

                if ($this->isShowQuery($sql)) {
                    $stmt = $this->db->prepare($sql);
                    $this->bindValuesWithType($stmt, $data_array);
                    $stmt->execute();
                    $this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->record_count = count($this->result);
                    $this->position = 0;
                    $this->eof = ($this->record_count === 0);
                } elseif ($this->page_size > 0) {
                    $countSql = "SELECT COUNT(*) AS c FROM (" . $sql . ") _t";
                    $stmt = $this->db->prepare($countSql);
                    $this->bindValuesWithType($stmt, $data_array);
                    $stmt->execute();
                    $this->record_count = (int)$stmt->fetchColumn();

                    $offset = ($page - 1) * $this->page_size;
                    $pagedSql = $this->inlineLimitOffset($sql, $this->page_size, $offset);

                    $stmt = $this->db->prepare($pagedSql);
                    $this->bindValuesWithType($stmt, $data_array);
                    $stmt->execute();
                    $this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->position = 0;
                    $this->eof = (count($this->result) === 0);
                } else {
                    $stmt = $this->db->prepare($sql);
                    $this->bindValuesWithType($stmt, $data_array);
                    $stmt->execute();
                    $this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->record_count = count($this->result);
                    $this->position = 0;
                    $this->eof = ($this->record_count === 0);
                }
            } else {
                $this->resetState();
            }
        } catch (Throwable $e) {
            $this->setError('資料庫錯誤：' . $e->getMessage());
            error_log("[DB ERROR] recordset 查詢錯誤：" . $e->getMessage());
            $this->resetState();
        }
    }

    /** 判斷是否為 SHOW 語句（需特殊 LIKE 處理） */
    private function isShowQuery(string $sql): bool {
        return (bool)preg_match('/^\s*SHOW\s+/i', $sql);
    }

    /**
     * 將 SHOW … LIKE 占位符改為 quote 內嵌
     *
     * @param array<int|string, mixed> $data  綁定參數（可能被移除已內嵌的鍵）
     */
    private function expandShowLike(string $sql, array &$data): string {
        if ($this->db === null) {
            return $sql;
        }
        return db_expand_show_like_sql($this->db, $sql, $data);
    }

    /** 移除尾端 LIMIT/OFFSET 占位符並內嵌數值分頁子句 */
    private function inlineLimitOffset(string $sql, ?int $limit, ?int $offset): string {
        if ($limit === null || $limit < 0) return $sql;
        $offset = max(0, (int)($offset ?? 0));
        $limit  = max(0, (int)$limit);

        $sql = preg_replace('/\s+LIMIT\s+:[\w]+(\s+OFFSET\s+:[\w]+)?\s*$/i', '', $sql);
        return $sql . " LIMIT {$limit} OFFSET {$offset}";
    }

    /** 重設結果集、游標與欄位快取為空狀態 */
    private function resetState(): void {
        $this->field_cache_lower = [];
        $this->field_cache_position = null;
        $this->eof = true;
        $this->result = [];
        $this->record_count = 0;
        $this->position = 0;
        $this->page_size = $this->page_size > 0 ? $this->page_size : -1;
    }

    /** 設定每頁筆數（≤0 表示不分頁） */
    public function setPageSize(int $size): void {
        $this->page_size = $size > 0 ? $size : -1;
    }

    /** 將游標移至指定頁首筆（需已啟用分頁） */
    public function absolutepage(int $page): void {
        if ($this->page_size <= 0) {
            $this->position = 0;
            $this->eof = ($this->record_count === 0);
            return;
        }
        $this->position = $this->page_size * (max(1, $page) - 1);
        $this->eof = ($this->position >= $this->record_count);
    }

    /** 計算總頁數（未分頁時回傳 0） */
    public function page_count(): int {
        if ($this->page_size <= 0) return 0;
        $q = intdiv($this->record_count, $this->page_size);
        return ($this->record_count % $this->page_size > 0) ? $q + 1 : $q;
    }

    /** 將游標移至指定列索引（0-based） */
    public function move(int $position): void {
        $this->position = max(0, $position);
        $this->eof = ($this->position >= $this->record_count);
    }

    /** 游標移至下一列 */
    public function movenext(): void {
        $this->position++;
        $this->eof = ($this->position >= $this->record_count);
    }

    /** 游標回到第一列 */
    public function movefirst(): void {
        $this->position = 0;
        $this->eof = ($this->record_count === 0);
    }

    /**
     * 建立目前列之欄名小寫對照（同一 position 只算一次）
     */
    private function ensureFieldCache(): void {
        if ($this->eof || !isset($this->result[$this->position])) {
            $this->field_cache_lower = [];
            $this->field_cache_position = null;
            return;
        }
        if ($this->field_cache_position === $this->position) {
            return;
        }
        $line = $this->result[$this->position];
        $this->field_cache_lower = array_change_key_case($line, CASE_LOWER);
        $this->field_cache_position = $this->position;
    }

    /** 取得目前列欄位值（欄名不區分大小寫） */
    public function field(string $field_name): mixed {
        if ($this->eof || !isset($this->result[$this->position])) {
            return null;
        }
        $this->ensureFieldCache();
        $target = strtolower($field_name);
        return $this->field_cache_lower[$target] ?? null;
    }

    /** 取得最近一次錯誤訊息 */
    public function getErrorMessage(): string {
        return $this->error_message;
    }

    /** 設定錯誤訊息 */
    private function setError(string $msg): void {
        $this->error_message = $msg;
    }

    /** 釋放 PDO 連線參考 */
    public function __destruct() {
        $this->db = null;
    }

    /** 手動關閉並釋放 PDO 連線參考 */
    public function close(): void {
        $this->db = null;
    }
}

/**
 * DB 包裝：交易、欄位快取 TTL、driver-aware 欄位列表、彈性 WHERE/ORDER Builder、斷線重試
 */
class dbPDO {
    use PdoBindHelpers;

    private ?PDO $db = null;
    private string $last_sql = '';
    private int|string|null $last_id = null;
    private int $last_num_rows = 0;

    private string $error_message = '';
    private string|int|null $error_code = null;
    private ?array $error_info = null;

    private static array $tableColumnsCache = [];
    private static array $tableColumnsCacheAt = [];

    /** 若為 true，表示使用外部傳入的 PDO，重連時不可改換連線。 */
    private bool $pdoInjected = false;

    /**
     * 建立 dbPDO（可注入外部 PDO 或自動 sql_conn）
     *
     * @param PDO|null $pdo  外部連線；null 時自動建立
     */
    public function __construct(?PDO $pdo = null) {
        try {
            if ($pdo instanceof PDO) {
                $this->db = $pdo;
                $this->pdoInjected = true;
                return;
            }
            $this->db = sql_conn();
            if ($this->db === null) {
                throw new RuntimeException("無法建立資料庫連線。");
            }
        } catch (Throwable $e) {
            $this->error_message = '資料庫錯誤：' . $e->getMessage();
            error_log("[DB ERROR] 建構時失敗：" . $e->getMessage());
        }
    }

    /* =========================
       交易管理
    ========================= */

    /** 是否處於交易中 */
    public function inTransaction(): bool {
        try {
            return ($this->db instanceof PDO) ? $this->db->inTransaction() : false;
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            return false;
        }
    }

    /** 開始交易（已在交易中則略過） */
    public function begin(): bool {
        try {
            if (!($this->db instanceof PDO)) {
                $this->error_message = '資料庫未連線，無法開始交易。';
                return false;
            }

            if ($this->db->inTransaction()) {
                return true;
            }

            return $this->db->beginTransaction();
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            $this->error_code = $e->getCode();
            error_log("[DB ERROR] begin() 失敗：" . $e->getMessage());
            return false;
        }
    }

    /** 提交交易 */
    public function commit(): bool {
        try {
            if (!($this->db instanceof PDO)) {
                $this->error_message = '資料庫未連線，無法提交交易。';
                return false;
            }

            if (!$this->db->inTransaction()) {
                return false;
            }

            return $this->db->commit();
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            $this->error_code = $e->getCode();
            error_log("[DB ERROR] commit() 失敗：" . $e->getMessage());
            return false;
        }
    }

    /** 回滾交易 */
    public function rollBack(): bool {
        try {
            if (!($this->db instanceof PDO)) {
                $this->error_message = '資料庫未連線，無法回滾交易。';
                return false;
            }

            if (!$this->db->inTransaction()) {
                return false;
            }

            return $this->db->rollBack();
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            $this->error_code = $e->getCode();
            error_log("[DB ERROR] rollBack() 失敗：" . $e->getMessage());
            return false;
        }
    }

    /**
     * 在交易中執行 callback（自動 begin/commit/rollBack）
     *
     * @param callable(dbPDO): mixed $callback
     * @return mixed callback 回傳值
     */
    public function transaction(callable $callback): mixed {
        $startedHere = false;

        try {
            if (!$this->inTransaction()) {
                if (!$this->begin()) {
                    throw new RuntimeException('無法開始 transaction。');
                }
                $startedHere = true;
            }

            $result = $callback($this);

            if ($startedHere) {
                if (!$this->commit()) {
                    throw new RuntimeException('transaction commit 失敗。');
                }
            }

            return $result;
        } catch (Throwable $e) {
            if ($startedHere && $this->inTransaction()) {
                $this->rollBack();
            }

            $this->error_message = $e->getMessage();
            $this->error_code = $e->getCode();
            error_log("[DB ERROR] transaction() 失敗：" . $e->getMessage());

            throw $e;
        }
    }

    /** 操作失敗時拋出 RuntimeException */
    public function must(bool $ok, string $message = '資料庫操作失敗'): void {
        if ($ok) {
            return;
        }

        $err = trim($this->error_message);
        if ($err !== '') {
            throw new RuntimeException($message . '：' . $err);
        }

        throw new RuntimeException($message);
    }

    /* =========================
       輔助
    ========================= */

    /** 驗證識別字是否為安全欄位/表名格式 */
    private function isSafeName(string $name): bool {
        $name = trim($name);
        return (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }

    /** 依 driver 加上引號（MySQL `、PostgreSQL "） */
    private function quoteIdent(string $name): string {
        if (!$this->isSafeName($name)) {
            throw new Exception("不合法識別字串：{$name}");
        }
        if ($this->db === null) return "`{$name}`";
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            return '"' . $name . '"';
        }
        return '`' . $name . '`';
    }

    /** 讀取 DB_SCHEMA_CACHE_TTL（預設 300 秒） */
    private function getSchemaCacheTtl(): int {
        $raw = getenv('DB_SCHEMA_CACHE_TTL') ?: ($_ENV['DB_SCHEMA_CACHE_TTL'] ?? '300');
        $ttl = (int)$raw;
        return $ttl > 0 ? $ttl : 300;
    }

    /** 取得目前資料庫名稱（MySQL / PostgreSQL） */
    private function dbNameOrEmpty(): string {
        if ($this->db === null) return '';
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $v = $this->db->query('SELECT DATABASE()')->fetchColumn();
                return is_string($v) ? $v : '';
            }
            if ($driver === 'pgsql') {
                $v = $this->db->query('SELECT current_database()')->fetchColumn();
                return is_string($v) ? $v : '';
            }
            return '';
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * 取得資料表欄位集合（含 TTL 快取）
     *
     * @return array<string, int>  欄名 => 索引（供 isset 查詢）
     */
    private function getTableColumns(string $table): array {
        $table = trim($table);
        if (!$this->isSafeName($table)) {
            throw new Exception("不合法資料表名稱：$table");
        }
        if ($this->db === null) {
            throw new Exception("資料庫未連線，無法取得欄位資訊。");
        }

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dbname = $this->dbNameOrEmpty();
        $cacheKey = $driver . '|' . $dbname . '|' . $table;
        $ttl = $this->getSchemaCacheTtl();
        $now = time();

        if (isset(self::$tableColumnsCache[$cacheKey], self::$tableColumnsCacheAt[$cacheKey])
            && ($now - self::$tableColumnsCacheAt[$cacheKey] < $ttl)) {
            return self::$tableColumnsCache[$cacheKey];
        }

        $cols = [];
        if ($driver === 'mysql') {
            $stmt = $this->db->prepare("DESCRIBE " . $this->quoteIdent($table));
            $stmt->execute();
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } elseif ($driver === 'sqlite') {
            $stmt = $this->db->prepare("PRAGMA table_info(" . $this->quoteIdent($table) . ")");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $cols = array_map(static fn($r) => $r['name'] ?? '', $rows);
        } else {
            $stmt = $this->db->prepare("
                SELECT column_name
                FROM information_schema.columns
                WHERE table_name = :t
            ");
            $stmt->execute([':t' => $table]);
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        $cols = array_values(array_filter(array_map('strval', $cols)));
        $set  = array_flip($cols);

        self::$tableColumnsCache[$cacheKey] = $set;
        self::$tableColumnsCacheAt[$cacheKey] = $now;
        return $set;
    }

    /** 確認指定欄位存在於資料表 */
    private function assertColumnsExist(string $table, array $columns): void {
        if (empty($columns)) return;
        $tableCols = $this->getTableColumns($table);
        foreach ($columns as $c) {
            $c = trim($c);
            if (!$this->isSafeName($c) || !isset($tableCols[$c])) {
                throw new Exception("資料表 `$table` 無此欄位：$c");
            }
        }
    }

    /**
     * 由條件陣列組 WHERE 子句與綁定參數
     *
     * @param array<string, mixed> $conds  欄位 => 值或 ['IN'=>[], 'LIKE'=>, 'BETWEEN'=>[], 'IS'=>null]
     * @return array{sql: string, params: array<string, mixed>}
     */
    private function buildWhere(array $conds): array {
        $Q = fn(string $id) => $this->quoteIdent($id);
        $parts  = [];
        $params = [];
        foreach ($conds as $col => $val) {
            if (!$this->isSafeName((string)$col)) {
                throw new Exception("不合法欄位名稱：$col");
            }
            $phBase = ":w_" . $col;

            if (is_array($val)) {
                if (isset($val['IN']) && is_array($val['IN'])) {
                    $inPh = [];
                    foreach (array_values($val['IN']) as $i => $v) {
                        $ph = "{$phBase}_in{$i}";
                        $inPh[] = $ph;
                        $params[$ph] = $v;
                    }
                    if (empty($inPh)) {
                        $parts[] = "1=0";
                    } else {
                        $parts[] = $Q($col) . " IN (" . implode(',', $inPh) . ")";
                    }
                } elseif (array_key_exists('LIKE', $val)) {
                    $ph = "{$phBase}_like";
                    $parts[] = $Q($col) . " LIKE $ph";
                    $params[$ph] = $val['LIKE'];
                } elseif (isset($val['BETWEEN']) && is_array($val['BETWEEN']) && count($val['BETWEEN']) === 2) {
                    $ph1 = "{$phBase}_b1";
                    $ph2 = "{$phBase}_b2";
                    $parts[] = $Q($col) . " BETWEEN $ph1 AND $ph2";
                    $params[$ph1] = array_values($val['BETWEEN'])[0];
                    $params[$ph2] = array_values($val['BETWEEN'])[1];
                } elseif (array_key_exists('IS', $val)) {
                    if ($val['IS'] === null) {
                        $parts[] = $Q($col) . " IS NULL";
                    } else {
                        $parts[] = $Q($col) . " IS NOT NULL";
                    }
                } else {
                    throw new Exception("不支援的條件格式：$col");
                }
            } else {
                $parts[] = $Q($col) . " = $phBase";
                $params[$phBase] = $val;
            }
        }
        $sql = $parts ? (' WHERE ' . implode(' AND ', $parts)) : '';
        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * 組 ORDER BY 子句
     *
     * @param list<array{col: string, desc?: bool}> $orders
     */
    public function buildOrderBy(array $orders, ?string $table = null): string {
        if (empty($orders)) return '';
        if ($table !== null) {
            $this->assertColumnsExist($table, array_map(fn($o) => $o['col'], $orders));
        } else {
            foreach ($orders as $o) {
                if (!$this->isSafeName($o['col'])) {
                    throw new Exception("不合法排序欄位：" . $o['col']);
                }
            }
        }
        $Q = fn(string $id) => $this->quoteIdent($id);
        $parts = [];
        foreach ($orders as $o) {
            $dir = (!empty($o['desc'])) ? ' DESC' : ' ASC';
            $parts[] = $Q($o['col']) . $dir;
        }
        return ' ORDER BY ' . implode(', ', $parts);
    }

    /** 判斷 PDO 例外是否為連線中斷（可重試） */
    private function shouldRetry(PDOException $e): bool {
        $msg = strtolower($e->getMessage());
        if (str_contains($msg, 'server has gone away') || str_contains($msg, 'lost connection')) return true;

        $code = (string)$e->getCode();
        if (in_array($code, ['2006','2013'], true)) return true;

        $sqlstate = $e->errorInfo[0] ?? '';
        if (in_array($sqlstate, ['HY000','08S01'], true)) return true;
        return false;
    }

    /** 嘗試重新建立連線（外部注入 PDO 時不重連） */
    private function tryReconnect(): bool {
        if ($this->pdoInjected) {
            return false;
        }
        try {
            $this->db = sql_conn();
            return $this->db !== null;
        } catch (Throwable $e) {
            error_log("[DB ERROR] 重連失敗：" . $e->getMessage());
            return false;
        }
    }

    /**
     * 執行 DB 操作並在連線中斷時重試一次
     *
     * @param callable(PDO): mixed $runner
     * @return mixed|false
     */
    private function runWithRetry(callable $runner, string $context) {
        if ($this->db === null) {
            $this->error_message = '資料庫未連線。';
            return false;
        }
        try {
            return $runner($this->db);
        } catch (PDOException $e) {
            if ($this->shouldRetry($e) && $this->tryReconnect()) {
                try {
                    return $runner($this->db);
                } catch (PDOException $e2) {
                    $this->handlePdoException($e2, $context . ' retry');
                    return false;
                }
            }
            $this->handlePdoException($e, $context);
            return false;
        }
    }

    /* =========================
       CRUD
    ========================= */

    /**
     * INSERT 一筆資料
     *
     * @param array<string, mixed> $data
     */
    public function insert(?string $table = null, array $data = []): bool {
        if ($this->db === null || !$table || empty($data)) return false;
        $table = trim($table);
        if (!$this->isSafeName($table)) throw new Exception("不合法資料表名稱：$table");
        foreach (array_keys($data) as $key) {
            if (!$this->isSafeName((string)$key)) throw new Exception("不合法欄位名稱：$key");
        }
        $this->assertColumnsExist($table, array_keys($data));

        $Q = fn(string $id) => $this->quoteIdent($id);
        $columns = implode(',', array_map(static fn($col) => $Q($col), array_keys($data)));
        $placeholders = ':' . implode(',:', array_keys($data));
        $this->last_sql = "INSERT INTO " . $Q($table) . " ($columns) VALUES ($placeholders)";

        $runner = function(PDO $conn) use ($data): bool {
            $stmt = $conn->prepare($this->last_sql);
            $this->bindValuesWithType($stmt, $data);
            $stmt->execute();
            $this->last_id = $conn->lastInsertId();
            $this->last_num_rows = $stmt->rowCount();
            return true;
        };
        $ret = $this->runWithRetry($runner, 'insert()');
        return $ret === false ? false : true;
    }

    /**
     * 依主鍵 UPDATE（$add 已停用，請用 updateWhere）
     *
     * @param array<string, mixed>|null $data
     */
    public function update(?string $table = null, ?array $data = null, ?string $key_column = null, mixed $id = null, string $add = ""): bool {
        if ($this->db === null || !$table || !$data || !$key_column || $id === null) return false;
        if (trim($add) !== '') {
            throw new Exception("安全性考量：update() 不再允許使用自訂 SQL 片段（\$add）。請改用 updateWhere()。");
        }

        $table = trim($table);
        $key_column = trim($key_column);
        if (!$this->isSafeName($table))      throw new Exception("不合法資料表名稱：$table");
        if (!$this->isSafeName($key_column)) throw new Exception("不合法主鍵欄位名稱：$key_column");
        foreach (array_keys($data) as $k) {
            if (!$this->isSafeName((string)$k)) throw new Exception("不合法欄位名稱：$k");
        }

        $this->assertColumnsExist($table, array_keys($data));
        $this->assertColumnsExist($table, [$key_column]);

        $Q = fn(string $id) => $this->quoteIdent($id);
        $set_clause = [];
        foreach ($data as $k => $_) $set_clause[] = $Q($k) . " = :set_$k";

        $this->last_sql = "UPDATE " . $Q($table) . " SET " . implode(',', $set_clause) . " WHERE " . $Q($key_column) . " = :_pk";

        $runner = function(PDO $conn) use ($data, $id): bool {
            $stmt = $conn->prepare($this->last_sql);
            foreach ($data as $k => $v) $this->bindOne($stmt, ":set_$k", $v);
            $this->bindOne($stmt, ':_pk', $id);
            $stmt->execute();
            $this->last_num_rows = $stmt->rowCount();
            return true;
        };
        $ret = $this->runWithRetry($runner, 'update()');
        return $ret === false ? false : true;
    }

    /**
     * 依 WHERE 條件 UPDATE
     *
     * @param array<string, mixed>|null $data
     * @param array<string, mixed>|null $where
     */
    public function updateWhere(?string $table = null, ?array $data = null, ?array $where = null): bool {
        if ($this->db === null || !$table || !$data || empty($where)) return false;

        $table = trim($table);
        if (!$this->isSafeName($table)) throw new Exception("不合法資料表名稱：$table");
        foreach (array_keys($data) as $k) {
            if (!$this->isSafeName((string)$k)) throw new Exception("不合法欄位名稱：$k");
        }
        $this->assertColumnsExist($table, array_keys($data));
        $this->assertColumnsExist($table, array_keys($where));

        $Q = fn(string $id) => $this->quoteIdent($id);
        $set_clause = [];
        foreach ($data as $k => $_) $set_clause[] = $Q($k) . " = :set_$k";

        $wb = $this->buildWhere($where);
        $sql = "UPDATE " . $Q($table) . " SET " . implode(',', $set_clause) . $wb['sql'];
        $this->last_sql = $sql;

        $runner = function(PDO $conn) use ($data, $wb): bool {
            $stmt = $conn->prepare($this->last_sql);
            foreach ($data as $k => $v) $this->bindOne($stmt, ":set_$k", $v);
            foreach ($wb['params'] as $ph => $v) $this->bindOne($stmt, $ph, $v);
            $stmt->execute();
            $this->last_num_rows = $stmt->rowCount();
            return true;
        };
        $ret = $this->runWithRetry($runner, 'updateWhere()');
        return $ret === false ? false : true;
    }

    /** 依主鍵 DELETE 一筆 */
    public function delete(?string $table = null, ?string $key_column = null, mixed $value = null): bool {
        if ($this->db === null || !$table || !$key_column || $value === null) return false;

        $table = trim($table);
        $key_column = trim($key_column);
        if (!$this->isSafeName($table))      throw new Exception("不合法的資料表名稱：$table");
        if (!$this->isSafeName($key_column)) throw new Exception("不合法的欄位名稱：$key_column");

        $this->assertColumnsExist($table, [$key_column]);

        $Q = fn(string $id) => $this->quoteIdent($id);
        $this->last_sql = "DELETE FROM " . $Q($table) . " WHERE " . $Q($key_column) . " = :val";

        $runner = function(PDO $conn) use ($value): bool {
            $stmt = $conn->prepare($this->last_sql);
            $this->bindOne($stmt, ':val', $value);
            $stmt->execute();
            $this->last_num_rows = $stmt->rowCount();
            return true;
        };
        $ret = $this->runWithRetry($runner, 'delete()');
        return $ret === false ? false : true;
    }

    /**
     * 依條件 DELETE（至少需一個條件）
     *
     * @param array<string, mixed> $conditions
     */
    public function deleteWithConditions(?string $table = null, array $conditions = []): bool {
        if ($this->db === null || !$table || empty($conditions)) return false;

        $table = trim($table);
        if (!$this->isSafeName($table)) throw new Exception("不合法的資料表名稱：$table");
        $this->assertColumnsExist($table, array_keys($conditions));

        $Q = fn(string $id) => $this->quoteIdent($id);
        $wb = $this->buildWhere($conditions);
        if ($wb['sql'] === '') {
            throw new Exception('刪除操作需要至少一個條件。');
        }

        $this->last_sql = "DELETE FROM " . $Q($table) . $wb['sql'];

        $runner = function(PDO $conn) use ($wb): bool {
            $stmt = $conn->prepare($this->last_sql);
            foreach ($wb['params'] as $ph => $v) $this->bindOne($stmt, $ph, $v);
            $stmt->execute();
            $this->last_num_rows = $stmt->rowCount();
            return true;
        };
        $ret = $this->runWithRetry($runner, 'deleteWithConditions()');
        return $ret === false ? false : true;
    }

    /**
     * 執行 SELECT 等查詢並回傳關聯陣列列
     *
     * @param array<int|string, mixed> $data
     * @return list<array<string, mixed>>|false
     */
    public function execute(?string $sql = null, array $data = []): array|false {
        if ($this->db === null) {
            $this->error_message = '資料庫未連線，execute 無法執行。';
            error_log("[DB ERROR] execute() 失敗，db 為 null。SQL: " . ($sql ?? 'NULL'));
            return false;
        }
        if (!$sql) return [];

        $this->last_sql = $sql;

        $runner = function(PDO $conn) use ($data) {
            $stmt = $conn->prepare($this->last_sql);
            $this->bindValuesWithType($stmt, $data);
            $stmt->execute();
            $this->last_num_rows = $stmt->rowCount();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        };
        $ret = $this->runWithRetry($runner, 'execute()');
        return $ret === false ? false : $ret;
    }

    /** execute 別名 */
    public function query(?string $sql = null, array $data = []): array|false {
        return $this->execute($sql, $data);
    }

    /* =========================
       狀態與錯誤
    ========================= */

    /** 取得最近一次執行的 SQL */
    public function getLastSql(): string {
        return $this->last_sql;
    }

    /** 取得最近一次 INSERT 的自增 ID */
    public function getLastId(): int|string|null {
        return $this->last_id;
    }

    /** 取得最近一次操作影響列數 */
    public function getLastNumRows(): int {
        return $this->last_num_rows;
    }

    /** 取得錯誤訊息字串 */
    public function getErrorMessage(): string {
        return $this->error_message;
    }

    /**
     * 取得完整錯誤資訊
     *
     * @return array{message: string, code: string|int|null, info: array|null, last_sql: string}
     */
    public function getError(): array {
        return [
            'message' => $this->error_message,
            'code'    => $this->error_code,
            'info'    => $this->error_info,
            'last_sql'=> $this->last_sql,
        ];
    }

    /** 記錄 PDO 例外至內部狀態與 error_log */
    private function handlePdoException(PDOException $e, string $context): void {
        $this->error_message = "資料庫錯誤：{$e->getMessage()}";
        $this->error_info = $e->errorInfo ?? null;
        $this->error_code = $e->getCode();
        error_log("[DB ERROR] {$context} 例外：" . $e->getMessage());
    }

    /** 釋放 PDO 連線參考 */
    public function __destruct() {
        $this->db = null;
    }

    /** 手動關閉並釋放 PDO 連線參考 */
    public function close(): void {
        $this->db = null;
    }
}

/**
 * 簡易執行 SQL 並回傳結果（獨立連線，無 dbPDO 狀態）
 *
 * @param array<int|string, mixed> $data  綁定參數
 * @return list<array<string, mixed>>|false
 */
function execute_sql(?string $sql = null, array $data = []): array|false {
    try {
        $conn = sql_conn();
        if ($conn === null) {
            error_log("[DB ERROR] execute_sql() 無法建立資料庫連線。SQL: " . ($sql ?? 'NULL'));
            return false;
        }
        if (!$sql) return [];

        $sql = db_expand_show_like_sql($conn, $sql, $data);

        $stmt = $conn->prepare($sql);
        db_pdo_bind_values($stmt, $data);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[DB ERROR] execute_sql() 例外：" . $e->getMessage());
        return false;
    }
}