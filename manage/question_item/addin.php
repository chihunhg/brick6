<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$answerSlots = max(1, (int)($detailConfig['answer_slots'] ?? 10));



manage_child_addin_run([

    'config'         => $detailConfig,

    'parent_resolve' => 'question_item_resolve_class_pkey',

    'parent_load'    => 'question_item_load_parent',

    'parent_fail_msg'=> '查無問卷類別資料',

    'parent_fail_url'=> '../question/list.php',

    'return_url'     => static fn(array $parent): string => question_child_return_url(

        (int)$parent['Question_D_PKey'],

        'list.php',

        'Question_D_PKey'

    ),

    'validate'       => static function (array $parent, array $filter) use ($answerSlots): string {

        $msg = '';

        if (safe_int($filter['Sort'] ?? 0) <= 0) {

            $msg .= "【順序】空白或非數字格式\n";

        }

        if (trim((string)($filter['Qtype'] ?? '')) === '') {

            $msg .= "【題型】請選擇\n";

        }

        $msg .= question_item_validate_strname_from_filter($filter);

        $qtype = safe_int($filter['Qtype'] ?? 0);

        if ($qtype === 1 || $qtype === 2) {

            $msg .= question_item_validate_answers_from_filter($filter, $answerSlots);

        }



        return $msg;

    },

    'verify_edit_row' => static function (int $formPKey, array $parent): bool {
        return question_item_row_belongs_to_class($formPKey, (int)$parent['Question_D_PKey']);
    },

    'save'           => static function (array $parent, int $formPKey, array $filter, string $loginId) use ($answerSlots): array {

        return question_item_save(

            (int)$parent['Question_PKey'],

            (int)$parent['Question_D_PKey'],

            $formPKey,

            $filter,

            $loginId,

            $answerSlots

        );

    },

]);

