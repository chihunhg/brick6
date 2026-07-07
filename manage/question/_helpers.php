<?php

declare(strict_types=1);



if (!function_exists('question_display_strname')) {
    /** 問卷顯示名稱（主檔 strName，空則取 question_lang 首筆） */
    function question_display_strname(int $questionPKey): string
    {
        if ($questionPKey <= 0) {
            return '';
        }

        $row = crud_fetch_one(
            'SELECT strName FROM question WHERE PKey = :pk LIMIT 1',
            ['pk' => $questionPKey]
        );
        if ($row !== null && trim((string)($row['strName'] ?? '')) !== '') {
            return (string)$row['strName'];
        }

        $row = crud_fetch_one(
            'SELECT strName FROM question_lang
             WHERE Question_PKey = :qpk AND TRIM(strName) <> \'\'
             ORDER BY intLang ASC LIMIT 1',
            ['qpk' => $questionPKey]
        );

        return (string)($row['strName'] ?? '');
    }
}

if (!function_exists('question_child_return_url')) {
    /**
     * 問卷子模組列表返回網址
     *
     * @param string $fkParam 查詢參數名（question_class 用 Question_PKey；question_item 用 Question_D_PKey）
     */
    function question_child_return_url(
        int $parentPKey,
        string $listFile = 'list.php',
        string $fkParam = 'Question_PKey'
    ): string {
        return manage_child_return_url($parentPKey, $listFile, $fkParam);
    }
}

if (!function_exists('question_delete_report_rows')) {
    /** 刪除問卷填答主檔、明細（question_report / question_report_p / question_report_d） */
    function question_delete_report_rows(int $questionPKey): void
    {
        if ($questionPKey <= 0) {
            return;
        }

        $pdo = new dbPDO();

        if (function_exists('chkTable') && chkTable('question_report_d') && chkTable('question_report_p')) {
            $reportRows = crud_fetch_all(
                'SELECT PKey FROM question_report_p WHERE Question_PKey = :fk',
                ['fk' => $questionPKey]
            );
            foreach ($reportRows as $row) {
                $reportPk = (int)($row['PKey'] ?? 0);
                if ($reportPk <= 0) {
                    continue;
                }
                $pdo->delete('question_report_d', ' Report_PKey', $reportPk);
            }
        }

        if (function_exists('chkTable') && chkTable('question_report')) {
            $pdo->delete('question_report', ' Question_PKey', $questionPKey);
        }

        if (function_exists('chkTable') && chkTable('question_report_p')) {
            $pdo->delete('question_report_p', ' Question_PKey', $questionPKey);
        }

        $pdo->close();
    }
}

if (!function_exists('question_delete_related_rows')) {

    /** 刪除問卷關聯的類別、題目、答案、填答紀錄 */
    function question_delete_related_rows(int $questionPKey): void
    {
        if ($questionPKey <= 0) {
            return;
        }

        require_once __DIR__ . '/../question_class/_form_data.php';
        require_once __DIR__ . '/../question_item/_form_data.php';

        $classRows = crud_fetch_all(
            'SELECT PKey FROM question_class WHERE Question_PKey = :fk',
            ['fk' => $questionPKey]
        );
        foreach ($classRows as $row) {
            $classPk = (int)($row['PKey'] ?? 0);
            if ($classPk <= 0) {
                continue;
            }
            question_class_delete_related_rows($classPk);
        }

        $orphanItems = crud_fetch_all(
            'SELECT PKey FROM question_item WHERE Question_PKey = :fk',
            ['fk' => $questionPKey]
        );
        foreach ($orphanItems as $row) {
            $itemPk = (int)($row['PKey'] ?? 0);
            if ($itemPk <= 0) {
                continue;
            }
            question_item_delete_related_rows($itemPk);
        }

        question_delete_report_rows($questionPKey);
    }

}



if (!function_exists('question_msg_pkey_for_row')) {
    /** question_msg.PKey 非 AUTO_INCREMENT，以 Question_PKey * 100 + Sort 產生唯一鍵 */
    function question_msg_pkey_for_row(int $questionPKey, int $sort): int
    {
        return $questionPKey * 100 + $sort;
    }
}

