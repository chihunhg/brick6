<?php
declare(strict_types=1);
/**
 * company 模組資料表設定（結構同 paper/_config.php）
 */
return [
    'master'         => 'company',       // 主檔資料表
    'img'            => 'company_img',   // 圖片/檔案子表（無則留空）
    'lang'           => 'company_lang',  // 語系子表（無則留空）
    'msg'            => 'company_msg',   // 內文子表（CKEditor，無則留空）
    'link'           => '',              // 連結/關聯子表（無則留空）
    'fk'             => 'Company_PKey',  // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col'  => 'Module_PKey',   // 主檔所屬模組欄位
    'csrf'           => 'company_addin', // addin 表單 CSRF key
    'has_sort'       => true,            // 是否顯示/儲存順序欄位
    'img_slot_max'   => 7,               // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from'  => 8,               // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix'  => 'company_',      // 上傳檔案目錄前綴
    'list_csrf'      => 'company_list',  // 列表頁 CSRF key
    'list_file'      => 'list.php',      // 列表頁檔名
];
