<?php
declare(strict_types=1);
/**
 * class3 模組資料表設定
 */
return [
    'master'        => 'dbclass3',       // 主檔資料表
    'img'           => 'dbclass3_img',   // 圖片/檔案子表（無則留空）
    'lang'          => 'dbclass3_lang',  // 語系子表（無則留空）
    'msg'           => '',               // 內文子表（CKEditor，無則留空）
    'link'          => '',               // 連結/關聯子表（無則留空）
    'fk'            => 'Class3_PKey',    // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey',    // 主檔所屬模組欄位
    'csrf'          => 'dbclass3_addin', // addin 表單 CSRF key
    'has_sort'      => true,             // 是否顯示/儲存順序欄位
    'img_slot_max'  => 1,                // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 2,                // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'dbclass3_',      // 上傳檔案目錄前綴
    'delete_lock_tables' => [           // 刪除前引用檢查（表名 => 外鍵欄位）
        'news'      => 'Class3_PKey',
        'paper'     => 'Class3_PKey',
        'product'   => 'Class3_PKey',
        'knowledge' => 'Class3_PKey',
        'video'     => 'Class3_PKey',
        'filedown'  => 'Class3_PKey',
        'faq'       => 'Class3_PKey',
        'question'  => 'Class3_PKey',
        'album'     => 'Class3_PKey',
        'dbweb'     => 'Class3_PKey',
        'tag'       => 'Class3_PKey',
    ],
];
