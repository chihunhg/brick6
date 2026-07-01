<?php

declare(strict_types=1);



/**

 * 問卷主檔（question / question_img / question_lang / question_msg）

 */

return [

    'master'         => 'question',
    'img'            => 'question_img',
    'lang'           => 'question_lang',
    'msg'            => 'question_msg',
    'link'           => 'question_item',
    'fk'             => 'Question_PKey',
    'module_pk_col'  => 'Module_PKey',
    'csrf'           => 'question_addin',
    'list_csrf'      => 'question_list',
    'list_file'      => 'list.php',
    'forder_prefix'  => 'question_',
    'has_sort'       => true,
    'content_blocks' => 1,
    'photo_slots'    => 1,

];

