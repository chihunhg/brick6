<?php
declare(strict_types=1);

if (!function_exists('album_d_resolve_album_pkey')) {
    function album_d_resolve_album_pkey(): int
    {
        global $filter_array;

        foreach (['Album_PKey', 'album_pkey'] as $key) {
            if (isset($_GET[$key]) && is_scalar($_GET[$key])) {
                $v = safe_int((string)$_GET[$key]);
                if ($v > 0) {
                    return $v;
                }
            }
        }

        if (!empty($_SERVER['QUERY_STRING']) && is_string($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $qs);
            if (is_array($qs)) {
                foreach (['Album_PKey', 'album_pkey'] as $key) {
                    if (isset($qs[$key]) && is_scalar($qs[$key])) {
                        $v = safe_int((string)$qs[$key]);
                        if ($v > 0) {
                            return $v;
                        }
                    }
                }
            }
        }

        $pk = safe_int($filter_array['Album_PKey'] ?? $_REQUEST['Album_PKey'] ?? 0);
        $rowPKey = safe_int($filter_array['PKey'] ?? $_REQUEST['PKey'] ?? 0);
        if ($pk <= 0 && $rowPKey > 0) {
            $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
            if ($script === 'update.php') {
                $imgRow = crud_fetch_one(
                    'SELECT Album_PKey FROM album_img WHERE PKey = :pk LIMIT 1',
                    ['pk' => $rowPKey]
                );
                if ($imgRow !== null) {
                    $apk = (int)($imgRow['Album_PKey'] ?? 0);
                    if ($apk > 0) {
                        return $apk;
                    }
                }
            }
            $pk = $rowPKey;
        }

        return $pk;
    }
}

if (!function_exists('album_d_resolve_parent_strname')) {
    function album_d_resolve_parent_strname(int $albumPKey, ?array $masterRow = null): string
    {
        $name = '';
        if ($masterRow !== null) {
            $name = trim((string)($masterRow['strName'] ?? ''));
        }
        if ($name !== '' || $albumPKey <= 0) {
            return $name;
        }

        if (function_exists('chkTable') && chkTable('album_lang')
            && function_exists('crud_load_lang_slots_data')) {
            $langData = crud_load_lang_slots_data('album_lang', 'Album_PKey', $albumPKey);
            global $array_lang;
            $langCount = !empty($array_lang) && is_array($array_lang)
                ? count($array_lang)
                : 6;
            for ($i = 1; $i <= $langCount; $i++) {
                $langName = trim((string)($langData['strName'][$i] ?? ''));
                if ($langName !== '') {
                    return $langName;
                }
            }
        }

        return $name;
    }
}

if (!function_exists('album_d_fetch_album_row')) {
    /** @return array<string,mixed>|null */
    function album_d_fetch_album_row(int $albumPKey): ?array
    {
        if ($albumPKey <= 0) {
            return null;
        }
        return crud_fetch_one(
            'SELECT * FROM album WHERE PKey = :pk LIMIT 1',
            ['pk' => $albumPKey]
        );
    }
}

if (!function_exists('album_d_load_parent')) {
    /** @return array{ok:bool, Album_PKey:int, Album_Name:string, album_row:array<string,mixed>|null} */
    function album_d_load_parent(int $albumPKey): array
    {
        $result = ['ok' => false, 'Album_PKey' => 0, 'Album_Name' => '', 'album_row' => null];
        if ($albumPKey <= 0) {
            return $result;
        }
        $row = album_d_fetch_album_row($albumPKey);
        $result['album_row'] = $row;
        if ($row === null) {
            return $result;
        }
        $result['ok'] = true;
        $result['Album_PKey'] = (int)($row['PKey'] ?? $row['pkey'] ?? 0);
        $result['Album_Name'] = album_d_resolve_parent_strname($result['Album_PKey'], $row);
        return $result;
    }
}

