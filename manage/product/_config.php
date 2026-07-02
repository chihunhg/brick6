<?php
declare(strict_types=1);
/**
 * 產品管理模組（product / product_img / product_lang / product_msg / product_relation）
 */
return [
    'master'        => 'product',          // 主檔資料表
    'img'           => 'product_img',      // 圖片/檔案子表（無則留空）
    'lang'          => 'product_lang',     // 語系子表（無則留空）
    'msg'           => 'product_msg',      // 內文子表（CKEditor，無則留空）
    'link'          => 'product_relation', // 連結/關聯子表（無則留空）
    'fk'            => 'Product_PKey',     // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col' => 'Module_PKey',    // 主檔所屬模組欄位
    'csrf'          => 'product_addin',    // addin 表單 CSRF key
    'has_sort'      => true,               // 是否顯示/儲存順序欄位
    'img_slot_max'  => 8,                 // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from' => 7,                  // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix' => 'product_',         // 上傳檔案目錄前綴
];
