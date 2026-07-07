<?php
declare(strict_types=1);

/**
 * 列表批次上下架：POST Batch=1、Upload=Yes|No、nid[]=PKey…
 * 由各模組 _upload.php 在定義 $table_name 後 require。
 */
if (!function_exists('manage_handle_upload_batch_request')) {
    /**
     * 處理列表批次上下架 POST（Batch=1）；命中則 echo 後 exit，未命中回傳 false
     */
    function manage_handle_upload_batch_request(string $tableName, string $pkName = 'PKey'): bool {
        global $filter_array, $WorkFile, $Login_ID, $Module_PKey, $Module_Name;

        if (($filter_array['Batch'] ?? '') !== '1') {
            return false;
        }

        $ids = $filter_array['nid'] ?? [];
        if (!is_array($ids)) {
            $ids = ($ids !== '' && $ids !== null) ? [$ids] : [];
        }

        $upload = isset($filter_array['Upload'])
            ? (string)SqlFilter($filter_array['Upload'], 'tab')
            : 'No';

        if ($upload !== 'Yes' && $upload !== 'No') {
            echo 'Upload 值錯誤';
            exit;
        }

        $res = update_upload_batch_by_table(
            $tableName,
            $pkName,
            $ids,
            $upload,
            [
                'WorkFile'    => $WorkFile ?? __FILE__,
                'Login_ID'    => $Login_ID ?? 'system',
                'Module_PKey' => $Module_PKey ?? 0,
                'Module_Name' => $Module_Name ?? '',
                'Action'      => $upload === 'Yes' ? '批次發佈' : '批次下架',
            ]
        );

        if (empty($res['ok'])) {
            echo $res['error'] ?? '更新失敗';
            exit;
        }

        echo 'OK';
        exit;
    }
}
