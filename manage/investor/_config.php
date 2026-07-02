<?php
declare(strict_types=1);

/**
 * investor 模組資料表設定（投資組合管理）
 *
 * 子表 FK 欄位：schema 為 Invenstor_PKey（拼字），舊站可能為 Investor_PKey
 */
$investorFk = 'Invenstor_PKey';
if (function_exists('crud_table_has_column')) {
    if (!crud_table_has_column('investor_lang', 'Invenstor_PKey')
        && crud_table_has_column('investor_lang', 'Investor_PKey')) {
        $investorFk = 'Investor_PKey';
    }
}

return [
    'master'          => 'investor',     // 主檔資料表
    'img'             => 'investor_img', // 圖片/檔案子表（無則留空）
    'lang'            => 'investor_lang', // 語系子表（無則留空）
    'msg'             => 'investor_msg', // 內文子表（CKEditor，無則留空）
    'link'            => '',             // 連結/關聯子表（無則留空）
    'fk'              => $investorFk,    // 子表外鍵欄位（指向主檔 PKey）
    'module_pk_col'   => 'Module_PKey',  // 主檔所屬模組欄位
    'csrf'            => 'investor_addin', // addin 表單 CSRF key
    'has_sort'        => true,           // 是否顯示/儲存順序欄位
    'img_slot_max'    => 10,             // 圖片/檔案欄位總數（Photo1～N）
    'img_file_from'   => 8,              // 檔案欄起始序號（此欄起為檔案上傳）
    'forder_prefix'   => 'investor_',    // 上傳檔案目錄前綴
    'list_csrf'       => 'investor_list', // 列表頁 CSRF key
    'list_file'       => 'list.php',     // 列表頁檔名
    'content_blocks'  => 6,              // CKEditor 內容區塊數
    'file_lang_slots' => [1 => 8, 2 => 9, 3 => 10], // 語系區塊對應的檔案欄序號
    'list_show_type'  => true,           // 列表是否顯示類型欄
];
