<?php
declare(strict_types=1);
/**
 * 會員管理模組（member）
 */
return [
    'master'        => 'member',         // 主檔資料表
    'img'           => '',               // 圖片/檔案子表（無則留空）
    'lang'          => '',               // 語系子表（無則留空）
    'msg'           => '',               // 內文子表（CKEditor，無則留空）
    'link'          => '',               // 連結/關聯子表（無則留空）
    'fk'            => 'PKey',           // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey',    // 主檔所屬模組欄位
    'csrf'          => 'member_addin',   // addin 表單 CSRF key
    'has_sort'      => false,            // 是否顯示/儲存順序欄位
    'img_slot_max'  => 0,                // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 1,                // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'member_',        // 上傳檔案目錄前綴
];
