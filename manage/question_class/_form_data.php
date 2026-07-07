<?php

declare(strict_types=1);



require_once __DIR__ . '/../question/_helpers.php';



if (!function_exists('question_class_resolve_question_pkey')) {

    /** 自 filter／REQUEST 解析問卷主檔 PKey */

    function question_class_resolve_question_pkey(): int

    {

        global $filter_array;

        $pk = safe_int($filter_array['Question_PKey'] ?? $_REQUEST['Question_PKey'] ?? 0);

        if ($pk <= 0) {

            $pk = safe_int($filter_array['PKey'] ?? $_REQUEST['PKey'] ?? 0);

        }

        return $pk;

    }

}



if (!function_exists('question_class_load_parent')) {

    /** @return array{ok:bool, Question_PKey:int, Question_Name:string} */

    function question_class_load_parent(int $questionPKey): array

    {

        $result = ['ok' => false, 'Question_PKey' => 0, 'Question_Name' => ''];

        if ($questionPKey <= 0) {

            return $result;

        }

        $row = crud_fetch_one(

            'SELECT PKey, strName FROM question WHERE PKey = :pk LIMIT 1',

            ['pk' => $questionPKey]

        );

        if ($row === null) {

            return $result;

        }

        $result['ok'] = true;

        $result['Question_PKey'] = (int)$row['PKey'];

        $result['Question_Name'] = question_display_strname((int)$row['PKey']);

        return $result;

    }

}



if (!function_exists('question_class_delete_related_rows')) {

    /** 刪除類別及其題目、語系等關聯資料 */

    function question_class_delete_related_rows(int $classPKey): void

    {

        if ($classPKey <= 0) {

            return;

        }

        $anchor = crud_fetch_one(

            'SELECT Question_PKey, Sort FROM question_class WHERE PKey = :pk LIMIT 1',

            ['pk' => $classPKey]

        );

        if ($anchor === null) {

            return;

        }

        require_once __DIR__ . '/../question_item/_form_data.php';

        $questionPKey = (int)$anchor['Question_PKey'];

        $sort = (int)$anchor['Sort'];

        $itemRows = crud_fetch_all(

            'SELECT PKey FROM question_item WHERE Question_D_PKey = :fk',

            ['fk' => $classPKey]

        );

        foreach ($itemRows as $itemRow) {

            $itemPk = (int)($itemRow['PKey'] ?? 0);

            if ($itemPk <= 0) {

                continue;

            }

            question_item_delete_related_rows($itemPk);

        }

        $pdo = new dbPDO();

        $pdo->deleteWithConditions('question_class_lang', [

            'Question_Class_PKey' => $classPKey,

        ]);

        $pdo->delete('question_class', ' PKey', $classPKey);

        $pdo->close();

    }

}



if (!function_exists('question_class_form_skip_global')) {
    /** @return list<string> */
    function question_class_form_skip_global(): array
    {
        return ['Question_PKey', 'Question_Name'];
    }
}

if (!function_exists('question_class_lang_count')) {
    /** 後台語系數量 */
    function question_class_lang_count(): int
    {
        return manage_lang_count();
    }
}

if (!function_exists('question_class_empty_strname_slots')) {
    /** @return array<int,string> */
    function question_class_empty_strname_slots(): array
    {
        return manage_empty_lang_slots();
    }
}

if (!function_exists('question_class_resolve_strname_from_filter')) {
    /** 自表單 filter 解析類別主檔名稱 */
    function question_class_resolve_strname_from_filter(array $filter): string
    {
        return manage_resolve_lang_field_from_filter($filter, 'strName');
    }
}

if (!function_exists('question_class_validate_strname_from_filter')) {
    /** 驗證類別名稱至少填寫一個語系 */
    function question_class_validate_strname_from_filter(array $filter): string
    {
        return manage_validate_lang_field_from_filter(
            $filter,
            "【類別名稱】為空白（請至少填寫一個語系）\n",
            'strName'
        );
    }
}



if (!function_exists('question_class_find_lang_row')) {

    /** @return array<string,mixed>|null */

    function question_class_find_lang_row(int $classPKey, int $intLang): ?array

    {

        if ($classPKey <= 0 || $intLang <= 0) {

            return null;

        }

        return crud_fetch_one(

            'SELECT PKey, strName FROM question_class_lang

             WHERE Question_Class_PKey = :cpk AND intLang = :lang LIMIT 1',

            ['cpk' => $classPKey, 'lang' => $intLang]

        );

    }

}



