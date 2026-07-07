<?php
declare(strict_types=1);

if (!function_exists('tag_relation_table')) {
/**
 * tag_d 關聯標籤：news / paper 等模組共用
 */

    /** 回傳 tag_d 關聯表名 */
    function tag_relation_table(): string {
        return 'tag_d';
    }
}

if (!function_exists('tag_relation_resolve_module_pkey')) {
    /** 標籤資料來源單元 Module_PKey（優先 _config tag_module_pkey，其次 program.strLink=tag） */
    function tag_relation_resolve_module_pkey(?array $config = null): int {
        static $cache = [];

        if ($config === null && is_file(__DIR__ . '/../manage/news/_config.php')) {
            // 無 config 時不強制載入
        }
        $fromConfig = (int)($config['tag_module_pkey'] ?? 0);
        if ($fromConfig > 0) {
            return $fromConfig;
        }

        $cacheKey = 'default';
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $fallback = (int)($GLOBALS['Module_PKey'] ?? 0);
        if (!function_exists('chkTable') || !chkTable('module_p') || !chkTable('program')) {
            return $cache[$cacheKey] = $fallback;
        }

        $row = crud_fetch_one(
            'SELECT mp.PKey FROM module_p mp'
            . ' INNER JOIN program pr ON pr.PKey = mp.intUse'
            . " WHERE pr.strLink = 'tag' ORDER BY mp.PKey LIMIT 1"
        );
        $resolved = (int)($row['PKey'] ?? 0);
        return $cache[$cacheKey] = ($resolved > 0 ? $resolved : $fallback);
    }
}

if (!function_exists('tag_relation_normalize_parent_col')) {
    /** 驗證並正規化父表 PKey 欄位名（大小寫容錯） */
    function tag_relation_normalize_parent_col(string $col): string {
        $col = trim($col);
        if ($col === '' || !function_exists('crud_is_safe_sql_identifier') || !crud_is_safe_sql_identifier($col)) {
            return '';
        }
        $table = tag_relation_table();
        if (!function_exists('chkTable') || !chkTable($table)) {
            return '';
        }
        if (function_exists('crud_table_has_column') && !crud_table_has_column($table, $col)) {
            foreach (['News_PKey', 'News_Pkey', 'Paper_PKey', 'Paper_Pkey'] as $candidate) {
                if (strcasecmp($candidate, $col) === 0 && crud_table_has_column($table, $candidate)) {
                    return $candidate;
                }
            }
            return '';
        }
        return $col;
    }
}

if (!function_exists('tag_relation_display_sql')) {
    /** 標籤顯示名稱 SQL 片段（優先 tag_lang） */
    function tag_relation_display_sql(): string {
        if (function_exists('chkTable') && chkTable('tag_lang')) {
            return "TRIM(COALESCE(
                (SELECT tl.strName FROM tag_lang tl WHERE tl.Tag_PKey = t.PKey AND tl.intLang = 1 LIMIT 1),
                t.strName
            ))";
        }
        return 'TRIM(t.strName)';
    }
}

if (!function_exists('tag_relation_load')) {
    /**
     * @return list<array{rowPKey:int,targetPKey:int,strName:string}>
     */
    function tag_relation_load(int $parentPKey, string $parentCol): array {
        $parentCol = tag_relation_normalize_parent_col($parentCol);
        $table = tag_relation_table();
        if ($parentPKey <= 0 || $parentCol === '' || !function_exists('chkTable') || !chkTable($table)) {
            return [];
        }

        $displaySql = tag_relation_display_sql();
        $sql = 'SELECT d.PKey, d.Tag_PKey AS TargetPKey, ' . $displaySql . ' AS strName'
            . ' FROM `' . $table . '` d'
            . ' INNER JOIN tag t ON t.PKey = d.Tag_PKey'
            . ' WHERE d.`' . $parentCol . '` = :pk'
            . ' ORDER BY d.PKey';

        $rows = crud_fetch_all($sql, ['pk' => $parentPKey]);
        $out = [];
        foreach ($rows as $r) {
            $target = (int)($r['TargetPKey'] ?? 0);
            if ($target <= 0) {
                continue;
            }
            $out[] = [
                'rowPKey'    => (int)($r['PKey'] ?? 0),
                'targetPKey' => $target,
                'strName'    => (string)($r['strName'] ?? ''),
            ];
        }
        return $out;
    }
}

if (!function_exists('tag_relation_save')) {
    /** 依表單 Tag* 欄位同步 tag_d */
    function tag_relation_save(int $parentPKey, string $parentCol, array $filter): void {
        $parentCol = tag_relation_normalize_parent_col($parentCol);
        $table = tag_relation_table();
        if ($parentPKey <= 0 || $parentCol === '' || !function_exists('chkTable') || !chkTable($table)) {
            return;
        }

        $total = (int)($filter['Tag_Total'] ?? 0);
        $pdo = new dbPDO();
        if (!$pdo->delete($table, $parentCol, SqlFilter($parentPKey, 'int'))) {
            $err = method_exists($pdo, 'getErrorMessage') ? trim((string)$pdo->getErrorMessage()) : '';
            if ($err !== '') {
                throw new RuntimeException('清除舊標籤關聯失敗：' . $err);
            }
        }

        $seen = [];
        for ($i = 1; $i <= $total; $i++) {
            $tagPKey = (int)($filter['Tag' . $i] ?? 0);
            if ($tagPKey <= 0 || isset($seen[$tagPKey])) {
                continue;
            }
            $seen[$tagPKey] = true;

            $row = [
                'Tag_PKey'  => SqlFilter($tagPKey, 'int'),
                $parentCol  => SqlFilter($parentPKey, 'int'),
                'dtDate'    => date('Y-m-d H:i:s'),
            ];
            if (function_exists('crud_table_has_column')) {
                if (crud_table_has_column($table, 'Product_PKey')) {
                    $row['Product_PKey'] = SqlFilter(0, 'int');
                }
                if (crud_table_has_column($table, 'Paper_PKey') && $parentCol !== 'Paper_PKey') {
                    $row['Paper_PKey'] = SqlFilter(0, 'int');
                }
                if (crud_table_has_column($table, 'News_PKey') && $parentCol !== 'News_PKey') {
                    $row['News_PKey'] = SqlFilter(0, 'int');
                }
            }
            $row = function_exists('crud_filter_row_for_table')
                ? crud_filter_row_for_table($table, $row)
                : $row;
            if ($row === [] || !array_key_exists('Tag_PKey', $row)) {
                continue;
            }
            if (!$pdo->insert($table, $row)) {
                $err = method_exists($pdo, 'getErrorMessage') ? trim((string)$pdo->getErrorMessage()) : '';
                throw new RuntimeException(
                    $err !== '' ? $err : ('tag_d 寫入失敗（Tag_PKey=' . $tagPKey . '）')
                );
            }
        }
        $pdo->close();
    }
}
