<?php
declare(strict_types=1);
/**
 * module_class 表單資料
 */

if (!function_exists('module_class_next_sort')) {
    /** 新增下一個 Sort 值 */
    function module_class_next_sort(): int
    {
        return crud_next_sort('module_class', [], 'Sort');
    }
}

if (!function_exists('module_class_detail_init_defaults')) {
    /** 初始化表單預設變數 */
    function module_class_detail_init_defaults(): void
    {
        $GLOBALS['module_class_form_vars'] = [
            'Update_PKey' => 0,
            'Sort'        => 0,
            'strName'     => '',
            'Upload'      => 'Yes',
            'dtUDate'     => '',
            'UserID'      => '',
        ];
        module_class_detail_export_vars();
    }
}

if (!function_exists('module_class_detail_export_vars')) {
    /** 將 module_class_form_vars 匯出至 $GLOBALS */
    function module_class_detail_export_vars(): void
    {
        foreach ($GLOBALS['module_class_form_vars'] as $key => $val) {
            $GLOBALS[$key] = $val;
        }
    }
}

if (!function_exists('module_class_detail_load')) {
    /** 自 DB 載入一筆主檔 */
    function module_class_detail_load(int $pkey): bool
    {
        if ($pkey <= 0) {
            return false;
        }

        $row = crud_fetch_one(
            'SELECT PKey, Sort, strName, Upload, UserID, dtUDate, dtDate FROM module_class WHERE PKey = :pk LIMIT 1',
            ['pk' => $pkey]
        );
        if ($row === null) {
            return false;
        }

        if (!isset($GLOBALS['module_class_form_vars'])) {
            module_class_detail_init_defaults();
        }

        $v = &$GLOBALS['module_class_form_vars'];
        $v['Update_PKey'] = (int)($row['PKey'] ?? 0);
        $v['Sort']        = (int)($row['Sort'] ?? 0);
        $v['strName']     = (string)($row['strName'] ?? '');
        $v['Upload']      = (string)($row['Upload'] ?? 'Yes');
        $v['dtUDate']     = (string)($row['dtUDate'] ?? '');
        $v['UserID']      = (string)($row['UserID'] ?? '');
        module_class_detail_export_vars();

        return true;
    }
}

if (!function_exists('module_class_detail_load_copy')) {
    /** 複製來源列至新增表單（不保留 PKey） */
    function module_class_detail_load_copy(int $pkey): void
    {
        if ($pkey <= 0 || !module_class_detail_load($pkey)) {
            return;
        }
        $GLOBALS['module_class_form_vars']['Update_PKey'] = 0;
        $GLOBALS['module_class_form_vars']['Sort'] = module_class_next_sort();
        module_class_detail_export_vars();
    }
}

if (!function_exists('module_class_addin_validate')) {
    /** @param array<string, mixed> $filter */
    function module_class_addin_validate(array $filter): string
    {
        $msg = '';
        $sort = trim((string)($filter['Sort'] ?? ''));
        if ($sort === '' || !preg_match('/^\d+$/', $sort)) {
            $msg .= "【順序】空白或非數字格式\n";
        }
        $name = trim((string)($filter['strName'] ?? ''));
        if ($name === '') {
            $msg .= "【標題】不可空白\n";
        }
        $upload = (string)($filter['Upload'] ?? '');
        if (!in_array($upload, ['Yes', 'No'], true)) {
            $msg .= "【上下架】格式錯誤\n";
        }
        return $msg;
    }
}

if (!function_exists('module_class_addin_build_master_data')) {
    /** @param array<string, mixed> $filter */
    function module_class_addin_build_master_data(array $filter, string $loginId, bool $isNew): array
    {
        $sort = safe_int($filter['Sort'] ?? 0);
        if ($sort <= 0 && $isNew) {
            $sort = module_class_next_sort();
        }

        $data = [
            'Sort'    => SqlFilter(max(0, $sort), 'int'),
            'strName' => SqlFilter(trim((string)($filter['strName'] ?? '')), 'tab'),
            'Upload'  => SqlFilter((string)($filter['Upload'] ?? 'Yes'), 'tab'),
            'dtUDate' => date('Y-m-d H:i:s'),
            'UserID'  => SqlFilter($loginId, 'tab'),
        ];

        return $data;
    }
}
