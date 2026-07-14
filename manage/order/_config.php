<?php
declare(strict_types=1);
/**
 * 訂單管理模組（order_p / order_d）
 */
return [
    'master'           => 'order_p',
    'detail'           => 'order_d',
    'fk'               => 'Order_PKey',
    'module_pk_col'    => 'Module_PKey',
    'csrf'             => 'order_update',
    'list_csrf'        => 'order_list',
    'list_file'        => 'list.php',
    'list_layout'      => 'full',
    'page_size'        => 30,
    'has_sort'         => false,   // 編輯表單不顯示順序
    'list_show_sort'   => false,   // 列表不顯示「確認更新排序」
    'list_show_upload' => false,   // 列表不顯示批次發佈／下架
    'list_show_add'    => false,   // 列表不顯示新增（訂單由前台產生）
];
