<?php

declare(strict_types=1);

require_once __DIR__ . '/../question/_helpers.php';
require_once __DIR__ . '/../question_class/_form_data.php';

if (!function_exists('question_item_lang_table')) {
    function question_item_lang_table(): string
    {
        return 'question_itme_lang';
    }
}

if (!function_exists('question_item_resolve_class_pkey')) {
    function question_item_resolve_class_pkey(): int
    {
        global $filter_array;

        $pk = safe_int($filter_array['Question_D_PKey'] ?? $_REQUEST['Question_D_PKey'] ?? 0);
        if ($pk <= 0) {
            $pk = safe_int($filter_array['PKey'] ?? $_REQUEST['PKey'] ?? 0);
        }

        return $pk;
    }
}

if (!function_exists('question_item_load_parent')) {
    /**
     * @return array{ok:bool, Question_PKey:int, Question_D_PKey:int, Question_Name:string, Question_Class_Name:string}
     */
    function question_item_load_parent(int $classPKey): array
    {
        $empty = [
            'ok'                  => false,
            'Question_PKey'       => 0,
            'Question_D_PKey'     => 0,
            'Question_Name'         => '',
            'Question_Class_Name' => '',
        ];

        if ($classPKey <= 0) {
            return $empty;
        }

        $row = crud_fetch_one(
            'SELECT qc.PKey, qc.Question_PKey, qc.Sort, qc.strName AS class_name, q.strName AS question_name
             FROM question_class qc
             INNER JOIN question q ON q.PKey = qc.Question_PKey
             WHERE qc.PKey = :pk LIMIT 1',
            ['pk' => $classPKey]
        );

        if ($row === null) {
            return $empty;
        }

        $questionPKey = (int)($row['Question_PKey'] ?? 0);

        return [
            'ok'                  => true,
            'Question_PKey'       => $questionPKey,
            'Question_D_PKey'     => (int)($row['PKey'] ?? $classPKey),
            'Question_Name'       => question_display_strname($questionPKey),
            'Question_Class_Name' => question_class_display_strname($classPKey),
        ];
    }
}

if (!function_exists('question_item_form_skip_global')) {
    /** @return list<string> */
    function question_item_form_skip_global(): array
    {
        return ['Question_PKey', 'Question_D_PKey', 'Question_Name', 'Question_Class_Name'];
    }
}

if (!function_exists('question_item_lang_count')) {
    function question_item_lang_count(): int
    {
        return manage_lang_count();
    }
}

if (!function_exists('question_item_empty_strname_slots')) {
    /** @return array<int,string> */
    function question_item_empty_strname_slots(): array
    {
        return manage_empty_lang_slots();
    }
}

if (!function_exists('question_item_resolve_strname_from_filter')) {
    function question_item_resolve_strname_from_filter(array $filter): string
    {
        return manage_resolve_lang_field_from_filter($filter, 'strName', null, ['strName']);
    }
}

if (!function_exists('question_item_validate_strname_from_filter')) {
    function question_item_validate_strname_from_filter(array $filter): string
    {
        return manage_validate_lang_field_from_filter(
            $filter,
            "【題目名稱】為空白（請至少填寫一個語系）\n",
            'strName',
            null,
            ['strName']
        );
    }
}

if (!function_exists('question_item_empty_answer_slots')) {
    /** @return array<int,array<int,string>> */
    function question_item_empty_answer_slots(int $maxSlots = 10): array
    {
        return manage_empty_nested_lang_slots($maxSlots);
    }
}

if (!function_exists('question_item_answer_filter_key')) {
    function question_item_answer_filter_key(int $slot, int $lang): string
    {
        return 'A_Name' . $slot . '_' . $lang;
    }
}

