<?php
declare(strict_types=1);
/**
 * 各模組複製為 _config.php 後修改（範例：news、paper、product）
 *
 * 槽位設定（對應 product 模組）：
 *   img_slot_max  = 圖片數 + 檔案數（總欄位 Photo1～N）
 *   img_file_from = 圖片數 + 1（檔案起始欄；純圖片時設 max+1）
 * 例：7 圖 + 2 檔 → max=9, file_from=8
 */
return [
    'master'        => 'news',        // 主檔資料表
    'img'           => 'news_img',    // 圖片/檔案子表（無則留空）
    'lang'          => 'news_lang',   // 語系子表（無則留空）
    'msg'           => 'news_msg',    // 內文子表（CKEditor，無則留空）
    'link'          => 'news_link',   // 連結/關聯子表（無則留空）
    'fk'            => 'News_PKey',   // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey', // 主檔所屬模組欄位
    'csrf'          => 'news_addin',  // addin 表單 CSRF key
    'has_sort'      => false,         // 是否顯示/儲存順序欄位
    'img_slot_max'  => 7,             // 圖片/檔案欄位總數
    'img_file_from' => 8,             // 檔案欄起始序號
    'forder_prefix' => 'news_',       // 上傳檔案目錄前綴
];