if (!function_exists('question_class_display_strname')) {

    /** 顯示用類別名稱（主檔或語系表 fallback） */

    function question_class_display_strname(int $classPKey): string

    {

        if ($classPKey <= 0) {

            return '';

        }

        $row = crud_fetch_one(

            'SELECT strName FROM question_class WHERE PKey = :pk LIMIT 1',

            ['pk' => $classPKey]

        );

        if ($row !== null && trim((string)($row['strName'] ?? '')) !== '') {

            return (string)$row['strName'];

        }

        $row = crud_fetch_one(

            'SELECT strName FROM question_class_lang

             WHERE Question_Class_PKey = :cpk AND TRIM(strName) <> \'\'

             ORDER BY intLang ASC LIMIT 1',

            ['cpk' => $classPKey]

        );

        return (string)($row['strName'] ?? '');

    }

}



if (!function_exists('question_class_save_multilang')) {

    /** @return array{action:string} */

    function question_class_save_multilang(

        int $questionPKey,

        int $anchorPKey,

        array $filter,

        string $loginId

    ): array {

        $sort = safe_int($filter['Sort'] ?? 0);

        if ($sort <= 0) {

            throw new InvalidArgumentException('invalid sort');

        }

        $upload = isset($filter['Upload'])

            ? SqlFilter((string)$filter['Upload'], 'tab')

            : SqlFilter('Yes', 'tab');

        $now = date('Y-m-d H:i:s');

        $langCount = question_class_lang_count();

        $primaryName = question_class_resolve_strname_from_filter($filter);

        $wasUpdate = $anchorPKey > 0;

        $oldSort = $sort;

        if ($wasUpdate) {

            $anchor = crud_fetch_one(

                'SELECT Sort FROM question_class WHERE PKey = :pk AND Question_PKey = :qpk LIMIT 1',

                ['pk' => $anchorPKey, 'qpk' => $questionPKey]

            );

            if ($anchor === null) {

                throw new RuntimeException('anchor row not found');

            }

            $oldSort = (int)$anchor['Sort'];

        }

        $masterData = [

            'Question_PKey' => SqlFilter($questionPKey, 'int'),

            'Sort'          => SqlFilter($sort, 'int'),

            'strName'       => SqlFilter($primaryName, 'tab'),

            'Upload'        => $upload,

            'UserID'        => SqlFilter($loginId, 'tab'),

            'dtUDate'       => $now,

        ];

        $pdo = new dbPDO();

        if ($wasUpdate) {

            $pdo->update('question_class', $masterData, 'PKey', $anchorPKey);

            $err = $pdo->getErrorMessage();

            if ($err !== '') {

                $pdo->close();

                crud_fail_db('update question_class', (string)$err, $masterData, true);

            }

        } else {

            $masterData['dtDate'] = $now;

            $pdo->insert('question_class', $masterData);

            $err = $pdo->getErrorMessage();

            if ($err !== '') {

                $pdo->close();

                crud_fail_db('insert question_class', (string)$err, $masterData, true);

            }

            $anchorPKey = (int)$pdo->getLastId();

        }

        if ($wasUpdate && $oldSort !== $sort) {
            manage_child_update_lang_sort([
                'table'      => 'question_class_lang',
                'new_sort'   => $sort,
                'old_sort'   => $oldSort,
                'where'      => ['Question_Class_PKey' => SqlFilter($anchorPKey, 'int')],
                'fail_label' => 'update question_class_lang sort',
                'pdo'        => $pdo,
                'now'        => $now,
            ]);
        }

        manage_child_save_lang_rows([
            'table'         => 'question_class_lang',
            'filter'        => $filter,
            'sort'          => $sort,
            'lang_count'    => $langCount,
            'fixed_columns' => [
                'Question_Class_PKey' => SqlFilter($anchorPKey, 'int'),
            ],
            'find_existing' => static function (int $lang) use ($anchorPKey): ?array {
                return question_class_find_lang_row($anchorPKey, $lang);
            },
            'fail_label' => 'question_class_lang',
            'pdo'        => $pdo,
            'now'        => $now,
        ]);

        $pdo->close();

        return ['action' => $wasUpdate ? '修改成功!' : '新增成功!'];

    }

}



