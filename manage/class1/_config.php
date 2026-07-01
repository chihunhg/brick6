<?php
declare(strict_types=1);
/**
 * class1 模組資料表設定（複製到其他模組時改這裡即可）
 *
 * master  : 主檔
 * img     : 圖片子表（無則留空字串，載入時會略過）
 * lang    : 語系子表
 * msg     : 內文子表
 * link    : 連結子表
 * fk      : 子表外鍵欄位名稱（對應主檔 PKey，如 dbclass1_lang.Class1_PKey）
 * module_pk_col : 主檔所屬模組欄位（如 dbclass1.Module_PKey）
 * csrf    : addin.php 使用的 CSRF key
 * delete_lock_tables : 刪除前檢查（子表 => 外鍵欄位），任一表仍有引用則不可刪；不存在或欄位錯誤的項目會自動略過
 */
return [
    'master' => 'dbclass1',
    'img'    => 'dbclass1_img',
    'lang'   => 'dbclass1_lang',
    'msg'    => 'dbclass1_msg',
    'link'   => '',
    'fk'     => 'Class1_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'   => 'dbclass1_addin',
    'delete_lock_tables' => [
        'dbclass2' => 'Class1_PKey',
        'news'     => 'Class1_PKey',
        'paper'    => 'Class1_PKey',
        'product'  => 'Class1_PKey',        
        'knowledge'  => 'Class1_PKey',
        'video'  => 'Class1_PKey',
        'filedown'  => 'Class1_PKey',
        'faq'  => 'Class1_PKey',
        'question'  => 'Class1_PKey',
        'album'  => 'Class1_PKey',
        'dbweb'    => 'Class1_PKey',
        'tag'  => 'Class1_PKey',
    ],
];