if (!function_exists('question_item_resolve_answer_from_filter')) {
    function question_item_resolve_answer_from_filter(array $filter, int $maxSlots = 10): string
    {
        for ($lang = 1; $lang <= question_item_lang_count(); $lang++) {
            for ($i = 1; $i <= $maxSlots; $i++) {
                $name = trim((string)($filter[question_item_answer_filter_key($i, $lang)] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return '';
    }
}

if (!function_exists('question_item_validate_answers_from_filter')) {
    function question_item_validate_answers_from_filter(array $filter, int $maxSlots = 10): string
    {
        if (question_item_resolve_answer_from_filter($filter, $maxSlots) === '') {
            return "【答案名稱】至少填寫一項（請至少填寫一個語系）\n";
        }

        return '';
    }
}

if (!function_exists('question_item_find_lang_row')) {
    /** @return array<string,mixed>|null */
    function question_item_find_lang_row(int $itemPKey, int $intLang): ?array
    {
        $table = question_item_lang_table();
        if ($itemPKey <= 0 || $intLang <= 0) {
            return null;
        }
        if (!function_exists('chkTable') || !chkTable($table)) {
            return null;
        }

        return crud_fetch_one(
            "SELECT PKey, strName FROM {$table}
             WHERE Question_Item_PKey = :ipk AND intLang = :lang LIMIT 1",
            ['ipk' => $itemPKey, 'lang' => $intLang]
        );
    }
}

if (!function_exists('question_item_display_strname')) {
    function question_item_display_strname(int $itemPKey): string
    {
        if ($itemPKey <= 0) {
            return '';
        }

        $row = crud_fetch_one(
            'SELECT strName FROM question_item WHERE PKey = :pk LIMIT 1',
            ['pk' => $itemPKey]
        );

        if ($row !== null && trim((string)($row['strName'] ?? '')) !== '') {
            return (string)$row['strName'];
        }

        $table = question_item_lang_table();
        if (!function_exists('chkTable') || !chkTable($table)) {
            return '';
        }

        $row = crud_fetch_one(
            "SELECT strName FROM {$table}
             WHERE Question_Item_PKey = :ipk AND TRIM(strName) <> ''
             ORDER BY intLang ASC LIMIT 1",
            ['ipk' => $itemPKey]
        );

        return (string)($row['strName'] ?? '');
    }
}

if (!function_exists('question_item_delete_answer_lang_rows')) {
    function question_item_delete_answer_lang_rows(int $itemPKey, ?dbPDO $pdo = null): void
    {
        if ($itemPKey <= 0 || !function_exists('chkTable') || !chkTable('question_answer_lang')) {
            return;
        }

        $answerRows = crud_fetch_all(
            'SELECT PKey FROM question_answer WHERE Question_I_PKey = :fk',
            ['fk' => $itemPKey]
        );
        if ($answerRows === []) {
            return;
        }

        $ownPdo = !($pdo instanceof dbPDO);
        $db = $ownPdo ? new dbPDO() : $pdo;
        foreach ($answerRows as $answerRow) {
            $answerPKey = (int)($answerRow['PKey'] ?? 0);
            if ($answerPKey <= 0) {
                continue;
            }
            $db->delete('question_answer_lang', ' Question_Answer_PKey', $answerPKey);
        }
        if ($ownPdo) {
            $db->close();
        }
    }
}

if (!function_exists('question_item_delete_related_rows')) {
    function question_item_delete_related_rows(int $itemPKey): void
    {
        if ($itemPKey <= 0) {
            return;
        }

        $pdo = new dbPDO();
        question_item_delete_answer_lang_rows($itemPKey, $pdo);
        $pdo->delete('question_answer', ' Question_I_PKey', $itemPKey);

        $langTable = question_item_lang_table();
        if (function_exists('chkTable') && chkTable($langTable)) {
            $pdo->deleteWithConditions($langTable, [
                'Question_Item_PKey' => $itemPKey,
            ]);
        }

        $pdo->delete('question_item', ' PKey', $itemPKey);
        $pdo->close();
    }
}

if (!function_exists('question_item_form_init')) {
    function question_item_form_init(): void
    {
        manage_form_bag_init('question_item_form', [
            'Update_PKey'         => 0,
            'Question_PKey'       => 0,
            'Question_D_PKey'     => 0,
            'Question_Name'       => '',
            'Question_Class_Name' => '',
            'Sort'                => 1,
            'strName'             => question_item_empty_strname_slots(),
            'Qtype'               => '',
            'Other'               => '',
            'Must'                => '',
            'Upload'              => 'Yes',
            'dtUDate'             => '',
            'UserID'              => '',
            'A_Name'              => question_item_empty_answer_slots(),
        ], question_item_form_skip_global());
    }
}

if (!function_exists('question_item_form_export')) {
    function question_item_form_export(): void
    {
        manage_form_bag_export('question_item_form', question_item_form_skip_global());
    }
}

if (!function_exists('question_item_form_apply_parent')) {
    function question_item_form_apply_parent(
        int $questionPKey,
        int $classPKey,
        string $questionName,
        string $className
    ): void {
        if (!isset($GLOBALS['question_item_form']) || !is_array($GLOBALS['question_item_form'])) {
            question_item_form_init();
        }

        manage_form_bag_apply_fields('question_item_form', [
            'Question_PKey'       => $questionPKey,
            'Question_D_PKey'     => $classPKey,
            'Question_Name'       => $questionName,
            'Question_Class_Name' => $className,
        ], question_item_form_skip_global());
    }
}

if (!function_exists('question_item_breadcrumbs_for_form')) {
    /** @return list<array{label:string, href?:string}> */
    function question_item_breadcrumbs_for_form(
        int $questionPKey,
        string $questionName,
        int $classPKey,
        string $className
    ): array {
        global $manNo;

        $classListUrl = question_child_return_url($questionPKey, '../question_class/list.php');

        return [
            ['label' => '單元管理'],
            ['label' => (string)($GLOBALS['Module_Name'] ?? '')],
            [
                'label' => '問卷管理',
                'href'  => '../question/list.php?manNo=' . urlencode((string)($manNo ?? '')),
            ],
            ['label' => $questionName, 'href' => $classListUrl],
            ['label' => $className, 'href' => question_child_return_url($classPKey, 'list.php', 'Question_D_PKey')],
            ['label' => manage_breadcrumb_form_action_label()],
        ];
    }
}

if (!function_exists('question_item_load_answers')) {
    /** @return array<int,array<int,string>> */
    function question_item_load_answers(int $itemPKey, int $maxSlots = 10): array
    {
        $names = question_item_empty_answer_slots($maxSlots);
        if ($itemPKey <= 0) {
            return $names;
        }

        $langCount = question_item_lang_count();
        $hasLangTable = function_exists('chkTable') && chkTable('question_answer_lang');
        $answerRows = crud_fetch_all(
            'SELECT PKey, Sort, strName FROM question_answer WHERE Question_I_PKey = :fk ORDER BY Sort',
            ['fk' => $itemPKey]
        );

        foreach ($answerRows as $answerRow) {
            $sort = (int)($answerRow['Sort'] ?? 0);
            $answerPKey = (int)($answerRow['PKey'] ?? 0);
            if ($sort < 1 || $sort > $maxSlots || $answerPKey <= 0) {
                continue;
            }

            $loaded = false;
            if ($hasLangTable) {
                $langRows = crud_fetch_all(
                    'SELECT intLang, strName FROM question_answer_lang
                     WHERE Question_Answer_PKey = :apk ORDER BY intLang',
                    ['apk' => $answerPKey]
                );
                foreach ($langRows as $langRow) {
                    $lang = (int)($langRow['intLang'] ?? 0);
                    if ($lang >= 1 && $lang <= $langCount) {
                        $names[$lang][$sort] = (string)($langRow['strName'] ?? '');
                        $loaded = true;
                    }
                }
            }

            if (!$loaded) {
                $names[1][$sort] = (string)($answerRow['strName'] ?? '');
            }
        }

        return $names;
    }
}

if (!function_exists('question_item_row_belongs_to_class')) {
    function question_item_row_belongs_to_class(int $itemPKey, int $classPKey): bool
    {
        if ($itemPKey <= 0 || $classPKey <= 0) {
            return false;
        }

        $row = crud_fetch_one(
            'SELECT Question_D_PKey FROM question_item WHERE PKey = :pk LIMIT 1',
            ['pk' => $itemPKey]
        );
        if ($row === null) {
            return false;
        }

        $itemDpk = (int)($row['Question_D_PKey'] ?? 0);
        if ($itemDpk === $classPKey) {
            return true;
        }

        $classRow = crud_fetch_one(
            'SELECT Sort FROM question_class WHERE PKey = :pk LIMIT 1',
            ['pk' => $classPKey]
        );
        if ($classRow === null) {
            return false;
        }

        $classSort = (int)($classRow['Sort'] ?? 0);

        return $classSort > 0 && $itemDpk === $classSort;
    }
}

if (!function_exists('question_item_form_load')) {
    function question_item_form_load(int $pkey, int $classPKey): bool
    {
        if ($pkey <= 0 || !question_item_row_belongs_to_class($pkey, $classPKey)) {
            return false;
        }

        $row = crud_fetch_one(
            'SELECT * FROM question_item WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
        if ($row === null) {
            return false;
        }

        if (!isset($GLOBALS['question_item_form'])) {
            question_item_form_init();
        }

        $f = &$GLOBALS['question_item_form'];
        $sort = (int)($row['Sort'] ?? 0);
        $questionPKey = (int)($row['Question_PKey'] ?? 0);

        $f['Update_PKey']     = $pkey;
        $f['Question_PKey']   = $questionPKey;
        $f['Question_D_PKey'] = $classPKey;
        $f['Sort']            = $sort > 0 ? $sort : 1;
        $f['strName']         = question_item_empty_strname_slots();

        $langTable = question_item_lang_table();
        if (function_exists('chkTable') && chkTable($langTable)) {
            $langRows = crud_fetch_all(
                "SELECT intLang, strName FROM {$langTable}
                 WHERE Question_Item_PKey = :ipk ORDER BY intLang",
                ['ipk' => $pkey]
            );
            $f['strName'] = manage_child_apply_lang_rows_to_slots(
                $langRows,
                $f['strName'],
                question_item_lang_count()
            );
        }

        $hasLangName = false;
        foreach ($f['strName'] as $slotName) {
            if (trim((string)$slotName) !== '') {
                $hasLangName = true;
                break;
            }
        }
        if (!$hasLangName) {
            $f['strName'][1] = (string)($row['strName'] ?? '');
        }

        $f['Qtype']    = (string)($row['Qtype'] ?? '');
        $f['Other']    = (string)($row['Other'] ?? '');
        $f['Must']     = (string)($row['Must'] ?? '');
        $f['Upload']   = (string)($row['Upload'] ?? 'Yes');
        $f['dtUDate']  = (string)($row['dtUDate'] ?? '');
        $f['UserID']   = (string)($row['UserID'] ?? '');
        $answerSlots = 10;
        if (is_file(__DIR__ . '/_config.php')) {
            $itemCfg = require __DIR__ . '/_config.php';
            $answerSlots = max(1, (int)($itemCfg['answer_slots'] ?? 10));
        }
        $f['A_Name'] = question_item_load_answers($pkey, $answerSlots);

        question_item_form_export();

        return true;
    }
}

if (!function_exists('question_item_next_sort')) {
    function question_item_next_sort(int $classPKey): int
    {
        $max = crud_fetch_scalar(
            'SELECT MAX(Sort) AS m FROM question_item WHERE Question_D_PKey = :fk',
            ['fk' => $classPKey],
            'm'
        );

        return ((int)$max) + 1;
    }
}

if (!function_exists('question_item_save_answers')) {
    function question_item_save_answers(
        int $questionPKey,
        int $classPKey,
        int $itemPKey,
        array $filter,
        int $maxSlots = 10
    ): void {
        if ($itemPKey <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $langCount = question_item_lang_count();
        $hasLangTable = function_exists('chkTable') && chkTable('question_answer_lang');

        $pdo = new dbPDO();
        question_item_delete_answer_lang_rows($itemPKey, $pdo);
        $pdo->delete('question_answer', ' Question_I_PKey', $itemPKey);
        $pdo->close();

        for ($i = 1; $i <= $maxSlots; $i++) {
            $primaryName = '';
            $langNames = [];
            for ($lang = 1; $lang <= $langCount; $lang++) {
                $name = trim((string)($filter[question_item_answer_filter_key($i, $lang)] ?? ''));
                if ($name === '') {
                    continue;
                }
                $langNames[$lang] = $name;
                if ($primaryName === '') {
                    $primaryName = $name;
                }
            }
            if ($primaryName === '') {
                continue;
            }

            $answerRow = [
                'Question_PKey'   => SqlFilter($questionPKey, 'int'),
                'Question_D_PKey' => SqlFilter($classPKey, 'int'),
                'Question_I_PKey' => SqlFilter($itemPKey, 'int'),
                'Sort'            => SqlFilter($i, 'int'),
                'strName'         => SqlFilter((string)($langNames[1] ?? $primaryName), 'tab'),
                'dtDate'          => $now,
            ];
            $memoKey = 'Memo' . $i . '_1';
            if (isset($filter[$memoKey])) {
                $answerRow['Memo'] = SqlFilter((string)$filter[$memoKey], 'tab');
            }
            $answerRow = crud_filter_row_for_table('question_answer', $answerRow);

            $pdo = new dbPDO();
            $pdo->insert('question_answer', $answerRow);
            $err = $pdo->getErrorMessage();
            $answerPKey = (int)$pdo->getLastId();
            $pdo->close();
            if ($err !== '') {
                crud_fail_db('insert question_answer', (string)$err, $answerRow, true);
            }
            if ($answerPKey <= 0) {
                continue;
            }

            if (!$hasLangTable) {
                continue;
            }

            foreach ($langNames as $lang => $name) {
                $langRow = [
                    'Question_Answer_PKey' => SqlFilter($answerPKey, 'int'),
                    'Question_D_PKey'      => SqlFilter($classPKey, 'int'),
                    'Sort'                 => SqlFilter($i, 'int'),
                    'intLang'              => SqlFilter((int)$lang, 'int'),
                    'isShow'               => SqlFilter('Y', 'tab'),
                    'strName'              => SqlFilter($name, 'tab'),
                    'dtDate'               => $now,
                ];
                $memoLangKey = 'Memo' . $i . '_' . $lang;
                if (isset($filter[$memoLangKey])) {
                    $langRow['Memo'] = SqlFilter((string)$filter[$memoLangKey], 'tab');
                }
                $langRow = crud_filter_row_for_table('question_answer_lang', $langRow);

                $pdo = new dbPDO();
                $pdo->insert('question_answer_lang', $langRow);
                $err = $pdo->getErrorMessage();
                $pdo->close();
                if ($err !== '') {
                    crud_fail_db('insert question_answer_lang', (string)$err, $langRow, true);
                }
            }
        }
    }
}

if (!function_exists('question_item_save')) {
    /** @return array{action:string} */
    function question_item_save(
        int $questionPKey,
        int $classPKey,
        int $anchorPKey,
        array $filter,
        string $loginId,
        int $answerSlots = 10
    ): array {
        $sort = safe_int($filter['Sort'] ?? 0);
        if ($sort <= 0) {
            throw new InvalidArgumentException('invalid sort');
        }

        $qtype = safe_int($filter['Qtype'] ?? 0);
        $other = !empty($filter['Other']) && (string)$filter['Other'] === 'Yes' ? 'Yes' : '';
        $must = !empty($filter['Must']) && (string)$filter['Must'] === 'Yes' ? 'Yes' : '';
        $upload = isset($filter['Upload'])
            ? SqlFilter((string)$filter['Upload'], 'tab')
            : SqlFilter('Yes', 'tab');
        $now = date('Y-m-d H:i:s');
        $langCount = question_item_lang_count();
        $primaryName = question_item_resolve_strname_from_filter($filter);
        $wasUpdate = $anchorPKey > 0;
        $oldSort = $sort;

        if ($wasUpdate) {
            if (!question_item_row_belongs_to_class($anchorPKey, $classPKey)) {
                throw new RuntimeException('anchor row not found');
            }
            $anchor = crud_fetch_one(
                'SELECT Sort FROM question_item WHERE PKey = :pk LIMIT 1',
                ['pk' => $anchorPKey]
            );
            if ($anchor === null) {
                throw new RuntimeException('anchor row not found');
            }
            $oldSort = (int)$anchor['Sort'];
        }

        $masterData = [
            'Question_PKey'   => SqlFilter($questionPKey, 'int'),
            'Question_D_PKey' => SqlFilter($classPKey, 'int'),
            'Sort'            => SqlFilter($sort, 'int'),
            'strName'         => SqlFilter($primaryName, 'tab'),
            'Qtype'           => SqlFilter($qtype, 'int'),
            'Other'           => SqlFilter($other, 'tab'),
            'Must'            => SqlFilter($must, 'tab'),
            'Upload'          => $upload,
            'UserID'          => SqlFilter($loginId, 'tab'),
            'dtUDate'         => $now,
        ];
        $masterData = crud_filter_row_for_table('question_item', $masterData);

        $pdo = new dbPDO();
        if ($wasUpdate) {
            $pdo->update('question_item', $masterData, 'PKey', $anchorPKey);
            $err = $pdo->getErrorMessage();
            if ($err !== '') {
                $pdo->close();
                crud_fail_db('update question_item', (string)$err, $masterData, true);
            }
            $itemPKey = $anchorPKey;
        } else {
            $masterData['dtDate'] = $now;
            $pdo->insert('question_item', $masterData);
            $err = $pdo->getErrorMessage();
            if ($err !== '') {
                $pdo->close();
                crud_fail_db('insert question_item', (string)$err, $masterData, true);
            }
            $itemPKey = (int)$pdo->getLastId();
        }

        $langTable = question_item_lang_table();
        if ($wasUpdate && $oldSort !== $sort) {
            manage_child_update_lang_sort([
                'table'      => $langTable,
                'new_sort'   => $sort,
                'old_sort'   => $oldSort,
                'where'      => [
                    'Question_Item_PKey' => SqlFilter($itemPKey, 'int'),
                ],
                'fail_label' => 'update question_itme_lang sort',
                'pdo'        => $pdo,
                'now'        => $now,
            ]);
        }

        manage_child_save_lang_rows([
            'table'         => $langTable,
            'filter'        => $filter,
            'sort'          => $sort,
            'lang_count'    => $langCount,
            'fixed_columns' => [
                'Question_Item_PKey' => SqlFilter($itemPKey, 'int'),
            ],
            'find_existing' => static function (int $lang) use ($itemPKey): ?array {
                return question_item_find_lang_row($itemPKey, $lang);
            },
            'fail_label' => 'question_itme_lang',
            'pdo'        => $pdo,
            'now'        => $now,
        ]);

        $pdo->close();

        if ($qtype === 1 || $qtype === 2) {
            question_item_save_answers($questionPKey, $classPKey, $itemPKey, $filter, $answerSlots);
        } else {
            $pdo = new dbPDO();
            question_item_delete_answer_lang_rows($itemPKey, $pdo);
            $pdo->delete('question_answer', ' Question_I_PKey', $itemPKey);
            $pdo->close();
        }

        return ['action' => $wasUpdate ? '修改成功!' : '新增成功!'];
    }
}
