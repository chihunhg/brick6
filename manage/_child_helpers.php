<?php

declare(strict_types=1);

/**
 * 後台子模組共用（返回 URL、多語系子表儲存）
 */

if (!function_exists('manage_child_call')) {
    /**
     * 安全呼叫回呼（字串函式名或 callable）；無效則拋 InvalidArgumentException
     */
    function manage_child_call(mixed $callable, mixed ...$args): mixed
    {
        if (is_string($callable) && $callable !== '' && function_exists($callable)) {
            return $callable(...$args);
        }
        if (is_callable($callable)) {
            return $callable(...$args);
        }

        throw new InvalidArgumentException('invalid callback');
    }
}

if (!function_exists('manage_child_return_url')) {
    /**
     * 子模組列表返回網址（帶父鍵、manNo、subNo）
     */
    function manage_child_return_url(
        int $parentPKey,
        string $listFile = 'list.php',
        string $fkParam = 'PKey'
    ): string {
        global $manNo, $subNo;

        $params = [];
        if ($parentPKey > 0 && $fkParam !== '') {
            $params[$fkParam] = $parentPKey;
            $params['PKey'] = $parentPKey;
        }
        if ((int)($manNo ?? 0) > 0) {
            $params['manNo'] = (int)$manNo;
        }
        if ((int)($subNo ?? 0) > 0) {
            $params['subNo'] = (int)$subNo;
        }

        $qs = http_build_query($params);

        return $listFile . ($qs !== '' ? '?' . $qs : '');
    }
}

if (!function_exists('manage_child_update_lang_sort')) {
    /**
     * 主檔 Sort 變更時，同步更新語系子表 Sort
     *
     * @param array{
     *   table: string,
     *   new_sort: int,
     *   old_sort: int,
     *   where: array<string,mixed>,
     *   fail_label?: string,
     *   pdo?: dbPDO|null,
     *   now?: string,
     * } $config
     */
    function manage_child_update_lang_sort(array $config): void
    {
        $table = (string)($config['table'] ?? '');
        $newSort = (int)($config['new_sort'] ?? 0);
        $oldSort = (int)($config['old_sort'] ?? 0);
        if ($table === '' || $newSort === $oldSort || $oldSort <= 0) {
            return;
        }
        if (!function_exists('chkTable') || !chkTable($table)) {
            return;
        }

        $where = (array)($config['where'] ?? []);
        $now = (string)($config['now'] ?? date('Y-m-d H:i:s'));
        $failLabel = (string)($config['fail_label'] ?? 'update lang sort');
        $ownPdo = !isset($config['pdo']) || !($config['pdo'] instanceof dbPDO);
        $pdo = $ownPdo ? new dbPDO() : $config['pdo'];

        $whereParts = ['Sort = :oldSort'];
        $params = [
            'newSort' => SqlFilter($newSort, 'int'),
            'dt'      => $now,
            'oldSort' => SqlFilter($oldSort, 'int'),
        ];
        foreach ($where as $col => $val) {
            $paramKey = 'w_' . $col;
            $whereParts[] = $col . ' = :' . $paramKey;
            $params[$paramKey] = $val;
        }

        $pdo->execute(
            'UPDATE ' . $table . ' SET Sort = :newSort, dtDate = :dt WHERE ' . implode(' AND ', $whereParts),
            $params
        );
        $err = $pdo->getErrorMessage();
        if ($err !== '') {
            if ($ownPdo) {
                $pdo->close();
            }
            crud_fail_db($failLabel, (string)$err, [], true);
        }

        if ($ownPdo) {
            $pdo->close();
        }
    }
}

