<?php
declare(strict_types=1);

/**
 * Gemini token 用量：每次產文寫入明細，可依單元與日期查詢。
 */

if (!function_exists('gemini_token_usage_normalize_module')) {
    function gemini_token_usage_normalize_module(string $raw): string
    {
        $raw = strtolower(trim($raw));
        $raw = preg_replace('/[^a-z0-9_]/', '', $raw) ?? '';

        return substr($raw, 0, 64);
    }
}

if (!function_exists('gemini_token_usage_module_from_table')) {
    /** 資料表名（如 dbpaper）轉成單元代碼 paper */
    function gemini_token_usage_module_from_table(string $table): string
    {
        $table = strtolower(trim($table));
        if (str_starts_with($table, 'db') && strlen($table) > 2) {
            $table = substr($table, 2);
        }

        return gemini_token_usage_normalize_module($table);
    }
}

if (!function_exists('gemini_token_usage_bind_module')) {
    /** 同一請求後續的 Gemini 呼叫沿用此單元（語意搜尋等） */
    function gemini_token_usage_bind_module(string $module): void
    {
        $module = gemini_token_usage_normalize_module($module);
        if ($module !== '') {
            $GLOBALS['gemini_token_usage_module'] = $module;
        }
    }
}

if (!function_exists('gemini_token_usage_resolve_module')) {
    /**
     * 單元代碼：請求參數、已綁定單元、或 Referer 的 /manage/{單元}/
     *
     * @param array<string, mixed> $input
     */
    function gemini_token_usage_resolve_module(array $input = []): string
    {
        $fromInput = gemini_token_usage_normalize_module((string)($input['module'] ?? ''));
        if ($fromInput !== '') {
            return $fromInput;
        }

        $bound = gemini_token_usage_normalize_module((string)($GLOBALS['gemini_token_usage_module'] ?? ''));
        if ($bound !== '') {
            return $bound;
        }

        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if (preg_match('#/manage/([a-z0-9_]+)/#i', $referer, $match) === 1) {
            $module = gemini_token_usage_normalize_module($match[1]);
            if ($module !== '' && $module !== 'control') {
                return $module;
            }
        }

        return 'unknown';
    }
}

if (!function_exists('gemini_token_usage_feature_from_script')) {
    function gemini_token_usage_feature_from_script(): string
    {
        $base = strtolower(basename((string)($_SERVER['SCRIPT_NAME'] ?? '')));

        return match ($base) {
            'generate_editor.php' => 'editor',
            'generate_tdk.php' => 'tdk',
            'generate_summary.php' => 'summary',
            'generate_content_tdk.php' => 'content_tdk',
            default => 'generate',
        };
    }
}

if (!function_exists('gemini_token_usage_module_label')) {
    function gemini_token_usage_module_label(string $module): string
    {
        return match ($module) {
            'paper' => '專欄',
            'news' => '最新消息',
            'product' => '產品',
            'album' => '相簿',
            'album_d' => '相簿明細',
            'faq' => '問答',
            'faqdemo' => '問答示範',
            'company' => '公司',
            'investor' => '投資人',
            'video' => '影音',
            'filedown' => '檔案下載',
            'history' => '沿革',
            'weblink' => '相關連結',
            'question' => '問卷',
            'ad' => '廣告',
            'member' => '會員',
            'class1' => '分類一',
            'class2' => '分類二',
            'class3' => '分類三',
            'module' => '模組',
            'module2' => '模組二',
            'semantic' => '語意搜尋',
            'unknown' => '未標示單元',
            default => $module,
        };
    }
}

if (!function_exists('gemini_token_usage_feature_label')) {
    function gemini_token_usage_feature_label(string $feature): string
    {
        return match ($feature) {
            'editor' => '內文',
            'tdk' => 'TDK',
            'summary' => '搜尋摘要',
            'content_tdk' => '內文與 TDK',
            'search' => '搜尋擴詞',
            default => $feature,
        };
    }
}

if (!function_exists('gemini_token_usage_pdo')) {
    function gemini_token_usage_pdo(): ?PDO
    {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            return $GLOBALS['pdo'];
        }
        if (!function_exists('sql_conn')) {
            $conn = dirname(__DIR__) . '/include/Conn.php';
            if (is_file($conn)) {
                require_once $conn;
            }
        }
        if (!function_exists('sql_conn')) {
            return null;
        }

        $pdo = sql_conn();

        return $pdo instanceof PDO ? $pdo : null;
    }
}

