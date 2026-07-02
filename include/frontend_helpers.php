<?php

declare(strict_types=1);

/**
 * 前台列表／內頁共用（對應 manage 模組 _config.php 模式）
 *
 * 使用方式（例：news.php）：
 *   $frontendConfig = array_merge(require __DIR__.'/manage/news/_config.php', [
 *       'view' => 'view_news', 'class_link' => 'news', 'detail_link' => 'news-detail',
 *       'publish_window' => true, 'order_by' => 'OpenDate DESC', 'page_size' => 12,
 *   ]);
 *   frontend_module_set_config($frontendConfig);
 */

if (!function_exists('frontend_module_registry')) {
    /** @return array<string, int> */
    function frontend_module_registry(): array
    {
        static $registry = null;
        if ($registry === null) {
            $path = __DIR__ . '/frontend_modules.php';
            $registry = is_file($path) ? require $path : [];
        }

        return is_array($registry) ? $registry : [];
    }
}

if (!function_exists('frontend_module_pkey')) {
    /** 依單元 slug 取得 Module_PKey（定義於 include/frontend_modules.php） */
    function frontend_module_pkey(string $slug): int
    {
        $reg = frontend_module_registry();
        if (!isset($reg[$slug])) {
            throw new RuntimeException("frontend_module_pkey: 未定義的單元 [{$slug}]");
        }

        return (int)$reg[$slug];
    }
}

if (!function_exists('frontend_module_pkey_for_link')) {
    /**
     * 依前台列表頁檔名（如 news.htm）從 _inc 載入的 $Array_MU_Link 反查 Module_PKey；
     * 用於尚未列入 frontend_modules.php 的單元（如 events.htm）。
     */
    function frontend_module_pkey_for_link(string $pageLink): int
    {
        global $Array_MU_Link;

        $base = strtolower(basename(trim($pageLink)));
        if ($base === '' || empty($Array_MU_Link) || !is_array($Array_MU_Link)) {
            return 0;
        }

        foreach ($Array_MU_Link as $pkey => $link) {
            if (strtolower(basename((string)$link)) === $base) {
                return (int)$pkey;
            }
        }

        return 0;
    }
}

if (!function_exists('frontend_nav_module_items')) {
    /**
     * 主選單單元（來自 _inc.php 的 $Array_MU_*，依 PKey 排序）
     *
     * @return list<array{pkey:int,name:string,link:string,layer:int}>
     */
    function frontend_nav_module_items(): array
    {
        global $Array_MU_PKey, $Array_MU_Name, $Array_MU_Link, $Array_MU_Layer;

        $items = [];
        if (empty($Array_MU_PKey) || !is_array($Array_MU_PKey)) {
            return $items;
        }

        foreach ($Array_MU_PKey as $pkey => $_) {
            $pkey = (int)$pkey;
            $name = trim((string)($Array_MU_Name[$pkey] ?? ''));
            $link = trim((string)($Array_MU_Link[$pkey] ?? ''));
            if ($pkey <= 0 || $name === '' || $link === '') {
                continue;
            }
            $items[] = [
                'pkey'  => $pkey,
                'name'  => $name,
                'link'  => $link,
                'layer' => (int)($Array_MU_Layer[$pkey] ?? 0),
            ];
        }

        return $items;
    }
}

if (!function_exists('frontend_nav_list_slug')) {
    /** 列表頁檔名不含副檔名（news.htm → news） */
    function frontend_nav_list_slug(string $pageLink): string
    {
        $slug = pathinfo(basename($pageLink), PATHINFO_FILENAME);
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$slug);

        return $slug !== '' ? $slug : 'index';
    }
}

if (!function_exists('frontend_nav_href')) {
    function frontend_nav_href(string $pageLink): string
    {
        global $web_root;

        $pageLink = trim($pageLink);
        if ($pageLink === '') {
            return '#';
        }
        if (preg_match('#^(?:https?:)?//#i', $pageLink)) {
            return safe_href($pageLink);
        }

        return safe_href((string)($web_root . ltrim($pageLink, '/')));
    }
}

if (!function_exists('frontend_nav_class1_items')) {
    /** @return list<array<string,mixed>> */
    function frontend_nav_class1_items(int $modulePKey): array
    {
        global $this_lang;

        if ($modulePKey <= 0) {
            return [];
        }

        return crud_fetch_all(
            'SELECT PKey, strName FROM view_dbclass1'
            . ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey AND intLang = :intLang'
            . ' ORDER BY Sort',
            [
                'Upload'      => 'Yes',
                'Module_PKey' => $modulePKey,
                'intLang'     => (int)$this_lang,
            ]
        );
    }
}

if (!function_exists('frontend_nav_company_items')) {
    /**
     * 關於我們下拉選單（view_company 上架項目）
     *
     * @return list<array<string,mixed>>
     */
    function frontend_nav_company_items(int $modulePKey): array
    {
        global $this_lang;

        if ($modulePKey <= 0) {
            return [];
        }

        return crud_fetch_all(
            'SELECT PKey, strName FROM view_company'
            . ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey AND intLang = :intLang'
            . ' ORDER BY Sort',
            [
                'Upload'      => 'Yes',
                'Module_PKey' => $modulePKey,
                'intLang'     => (int)$this_lang,
            ]
        );
    }
}

if (!function_exists('frontend_nav_sub_items')) {
    /** 主選單下拉項目：關於我們用 view_company，其餘用 view_dbclass1 */
    function frontend_nav_sub_items(int $modulePKey): array
    {
        if ($modulePKey > 0 && $modulePKey === frontend_module_pkey('company')) {
            return frontend_nav_company_items($modulePKey);
        }

        return frontend_nav_class1_items($modulePKey);
    }
}

if (!function_exists('frontend_company_detail_href')) {
    function frontend_company_detail_href(int $pkey): string
    {
        global $web_root;

        if ($pkey <= 0) {
            return safe_href((string)$web_root);
        }

        return safe_href((string)($web_root . 'company' . $pkey . '.htm'));
    }
}

if (!function_exists('frontend_nav_sub_href')) {
    function frontend_nav_sub_href(int $modulePKey, string $listPageLink, int $itemPKey): string
    {
        if ($modulePKey > 0 && $modulePKey === frontend_module_pkey('company')) {
            return frontend_company_detail_href($itemPKey);
        }

        return frontend_nav_class1_href($listPageLink, $itemPKey);
    }
}

if (!function_exists('frontend_nav_class1_href')) {
    function frontend_nav_class1_href(string $listPageLink, int $class1PKey): string
    {
        global $web_root;

        $slug = frontend_nav_list_slug($listPageLink);

        return safe_href((string)($web_root . $slug . $class1PKey . '.htm'));
    }
}

if (!function_exists('frontend_nav_is_active')) {
    function frontend_nav_is_active(int $modulePKey): bool
    {
        global $Module_PKey, $page_link, $Array_MU_Link;

        if (!empty($Module_PKey) && (int)$Module_PKey === $modulePKey) {
            return true;
        }

        $muLink = trim((string)($Array_MU_Link[$modulePKey] ?? ''));
        if ($muLink !== '' && !empty($page_link)) {
            return strtolower(basename((string)$page_link)) === strtolower(basename($muLink));
        }

        return false;
    }
}

if (!function_exists('frontend_class1_display_name')) {
    /** Class1>0 顯示分類名稱，否則回傳列表預設標題（如「所有資訊」） */
    function frontend_class1_display_name(int $class1PKey, string $fallbackAll): string
    {
        if ($class1PKey <= 0) {
            return $fallbackAll;
        }

        $row = crud_fetch_one(
            'SELECT strName FROM view_dbclass1 WHERE PKey = :PKey',
            ['PKey' => $class1PKey]
        );
        if ($row === null) {
            return $fallbackAll;
        }

        return (string)crud_row_val($row, 'strName');
    }
}

