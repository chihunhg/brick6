<?php
declare(strict_types=1);

/**
 * tag 模組資料表設定（標籤管理：主檔 + 語系）
 */
return [
    'master'         => 'tag',
    'img'            => '',
    'lang'           => 'tag_lang',
    'msg'            => '',
    'link'           => '',
    'fk'             => 'Tag_PKey',
    'module_pk_col'  => 'Module_PKey',
    'csrf'           => 'tag_addin',
    'list_csrf'      => 'tag_list',
    'list_file'      => 'list.php',
    'forder_prefix'  => 'tag_',
];
