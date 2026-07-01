<?php
declare(strict_types=1);

require_once __DIR__ . '/_helpers.php';

if (!function_exists('album_d_form_skip_global')) {
    /** @return list<string> */
    function album_d_form_skip_global(): array
    {
        return ['Album_PKey', 'Album_Name'];
    }
}

if (!function_exists('album_d_form_init')) {
    function album_d_form_init(): void
    {
        manage_form_bag_init('album_d_form', [
            'Update_PKey' => 0,
            'Album_PKey'  => 0,
            'Album_Name'  => '',
            'Sort'        => 1,
            'Photo1'      => '',
            'PhotoM'      => '',
            'Forder'      => '',
            'dtUDate'     => '',
            'UserID'      => '',
        ], album_d_form_skip_global());
    }
}

if (!function_exists('album_d_form_export')) {
    function album_d_form_export(): void
    {
        manage_form_bag_export('album_d_form', album_d_form_skip_global());
    }
}

if (!function_exists('album_d_form_apply_parent')) {
    function album_d_form_apply_parent(int $albumPKey, string $albumName): void
    {
        if (!isset($GLOBALS['album_d_form']) || !is_array($GLOBALS['album_d_form'])) {
            album_d_form_init();
        }

        manage_form_bag_apply_fields('album_d_form', [
            'Album_PKey' => $albumPKey,
            'Album_Name' => $albumName,
        ], album_d_form_skip_global());
    }
}

if (!function_exists('album_d_form_load')) {
    function album_d_form_load(int $pkey, int $albumPKey): bool
    {
        $row = album_d_fetch_row_for_edit($pkey, $albumPKey);
        if ($row === null) {
            return false;
        }
        $photoPath = '';
        if ((string)($row['Photo1'] ?? '') !== '') {
            $photoPath = (string)($row['Forder'] ?? '') . '/' . (string)$row['Photo1'];
        }
        $GLOBALS['album_d_form'] = [
            'Update_PKey' => (int)($row['PKey'] ?? 0),
            'Album_PKey'  => (int)($row['Album_PKey'] ?? 0),
            'Album_Name'  => (string)($row['Album_Name'] ?? ''),
            'Sort'        => (int)($row['Sort'] ?? 0),
            'Photo1'      => $photoPath,
            'PhotoM'      => (string)($row['PhotoM'] ?? ''),
            'Forder'      => (string)($row['Forder'] ?? ''),
            'dtUDate'     => (string)($row['dtUDate'] ?? ''),
            'UserID'      => (string)($row['UserID'] ?? ''),
        ];
        album_d_form_export();
        return true;
    }
}

if (!function_exists('album_d_addin_validate')) {
    function album_d_addin_validate(
        array $parent,
        array $filter,
        int $formPKey,
        array $file_array,
        int $addSlots
    ): string {
        $msg = '';
        $albumPKey = (int)($parent['Album_PKey'] ?? 0);

        if ($formPKey > 0) {
            if (safe_int($filter['Sort'] ?? 0) < 0) {
                $msg .= "【順序】空白或非數字格式\n";
            }
            // 編輯頁刪除圖檔會刪除整列；若列已不存在，改要求重新上傳（視同重建）
            if (album_d_fetch_row_for_edit($formPKey, $albumPKey) === null) {
                $hasUpload = false;
                for ($i = 1; $i <= $addSlots; $i++) {
                    if (!empty($file_array['Photo' . $i]['name'] ?? '')) {
                        $hasUpload = true;
                        break;
                    }
                }
                if (!$hasUpload) {
                    $msg .= "【上傳圖片】請至少選擇一張圖片\n";
                }
            }
        } else {
            $hasUpload = false;
            for ($i = 1; $i <= $addSlots; $i++) {
                if (!empty($file_array['Photo' . $i]['name'] ?? '')) {
                    $hasUpload = true;
                    break;
                }
            }
            if (!$hasUpload) {
                $msg .= "【上傳圖片】請至少選擇一張圖片\n";
            }
        }

        return $msg;
    }
}

