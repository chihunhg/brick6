<?php
declare(strict_types=1);
/**
 * news 模組資料表設定
 */
return [
    'master'   => 'news',
    'img'      => 'news_img',
    'lang'     => 'news_lang',
    'msg'      => 'news_msg',
    'link'     => 'news_link',
    'fk'            => 'News_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'news_addin',
    'has_sort'      => false,
    'list_show_type' => true,
    'tag_relation_parent_col' => 'News_PKey',
];