if (!function_exists('frontend_module_set_config')) {
    /**
     * 註冊前台模組資料表（列表／內頁 SQL 僅允許使用此設定內的識別子）
     *
     * @param array{
     *   master:string,
     *   fk:string,
     *   view?:string,
     *   img?:string,
     *   lang?:string,
     *   msg?:string,
     *   link?:string,
     *   class_link?:string,
     *   detail_link?:string,
     *   publish_window?:bool,
     *   order_by?:string,
     *   page_size?:int,
     *   class1_filter_min_count?:int
     * } $config
     */
    function frontend_module_set_config(array $config): void
    {
        $master = trim((string)($config['master'] ?? ''));
        $fk     = trim((string)($config['fk'] ?? ''));
        if ($master === '' || !crud_is_safe_sql_identifier($master)) {
            throw new RuntimeException('frontend_module_set_config: master 表名無效');
        }
        if ($fk === '' || !crud_is_safe_sql_identifier($fk)) {
            throw new RuntimeException('frontend_module_set_config: fk 欄位名無效');
        }

        $view = trim((string)($config['view'] ?? 'view_' . $master));
        if (!crud_is_safe_sql_identifier($view)) {
            throw new RuntimeException('frontend_module_set_config: view 表名無效');
        }

        $normalized = crud_normalize_module_config($config);
        if (($normalized['master'] ?? '') === '') {
            $normalized['master'] = $master;
        }
        if (($normalized['fk'] ?? '') === '') {
            $normalized['fk'] = $fk;
        }
        foreach (['img', 'lang', 'msg', 'link'] as $childKey) {
            $childTable = trim((string)($config[$childKey] ?? ''));
            if ($childTable !== '' && crud_is_safe_sql_identifier($childTable)) {
                $normalized[$childKey] = $childTable;
            }
        }

        $normalized['view'] = $view;
        $normalized['class_link'] = trim((string)($config['class_link'] ?? $master));
        $normalized['detail_link'] = trim((string)($config['detail_link'] ?? $master . '-detail'));
        $normalized['publish_window'] = !empty($config['publish_window']);
        $normalized['order_by'] = frontend_safe_order_by((string)($config['order_by'] ?? 'PKey DESC'));
        $normalized['page_size'] = max(1, (int)($config['page_size'] ?? 12));
        $normalized['class1_filter_min_count'] = max(1, (int)($config['class1_filter_min_count'] ?? 2));

        $GLOBALS['frontend_module_config'] = $normalized;
    }
}

if (!function_exists('frontend_module_config')) {
    /** @return array<string, mixed> */
    function frontend_module_config(): array
    {
        $cfg = $GLOBALS['frontend_module_config'] ?? null;
        if (!is_array($cfg) || ($cfg['master'] ?? '') === '' || ($cfg['fk'] ?? '') === '') {
            throw new RuntimeException('frontend_module_set_config() 尚未設定或缺少 master / fk');
        }

        return $cfg;
    }
}

if (!function_exists('frontend_view_table')) {
    function frontend_view_table(): string
    {
        $cfg = frontend_module_config();

        return (string)$cfg['view'];
    }
}

if (!function_exists('frontend_safe_order_by')) {
    function frontend_safe_order_by(string $orderBy, string $fallback = 'PKey DESC'): string
    {
        $orderBy = trim($orderBy);
        if ($orderBy !== '' && preg_match('/^[A-Za-z0-9_,\s]+$/', $orderBy)) {
            return $orderBy;
        }

        return $fallback;
    }
}

if (!function_exists('frontend_init_breadcrumb')) {
    function frontend_init_breadcrumb(string $moduleName, string $moduleLink): void
    {
        global $bread_name, $break_link, $lang_text, $this_lang;

        $bread_name = is_array($bread_name ?? null) ? $bread_name : [];
        $break_link = is_array($break_link ?? null) ? $break_link : [];

        $bread_name[] = e_attr($lang_text['home'][$this_lang] ?? '首頁');
        $break_link[] = '';
        $bread_name[] = e_attr($moduleName);
        $break_link[] = $moduleLink;
    }
}

if (!function_exists('frontend_class1_count')) {
    function frontend_class1_count(int $modulePKey, ?int $lang = null): int
    {
        global $this_lang;

        $lang = $lang ?? (int)$this_lang;
        $sql = 'SELECT COUNT(PKey) AS Total FROM view_dbclass1'
            . ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey AND intLang = :intLang';

        return crud_fetch_scalar($sql, [
            'Upload'       => 'Yes',
            'Module_PKey'  => $modulePKey,
            'intLang'      => $lang,
        ], 'Total');
    }
}

if (!function_exists('frontend_filter_class1')) {
    /** 僅從 QueryString 讀取 Class1（列表「所有資訊」不預設第一個分類） */
    function frontend_filter_class1(array $filter): int
    {
        if (isset($filter['Class1']) && ctype_digit((string)$filter['Class1'])) {
            return (int)$filter['Class1'];
        }

        return 0;
    }
}

if (!function_exists('frontend_resolve_class1')) {
    function frontend_resolve_class1(int $modulePKey, array $filter, ?int $lang = null): int
    {
        global $this_lang;

        $lang = $lang ?? (int)$this_lang;

        if (isset($filter['Class1']) && ctype_digit((string)$filter['Class1'])) {
            return (int)$filter['Class1'];
        }

        $sql = 'SELECT PKey FROM view_dbclass1'
            . ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey AND intLang = :intLang'
            . ' ORDER BY Sort, dtUDate DESC LIMIT 1';
        $row = crud_fetch_one($sql, [
            'Upload'      => 'Yes',
            'Module_PKey' => $modulePKey,
            'intLang'     => $lang,
        ]);

        return $row !== null ? crud_row_int($row, 'PKey') : 0;
    }
}

if (!function_exists('frontend_list_where')) {
    /**
     * 組列表 WHERE（參數化）
     *
     * @return array{0:string,1:array<string,mixed>}
     */
    function frontend_list_where(int $modulePKey, ?int $lang = null): array
    {
        global $this_lang;

        $cfg = frontend_module_config();
        $lang = $lang ?? (int)$this_lang;

        if (!empty($cfg['publish_window'])) {
            $where = ' WHERE Module_PKey = :Module_PKey AND intLang = :intLang'
                . ' AND OpenDate <= :OpenDate AND EndDate >= :EndDate';
            $params = [
                'Module_PKey' => $modulePKey,
                'intLang'     => $lang,
                'OpenDate'    => date('Y-m-d H:i'),
                'EndDate'     => date('Y-m-d') . ' 23:59:59',
            ];
        } else {
            $where = ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey AND intLang = :intLang';
            $params = [
                'Upload'      => 'Yes',
                'Module_PKey' => $modulePKey,
                'intLang'     => $lang,
            ];
        }

        return [$where, $params];
    }
}

if (!function_exists('frontend_apply_class1_filter')) {
    /**
     * 依 Class1 加入條件並更新麵包屑；回傳 Class1 名稱
     */
    function frontend_apply_class1_filter(
        string &$where,
        array &$params,
        int $class1,
        int $class1Count,
        ?array $cfg = null
    ): string {
        global $bread_name, $break_link;

        $cfg = $cfg ?? frontend_module_config();
        $minCount = (int)($cfg['class1_filter_min_count'] ?? 2);
        $classLink = trim((string)($cfg['class_link'] ?? ''));

        if ($class1 <= 0 || $class1Count < $minCount) {
            return '';
        }

        $row = crud_fetch_one(
            'SELECT PKey, strName FROM view_dbclass1 WHERE PKey = :PKey',
            ['PKey' => $class1]
        );
        if ($row === null) {
            return '';
        }

        $class1PKey = crud_row_int($row, 'PKey');
        $class1Name = (string)crud_row_val($row, 'strName');

        $where .= ' AND Class1_PKey = :Class1_PKey';
        $params['Class1_PKey'] = $class1PKey;

        $bread_name = is_array($bread_name ?? null) ? $bread_name : [];
        $break_link = is_array($break_link ?? null) ? $break_link : [];
        $bread_name[] = e_attr($class1Name);
        $break_link[] = $classLink . $class1PKey . '.htm';

        return $class1Name;
    }
}

