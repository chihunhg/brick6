<?php
declare(strict_types=1);
/**
 * faqdemo 模組資料表設定（Day 7 onboarding：複製自 faq）
 */
return [
    'master'         => 'faqdemo',
    'img'            => 'faqdemo_img',
    'lang'           => 'faqdemo_lang',
    'msg'            => 'faqdemo_msg',
    'link'           => '',
    'fk'             => 'FAQDemo_PKey',
    'module_pk_col'  => 'Module_PKey',
    'csrf'           => 'faqdemo_addin',
    'has_sort'       => true,
    'img_slot_max'   => 1,
    'img_file_from'  => 2,
    'forder_prefix'  => 'faqdemo_',
    'list_csrf'      => 'faqdemo_list',
    'list_file'      => 'list.php',
    'content_blocks' => 1,
];
