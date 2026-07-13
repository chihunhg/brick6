<?php
declare(strict_types=1);
/**
 * 註冊／入會折價券模組（coupon_p / coupon_d）
 */
return [
    'master'        => 'coupon_p',
    'detail'        => 'coupon_d',
    'fk'            => 'Coupon_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'couponreg_addin',
    'list_csrf'     => 'couponreg_list',
    'list_file'     => 'list.php',
    'list_layout'   => 'full',
    'sort_column'   => 'PKey',
    'sort_direction'=> 'DESC',
];