if (!function_exists('frontend_list_total')) {
    function frontend_list_total(string $where, array $params): int
    {
        $view = frontend_view_table();
        $sql = "SELECT COUNT(PKey) AS Total FROM {$view}{$where}";

        return crud_fetch_scalar($sql, $params, 'Total');
    }
}

if (!function_exists('frontend_list_paginate')) {
    /**
     * @return array{tPage:int,tPageTotal:int,offset:int,pageSize:int,total:int}
     */
    function frontend_list_paginate(int $total, $page = null, ?int $pageSize = null): array
    {
        $cfg = frontend_module_config();
        $pageSize = $pageSize ?? (int)$cfg['page_size'];

        return crud_paginate($total, $pageSize, $page);
    }
}

if (!function_exists('frontend_fetch_list')) {
    /** @return list<array<string,mixed>> */
    function frontend_fetch_list(string $where, array $params, int $offset, int $limit): array
    {
        $cfg = frontend_module_config();
        $view = frontend_view_table();
        $orderBy = frontend_safe_order_by((string)($cfg['order_by'] ?? 'PKey DESC'));
        $sql = "SELECT * FROM {$view}{$where} ORDER BY {$orderBy} LIMIT "
            . (int)$limit . ' OFFSET ' . (int)$offset;

        return crud_fetch_all($sql, $params);
    }
}

