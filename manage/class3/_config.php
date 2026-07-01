<?php
declare(strict_types=1);
/**
 * class3 模組資料表設定
 *
 * delete_lock_tables : 刪除前檢查（子表 => 外鍵欄位）；不存在或欄位錯誤的項目會自動略過
 */
return [
    'master' => 'dbclass3',
    'img'    => 'dbclass3_img',
    'lang'   => 'dbclass3_lang',
    'msg'    => '',
    'link'   => '',
    'fk'     => 'Class3_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'   => 'dbclass3_addin',
    'delete_lock_tables' => [
        'news'       => 'Class3_PKey',
        'paper'      => 'Class3_PKey',
        'product'    => 'Class3_PKey',
        'knowledge'  => 'Class3_PKey',
        'video'      => 'Class3_PKey',
        'filedown'   => 'Class3_PKey',
        'faq'        => 'Class3_PKey',
        'question'   => 'Class3_PKey',
        'album'      => 'Class3_PKey',
        'dbweb'      => 'Class3_PKey',
        'tag'        => 'Class3_PKey',
    ],
];
