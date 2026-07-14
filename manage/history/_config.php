<?php
declare(strict_types=1);
/**
 * 歷史沿革（多語系標題 + 年份排序 + 列表圖 + CKEditor）
 */
return [
    'master'           => 'history',
    'img'              => 'history_img',
    'lang'             => 'history_lang',
    'msg'              => 'history_msg',
    'link'             => '',
    'fk'               => 'History_PKey',
    'module_pk_col'    => 'Module_PKey',
    'csrf'             => 'history_addin',
    'has_sort'         => true,
    'list_show_sort'   => true,
    'sort_column'      => 'intYear',
    'sort_direction'   => 'DESC',
    'img_slot_max'     => 1,
    'img_file_from'    => 2,
    'forder_prefix'    => 'history_',
    'list_csrf'        => 'history_list',
    'list_file'        => 'list.php',
    'list_layout'      => 'full',
    'content_blocks'   => 1,
];