if (!function_exists('frontend_breadcrumb_ldjson')) {
    /** @return array<string,mixed> */
    function frontend_breadcrumb_ldjson(): array
    {
        global $bread_name, $break_link, $web_url;

        $bread_name = is_array($bread_name ?? null) ? $bread_name : [];
        $break_link = is_array($break_link ?? null) ? $break_link : [];

        $elements = [];
        foreach ($bread_name as $i => $name) {
            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'item'     => safe_href((string)($web_url . ($break_link[$i] ?? ''))),
                'name'     => strip_tags((string)$name),
            ];
        }

        return [
            '@context'        => 'http://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}

if (!function_exists('frontend_detail_href')) {
    function frontend_detail_href(int $pkey, ?array $cfg = null): string
    {
        $cfg = $cfg ?? frontend_module_config();
        $prefix = trim((string)($cfg['detail_link'] ?? 'detail'));

        return $prefix . $pkey . '.htm';
    }
}

if (!function_exists('frontend_cover_image_url')) {
    function frontend_cover_image_url(int $parentPKey, ?array $cfg = null): string
    {
        global $web_root;

        $cfg = $cfg ?? frontend_module_config();
        $default = safe_href((string)($web_root . 'images/default/default_fb.jpg'));

        $imgTable = trim((string)($cfg['img'] ?? ''));
        $fkCol    = trim((string)($cfg['fk'] ?? ''));
        if ($parentPKey <= 0 || $imgTable === '' || $fkCol === '') {
            return $default;
        }

        $sql = "SELECT Forder, Photo1 FROM {$imgTable}"
            . " WHERE {$fkCol} = :parentPKey AND Photo1 <> '' AND Sort = 1"
            . ' ORDER BY Sort LIMIT 1';
        $row = crud_fetch_one($sql, ['parentPKey' => $parentPKey]);
        if ($row === null) {
            return $default;
        }

        $diskBase = frontend_upload_disk_base();
        $folder   = trim((string)crud_row_val($row, 'Forder'), "/\\");
        $photo    = basename((string)crud_row_val($row, 'Photo1'));
        if ($photo === '') {
            return $default;
        }

        $diskPath  = $diskBase . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $photo;
        $webpDisk  = preg_replace('/\.[^.]+$/i', '.webp', $diskPath);
        $ext       = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
        $useDisk   = ($ext !== 'gif' && is_file($webpDisk)) ? $webpDisk : (is_file($diskPath) ? $diskPath : null);

        if ($useDisk === null) {
            return $default;
        }

        $relUrl = 'Upload/' . $folder . '/' . basename($useDisk);

        return safe_href((string)($web_root . ltrim($relUrl, '/')));
    }
}

if (!function_exists('frontend_optional_cover_image_url')) {
    /** 有列表圖回傳 URL，否則 null（不含預設圖） */
    function frontend_optional_cover_image_url(int $parentPKey, ?array $cfg = null): ?string
    {
        global $web_root;

        $default = safe_href((string)($web_root . 'images/default/default_fb.jpg'));
        $url = frontend_cover_image_url($parentPKey, $cfg);

        return $url !== $default ? $url : null;
    }
}

if (!function_exists('frontend_upload_public_url')) {
    /** 上傳檔案公開 URL（webp 優先，gif 除外）；檔案不存在回傳 null */
    function frontend_upload_public_url(string $folder, string $filename): ?string
    {
        global $web_root;

        $diskBase = frontend_upload_disk_base();
        $folder   = trim($folder, "/\\");
        $filename = basename($filename);
        if ($folder === '' || $filename === '') {
            return null;
        }

        $diskPath = $diskBase . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($diskPath)) {
            return null;
        }

        $ext      = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
        $webpDisk = preg_replace('/\.[^.]+$/i', '.webp', $diskPath);
        $useDisk  = ($ext !== 'gif' && is_file($webpDisk)) ? $webpDisk : $diskPath;
        $relUrl   = 'Upload/' . $folder . '/' . basename($useDisk);

        return safe_href((string)($web_root . ltrim($relUrl, '/')));
    }
}

if (!function_exists('frontend_upload_thumb_url')) {
    /** 後台列表用極小縮圖（thumb_、s_、原檔依序） */
    function frontend_upload_thumb_url(string $folder, string $photo): ?string
    {
        $photo = basename($photo);
        if ($photo === '') {
            return null;
        }

        foreach (['thumb_', 's_', ''] as $prefix) {
            $url = frontend_upload_public_url($folder, $prefix . $photo);
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }
}

if (!function_exists('frontend_upload_grid_image_url')) {
    /** 前台區塊小圖（略過 thumb_，優先 s_ 再原檔） */
    function frontend_upload_grid_image_url(string $folder, string $photo): ?string
    {
        $photo = basename($photo);
        if ($photo === '') {
            return null;
        }

        foreach (['s_', ''] as $prefix) {
            $url = frontend_upload_public_url($folder, $prefix . $photo);
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }
}

if (!function_exists('frontend_album_cover_image_url')) {
    /** 相簿列表圖（album_img.Home = Yes） */
    function frontend_album_cover_image_url(int $albumPKey): string
    {
        global $web_root;

        $default = safe_href((string)($web_root . 'images/default/default_fb.jpg'));
        if ($albumPKey <= 0) {
            return $default;
        }

        $row = crud_fetch_one(
            "SELECT Forder, Photo1 FROM album_img"
            . " WHERE Album_PKey = :apk AND Home = :home AND Photo1 <> ''"
            . ' ORDER BY Sort LIMIT 1',
            ['apk' => $albumPKey, 'home' => 'Yes']
        );
        if ($row === null) {
            return $default;
        }

        $folder = trim((string)crud_row_val($row, 'Forder'), "/\\");
        $photo  = basename((string)crud_row_val($row, 'Photo1'));
        $url    = frontend_upload_public_url($folder, $photo);

        return $url ?? $default;
    }
}

if (!function_exists('frontend_fetch_album_gallery_items')) {
    /**
     * 相簿明細圖（排除列表圖 Home=Yes）
     *
     * @return list<array{thumb:string,full:string,caption:string}>
     */
    function frontend_fetch_album_gallery_items(int $albumPKey): array
    {
        if ($albumPKey <= 0) {
            return [];
        }

        $rows = crud_fetch_all(
            "SELECT Forder, Photo1, PhotoM FROM album_img"
            . " WHERE Album_PKey = :apk AND Photo1 <> ''"
            . " AND (Home IS NULL OR Home <> :home)"
            . ' ORDER BY Sort ASC',
            ['apk' => $albumPKey, 'home' => 'Yes']
        );

        $items = [];
        foreach ($rows as $row) {
            $folder = trim((string)crud_row_val($row, 'Forder'), "/\\");
            $photo  = basename((string)crud_row_val($row, 'Photo1'));
            if ($folder === '' || $photo === '') {
                continue;
            }

            $full = frontend_upload_public_url($folder, $photo);
            if ($full === null) {
                continue;
            }

            $thumb = frontend_upload_grid_image_url($folder, $photo) ?? $full;
            $items[] = [
                'thumb'   => $thumb,
                'full'    => $full,
                'caption' => strip_tags((string)crud_row_val($row, 'PhotoM')),
            ];
        }

        return $items;
    }
}

if (!function_exists('frontend_fetch_faq_items')) {
    /**
     * FAQ 單頁列表（Q：strName；A：faq_msg.Contents Sort 1）
     *
     * @return list<array{pkey:int,question:string,answer:string,image:?string}>
     */
    function frontend_fetch_faq_items(int $modulePKey, ?array $cfg = null): array
    {
        $cfg = $cfg ?? frontend_module_config();
        [$where, $params] = frontend_list_where($modulePKey);
        $view = frontend_view_table();
        $orderBy = frontend_safe_order_by((string)($cfg['order_by'] ?? 'Sort ASC'), 'Sort ASC');
        $rows = crud_fetch_all(
            "SELECT PKey, strName FROM {$view}{$where} ORDER BY {$orderBy}",
            $params
        );

        $items = [];
        foreach ($rows as $row) {
            $pkey = crud_row_int($row, 'PKey');
            $msgData = frontend_fetch_msg_contents($pkey, $cfg);
            $answer = trim((string)($msgData['contents'][1] ?? ''));
            if ($answer === '') {
                foreach ($msgData['contents'] as $html) {
                    $html = trim((string)$html);
                    if ($html !== '') {
                        $answer = $html;
                        break;
                    }
                }
            }

            $items[] = [
                'pkey'     => $pkey,
                'question' => (string)crud_row_val($row, 'strName'),
                'answer'   => $answer,
                'image'    => frontend_optional_cover_image_url($pkey, $cfg),
            ];
        }

        return $items;
    }
}

if (!function_exists('frontend_video_watch_href')) {
    function frontend_video_watch_href(string $movielink): ?string
    {
        $url = youtube_watch_url($movielink);

        return $url !== null ? safe_href($url) : null;
    }
}

if (!function_exists('frontend_video_card_image_url')) {
    /**
     * 影音列表圖：優先上傳圖，其次 YouTube 縮圖，最後預設圖
     */
    function frontend_video_card_image_url(int $parentPKey, string $movielink, ?array $cfg = null): string
    {
        global $web_root;

        $uploaded = frontend_optional_cover_image_url($parentPKey, $cfg);
        if ($uploaded !== null) {
            return $uploaded;
        }

        $thumb = youtube_thumbnail_url($movielink);
        if ($thumb !== null) {
            return safe_href($thumb);
        }

        return safe_href((string)($web_root . 'images/default/default_fb.jpg'));
    }
}

if (!function_exists('frontend_file_icon_class')) {
    /** 依副檔名回傳 Font Awesome 圖示 class（含 fas 前綴） */
    function frontend_file_icon_class(string $ext): string
    {
        $fa = function_exists('manage_file_icon_fa_class')
            ? manage_file_icon_fa_class($ext)
            : 'fa-file-alt';

        return 'fas ' . $fa;
    }
}

if (!function_exists('frontend_filedown_row_ext')) {
    function frontend_filedown_row_ext(array $row, ?array $cfg = null): string
    {
        global $this_lang;

        $ext = trim((string)crud_row_val($row, 'Ext'));
        if ($ext !== '') {
            return strtolower($ext);
        }

        $intLink = crud_row_int($row, 'intLink');
        if ($intLink === 1) {
            $url = trim((string)crud_row_val($row, 'strLink'));
            if ($url === '') {
                $url = trim((string)crud_row_val($row, 'strURL'));
            }

            return function_exists('manage_file_ext_from_path')
                ? manage_file_ext_from_path((string)(parse_url($url, PHP_URL_PATH) ?: $url))
                : '';
        }

        $fileName = trim((string)crud_row_val($row, 'FileName'));
        if ($fileName === '') {
            $cfg = $cfg ?? frontend_module_config();
            $imgTable = trim((string)($cfg['img'] ?? ''));
            $fkCol = trim((string)($cfg['fk'] ?? ''));
            $pkey = crud_row_int($row, 'PKey');
            if ($pkey > 0 && $imgTable !== '' && $fkCol !== '') {
                $imgRow = crud_fetch_one(
                    "SELECT Photo1 FROM {$imgTable}"
                    . " WHERE {$fkCol} = :pk AND Sort = :sort AND Photo1 <> '' LIMIT 1",
                    ['pk' => $pkey, 'sort' => (int)$this_lang]
                );
                $fileName = $imgRow !== null ? basename((string)crud_row_val($imgRow, 'Photo1')) : '';
            }
        }

        return function_exists('manage_file_ext_from_path')
            ? manage_file_ext_from_path($fileName)
            : strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }
}

if (!function_exists('frontend_filedown_download_href')) {
    /**
     * intLink=1 回傳自訂 URL；否則回傳上傳檔案路徑
     */
    function frontend_filedown_download_href(array $row, ?array $cfg = null): ?string
    {
        global $this_lang, $web_root;

        $intLink = crud_row_int($row, 'intLink');
        if ($intLink <= 0) {
            $intLink = 2;
        }

        if ($intLink === 1) {
            $url = trim((string)crud_row_val($row, 'strLink'));
            if ($url === '') {
                $url = trim((string)crud_row_val($row, 'strURL'));
            }

            return $url !== '' ? safe_href($url) : null;
        }

        $forder = trim((string)crud_row_val($row, 'Forder'), "/\\");
        $fileName = trim((string)crud_row_val($row, 'FileName'));
        if ($fileName === '') {
            $cfg = $cfg ?? frontend_module_config();
            $imgTable = trim((string)($cfg['img'] ?? ''));
            $fkCol = trim((string)($cfg['fk'] ?? ''));
            $pkey = crud_row_int($row, 'PKey');
            if ($pkey > 0 && $imgTable !== '' && $fkCol !== '') {
                $imgRow = crud_fetch_one(
                    "SELECT Forder, Photo1 FROM {$imgTable}"
                    . " WHERE {$fkCol} = :pk AND Sort = :sort AND Photo1 <> '' LIMIT 1",
                    ['pk' => $pkey, 'sort' => (int)$this_lang]
                );
                if ($imgRow !== null) {
                    $forder = trim((string)crud_row_val($imgRow, 'Forder'), "/\\");
                    $fileName = basename((string)crud_row_val($imgRow, 'Photo1'));
                }
            }
        }

        if ($fileName === '') {
            return null;
        }

        $diskBase = frontend_upload_disk_base();
        $diskPath = $diskBase . DIRECTORY_SEPARATOR . $forder . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($diskPath)) {
            return null;
        }

        $relUrl = 'Upload/' . ($forder !== '' ? $forder . '/' : '') . $fileName;

        return safe_href((string)($web_root . ltrim($relUrl, '/')));
    }
}

if (!function_exists('frontend_filedown_items_from_rows')) {
    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array{pkey:int,title:string,href:?string,ext:string,icon:string,is_file:bool}>
     */
    function frontend_filedown_items_from_rows(array $rows, ?array $cfg = null): array
    {
        $items = [];
        foreach ($rows as $row) {
            $ext = frontend_filedown_row_ext($row, $cfg);
            $items[] = [
                'pkey'    => crud_row_int($row, 'PKey'),
                'title'   => (string)crud_row_val($row, 'strName'),
                'href'    => frontend_filedown_download_href($row, $cfg),
                'ext'     => $ext,
                'icon'    => frontend_file_icon_class($ext),
                'is_file' => crud_row_int($row, 'intLink') !== 1,
            ];
        }

        return $items;
    }
}

if (!function_exists('frontend_show_type_list_link')) {
    /**
     * show_type：1 連結、2 內容明細（不含檔案模式）
     *
     * @return array{href:?string,target:string,rel:?string,external:bool}
     */
    function frontend_show_type_list_link(array $row, ?array $cfg = null): array
    {
        $showType = crud_row_int($row, 'show_type');
        if ($showType < 1 || $showType > 2) {
            $showType = 2;
        }

        $pkey = crud_row_int($row, 'PKey');
        $base = [
            'href'     => null,
            'target'   => '_self',
            'rel'      => null,
            'external' => false,
        ];

        if ($showType === 1) {
            $url = trim((string)crud_row_val($row, 'strURL'));
            if ($url === '') {
                $url = trim((string)crud_row_val($row, 'strLink'));
            }
            if ($url === '') {
                return $base;
            }

            return [
                'href'     => frontend_external_link_href($url),
                'target'   => '_blank',
                'rel'      => 'noopener noreferrer',
                'external' => true,
            ];
        }

        if ($pkey <= 0) {
            return $base;
        }

        return [
            'href'     => safe_href(frontend_detail_href($pkey, $cfg)),
            'target'   => '_self',
            'rel'      => null,
            'external' => false,
        ];
    }
}

if (!function_exists('frontend_investor_file_slot')) {
    /** 語系對應檔案槽（investor_img Sort 8/9/10） */
    function frontend_investor_file_slot(?int $lang = null): int
    {
        global $this_lang;

        $lang = $lang ?? (int)$this_lang;
        $cfg  = frontend_module_config();
        $map  = (array)($cfg['file_lang_slots'] ?? [1 => 8, 2 => 9, 3 => 10]);

        return (int)($map[$lang] ?? (7 + $lang));
    }
}

if (!function_exists('frontend_investor_file_href')) {
    /** show_type=3：intLink=1 自訂路徑，否則上傳檔 */
    function frontend_investor_file_href(array $row, ?array $cfg = null): ?string
    {
        global $this_lang, $web_root;

        $intLink = crud_row_int($row, 'intLink');
        if ($intLink <= 0) {
            $intLink = crud_row_int($row, 'intFileLink');
        }
        if ($intLink <= 0) {
            $intLink = 2;
        }

        if ($intLink === 1) {
            $url = trim((string)crud_row_val($row, 'strLink'));
            if ($url === '') {
                $url = trim((string)crud_row_val($row, 'strURL'));
            }

            return $url !== '' ? frontend_external_link_href($url) : null;
        }

        $forder   = trim((string)crud_row_val($row, 'Forder'), "/\\");
        $fileName = trim((string)crud_row_val($row, 'FileName'));
        if ($fileName === '') {
            $fileName = basename((string)crud_row_val($row, 'Photo1'));
        }

        $cfg      = $cfg ?? frontend_module_config();
        $imgTable = trim((string)($cfg['img'] ?? ''));
        $fkCol    = trim((string)($cfg['fk'] ?? ''));
        $pkey     = crud_row_int($row, 'PKey');
        $slot     = frontend_investor_file_slot();

        if ($fileName === '' && $pkey > 0 && $imgTable !== '' && $fkCol !== '') {
            $imgRow = crud_fetch_one(
                "SELECT Forder, Photo1 FROM {$imgTable}"
                . " WHERE {$fkCol} = :pk AND Sort = :sort AND Photo1 <> '' LIMIT 1",
                ['pk' => $pkey, 'sort' => $slot]
            );
            if ($imgRow !== null) {
                $forder   = trim((string)crud_row_val($imgRow, 'Forder'), "/\\");
                $fileName = basename((string)crud_row_val($imgRow, 'Photo1'));
            }
        }

        if ($fileName === '') {
            return null;
        }

        $diskBase = frontend_upload_disk_base();
        $diskPath = $diskBase . DIRECTORY_SEPARATOR . $forder . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($diskPath)) {
            return null;
        }

        $relUrl = 'Upload/' . ($forder !== '' ? $forder . '/' : '') . $fileName;

        return safe_href((string)($web_root . ltrim($relUrl, '/')));
    }
}

if (!function_exists('frontend_investor_row_ext')) {
    function frontend_investor_row_ext(array $row, ?array $cfg = null): string
    {
        $ext = trim((string)crud_row_val($row, 'Ext'));
        if ($ext !== '') {
            return strtolower($ext);
        }

        $intLink = crud_row_int($row, 'intLink');
        if ($intLink <= 0) {
            $intLink = crud_row_int($row, 'intFileLink');
        }
        if ($intLink === 1) {
            $url = trim((string)crud_row_val($row, 'strLink'));
            if ($url === '') {
                $url = trim((string)crud_row_val($row, 'strURL'));
            }

            return function_exists('manage_file_ext_from_path')
                ? manage_file_ext_from_path((string)(parse_url($url, PHP_URL_PATH) ?: $url))
                : '';
        }

        $fileName = trim((string)crud_row_val($row, 'FileName'));
        if ($fileName === '') {
            $fileName = basename((string)crud_row_val($row, 'Photo1'));
        }
        if ($fileName === '') {
            $href = frontend_investor_file_href($row, $cfg);
            if ($href !== null) {
                $fileName = (string)(parse_url($href, PHP_URL_PATH) ?: '');
            }
        }

        return function_exists('manage_file_ext_from_path')
            ? manage_file_ext_from_path($fileName)
            : strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }
}

if (!function_exists('frontend_investor_list_link')) {
    /**
     * show_type：1 連結、2 內容明細、3 檔案
     *
     * @return array{href:?string,target:string,rel:?string,is_file:bool,download:bool,external:bool}
     */
    function frontend_investor_list_link(array $row, ?array $cfg = null): array
    {
        $showType = crud_row_int($row, 'show_type');
        if ($showType < 1 || $showType > 3) {
            $showType = 2;
        }

        $pkey = crud_row_int($row, 'PKey');
        $base = [
            'href'     => null,
            'target'   => '_self',
            'rel'      => null,
            'is_file'  => false,
            'download' => false,
            'external' => false,
        ];

        if ($showType === 1) {
            $url = trim((string)crud_row_val($row, 'strURL'));
            if ($url === '') {
                $url = trim((string)crud_row_val($row, 'strLink'));
            }
            if ($url === '') {
                return $base;
            }

            return [
                'href'     => frontend_external_link_href($url),
                'target'   => '_blank',
                'rel'      => 'noopener noreferrer',
                'is_file'  => false,
                'download' => false,
                'external' => true,
            ];
        }

        if ($showType === 2) {
            if ($pkey <= 0) {
                return $base;
            }

            return [
                'href'     => safe_href(frontend_detail_href($pkey, $cfg)),
                'target'   => '_self',
                'rel'      => null,
                'is_file'  => false,
                'download' => false,
                'external' => false,
            ];
        }

        $fileHref = frontend_investor_file_href($row, $cfg);
        if ($fileHref === null) {
            return $base;
        }

        return [
            'href'     => $fileHref,
            'target'   => '_blank',
            'rel'      => 'noopener noreferrer',
            'is_file'  => true,
            'download' => true,
            'external' => false,
        ];
    }
}

if (!function_exists('frontend_investor_items_from_rows')) {
    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array{pkey:int,title:string,href:?string,target:string,rel:?string,is_file:bool,download:bool,external:bool,show_type:int,ext:string,icon:string}>
     */
    function frontend_investor_items_from_rows(array $rows, ?array $cfg = null): array
    {
        $items = [];
        foreach ($rows as $row) {
            $showType = crud_row_int($row, 'show_type');
            if ($showType < 1 || $showType > 3) {
                $showType = 2;
            }

            $link = frontend_investor_list_link($row, $cfg);
            $ext  = $showType === 3 ? frontend_investor_row_ext($row, $cfg) : '';
            $icon = match ($showType) {
                1       => 'fas fa-external-link-alt',
                3       => frontend_file_icon_class($ext),
                default => 'fas fa-angle-right',
            };

            $items[] = [
                'pkey'       => crud_row_int($row, 'PKey'),
                'title'      => (string)crud_row_val($row, 'strName'),
                'href'       => $link['href'],
                'target'     => $link['target'],
                'rel'        => $link['rel'],
                'is_file'    => $link['is_file'],
                'download'   => $link['download'],
                'external'   => $link['external'],
                'show_type'  => $showType,
                'ext'        => $ext,
                'icon'       => $icon,
            ];
        }

        return $items;
    }
}

if (!function_exists('frontend_banner_rows')) {
    /**
     * 首頁 Banner 列表（優先 view_dbad，無 view 時改查 dbad 主檔）
     *
     * @return list<array<string,mixed>>
     */
    function frontend_banner_rows(int $modulePKey = 1, ?int $lang = null): array
    {
        global $this_lang;

        $lang = $lang ?? (int)$this_lang;

        if (crud_table_exists('view_dbad')) {
            $rows = crud_fetch_all(
                'SELECT * FROM view_dbad'
                . ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey AND intLang = :intLang'
                . ' ORDER BY Sort',
                [
                    'Upload'      => 'Yes',
                    'Module_PKey' => $modulePKey,
                    'intLang'     => $lang,
                ]
            );
            if ($rows !== []) {
                return $rows;
            }
        }

        return crud_fetch_all(
            'SELECT * FROM dbad'
            . ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey'
            . ' ORDER BY Sort',
            [
                'Upload'      => 'Yes',
                'Module_PKey' => $modulePKey,
            ]
        );
    }
}

if (!function_exists('frontend_ad_slide_image_url')) {
    /** 首頁 Banner 桌機圖（webp 優先）；無檔案回傳 null */
    function frontend_ad_slide_image_url(int $adPKey): ?string
    {
        global $web_root, $upload_forder;

        if ($adPKey <= 0) {
            return null;
        }

        $row = crud_fetch_one(
            "SELECT Forder, Photo1 FROM dbad_img"
            . " WHERE AD_PKey = :AD_PKey AND Photo1 <> '' ORDER BY Sort LIMIT 1",
            ['AD_PKey' => $adPKey]
        );
        if ($row === null) {
            return null;
        }

        $diskBase = rtrim((string)($upload_forder ?? 'Upload/'), "/\\");
        $folder   = trim((string)crud_row_val($row, 'Forder'), "/\\");
        $photo    = basename((string)crud_row_val($row, 'Photo1'));
        if ($photo === '') {
            return null;
        }

        $diskPath = $diskBase . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $photo;
        if (!is_file($diskPath)) {
            return null;
        }

        $webpDisk = preg_replace('/\.[^.]+$/i', '.webp', $diskPath);
        $useDisk  = is_file($webpDisk) ? $webpDisk : $diskPath;
        $relUrl   = 'Upload/' . $folder . '/' . basename($useDisk);

        return safe_href((string)($web_root . ltrim($relUrl, '/')));
    }
}

if (!function_exists('frontend_banner_link_url')) {
    /** 驗證 Banner 連結（http/https 或站內相對路徑） */
    function frontend_banner_link_url(string $rawLink): string
    {
        $rawLink = trim($rawLink);
        if ($rawLink === '') {
            return '#';
        }

        if (filter_var($rawLink, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string)(parse_url($rawLink, PHP_URL_SCHEME) ?? ''));
            if ($scheme === 'http' || $scheme === 'https') {
                return $rawLink;
            }

            return '#';
        }

        if ($rawLink[0] === '/'
            || preg_match('#^(\./|\.\./)#', $rawLink)
            || preg_match('#^[A-Za-z0-9_\-./?=&%]+$#', $rawLink)) {
            return $rawLink;
        }

        return '#';
    }
}

