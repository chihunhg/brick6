<?php
declare(strict_types=1);
/**
 * class2 模組資料表設定
 */
return [
    'master'        => 'dbclass2',       // 主檔資料表
    'img'           => 'dbclass2_img',   // 圖片/檔案子表（無則留空）
    'lang'          => 'dbclass2_lang',  // 語系子表（無則留空）
    'msg'           => '',               // 內文子表（CKEditor，無則留空）
    'link'          => '',               // 連結/關聯子表（無則留空）
    'fk'            => 'Class2_PKey',    // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey',    // 主檔所屬模組欄位
    'csrf'          => 'dbclass2_addin', // addin 表單 CSRF key
    'has_sort'      => true,             // 是否顯示/儲存順序欄位
    'img_slot_max'  => 1,                // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 2,                // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'dbclass2_',      // 上傳檔案目錄前綴
    'delete_lock_tables' => [           // 刪除前引用檢查（表名 => 外鍵欄位）
        'dbclass3'  => 'Class2_PKey',
        'news'      => 'Class2_PKey',
        'paper'     => 'Class2_PKey',
        'product'   => 'Class2_PKey',
        'knowledge' => 'Class2_PKey',
        'video'     => 'Class2_PKey',
        'filedown'  => 'Class2_PKey',
        'faq'       => 'Class2_PKey',
        'question'  => 'Class2_PKey',
        'album'     => 'Class2_PKey',
        'dbweb'     => 'Class2_PKey',
        'tag'       => 'Class2_PKey',
    ],
];
