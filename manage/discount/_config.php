<?php
declare(strict_types=1);
/**
 * 優惠折抵模組（discount_p：滿件／滿額折抵運費）
 */
return [
    'master'           => 'discount_p',
    'fk'               => 'Discount_PKey',
    'module_pk_col'    => 'Module_PKey',
    'csrf'             => 'discount_addin',
    'list_csrf'        => 'discount_list',
    'list_file'        => 'list.php',
    'list_layout'      => 'full',
    'sort_column'      => 'OpenDate',
    'sort_direction'   => 'DESC',
    'has_sort'         => false,
    'list_show_sort'   => false,
    'list_show_upload' => false,
];
