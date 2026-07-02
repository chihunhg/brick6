<?php
declare(strict_types=1);
/**
 * news 模組資料表設定
 */
return [
    'master'                  => 'news',         // 主檔資料表
    'img'                     => 'news_img',     // 圖片/檔案子表（無則留空）
    'lang'                    => 'news_lang',    // 語系子表（無則留空）
    'msg'                     => 'news_msg',     // 內文子表（CKEditor，無則留空）
    'link'                    => 'news_link',    // 連結/關聯子表（無則留空）
    'fk'                      => 'News_PKey',    // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col'           => 'Module_PKey',  // 主檔所屬模組欄位
    'csrf'                    => 'news_addin',   // addin 表單 CSRF key
    'has_sort'                => false,          // 是否顯示/儲存順序欄位
    'img_slot_max'            => 7,              // 圖片/檔案欄位總數（Photo1 列表圖 + Photo2～N 內容圖）
    'img_file_from'           => 8,              // 檔案欄位起始（無檔案欄位設 max+1；7圖+2檔則設 8）
    'forder_prefix'           => 'news_',        // 上傳檔案目錄前綴
    'list_show_type'          => true,           // 列表是否顯示類型欄
    'tag_relation_parent_col' => 'News_PKey',    // 標籤關聯父欄位名
];
