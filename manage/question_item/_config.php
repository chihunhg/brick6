<?php

declare(strict_types=1);

return [
    'master'        => 'question_item',
    'lang'          => 'question_itme_lang',
    'fk'            => 'Question_PKey',
    'parent_fk'     => 'Question_D_PKey',
    'csrf'          => 'question_item_addin',
    'list_csrf'     => 'question_item_list',
    'list_file'     => 'list.php',
    'has_sort'      => true,
    'answer_slots'  => 10,
];
