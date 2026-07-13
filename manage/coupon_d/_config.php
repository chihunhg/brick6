<?php
declare(strict_types=1);
/**
 * 折價券發送明細（coupon_d）
 */
return [
    'master'        => 'coupon_d',
    'parent'        => 'coupon_p',
    'parent_fk'     => 'Coupon_PKey',
    'csrf'          => 'coupon_d_import',
    'list_csrf'     => 'coupon_d_list',
    'list_file'     => 'list.php',
    'list_layout'   => 'full',
    'page_size'     => 30,
    'parent_list'   => '../couponreg/list.php',
];
