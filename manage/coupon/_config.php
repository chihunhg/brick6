<?php
declare(strict_types=1);
/**
 * 批次折價券模組（coupon_p / coupon_d）
 */
return [
    'master'        => 'coupon_p',
    'detail'        => 'coupon_d',
    'fk'            => 'Coupon_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'coupon_addin',
    'list_csrf'     => 'coupon_list',
    'list_file'     => 'list.php',
    'list_layout'   => 'full',
    'sort_column'   => 'OpenDate',
    'sort_direction'=> 'DESC',
];
