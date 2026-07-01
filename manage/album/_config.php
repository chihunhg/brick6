<?php
declare(strict_types=1);
/**
 * album 模組資料表設定（列表圖 + CKEditor 內文存 album_msg，與 paper 相同子表結構）
 */
return [
    'master'         => 'album',
    'img'            => 'album_img',
    'lang'           => 'album_lang',
    'msg'            => 'album_msg',
    'link'           => '',
    'fk'             => 'Album_PKey',
    'module_pk_col'  => 'Module_PKey',
    'csrf'           => 'album_addin',
    'list_csrf'      => 'album_list',
    'list_file'      => 'list.php',
    'forder_prefix'  => 'album_',
    'content_blocks' => 1,
    'photo_slots'    => 1,
];
