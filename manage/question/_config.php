<?php
declare(strict_types=1);
/**
 * 問卷主檔（question / question_img / question_lang / question_msg）
 */
return [
    'master'         => 'question',      // 主檔資料表
    'img'            => 'question_img',  // 圖片/檔案子表（無則留空）
    'lang'           => 'question_lang', // 語系子表（無則留空）
    'msg'            => 'question_msg',  // 內文子表（CKEditor，無則留空）
    'link'           => 'question_item', // 連結/關聯子表（問卷題目）
    'fk'             => 'Question_PKey', // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col'  => 'Module_PKey',   // 主檔所屬模組欄位
    'csrf'           => 'question_addin', // addin 表單 CSRF key
    'has_sort'       => true,            // 是否顯示/儲存順序欄位
    'img_slot_max'   => 1,               // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from'  => 2,               // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix'  => 'question_',     // 上傳檔案目錄前綴
    'list_csrf'      => 'question_list', // 列表頁 CSRF key
    'list_file'      => 'list.php',      // 列表頁檔名
    'content_blocks' => 1,               // CKEditor 內容區塊數
];