if (!function_exists('question_class_form_init')) {
    /** 初始化類別表單 bag 預設值 */
    function question_class_form_init(): void
    {
        manage_form_bag_init('question_class_form', [
            'Update_PKey'   => 0,
            'Question_PKey' => 0,
            'Question_Name' => '',
            'Sort'          => 1,
            'strName'       => question_class_empty_strname_slots(),
            'Upload'        => 'Yes',
            'dtUDate'       => '',
            'UserID'        => '',
        ], question_class_form_skip_global());
    }
}

if (!function_exists('question_class_form_export')) {
    /** 將類別表單 bag 匯出至 $GLOBALS */
    function question_class_form_export(): void
    {
        manage_form_bag_export('question_class_form', question_class_form_skip_global());
    }
}

if (!function_exists('question_class_form_apply_parent')) {
    /** 寫入問卷父層資訊至表單 bag */
    function question_class_form_apply_parent(int $questionPKey, string $questionName): void
    {
        if (!isset($GLOBALS['question_class_form']) || !is_array($GLOBALS['question_class_form'])) {
            question_class_form_init();
        }

        manage_form_bag_apply_fields('question_class_form', [
            'Question_PKey' => $questionPKey,
            'Question_Name' => $questionName,
        ], question_class_form_skip_global());
    }
}



if (!function_exists('question_class_breadcrumbs_for_form')) {

    /** @return list<array{label:string, href?:string}> */

    function question_class_breadcrumbs_for_form(int $questionPKey, string $questionName): array

    {

        global $manNo;

        return [

            ['label' => '單元管理'],

            ['label' => (string)($GLOBALS['Module_Name'] ?? '')],

            [

                'label' => '問卷管理',

                'href'  => '../question/list.php?manNo=' . urlencode((string)($manNo ?? '')),

            ],

            ['label' => $questionName, 'href' => question_child_return_url($questionPKey)],

            ['label' => manage_breadcrumb_form_action_label()],

        ];

    }

}



if (!function_exists('question_class_form_load')) {

    /** 自 DB 載入類別至表單 bag */

    function question_class_form_load(int $pkey, int $questionPKey): bool

    {

        if ($pkey <= 0) {

            return false;

        }

        $anchor = crud_fetch_one(

            'SELECT * FROM question_class WHERE PKey = :pk AND Question_PKey = :qpk LIMIT 1',

            ['pk' => $pkey, 'qpk' => $questionPKey]

        );

        if ($anchor === null) {

            return false;

        }

        $sort = (int)($anchor['Sort'] ?? 0);

        $rows = crud_fetch_all(

            'SELECT intLang, strName FROM question_class_lang

             WHERE Question_Class_PKey = :cpk ORDER BY intLang',

            ['cpk' => $pkey]

        );

        if (!isset($GLOBALS['question_class_form'])) {

            question_class_form_init();

        }

        $f = &$GLOBALS['question_class_form'];

        $f['Update_PKey']   = $pkey;

        $f['Question_PKey'] = $questionPKey;

        $f['Sort']          = $sort > 0 ? $sort : 1;

        $f['strName'] = manage_child_apply_lang_rows_to_slots(
            $rows,
            question_class_empty_strname_slots(),
            question_class_lang_count()
        );

        $hasLangName = false;
        foreach ($f['strName'] as $slotName) {
            if (trim((string)$slotName) !== '') {
                $hasLangName = true;
                break;
            }
        }
        if (!$hasLangName) {
            $f['strName'][1] = (string)($anchor['strName'] ?? '');
        }

        $f['Upload']  = (string)($anchor['Upload'] ?? 'Yes');

        $f['dtUDate'] = (string)($anchor['dtUDate'] ?? '');

        $f['UserID']  = (string)($anchor['UserID'] ?? '');

        question_class_form_export();

        return true;

    }

}



if (!function_exists('question_class_next_sort')) {

    /** 同問卷下下一個 Sort 值 */

    function question_class_next_sort(int $questionPKey): int

    {

        $max = crud_fetch_scalar(

            'SELECT MAX(Sort) AS m FROM question_class WHERE Question_PKey = :fk',

            ['fk' => $questionPKey],

            'm'

        );

        return ((int)$max) + 1;

    }

}

