<?php
declare(strict_types=1);
/**
 * 訂閱電子報模組（epaper）
 */
return [
    'master'        => 'epaper',
    'fk'            => 'PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'epaper_addin',
    'list_csrf'     => 'epaper_list',
    'list_file'     => 'list.php',
    'list_layout'   => 'full',
    'page_size'     => 15,
];
