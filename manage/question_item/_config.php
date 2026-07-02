<?php
declare(strict_types=1);
/**
 * 問卷題目（question_item / question_itme_lang）
 */
return [
    'master'        => 'question_item',       // 主檔資料表
    'lang'          => 'question_itme_lang', // 語系子表（無則留空）
    'fk'            => 'Question_PKey',       // 子表外鍵欄位（指向 question 主檔）
    'parent_fk'     => 'Question_D_PKey',     // 父層外鍵欄位（指向 question_class）
    'csrf'          => 'question_item_addin', // addin 表單 CSRF key
    'has_sort'      => true,                  // 是否顯示/儲存順序欄位
    'img_slot_max'  => 0,                     // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 1,                     // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'question_item_',      // 上傳檔案目錄前綴
    'list_csrf'     => 'question_item_list',  // 列表頁 CSRF key
    'list_file'     => 'list.php',            // 列表頁檔名
    'answer_slots'  => 10,                    // 答案選項欄位數
];
