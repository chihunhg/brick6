<?php
declare(strict_types=1);
/**
 * faq 模組資料表設定（列表圖 + CKEditor 內文存 faq_msg，與 paper 相同子表結構）
 */
return [
    'master'         => 'faq',
    'img'            => 'faq_img',
    'lang'           => 'faq_lang',
    'msg'            => 'faq_msg',
    'link'           => '',
    'fk'             => 'FAQ_PKey',
    'module_pk_col'  => 'Module_PKey',
    'csrf'           => 'faq_addin',
    'list_csrf'      => 'faq_list',
    'list_file'      => 'list.php',
    'forder_prefix'  => 'faq_',
    'content_blocks' => 1,
    'photo_slots'    => 1,
];
