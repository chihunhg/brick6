<?php
declare(strict_types=1);
/**
 * 相簿圖庫（album_img 子列，排除 Home=Yes 的列表圖）
 */
return [
    'master'        => 'album_img',      // 主檔資料表（此模組以子表為主）
    'img'           => 'album_img',      // 圖片/檔案子表
    'fk'            => 'Album_PKey',     // 子表外鍵欄位（指向 album 主檔）
    'parent_table'  => 'album',          // 父主檔表名
    'csrf'          => 'album_d_addin',  // addin 表單 CSRF key
    'has_sort'      => true,             // 是否顯示/儲存順序欄位
    'img_slot_max'  => 10,               // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 11,               // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'album_',         // 上傳檔案目錄前綴
    'list_csrf'     => 'album_d_list',   // 列表頁 CSRF key
    'list_file'     => 'list.php',       // 列表頁檔名
];
