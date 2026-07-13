<?php
declare(strict_types=1);
/**
 * 訂單管理模組（order_p / order_d）
 */
return [
    'master'        => 'order_p',
    'detail'        => 'order_d',
    'fk'            => 'Order_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'order_update',
    'list_csrf'     => 'order_list',
    'list_file'     => 'list.php',
    'list_layout'   => 'full',
    'page_size'     => 30,
];