if (!function_exists('frontend_external_link_href')) {
    /** 外部或站內連結（http/https 或相對路徑），無效時為 # */
    function frontend_external_link_href(string $rawLink): string
    {
        return safe_href(frontend_banner_link_url($rawLink));
    }
}

if (!function_exists('frontend_link_target')) {
    /** 僅允許 _self、_blank（其餘視為 _blank） */
    function frontend_link_target(string $target): string
    {
        return strtolower(trim($target)) === '_self' ? '_self' : '_blank';
    }
}

if (!function_exists('frontend_dbad_card_image_url')) {
    /** dbad 列表圖（無圖時回傳預設圖） */
    function frontend_dbad_card_image_url(int $adPKey): string
    {
        global $web_root;

        $url = frontend_ad_slide_image_url($adPKey);
        if ($url !== null) {
            return $url;
        }

        return safe_href((string)($web_root . 'images/default/default_fb.jpg'));
    }
}

if (!function_exists('frontend_request_pkey')) {
    function frontend_request_pkey(array $filter): int
    {
        if (!isset($filter['PKey']) || $filter['PKey'] === false || $filter['PKey'] === null) {
            return 0;
        }

        return max(0, (int)$filter['PKey']);
    }
}

if (!function_exists('frontend_list_href')) {
    function frontend_list_href(?array $cfg = null): string
    {
        $cfg = $cfg ?? frontend_module_config();
        $classLink = trim((string)($cfg['class_link'] ?? ''));

        return $classLink !== '' ? $classLink . '.htm' : 'index.htm';
    }
}