if (!function_exists('manage_child_save_lang_rows')) {
    /**
     * 依 filter 的 strName{n}（或自訂 field_prefix）upsert / 刪除語系子表列
     *
     * @param array{
     *   table: string,
     *   filter: array,
     *   sort: int,
     *   fixed_columns?: array<string,mixed>,
     *   field_prefix?: string,
     *   lang_count?: int,
     *   find_existing: callable(int): ?array,
     *   fail_label?: string,
     *   pdo?: dbPDO|null,
     *   now?: string,
     *   is_show_default?: string,
     * } $config
     */
    function manage_child_save_lang_rows(array $config): void
    {
        $table = (string)($config['table'] ?? '');
        if ($table === '' || !function_exists('chkTable') || !chkTable($table)) {
            return;
        }

        $findExisting = $config['find_existing'] ?? null;
        if (!is_callable($findExisting)) {
            throw new InvalidArgumentException('find_existing callback required');
        }

        $filter = (array)($config['filter'] ?? []);
        $sort = (int)($config['sort'] ?? 0);
        $fieldPrefix = (string)($config['field_prefix'] ?? 'strName');
        $langCount = (int)($config['lang_count'] ?? manage_lang_count());
        $fixedColumns = (array)($config['fixed_columns'] ?? []);
        $failLabel = (string)($config['fail_label'] ?? 'save lang row');
        $isShowDefault = (string)($config['is_show_default'] ?? 'Y');
        $now = (string)($config['now'] ?? date('Y-m-d H:i:s'));
        $ownPdo = !isset($config['pdo']) || !($config['pdo'] instanceof dbPDO);
        $pdo = $ownPdo ? new dbPDO() : $config['pdo'];

        for ($lang = 1; $lang <= $langCount; $lang++) {
            $name = trim((string)($filter[manage_lang_filter_key($fieldPrefix, $lang)] ?? ''));
            /** @var array<string,mixed>|null $existing */
            $existing = $findExisting($lang);

            if ($name === '') {
                if ($existing !== null && isset($existing['PKey'])) {
                    $pdo->delete($table, ' PKey', (int)$existing['PKey']);
                    $err = $pdo->getErrorMessage();
                    if ($err !== '') {
                        if ($ownPdo) {
                            $pdo->close();
                        }
                        crud_fail_db('delete ' . $failLabel, (string)$err, ['PKey' => (int)$existing['PKey']], true);
                    }
                }
                continue;
            }

            $langData = array_merge($fixedColumns, [
                'Sort'    => SqlFilter($sort, 'int'),
                'intLang' => SqlFilter($lang, 'int'),
                'isShow'  => SqlFilter($isShowDefault, 'tab'),
                'strName' => SqlFilter($name, 'tab'),
                'dtDate'  => $now,
            ]);

            if ($existing !== null && isset($existing['PKey'])) {
                $pdo->update($table, $langData, 'PKey', (int)$existing['PKey']);
            } else {
                $pdo->insert($table, $langData);
            }

            $err = $pdo->getErrorMessage();
            if ($err !== '') {
                if ($ownPdo) {
                    $pdo->close();
                }
                crud_fail_db($failLabel, (string)$err, $langData, true);
            }
        }

        if ($ownPdo) {
            $pdo->close();
        }
    }
}

if (!function_exists('manage_child_apply_lang_rows_to_slots')) {
    /**
     * 將語系子表查詢結果填入 slot 陣列（form load 用）
     *
     * @param list<array<string,mixed>> $rows
     * @param array<int,string>         $slots
     * @return array<int,string>
     */
    function manage_child_apply_lang_rows_to_slots(array $rows, array $slots, ?int $langCount = null): array
    {
        $langCount = $langCount ?? manage_lang_count();
        foreach ($rows as $row) {
            $lang = (int)($row['intLang'] ?? 0);
            if ($lang >= 1 && $lang <= $langCount) {
                $slots[$lang] = (string)($row['strName'] ?? '');
            }
        }

        return $slots;
    }
}

if (!function_exists('manage_lang_apply_seo_from_lang_data')) {
    /**
     * 將 crud_load_lang_slots_data 的 Title 寫入 class1_form_vars.SeoTitle
     *
     * @param array<string,mixed> $langData
     */
    function manage_lang_apply_seo_from_lang_data(array $langData): void
    {
        if (!isset($GLOBALS['class1_form_vars']) || !is_array($GLOBALS['class1_form_vars'])) {
            return;
        }
        if (!isset($GLOBALS['class1_form_vars']['SeoTitle']) || !is_array($GLOBALS['class1_form_vars']['SeoTitle'])) {
            $GLOBALS['class1_form_vars']['SeoTitle'] = [];
        }
        foreach ($langData['Title'] ?? [] as $slot => $title) {
            $GLOBALS['class1_form_vars']['SeoTitle'][(int)$slot] = (string)$title;
        }
    }
}
