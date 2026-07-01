<?php
declare(strict_types=1);
/**
 * 單元模組（module_p / module_d / module_lang）設定
 *
 * master : module_p 主檔
 * lang   : module_lang 語系子表（Module_PKey → module_p.PKey）
 * child  : module_d 階層子表（由 crud_sync_module_d_layers 維護）
 */
return [
    'master' => 'module_p',
    'img'    => '',
    'lang'   => 'module_lang',
    'msg'    => '',
    'link'   => '',
    'fk'     => 'Module_PKey',
    'module_pk_col' => '',
    'csrf'   => 'module_p_addin',
    'delete_lock_tables' => [],
];
