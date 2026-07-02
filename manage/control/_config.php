<?php
declare(strict_types=1);
/**
 * 後台帳號管理（webcontrol）
 */
return [
    'master'        => 'webcontrol',     // 主檔資料表
    'fk'            => 'PKey',           // 子表外鍵欄位（指向主檔 PKey）
    'csrf'          => 'manage_form',    // addin 表單 CSRF key
    'has_sort'      => false,            // 是否顯示/儲存順序欄位
    'img_slot_max'  => 0,                // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 1,                // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'webcontrol_',    // 上傳檔案目錄前綴
    'list_csrf'     => 'control_list',   // 列表頁 CSRF key
    'list_file'     => 'list.php',       // 列表頁檔名
];
