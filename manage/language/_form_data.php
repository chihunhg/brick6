<?php
declare(strict_types=1);
/**
 * language 表單資料
 */

if (!function_exists('language_require_admin')) {
    function language_require_admin(): void {
        global $Login_ID, $s3;
        if (($Login_ID ?? '') !== 'Admin') {
            manage_alert_script('無進入[' . ($s3 ?? '語系設定') . ']權限!', '../index.php');
            exit;
        }
    }
}

if (!function_exists('language_next_sort')) {
    function language_next_sort(): int {
        return crud_next_sort('language', [], 'Sort');
    }
}

if (!function_exists('language_detail_init_defaults')) {
    function language_detail_init_defaults(): void {
        $GLOBALS['language_form_vars'] = [
            'Update_PKey' => 0,
            'Sort'        => 0,
            'strName'     => '',
            'Upload'      => 'Yes',
            'dtUDate'     => '',
            'UserID'      => '',
        ];
        language_detail_export_vars();
    }
}

if (!function_exists('language_detail_export_vars')) {
    function language_detail_export_vars(): void {
        foreach ($GLOBALS['language_form_vars'] as $key => $val) {
            $GLOBALS[$key] = $val;
        }
    }
}

if (!function_exists('language_detail_load')) {
    function language_detail_load(int $pkey): bool {
        if ($pkey <= 0) {
            return false;
        }

        $row = crud_fetch_one(
            'SELECT PKey, Sort, strName, Upload, UserID, dtUDate, dtDate FROM language WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
        if ($row === null) {
            return false;
        }

        if (!isset($GLOBALS['language_form_vars'])) {
            language_detail_init_defaults();
        }

        $v = &$GLOBALS['language_form_vars'];
        $v['Update_PKey'] = (int)($row['PKey'] ?? 0);
        $v['Sort']        = (int)($row['Sort'] ?? 0);
        $v['strName']     = (string)($row['strName'] ?? '');
        $v['Upload']      = (string)($row['Upload'] ?? 'Yes');
        $v['dtUDate']     = (string)($row['dtUDate'] ?? '');
        $v['UserID']      = (string)($row['UserID'] ?? '');
        language_detail_export_vars();

        return true;
    }
}

if (!function_exists('language_addin_validate')) {
    /** @param array<string, mixed> $filter */
    function language_addin_validate(array $filter): string {
        $msg = '';
        $name = trim((string)($filter['strName'] ?? ''));
        if ($name === '') {
            $msg .= "【語系名稱】不可空白\n";
        }
        $upload = (string)($filter['Upload'] ?? '');
        if (!in_array($upload, ['Yes', 'No'], true)) {
            $msg .= "【上下架】格式錯誤\n";
        }
        return $msg;
    }
}

if (!function_exists('language_addin_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function language_addin_build_master_data(array $filter, string $loginId, bool $isNew): array {
        $data = [
            'strName' => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'Upload'  => SqlFilter((string)($filter['Upload'] ?? 'Yes'), 'tab'),
            'dtUDate' => date('Y-m-d H:i:s'),
            'UserID'  => SqlFilter($loginId, 'tab'),
        ];
        if ($isNew) {
            $sort = safe_int($filter['Sort'] ?? 0);
            if ($sort <= 0) {
                $sort = language_next_sort();
            }
            $data['Sort'] = SqlFilter($sort, 'int');
        }
        return $data;
    }
}
