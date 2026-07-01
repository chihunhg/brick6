<?php
declare(strict_types=1);
/**
 * class2 模組資料表設定
 *
 * delete_lock_tables : 刪除前檢查（子表 => 外鍵欄位）；不存在或欄位錯誤的項目會自動略過
 */
return [
    'master' => 'dbclass2',
    'img'    => 'dbclass2_img',
    'lang'   => 'dbclass2_lang',
    'msg'    => '',
    'link'   => '',
    'fk'     => 'Class2_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'   => 'dbclass2_addin',
    'delete_lock_tables' => [
        'dbclass3'   => 'Class2_PKey',
        'news'       => 'Class2_PKey',
        'paper'      => 'Class2_PKey',
        'product'    => 'Class2_PKey',
        'knowledge'  => 'Class2_PKey',
        'video'      => 'Class2_PKey',
        'filedown'   => 'Class2_PKey',
        'faq'        => 'Class2_PKey',
        'question'   => 'Class2_PKey',
        'album'      => 'Class2_PKey',
        'dbweb'      => 'Class2_PKey',
        'tag'        => 'Class2_PKey',
    ],
];
