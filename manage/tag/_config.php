<?php
declare(strict_types=1);
/**
 * tag 模組資料表設定（標籤管理：主檔 + 語系）
 */
return [
    'master'        => 'tag',            // 主檔資料表
    'img'           => '',               // 圖片/檔案子表（無則留空）
    'lang'          => 'tag_lang',       // 語系子表（無則留空）
    'msg'           => '',               // 內文子表（CKEditor，無則留空）
    'link'          => '',               // 連結/關聯子表（無則留空）
    'fk'            => 'Tag_PKey',       // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey',    // 主檔所屬模組欄位
    'csrf'          => 'tag_addin',      // addin 表單 CSRF key
    'has_sort'      => true,             // 是否顯示/儲存順序欄位
    'img_slot_max'  => 0,                // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 1,                // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'tag_',           // 上傳檔案目錄前綴
    'list_csrf'     => 'tag_list',       // 列表頁 CSRF key
    'list_file'     => 'list.php',       // 列表頁檔名
];
