<?php
declare(strict_types=1);
/**
 * 單元模組（module_p / module_d / module_lang）設定
 */
return [
    'master'             => 'module_p',    // 主檔資料表
    'img'                => '',          // 圖片/檔案子表（無則留空）
    'lang'               => 'module_lang', // 語系子表（無則留空）
    'msg'                => '',          // 內文子表（CKEditor，無則留空）
    'link'               => '',          // 連結/關聯子表（無則留空）
    'fk'                 => 'Module_PKey', // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col'      => '',          // 主檔所屬模組欄位（此模組無）
    'csrf'               => 'module_p_addin', // addin 表單 CSRF key
    'has_sort'           => false,       // 是否顯示/儲存順序欄位
    'img_slot_max'       => 0,           // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from'      => 1,           // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix'      => 'module_',   // 上傳檔案目錄前綴
    'delete_lock_tables' => [],          // 刪除前引用檢查（表名 => 外鍵欄位）
];