if (!function_exists('album_d_build_debug_info')) {
    /** @return array<string,mixed> */
    function album_d_build_debug_info(int $albumPKey, array $parent): array
    {
        global $filter_array;

        $langRows = [];
        if ($albumPKey > 0 && function_exists('chkTable') && chkTable('album_lang')) {
            $langRows = crud_fetch_all(
                'SELECT * FROM album_lang WHERE Album_PKey = :pk ORDER BY Sort',
                ['pk' => $albumPKey]
            );
        }

        $langSlots = [];
        if ($albumPKey > 0 && function_exists('crud_load_lang_slots_data')) {
            $langSlots = crud_load_lang_slots_data('album_lang', 'Album_PKey', $albumPKey);
        }

        return [
            'request' => [
                'GET'           => $_GET,
                'QUERY_STRING'  => (string)($_SERVER['QUERY_STRING'] ?? ''),
                'resolved_pkey' => album_d_resolve_album_pkey(),
            ],
            'filter_array' => is_array($filter_array ?? null) ? $filter_array : [],
            'parent'       => $parent,
            'album_row'    => $parent['album_row'] ?? album_d_fetch_album_row($albumPKey),
            'album_lang'   => $langRows,
            'lang_slots'   => $langSlots,
            'album_d_form' => $GLOBALS['album_d_form'] ?? null,
            'globals'      => [
                'Album_PKey' => $GLOBALS['Album_PKey'] ?? null,
                'Album_Name' => $GLOBALS['Album_Name'] ?? null,
            ],
        ];
    }
}

if (!function_exists('album_d_child_return_url')) {
    function album_d_child_return_url(int $albumPKey, string $listFile = 'list.php'): string
    {
        return manage_child_return_url($albumPKey, $listFile, 'Album_PKey');
    }
}

if (!function_exists('album_d_list_where')) {
    /** @return array{0:string,1:array<string,mixed>} */
    function album_d_list_where(int $albumPKey): array
    {
        return [
            ' WHERE Album_PKey = :Album_PKey AND (Home IS NULL OR Home <> :Home)',
            [
                'Album_PKey' => SqlFilter($albumPKey, 'int'),
                'Home'       => SqlFilter('Yes', 'tab'),
            ],
        ];
    }
}

if (!function_exists('album_d_next_sort')) {
    function album_d_next_sort(int $albumPKey): int
    {
        if ($albumPKey <= 0) {
            return 1;
        }
        $max = (int)crud_fetch_scalar(
            'SELECT COALESCE(MAX(Sort), 0) AS M FROM album_img
             WHERE Album_PKey = :apk AND (Home IS NULL OR Home <> :home)',
            ['apk' => $albumPKey, 'home' => 'Yes'],
            'M'
        );
        return $max + 1;
    }
}

if (!function_exists('album_d_delete_row_files')) {
  /** @param int[] $pkeys */
    function album_d_delete_row_files(array $pkeys): void
    {
        $uploadBase = crud_upload_base();
        foreach ($pkeys as $pk) {
            $pk = (int)$pk;
            if ($pk <= 0) {
                continue;
            }
            $row = crud_fetch_one(
                'SELECT Forder, Photo1 FROM album_img WHERE PKey = :pk LIMIT 1',
                ['pk' => $pk]
            );
            if ($row === null || (string)($row['Photo1'] ?? '') === '') {
                continue;
            }
            crud_delete_image_variants(
                $uploadBase,
                (string)($row['Forder'] ?? ''),
                (string)$row['Photo1']
            );
        }
    }
}

if (!function_exists('album_d_thumb_url')) {
    function album_d_thumb_url(array $row): string
    {
        $forder = (string)($row['Forder'] ?? '');
        $photo  = (string)($row['Photo1'] ?? '');
        if ($forder === '' || $photo === '') {
            return '';
        }
        $relDir = '../../Upload/' . $forder . '/';
        $absDir = rtrim(crud_upload_base(), '/\\') . '/' . $forder . '/';
        foreach (['thumb_', 's_', ''] as $prefix) {
            $file = $prefix . $photo;
            if (is_file($absDir . $file)) {
                return $relDir . $file;
            }
        }
        return '';
    }
}

if (!function_exists('album_d_breadcrumbs_for_form')) {
    /** @return list<array{label:string, href?:string}> */
    function album_d_breadcrumbs_for_form(int $albumPKey, string $albumName): array
    {
        global $manNo;
        return [
            ['label' => '單元管理'],
            ['label' => (string)($GLOBALS['Module_Name'] ?? '')],
            [
                'label' => '相簿管理',
                'href'  => '../album/list.php?manNo=' . urlencode((string)($manNo ?? '')),
            ],
            ['label' => $albumName, 'href' => album_d_child_return_url($albumPKey)],
            ['label' => manage_breadcrumb_form_action_label()],
        ];
    }
}

