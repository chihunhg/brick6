<?php
declare(strict_types=1);
/**
 * company 模組資料表設定（結構同 paper/_config.php）
 *
 * master  : 主檔
 * img     : 圖片子表（無則留空字串，載入時會略過）
 * lang    : 語系子表
 * msg     : 內文子表
 * link    : 連結子表
 * fk      : 子表外鍵欄位名稱（對應主檔 PKey）
 * csrf    : addin.php 使用的 CSRF key
 */
return [
    'master'         => 'company',
    'img'            => 'company_img',
    'lang'           => 'company_lang',
    'msg'            => 'company_msg',
    'link'           => '',
    'fk'             => 'Company_PKey',
    'module_pk_col'  => 'Module_PKey',
    'csrf'           => 'company_addin',
    'list_csrf'      => 'company_list',
    'list_file'      => 'list.php',
    'forder_prefix'  => 'company_',
];