if (!function_exists('frontend_class1_list_href')) {
    /** Class1>0 回傳 news5.htm 等分類列表，否則回傳模組總列表（news.htm） */
    function frontend_class1_list_href(int $class1PKey, ?array $cfg = null): string
    {
        $cfg = $cfg ?? frontend_module_config();
        $classLink = trim((string)($cfg['class_link'] ?? ''));
        if ($class1PKey > 0 && $classLink !== '') {
            return $classLink . $class1PKey . '.htm';
        }

        return frontend_list_href($cfg);
    }
}

if (!function_exists('frontend_sidebar_should_show')) {
    /** 單元有 dbclass1 項目時顯示右側分類選單 */
    function frontend_sidebar_should_show(int $modulePKey): bool
    {
        return $modulePKey > 0 && frontend_class1_count($modulePKey) >= 1;
    }
}

if (!function_exists('frontend_sidebar_class1_active')) {
    /** 比對目前 Class1（dbclass1.PKey）是否為選中狀態 */
    function frontend_sidebar_class1_active(int $itemClass1PKey, int $currentClass1): bool
    {
        if ($itemClass1PKey <= 0) {
            return $currentClass1 <= 0;
        }

        return $itemClass1PKey === $currentClass1;
    }
}

if (!function_exists('frontend_upload_disk_base')) {
    /** 上傳目錄實體路徑（結尾無斜線） */
    function frontend_upload_disk_base(): string
    {
        global $upload_forder;

        if (!empty($GLOBALS['PathForder'])) {
            return rtrim((string)$GLOBALS['PathForder'], "/\\") . DIRECTORY_SEPARATOR . 'Upload';
        }
        if (!empty($GLOBALS['upload_folder'])) {
            return rtrim((string)$GLOBALS['upload_folder'], "/\\");
        }

        $rel = rtrim((string)($upload_forder ?? 'Upload/'), "/\\");

        return $rel;
    }
}

if (!function_exists('frontend_detail_where')) {
    /**
     * 內頁 WHERE（繼承列表條件 + PKey）
     *
     * @return array{0:string,1:array<string,mixed>}
     */
    function frontend_detail_where(int $modulePKey, int $pkey, ?int $lang = null): array
    {
        [$where, $params] = frontend_list_where($modulePKey, $lang);
        $where .= ' AND PKey = :PKey';
        $params['PKey'] = $pkey;

        return [$where, $params];
    }
}

if (!function_exists('frontend_lang_seo_title')) {
    /** @see crud_lang_seo_title()（定義於 crud_helpers.php） */
    function frontend_lang_seo_title(array $row): string
    {
        return crud_lang_seo_title($row);
    }
}

