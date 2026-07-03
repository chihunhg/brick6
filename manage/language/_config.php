<?php
declare(strict_types=1);
/**
 * class1 模組資料表設定（複製到其他模組時改這裡即可）
 */
return [
    'master'        => 'language',       // 主檔資料表
    'img'           => '',   // 圖片/檔案子表（無則留空）
    'lang'          => '',  // 語系子表（無則留空）
    'msg'           => '',   // 內文子表（CKEditor，無則留空）
    'link'          => '',               // 連結/關聯子表（無則留空）
    'fk'            => 'Language_PKey',    // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey',    // 主檔所屬模組欄位
    'csrf'          => 'language_addin', // addin 表單 CSRF key
    'has_sort'      => true,             // 是否顯示/儲存順序欄位
    'img_slot_max'  => 1,                // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 2,                // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'class1_',        // 上傳檔案目錄前綴
    'delete_lock_tables' => [],
];
