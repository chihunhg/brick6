<?php
declare(strict_types=1);
/**
 * paper 模組資料表設定（class1 範本，複製到其他模組時改這裡即可）
 */
return [
    'master'                  => 'paper',        // 主檔資料表
    'img'                     => 'paper_img',    // 圖片/檔案子表（無則留空）
    'lang'                    => 'paper_lang',   // 語系子表（無則留空）
    'msg'                     => 'paper_msg',    // 內文子表（CKEditor，無則留空）
    'link'                    => 'paper_link',   // 連結/關聯子表（無則留空）
    'fk'                      => 'Paper_PKey',   // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col'           => 'Module_PKey',  // 主檔所屬模組欄位
    'csrf'                    => 'paper_addin',  // addin 表單 CSRF key
    'has_sort'                => true,             // 是否顯示/儲存順序欄位
    'img_slot_max'            => 7,             // 圖片欄位總數（Photo1 列表圖 + Photo2～N 內容圖）
    'img_file_from'           => 8,             // 檔案欄位起始（paper 無檔案欄位，設 max+1 即可）
    'forder_prefix'           => 'paper_',       // 上傳檔案目錄前綴
    'tag_relation_parent_col' => 'Paper_PKey',   // 標籤關聯父欄位名
];
