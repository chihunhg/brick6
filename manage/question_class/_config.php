<?php
declare(strict_types=1);
/**
 * 問卷分類（question_class / question_class_lang）
 */
return [
    'master'        => 'question_class',       // 主檔資料表
    'img'           => '',                     // 圖片/檔案子表（無則留空）
    'lang'          => 'question_class_lang', // 語系子表（無則留空）
    'msg'           => '',                     // 內文子表（CKEditor，無則留空）
    'fk'            => 'Question_PKey',        // 子表外鍵欄位（指向 question 主檔）
    'parent_fk'     => 'Question_PKey',        // 父層外鍵欄位（指向 question 主檔）
    'csrf'          => 'question_class_addin', // addin 表單 CSRF key
    'has_sort'      => true,                   // 是否顯示/儲存順序欄位
    'img_slot_max'  => 0,                      // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 1,                      // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'question_class_',      // 上傳檔案目錄前綴
    'list_csrf'     => 'question_class_list',  // 列表頁 CSRF key
    'list_file'     => 'list.php',             // 列表頁檔名
];