if (!function_exists('frontend_fetch_detail')) {
    /** @return array<string,mixed>|null */
    function frontend_fetch_detail(int $modulePKey, int $pkey, ?int $lang = null): ?array
    {
        if ($pkey <= 0) {
            return null;
        }

        [$where, $params] = frontend_detail_where($modulePKey, $pkey, $lang);
        $view = frontend_view_table();
        $sql = "SELECT * FROM {$view}{$where} LIMIT 1";

        return crud_fetch_one($sql, $params);
    }
}

if (!function_exists('frontend_apply_detail_class1_breadcrumb')) {
    function frontend_apply_detail_class1_breadcrumb(int $class1, int $class1Count, ?array $cfg = null): void
    {
        global $bread_name, $break_link;

        $cfg = $cfg ?? frontend_module_config();
        $minCount = (int)($cfg['class1_filter_min_count'] ?? 2);
        $classLink = trim((string)($cfg['class_link'] ?? ''));

        if ($class1 <= 0 || $class1Count < $minCount || $classLink === '') {
            return;
        }

        $row = crud_fetch_one(
            'SELECT PKey, strName FROM view_dbclass1 WHERE PKey = :PKey',
            ['PKey' => $class1]
        );
        if ($row === null) {
            return;
        }

        $bread_name = is_array($bread_name ?? null) ? $bread_name : [];
        $break_link = is_array($break_link ?? null) ? $break_link : [];
        $bread_name[] = e_attr((string)crud_row_val($row, 'strName'));
        $break_link[] = $classLink . crud_row_int($row, 'PKey') . '.htm';
    }
}

if (!function_exists('frontend_append_detail_breadcrumb')) {
    function frontend_append_detail_breadcrumb(string $title): void
    {
        global $bread_name, $break_link, $page_link;

        $bread_name = is_array($bread_name ?? null) ? $bread_name : [];
        $break_link = is_array($break_link ?? null) ? $break_link : [];
        $bread_name[] = e($title);
        $break_link[] = $page_link;
    }
}

if (!function_exists('frontend_fetch_msg_contents')) {
    /**
     * @return array{contents:array<int,string>,show:array<int,int>,imgShow:array<int,int>}
     */
    function frontend_fetch_msg_contents(int $parentPKey, ?array $cfg = null): array
    {
        global $this_lang;

        $cfg = $cfg ?? frontend_module_config();
        $table = trim((string)($cfg['msg'] ?? ''));
        $fkCol = trim((string)($cfg['fk'] ?? ''));
        $contents = [];
        $show = [];
        $imgShow = [];

        if ($parentPKey <= 0 || $table === '' || $fkCol === '') {
            return ['contents' => $contents, 'show' => $show, 'imgShow' => $imgShow];
        }

        $cols = 'Sort, Contents';
        if (function_exists('crud_table_has_column') && crud_table_has_column($table, 'isShow')) {
            $cols .= ', isShow';
        }
        if (function_exists('crud_table_has_column') && crud_table_has_column($table, 'imgShow')) {
            $cols .= ', imgShow';
        }

        $sql = "SELECT {$cols} FROM {$table}"
            . " WHERE {$fkCol} = :parentPKey AND intLang = :intLang ORDER BY Sort";

        foreach (crud_fetch_all($sql, ['parentPKey' => $parentPKey, 'intLang' => (int)$this_lang]) as $row) {
            $slot = crud_row_int($row, 'Sort');
            $contents[$slot] = rwd_table((string)crud_row_val($row, 'Contents'), 1);
            $show[$slot] = array_key_exists('isShow', $row) ? crud_row_int($row, 'isShow') : 1;
            if (array_key_exists('imgShow', $row)) {
                $imgShow[$slot] = manage_content_img_layout_normalize($row['imgShow']);
            }
        }

        return ['contents' => $contents, 'show' => $show, 'imgShow' => $imgShow];
    }
}

if (!function_exists('frontend_content_layout_value')) {
    /**
     * 內容區塊呈現方式（1上圖下文 2左圖右文 3右圖左文 4下圖上文）
     *
     * @param array<int,int> $layouts 內容欄位 1–6 => 1–4
     */
    function frontend_content_layout_value(int $contentSlot, array $layouts = []): int
    {
        $raw = (int)($layouts[$contentSlot] ?? 0);
        $imgSort = $contentSlot + 1;

        return manage_content_img_layout_slot_value($imgSort, $raw > 0 ? $raw : null);
    }
}

if (!function_exists('frontend_content_layout_css')) {
    /** @param array<int,int> $layouts */
    function frontend_content_layout_css(int $contentSlot, array $layouts = []): string
    {
        return match (frontend_content_layout_value($contentSlot, $layouts)) {
            2       => 'tx01 img-left',
            3       => 'tx01 img-right',
            4       => 'tx01 img-bottom',
            default => 'tx01',
        };
    }
}

if (!function_exists('frontend_fetch_content_layouts')) {
    /**
     * 內容區塊呈現方式（內容欄位 1–6）
     * 優先 news_msg.imgShow；其次 news_img.intType（img Sort = 內容欄位 + 1）
     *
     * @return array<int,int>
     */
    function frontend_fetch_content_layouts(int $parentPKey, ?array $cfg = null): array
    {
        global $this_lang;

        $cfg = $cfg ?? frontend_module_config();
        $fkCol = trim((string)($cfg['fk'] ?? ''));
        $layouts = [];

        if ($parentPKey <= 0 || $fkCol === '') {
            return $layouts;
        }

        $imgTable = trim((string)($cfg['img'] ?? ''));
        if ($imgTable !== '') {
            $layoutCol = null;
            if (function_exists('crud_table_has_column')) {
                if (crud_table_has_column($imgTable, 'intType')) {
                    $layoutCol = 'intType';
                } elseif (crud_table_has_column($imgTable, 'imgShow')) {
                    $layoutCol = 'imgShow';
                } elseif (crud_table_has_column($imgTable, 'isShow')) {
                    $layoutCol = 'isShow';
                }
            }
            if ($layoutCol !== null) {
                $sql = "SELECT Sort, {$layoutCol} FROM {$imgTable}"
                    . " WHERE {$fkCol} = :parentPKey AND Sort > 1 ORDER BY Sort";
                foreach (crud_fetch_all($sql, ['parentPKey' => $parentPKey]) as $row) {
                    $contentSlot = crud_row_int($row, 'Sort') - 1;
                    if ($contentSlot < 1) {
                        continue;
                    }
                    $n = manage_content_img_layout_normalize($row[$layoutCol]);
                    if ($n > 0) {
                        $layouts[$contentSlot] = $n;
                    }
                }
            }
        }

        $msgTable = trim((string)($cfg['msg'] ?? ''));
        if ($msgTable !== ''
            && function_exists('crud_table_has_column')
            && crud_table_has_column($msgTable, 'imgShow')
        ) {
            $sql = "SELECT Sort, imgShow FROM {$msgTable}"
                . " WHERE {$fkCol} = :parentPKey AND intLang = :intLang ORDER BY Sort";
            foreach (crud_fetch_all($sql, ['parentPKey' => $parentPKey, 'intLang' => (int)$this_lang]) as $row) {
                $contentSlot = crud_row_int($row, 'Sort');
                $n = manage_content_img_layout_normalize($row['imgShow']);
                if ($n > 0) {
                    $layouts[$contentSlot] = $n;
                }
            }
        }

        return $layouts;
    }
}

