<?php
declare(strict_types=1);

/**
 * 相簿圖庫（album_img 子列，排除 Home=Yes 的列表圖）
 */
return [
    'master'          => 'album_img',
    'img'             => 'album_img',
    'fk'              => 'Album_PKey',
    'parent_table'    => 'album',
    'csrf'            => 'album_d_addin',
    'list_csrf'       => 'album_d_list',
    'list_file'       => 'list.php',
    'forder_prefix'   => 'album_',
    'has_sort'        => true,
    'add_photo_slots' => 10,
];