if (!function_exists('question_save_msg_langs')) {
    /**
     * 各語系問卷介紹寫入 question_msg（Sort = 語系代碼）
     *
     * @param array<int,string> $contentsByLang  key 為語系序號
     */
    function question_save_msg_langs(int $questionPKey, array $contentsByLang): void
    {
        if ($questionPKey <= 0 || !function_exists('chkTable') || !chkTable('question_msg')) {
            return;
        }

        foreach ($contentsByLang as $lang => $html) {
            $lang = (int)$lang;
            if ($lang <= 0) {
                continue;
            }

            $existing = crud_fetch_one(
                'SELECT PKey FROM question_msg WHERE Question_PKey = :fk AND Sort = :sort LIMIT 1',
                ['fk' => $questionPKey, 'sort' => $lang]
            );

            $row = [
                'Question_PKey' => SqlFilter($questionPKey, 'int'),
                'Sort'          => SqlFilter($lang, 'int'),
                'Contents'      => SqlFilter((string)$html, 'tab'),
                'dtDate'        => date('Y-m-d H:i:s'),
            ];

            $pdo = new dbPDO();
            if ($existing !== null) {
                $pdo->update('question_msg', $row, 'PKey', (int)$existing['PKey']);
            } else {
                $row['PKey'] = SqlFilter(question_msg_pkey_for_row($questionPKey, $lang), 'int');
                $pdo->insert('question_msg', $row);
            }
            $pdo->close();
        }
    }
}

if (!function_exists('question_normalize_editor_html')) {
    /** 還原問卷介紹 HTML（避免 CKEditor 顯示 &lt;p&gt; 或原始標籤字串） */
    function question_normalize_editor_html(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/iu', '', $html) ?? $html;
        for ($pass = 0; $pass < 2; $pass++) {
            if (strpos($html, '&lt;') === false && strpos($html, '&amp;lt;') === false) {
                break;
            }
            if (!preg_match('/<[a-z][\s>\/]/i', $html)) {
                $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($decoded !== $html) {
                    $html = $decoded;
                }
            }
        }
        return $html;
    }
}

if (!function_exists('question_load_msg_contents_all')) {
    /** @return array<int,string> Sort（語系）=> Contents */
    function question_load_msg_contents_all(int $questionPKey): array
    {
        if ($questionPKey <= 0 || !function_exists('chkTable') || !chkTable('question_msg')) {
            return [];
        }

        $rows = crud_fetch_all(
            'SELECT Sort, Contents FROM question_msg WHERE Question_PKey = :fk ORDER BY Sort',
            ['fk' => $questionPKey]
        );
        $out = [];
        foreach ($rows as $row) {
            $sort = (int)($row['Sort'] ?? 0);
            if ($sort >= 1) {
                $out[$sort] = question_normalize_editor_html((string)($row['Contents'] ?? ''));
            }
        }
        return $out;
    }
}

if (!function_exists('question_report_counts_by_ids')) {
    /**
     * 各問卷填寫筆數（question_report_p.Question_PKey）
     *
     * @param int[] $questionPKeys
     * @return array<int,int>
     */
    function question_report_counts_by_ids(array $questionPKeys): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $questionPKeys), static fn(int $x): bool => $x > 0)));
        if ($ids === [] || !function_exists('chkTable') || !chkTable('question_report_p')) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $idx => $pk) {
            $key = 'pk' . $idx;
            $placeholders[] = ':' . $key;
            $params[$key] = $pk;
        }

        $rows = crud_fetch_all(
            'SELECT Question_PKey, COUNT(*) AS Total FROM question_report_p'
            . ' WHERE Question_PKey IN (' . implode(',', $placeholders) . ') GROUP BY Question_PKey',
            $params
        );

        $out = [];
        foreach ($rows as $row) {
            $qpk = (int)($row['Question_PKey'] ?? 0);
            if ($qpk > 0) {
                $out[$qpk] = (int)($row['Total'] ?? 0);
            }
        }
        return $out;
    }
}