if (!function_exists('album_d_addin_save')) {
    /**
     * @param array<string,mixed> $detailConfig
     * @return array{action:string}
     */
    function album_d_addin_save(
        array $parent,
        int $formPKey,
        array $filter,
        array $file_array,
        string $loginId,
        array $detailConfig
    ): array {
        $table_name = (string)($detailConfig['master'] ?? 'album_img');
        $addSlots = max(1, (int)($detailConfig['add_photo_slots'] ?? 10));
        $albumPKey = (int)($parent['Album_PKey'] ?? 0);
        $existingRow = ($formPKey > 0 && $albumPKey > 0)
            ? album_d_fetch_row_for_edit($formPKey, $albumPKey)
            : null;
        $isUpdate = $existingRow !== null;
        $recreateAfterDelete = $formPKey > 0 && !$isUpdate;

        $uploadDirInfo = crud_upload_dir();
        $upload_foder = $uploadDirInfo['dir'];
        if ($uploadDirInfo['error'] !== '') {
            throw new RuntimeException($uploadDirInfo['error']);
        }

        $ForderName = (string)($detailConfig['forder_prefix'] ?? 'album_');
        global $size_bytes;
        $size_bytes = (int)($size_bytes ?? 2000 * 1024);
        $maxSlots = $isUpdate ? 1 : $addSlots;
        $indices = range(1, $maxSlots);

        $Photo = [];
        $PhotoW = [];
        $PhotoH = [];
        $PhotoM = [];

        for ($i = 1; $i <= $maxSlots; $i++) {
            if (isset($filter['PhotoM' . $i])) {
                $PhotoM[$i] = (string)$filter['PhotoM' . $i];
            }
        }

        $uploadResult = crud_upload_file_slots($file_array, $upload_foder, $indices, [
            'forder_prefix' => $ForderName,
            'size_bytes'    => $size_bytes,
            'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'webp'],
            'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
            'field_prefix'  => 'Photo',
            'resize_thumb'  => true,
        ]);

        foreach ((array)($uploadResult['photos'] ?? []) as $idx => $filename) {
            $Photo[(int)$idx] = (string)$filename;
        }
        foreach ((array)($uploadResult['photoW'] ?? []) as $idx => $w) {
            $PhotoW[(int)$idx] = (int)$w;
        }
        foreach ((array)($uploadResult['photoH'] ?? []) as $idx => $h) {
            $PhotoH[(int)$idx] = (int)$h;
        }

        $uploadMsg = (string)($uploadResult['messages'] ?? '');
        if ($uploadMsg !== '') {
            throw new RuntimeException($uploadMsg);
        }

        $forderVal = rtrim((string)($uploadResult['monthdir'] ?? date('Ym')), "\\/");

        if ($isUpdate) {
            $data_array = [
                'Sort'    => SqlFilter($filter['Sort'] ?? 0, 'int'),
                'PhotoM'  => SqlFilter((string)($filter['PhotoM'] ?? ''), 'tab'),
                'dtUDate' => date('Y-m-d H:i:s'),
                'UserID'  => SqlFilter($loginId, 'tab'),
            ];
            if (!empty($Photo[1])) {
                if ($existingRow !== null && (string)($existingRow['Photo1'] ?? '') !== '') {
                    crud_delete_image_variants(
                        $upload_foder,
                        (string)($existingRow['Forder'] ?? ''),
                        (string)$existingRow['Photo1']
                    );
                }
                $data_array['Forder']  = SqlFilter($forderVal, 'tab');
                $data_array['Photo1']  = SqlFilter($Photo[1], 'tab');
                $data_array['PhotoW1'] = SqlFilter($PhotoW[1] ?? 0, 'int');
                $data_array['PhotoH1'] = SqlFilter($PhotoH[1] ?? 0, 'int');
                $data_array['intType'] = SqlFilter(1, 'int');
            }
            $data_array = crud_filter_row_for_table($table_name, $data_array);

            $pdo = new dbPDO();
            $pdo->update($table_name, $data_array, 'PKey', $formPKey);
            $err = $pdo->getErrorMessage();
            $pdo->close();
            if ($err !== '') {
                crud_fail_db('album_d update', $err, $data_array, true);
            }

            return ['action' => '修改成功!'];
        }

        $editSort = safe_int($filter['Sort'] ?? 0);
        $nextSort = $recreateAfterDelete && $editSort > 0
            ? $editSort
            : album_d_next_sort($albumPKey);
        $inserted = 0;
        $maxInsertSlots = $recreateAfterDelete ? 1 : $addSlots;
        $pdo = new dbPDO();
        for ($i = 1; $i <= $maxInsertSlots; $i++) {
            if (empty($Photo[$i])) {
                continue;
            }
            $photoMCaption = $recreateAfterDelete
                ? (string)($filter['PhotoM'] ?? $PhotoM[$i] ?? '')
                : (string)($PhotoM[$i] ?? $filter['PhotoM' . $i] ?? '');
            $data_array = [
                'Album_PKey' => SqlFilter($albumPKey, 'int'),
                'Sort'       => SqlFilter($nextSort + $inserted, 'int'),
                'PhotoM'     => SqlFilter($photoMCaption, 'tab'),
                'Forder'     => SqlFilter($forderVal, 'tab'),
                'Photo1'     => SqlFilter($Photo[$i], 'tab'),
                'PhotoW1'    => SqlFilter($PhotoW[$i] ?? 0, 'int'),
                'PhotoH1'    => SqlFilter($PhotoH[$i] ?? 0, 'int'),
                'intType'    => SqlFilter(1, 'int'),
                'dtUDate'    => date('Y-m-d H:i:s'),
                'dtDate'     => date('Y-m-d H:i:s'),
                'UserID'     => SqlFilter($loginId, 'tab'),
            ];
            $data_array = crud_filter_row_for_table($table_name, $data_array);
            $pdo->insert($table_name, $data_array);
            $err = $pdo->getErrorMessage();
            if ($err !== '') {
                $pdo->close();
                crud_fail_db('album_d insert', $err, $data_array, true);
            }
            $inserted++;
        }
        $pdo->close();

        if ($inserted === 0) {
            throw new RuntimeException('沒有成功上傳的圖片');
        }

        return ['action' => $recreateAfterDelete ? '修改成功!' : '新增成功!'];
    }
}