if (!function_exists('frontend_fetch_detail_photos')) {
    /**
     * 內頁內文圖（Sort > 1；webp 優先，gif 除外）
     *
     * @return array{photo:array<int,string>,photoM:array<int,string>}
     */
    function frontend_fetch_detail_photos(int $parentPKey, ?array $cfg = null): array
    {
        global $web_root;

        $cfg = $cfg ?? frontend_module_config();
        $imgTable = trim((string)($cfg['img'] ?? ''));
        $fkCol = trim((string)($cfg['fk'] ?? ''));
        $photo = [];
        $photoM = [];

        if ($parentPKey <= 0 || $imgTable === '' || $fkCol === '') {
            return ['photo' => $photo, 'photoM' => $photoM];
        }

        $diskBase = frontend_upload_disk_base();

        $sql = "SELECT Sort, Forder, Photo1, PhotoM FROM {$imgTable}"
            . " WHERE {$fkCol} = :parentPKey AND Photo1 <> :empty AND Sort > 1"
            . ' ORDER BY Sort';

        foreach (crud_fetch_all($sql, ['parentPKey' => $parentPKey, 'empty' => '']) as $row) {
            $folder = trim((string)crud_row_val($row, 'Forder'), "/\\");
            $file = basename((string)crud_row_val($row, 'Photo1'));
            if ($file === '') {
                continue;
            }

            $diskPath = $diskBase . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $file;
            $webpDisk = preg_replace('/\.[^.]+$/i', '.webp', $diskPath);
            $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));

            $useDisk = null;
            if ($ext !== 'gif' && is_file($webpDisk)) {
                $useDisk = $webpDisk;
            } elseif (is_file($diskPath)) {
                $useDisk = $diskPath;
            }

            if ($useDisk !== null) {
                $slot = crud_row_int($row, 'Sort') - 1;
                $relUrl = 'Upload/' . $folder . '/' . basename($useDisk);
                $photo[$slot] = (string)($web_root . ltrim($relUrl, '/'));
                $photoM[$slot] = e_attr((string)crud_row_val($row, 'PhotoM'));
            }
        }

        return ['photo' => $photo, 'photoM' => $photoM];
    }
}

if (!function_exists('frontend_fetch_detail_links')) {
    /** @return list<array{title:string,url:string}> */
    function frontend_fetch_detail_links(int $parentPKey, ?array $cfg = null): array
    {
        global $this_lang;

        $cfg = $cfg ?? frontend_module_config();
        $linkTable = trim((string)($cfg['link'] ?? ''));
        $fkCol = trim((string)($cfg['fk'] ?? ''));
        $links = [];

        if ($parentPKey <= 0 || $linkTable === '' || $fkCol === '') {
            return $links;
        }
        if (!crud_module_child_table_ok($linkTable, $fkCol)) {
            return $links;
        }

        $sql = "SELECT strName, strLink FROM {$linkTable}"
            . " WHERE {$fkCol} = :parentPKey AND intLang = :intLang ORDER BY Sort";

        foreach (crud_fetch_all($sql, ['parentPKey' => $parentPKey, 'intLang' => (int)$this_lang]) as $row) {
            $links[] = [
                'title' => e_attr((string)crud_row_val($row, 'strName')),
                'url'   => e_attr((string)crud_row_val($row, 'strLink')),
            ];
        }

        return $links;
    }
}

if (!function_exists('frontend_fetch_product_gallery_images')) {
    /**
     * 產品明細圖（product_img Sort 2–6，排除 intType=2 檔案欄）
     *
     * @return list<array{sort:int,url:string,thumb:string,alt:string}>
     */
    function frontend_fetch_product_gallery_images(int $productPKey, ?array $cfg = null): array
    {
        if ($productPKey <= 0) {
            return [];
        }

        $cfg      = $cfg ?? frontend_module_config();
        $imgTable = trim((string)($cfg['img'] ?? 'product_img'));
        $fkCol    = trim((string)($cfg['fk'] ?? 'Product_PKey'));

        if ($imgTable === '' || $fkCol === '' || !crud_is_safe_sql_identifier($imgTable) || !crud_is_safe_sql_identifier($fkCol)) {
            return [];
        }

        $cols = ['Sort', 'Forder', 'Photo1'];
        if (function_exists('crud_table_has_column') && crud_table_has_column($imgTable, 'intType')) {
            $cols[] = 'intType';
        }
        if (function_exists('crud_table_has_column') && crud_table_has_column($imgTable, 'PhotoM')) {
            $cols[] = 'PhotoM';
        }

        $sql = 'SELECT ' . implode(', ', $cols) . " FROM {$imgTable}"
            . " WHERE {$fkCol} = :pk AND Photo1 <> '' AND Sort BETWEEN 2 AND 6"
            . ' ORDER BY Sort';

        $images = [];
        foreach (crud_fetch_all($sql, ['pk' => $productPKey]) as $row) {
            if (array_key_exists('intType', $row) && (int)($row['intType'] ?? 1) === 2) {
                continue;
            }

            $folder = trim((string)crud_row_val($row, 'Forder'), "/\\");
            $photo  = basename((string)crud_row_val($row, 'Photo1'));
            if ($folder === '' || $photo === '') {
                continue;
            }

            $url = frontend_upload_public_url($folder, $photo);
            if ($url === null) {
                continue;
            }

            $thumb = frontend_upload_grid_image_url($folder, $photo) ?? $url;
            $images[] = [
                'sort'  => crud_row_int($row, 'Sort'),
                'url'   => $url,
                'thumb' => $thumb,
                'alt'   => strip_tags((string)crud_row_val($row, 'PhotoM')),
            ];
        }

        return $images;
    }
}

if (!function_exists('frontend_fetch_product_msg_tabs')) {
    /**
     * 產品內容書籤（product_msg Sort 1–6）
     *
     * @return list<array{slot:int,title:string,html:string}>
     */
    function frontend_fetch_product_msg_tabs(int $productPKey, ?array $cfg = null): array
    {
        global $this_lang;

        if ($productPKey <= 0) {
            return [];
        }

        $cfg      = $cfg ?? frontend_module_config();
        $msgTable = trim((string)($cfg['msg'] ?? 'product_msg'));
        $fkCol    = trim((string)($cfg['fk'] ?? 'Product_PKey'));

        if ($msgTable === '' || $fkCol === '' || !crud_is_safe_sql_identifier($msgTable) || !crud_is_safe_sql_identifier($fkCol)) {
            return [];
        }

        $cols = ['Sort', 'Contents'];
        if (function_exists('crud_table_has_column') && crud_table_has_column($msgTable, 'Title')) {
            $cols[] = 'Title';
        }
        if (function_exists('crud_table_has_column') && crud_table_has_column($msgTable, 'isShow')) {
            $cols[] = 'isShow';
        }

        $sql = 'SELECT ' . implode(', ', $cols) . " FROM {$msgTable}"
            . " WHERE {$fkCol} = :pk AND intLang = :lang"
            . ' ORDER BY Sort';

        $tabs = [];
        foreach (crud_fetch_all($sql, ['pk' => $productPKey, 'lang' => (int)$this_lang]) as $row) {
            if (array_key_exists('isShow', $row) && crud_row_int($row, 'isShow') === 0) {
                continue;
            }

            $html = trim((string)crud_row_val($row, 'Contents'));
            if ($html === '') {
                continue;
            }

            $slot  = crud_row_int($row, 'Sort');
            $title = trim((string)crud_row_val($row, 'Title'));
            if ($title === '') {
                $title = '內容' . $slot;
            }

            $tabs[] = [
                'slot'  => $slot,
                'title' => $title,
                'html'  => rwd_table($html, 1),
            ];
        }

        return $tabs;
    }
}

if (!function_exists('frontend_not_found_exit')) {
    function frontend_not_found_exit(?string $listHref = null): never
    {
        global $lang_text, $this_lang;

        $listUrl = safe_href($listHref ?? frontend_list_href());
        $listUrlAttr = e_attr($listUrl);
        $msg = (string)($lang_text['warn_data_not_found'][$this_lang] ?? '查無資料');

        echo '<!DOCTYPE html><html lang="zh-Hant"><head>';
        echo '<meta charset="utf-8">';
        echo '<meta http-equiv="refresh" content="0;url=' . $listUrlAttr . '">';
        echo '<title>' . e($msg) . '</title>';
        echo '</head><body>';
        echo script_open();
        echo 'alert(' . js_str($msg) . ');';
        echo 'location.replace(' . js_str($listUrl) . ');';
        echo script_close();
        echo '<noscript><p>' . e($msg) . '：<a href="' . $listUrlAttr . '">請點此返回</a></p></noscript>';
        echo '</body></html>';
        exit;
    }
}