if (!function_exists('album_d_fetch_row_for_edit')) {
    /** @return array<string,mixed>|null */
    function album_d_fetch_row_for_edit(int $pkey, int $albumPKey): ?array
    {
        if ($pkey <= 0 || $albumPKey <= 0) {
            return null;
        }
        return crud_fetch_one(
            'SELECT ai.*, a.strName AS Album_Name
             FROM album_img ai
             INNER JOIN album a ON a.PKey = ai.Album_PKey
             WHERE ai.PKey = :pk AND ai.Album_PKey = :apk
               AND (ai.Home IS NULL OR ai.Home <> :home)
             LIMIT 1',
            [
                'pk'   => $pkey,
                'apk'  => $albumPKey,
                'home' => 'Yes',
            ]
        );
    }
}

if (!function_exists('album_d_ajax_csrf_verify')) {
    function album_d_ajax_csrf_verify(string $csrfKey = 'album_d_addin'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $posted = (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $session = (string)($_SESSION['csrf'][$csrfKey] ?? '');
        if ($posted === '' || $session === '' || !hash_equals($session, $posted)) {
            crud_json_response(false, 'CSRF 驗證失敗');
        }
    }
}

if (!function_exists('album_d_staged_preview_url')) {
    function album_d_staged_preview_url(string $forder, string $photo): string
    {
        $forder = trim($forder);
        $photo = trim($photo);
        if ($forder === '' || $photo === '') {
            return '';
        }
        $relDir = '../../Upload/' . $forder . '/';
        $absDir = rtrim(crud_upload_base(), '/\\') . '/' . $forder . '/';
        foreach (['thumb_', 's_', ''] as $prefix) {
            $file = $prefix . $photo;
            if (is_file($absDir . $file)) {
                return $relDir . $file . '?' . time();
            }
        }

        return '';
    }
}

if (!function_exists('album_d_staging_count')) {
    function album_d_staging_count(int $albumPKey): int
    {
        if ($albumPKey <= 0 || !isset($_SESSION['album_d_staged'][$albumPKey])) {
            return 0;
        }
        $bucket = $_SESSION['album_d_staged'][$albumPKey];

        return is_array($bucket) ? count($bucket) : 0;
    }
}

if (!function_exists('album_d_staging_list')) {
    /**
     * @return list<array{upload_id:string, preview_url:string, original_name:string}>
     */
    function album_d_staging_list(int $albumPKey): array
    {
        if ($albumPKey <= 0) {
            return [];
        }
        $bucket = $_SESSION['album_d_staged'][$albumPKey] ?? [];
        if (!is_array($bucket)) {
            return [];
        }

        $result = [];
        foreach ($bucket as $id => $item) {
            if (!is_array($item)) {
                continue;
            }
            $forder = (string)($item['forder'] ?? '');
            $photo = (string)($item['photo'] ?? '');
            $result[] = [
                'upload_id'     => (string)$id,
                'preview_url'   => album_d_staged_preview_url($forder, $photo),
                'original_name' => (string)($item['name'] ?? ''),
            ];
        }

        return $result;
    }
}

if (!function_exists('album_d_staging_release')) {
    /** 清空暫存索引（不刪除實體檔；成功寫入 DB 後使用） */
    function album_d_staging_release(int $albumPKey): void
    {
        if ($albumPKey <= 0) {
            return;
        }
        unset($_SESSION['album_d_staged'][$albumPKey]);
    }
}

if (!function_exists('album_d_staging_purge')) {
    /** 放棄上傳：刪除暫存實體檔並清空 session */
    function album_d_staging_purge(int $albumPKey): void
    {
        if ($albumPKey <= 0) {
            return;
        }
        $bucket = $_SESSION['album_d_staged'][$albumPKey] ?? [];
        if (is_array($bucket)) {
            foreach ($bucket as $item) {
                if (!is_array($item) || (string)($item['photo'] ?? '') === '') {
                    continue;
                }
                crud_delete_image_variants(
                    crud_upload_base(),
                    (string)($item['forder'] ?? ''),
                    (string)$item['photo']
                );
            }
        }
        unset($_SESSION['album_d_staged'][$albumPKey]);
    }
}

if (!function_exists('album_d_staging_resolve')) {
    /**
     * @param list<string> $ids
     * @return list<array<string,mixed>>
     */
    function album_d_staging_resolve(int $albumPKey, array $ids): array
    {
        if ($albumPKey <= 0 || $ids === []) {
            return [];
        }
        $bucket = $_SESSION['album_d_staged'][$albumPKey] ?? [];
        if (!is_array($bucket)) {
            return [];
        }

        $result = [];
        foreach ($ids as $id) {
            $id = trim((string)$id);
            if ($id === '' || !isset($bucket[$id]) || !is_array($bucket[$id])) {
                continue;
            }
            $result[] = array_merge($bucket[$id], ['upload_id' => $id]);
        }

        return $result;
    }
}

if (!function_exists('album_d_staging_consume')) {
    /** @param list<string> $ids */
    function album_d_staging_consume(int $albumPKey, array $ids): void
    {
        if ($albumPKey <= 0 || $ids === []) {
            return;
        }
        if (!isset($_SESSION['album_d_staged'][$albumPKey]) || !is_array($_SESSION['album_d_staged'][$albumPKey])) {
            return;
        }
        foreach ($ids as $id) {
            unset($_SESSION['album_d_staged'][$albumPKey][trim((string)$id)]);
        }
    }
}

if (!function_exists('album_d_staging_remove')) {
    function album_d_staging_remove(int $albumPKey, string $id, bool $deleteFiles = true): bool
    {
        $id = trim($id);
        if ($albumPKey <= 0 || $id === '') {
            return false;
        }
        if (!isset($_SESSION['album_d_staged'][$albumPKey][$id])) {
            return false;
        }

        $item = (array)$_SESSION['album_d_staged'][$albumPKey][$id];
        if ($deleteFiles && (string)($item['photo'] ?? '') !== '') {
            crud_delete_image_variants(
                crud_upload_base(),
                (string)($item['forder'] ?? ''),
                (string)$item['photo']
            );
        }
        unset($_SESSION['album_d_staged'][$albumPKey][$id]);

        return true;
    }
}

if (!function_exists('album_d_upload_staged_file')) {
    /**
     * @param array<string,mixed> $file $_FILES 單檔結構
     * @param array<string,mixed> $detailConfig
     * @return array{upload_id:string, preview_url:string, original_name:string}
     */
    function album_d_upload_staged_file(int $albumPKey, array $file, array $detailConfig): array
    {
        if ($albumPKey <= 0) {
            throw new RuntimeException('相簿參數錯誤');
        }

        $maxSlots = max(1, (int)($detailConfig['img_slot_max'] ?? $detailConfig['add_photo_slots'] ?? 10));
        if (album_d_staging_count($albumPKey) >= $maxSlots) {
            throw new RuntimeException('已達最多 ' . $maxSlots . ' 張圖片');
        }

        $uploadDirInfo = crud_upload_dir();
        if ($uploadDirInfo['error'] !== '') {
            throw new RuntimeException($uploadDirInfo['error']);
        }

        global $size_bytes;
        $size_bytes = (int)($size_bytes ?? 2000 * 1024);

        $file_array = ['Photo1' => $file];
        $uploadResult = crud_upload_file_slots($file_array, $uploadDirInfo['dir'], [1], [
            'forder_prefix' => (string)($detailConfig['forder_prefix'] ?? 'album_'),
            'size_bytes'    => $size_bytes,
            'allowed_exts'  => ['gif', 'jpg', 'jpeg', 'png', 'webp'],
            'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
            'field_prefix'  => 'Photo',
            'resize_thumb'  => true,
        ]);

        $uploadMsg = trim((string)($uploadResult['messages'] ?? ''));
        if ($uploadMsg !== '') {
            throw new RuntimeException($uploadMsg);
        }
        if (empty($uploadResult['photos'][1])) {
            throw new RuntimeException('上傳失敗');
        }

        $id = bin2hex(random_bytes(16));
        $item = [
            'forder'  => rtrim((string)($uploadResult['monthdir'] ?? date('Ym')), "\\/"),
            'photo'   => (string)$uploadResult['photos'][1],
            'photoW'  => (int)($uploadResult['photoW'][1] ?? 0),
            'photoH'  => (int)($uploadResult['photoH'][1] ?? 0),
            'name'    => (string)($file['name'] ?? ''),
        ];

        if (!isset($_SESSION['album_d_staged']) || !is_array($_SESSION['album_d_staged'])) {
            $_SESSION['album_d_staged'] = [];
        }
        if (!isset($_SESSION['album_d_staged'][$albumPKey]) || !is_array($_SESSION['album_d_staged'][$albumPKey])) {
            $_SESSION['album_d_staged'][$albumPKey] = [];
        }
        $_SESSION['album_d_staged'][$albumPKey][$id] = $item;

        return [
            'upload_id'     => $id,
            'preview_url'   => album_d_staged_preview_url($item['forder'], $item['photo']),
            'original_name' => $item['name'],
        ];
    }
}
