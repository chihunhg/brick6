<?php
declare(strict_types=1);

/**
 * investor 模組資料表設定（投資組合管理）
 *
 * 子表 FK 欄位：schema 為 Invenstor_PKey（拼字），舊站可能為 Investor_PKey
 */
$investorFk = 'Invenstor_PKey';
if (function_exists('crud_table_has_column')) {
    if (!crud_table_has_column('investor_lang', 'Invenstor_PKey')
        && crud_table_has_column('investor_lang', 'Investor_PKey')) {
        $investorFk = 'Investor_PKey';
    }
}

return [
    'master'         => 'investor',
    'img'            => 'investor_img',
    'lang'           => 'investor_lang',
    'msg'            => 'investor_msg',
    'link'           => '',
    'fk'             => $investorFk,
    'module_pk_col'  => 'Module_PKey',
    'csrf'           => 'investor_addin',
    'list_csrf'      => 'investor_list',
    'list_file'      => 'list.php',
    'forder_prefix'  => 'investor_',
    'content_blocks' => 6,
    'photo_slots'    => 10,
    'file_lang_slots' => [1 => 8, 2 => 9, 3 => 10],
    'list_show_type'  => true,
];
