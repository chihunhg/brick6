<?php
declare(strict_types=1);
/**
 * 網站連結（dbweb / dbweb_img / dbweb_lang）
 */
return [
    'master'        => 'dbweb',          // 主檔資料表
    'img'           => 'dbweb_img',      // 圖片/檔案子表（無則留空）
    'lang'          => 'dbweb_lang',     // 語系子表（無則留空）
    'msg'           => '',               // 內文子表（CKEditor，無則留空）
    'link'          => '',               // 連結/關聯子表（無則留空）
    'fk'            => 'Web_PKey',       // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey',    // 主檔所屬模組欄位
    'csrf'          => 'dbweb_addin',    // addin 表單 CSRF key
    'has_sort'      => true,             // 是否顯示/儲存順序欄位
    'img_slot_max'  => 1,                // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 2,                // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'dbweb_',         // 上傳檔案目錄前綴
    'list_csrf'     => 'dbweb_list',     // 列表頁 CSRF key
    'list_file'     => 'list.php',       // 列表頁檔名
];
