<?php
declare(strict_types=1);
/**
 * faq 模組資料表設定（列表圖 + CKEditor 內文存 faq_msg）
 */
return [
    'master'         => 'faq',           // 主檔資料表
    'img'            => 'faq_img',       // 圖片/檔案子表（無則留空）
    'lang'           => 'faq_lang',      // 語系子表（無則留空）
    'msg'            => 'faq_msg',       // 內文子表（CKEditor，無則留空）
    'link'           => '',              // 連結/關聯子表（無則留空）
    'fk'             => 'FAQ_PKey',      // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col'  => 'Module_PKey',   // 主檔所屬模組欄位
    'csrf'           => 'faq_addin',     // addin 表單 CSRF key
    'has_sort'       => true,            // 是否顯示/儲存順序欄位
    'img_slot_max'   => 1,               // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from'  => 2,               // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix'  => 'faq_',          // 上傳檔案目錄前綴
    'list_csrf'      => 'faq_list',      // 列表頁 CSRF key
    'list_file'      => 'list.php',      // 列表頁檔名
    'content_blocks' => 1,               // CKEditor 內容區塊數
];
