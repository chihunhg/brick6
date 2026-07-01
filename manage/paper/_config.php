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
 * fk      : 子表外鍵欄位名稱（對應主檔 PKey）
 * csrf    : addin.php 使用的 CSRF key
 */
return [
    'master' => 'paper',
    'img'    => 'paper_img',
    'lang'   => 'paper_lang',
    'msg'    => 'paper_msg',
    'link'   => 'paper_link',
    'fk'            => 'Paper_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'paper_addin',
    'tag_relation_parent_col' => 'Paper_PKey',
];