if (!function_exists('gemini_token_usage_ensure_table')) {
    function gemini_token_usage_ensure_table(PDO $pdo): bool
    {
        static $ready = false;
        if ($ready) {
            return true;
        }

        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `gemini_token_usage` (
    `PKey` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dtDate` DATETIME NOT NULL,
    `strModule` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `strFeature` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `strModel` VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `intPromptTokens` INT UNSIGNED NOT NULL DEFAULT 0,
    `intCandidatesTokens` INT UNSIGNED NOT NULL DEFAULT 0,
    `intThoughtsTokens` INT UNSIGNED NOT NULL DEFAULT 0,
    `intTotalTokens` INT UNSIGNED NOT NULL DEFAULT 0,
    `strLogin` VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `strSuccess` CHAR(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`PKey`) USING BTREE,
    INDEX `idx_gemini_token_date_module` (`dtDate`, `strModule`) USING BTREE,
    INDEX `idx_gemini_token_module_date` (`strModule`, `dtDate`) USING BTREE
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        try {
            $pdo->exec($sql);
            $ready = true;
        } catch (Throwable $e) {
            error_log('[gemini_token_usage] create table: ' . $e->getMessage());

            return false;
        }

        return true;
    }
}

if (!function_exists('gemini_token_usage_record')) {
    /**
     * 寫入一筆 token 明細。失敗只記 log，不影響產文。
     *
     * @param array{module?: string, feature?: string, model?: string, success?: bool} $context
     */
    function gemini_token_usage_record(?\Gemini\Data\UsageMetadata $usage, array $context = []): void
    {
        if (!$usage instanceof \Gemini\Data\UsageMetadata || $usage->totalTokenCount <= 0) {
            return;
        }

        try {
            $pdo = gemini_token_usage_pdo();
            if (!$pdo instanceof PDO || !gemini_token_usage_ensure_table($pdo)) {
                return;
            }

            $module = gemini_token_usage_normalize_module((string)($context['module'] ?? ''));
            if ($module === '') {
                $module = gemini_token_usage_resolve_module();
            }
            $feature = strtolower(trim((string)($context['feature'] ?? '')));
            $feature = preg_replace('/[^a-z0-9_]/', '', $feature) ?? '';
            if ($feature === '') {
                $feature = gemini_token_usage_feature_from_script();
            }
            $model = substr(trim((string)($context['model'] ?? '')), 0, 80);
            if ($model === '' && function_exists('gemini_generative_model_id')) {
                $model = substr(gemini_generative_model_id(), 0, 80);
            }
            $login = substr(trim((string)($_SESSION['Login_ID'] ?? '')), 0, 80);
            $success = ($context['success'] ?? true) ? 'Y' : 'N';

            $stmt = $pdo->prepare(
                'INSERT INTO gemini_token_usage
                    (dtDate, strModule, strFeature, strModel, intPromptTokens, intCandidatesTokens, intThoughtsTokens, intTotalTokens, strLogin, strSuccess)
                 VALUES
                    (:dtDate, :strModule, :strFeature, :strModel, :intPromptTokens, :intCandidatesTokens, :intThoughtsTokens, :intTotalTokens, :strLogin, :strSuccess)'
            );
            $stmt->execute([
                ':dtDate' => date('Y-m-d H:i:s'),
                ':strModule' => $module,
                ':strFeature' => substr($feature, 0, 32),
                ':strModel' => $model,
                ':intPromptTokens' => max(0, $usage->promptTokenCount),
                ':intCandidatesTokens' => max(0, (int)($usage->candidatesTokenCount ?? 0)),
                ':intThoughtsTokens' => max(0, (int)($usage->thoughtsTokenCount ?? 0)),
                ':intTotalTokens' => max(0, $usage->totalTokenCount),
                ':strLogin' => $login,
                ':strSuccess' => $success,
            ]);
        } catch (Throwable $e) {
            error_log('[gemini_token_usage] insert: ' . $e->getMessage());
        }
    }
}

if (!function_exists('gemini_token_usage_date_range')) {
    /**
     * @return array{0: string, 1: string} [from Y-m-d, to Y-m-d]（含結束日）
     */
    function gemini_token_usage_date_range(string $from, string $to): array
    {
        $today = date('Y-m-d');
        $fromOk = DateTimeImmutable::createFromFormat('Y-m-d', $from);
        $toOk = DateTimeImmutable::createFromFormat('Y-m-d', $to);
        $fromDate = ($fromOk instanceof DateTimeImmutable && $fromOk->format('Y-m-d') === $from)
            ? $from
            : date('Y-m-d', strtotime('-29 days'));
        $toDate = ($toOk instanceof DateTimeImmutable && $toOk->format('Y-m-d') === $to)
            ? $to
            : $today;
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }
}

if (!function_exists('gemini_token_usage_summary')) {
    /**
     * 依日期、單元、功能加總
     *
     * @return list<array<string, mixed>>
     */
    function gemini_token_usage_summary(string $from, string $to, string $module = ''): array
    {
        $pdo = gemini_token_usage_pdo();
        if (!$pdo instanceof PDO || !gemini_token_usage_ensure_table($pdo)) {
            return [];
        }

        [$fromDate, $toDate] = gemini_token_usage_date_range($from, $to);
        $module = gemini_token_usage_normalize_module($module);
        $sql = 'SELECT DATE(dtDate) AS use_date, strModule, strFeature,
                    COUNT(*) AS call_count,
                    SUM(intPromptTokens) AS prompt_tokens,
                    SUM(intCandidatesTokens) AS output_tokens,
                    SUM(intThoughtsTokens) AS thought_tokens,
                    SUM(intTotalTokens) AS total_tokens
                FROM gemini_token_usage
                WHERE dtDate >= :fromDate AND dtDate < :toExclusive';
        $params = [
            ':fromDate' => $fromDate . ' 00:00:00',
            ':toExclusive' => date('Y-m-d', strtotime($toDate . ' +1 day')) . ' 00:00:00',
        ];
        if ($module !== '') {
            $sql .= ' AND strModule = :strModule';
            $params[':strModule'] = $module;
        }
        $sql .= ' GROUP BY DATE(dtDate), strModule, strFeature
                  ORDER BY use_date DESC, strModule ASC, strFeature ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('gemini_token_usage_details')) {
    /**
     * 單筆明細
     *
     * @return list<array<string, mixed>>
     */
    function gemini_token_usage_details(string $from, string $to, string $module = '', int $limit = 200): array
    {
        $pdo = gemini_token_usage_pdo();
        if (!$pdo instanceof PDO || !gemini_token_usage_ensure_table($pdo)) {
            return [];
        }

        [$fromDate, $toDate] = gemini_token_usage_date_range($from, $to);
        $module = gemini_token_usage_normalize_module($module);
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT dtDate, strModule, strFeature, strModel, intPromptTokens, intCandidatesTokens,
                    intThoughtsTokens, intTotalTokens, strLogin, strSuccess
                FROM gemini_token_usage
                WHERE dtDate >= :fromDate AND dtDate < :toExclusive';
        $params = [
            ':fromDate' => $fromDate . ' 00:00:00',
            ':toExclusive' => date('Y-m-d', strtotime($toDate . ' +1 day')) . ' 00:00:00',
        ];
        if ($module !== '') {
            $sql .= ' AND strModule = :strModule';
            $params[':strModule'] = $module;
        }
        $sql .= ' ORDER BY dtDate DESC, PKey DESC LIMIT ' . $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('gemini_token_usage_module_options')) {
    /**
     * @return list<string>
     */
    function gemini_token_usage_module_options(): array
    {
        $pdo = gemini_token_usage_pdo();
        if (!$pdo instanceof PDO || !gemini_token_usage_ensure_table($pdo)) {
            return [];
        }

        $stmt = $pdo->query(
            'SELECT DISTINCT strModule FROM gemini_token_usage WHERE strModule <> \'\' ORDER BY strModule ASC'
        );
        if ($stmt === false) {
            return [];
        }
        $modules = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $module = gemini_token_usage_normalize_module((string)($row['strModule'] ?? ''));
            if ($module !== '') {
                $modules[] = $module;
            }
        }

        return $modules;
    }
}
