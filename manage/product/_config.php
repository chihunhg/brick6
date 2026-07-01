<?php
declare(strict_types=1);
/**
 * 產品管理模組（product / product_img / product_lang / product_msg / product_relation）
 */
return [
    'master' => 'product',
    'img'    => 'product_img',
    'lang'   => 'product_lang',
    'msg'    => 'product_msg',
    'link'   => 'product_relation',
    'fk'            => 'Product_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'product_addin',
    'has_sort'      => true,
    'img_slot_max'  => 8,
    'img_file_from' => 7,
    'forder_prefix' => 'product_',
];
