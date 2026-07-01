<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



manage_child_addin_run([

    'config'         => $detailConfig,

    'parent_resolve' => static function (): int {

        global $filter_array;



        return safe_int($filter_array['Question_PKey'] ?? 0);

    },

    'parent_load'    => 'question_class_load_parent',

    'parent_fail_msg'=> '查無問卷資料',

    'parent_fail_url'=> '../question/list.php',

    'return_url'     => static fn(array $parent): string => question_child_return_url((int)$parent['Question_PKey']),

    'validate'       => static function (array $parent, array $filter): string {

        $msg = '';

        if (safe_int($filter['Sort'] ?? 0) <= 0) {

            $msg .= "【順序】空白或非數字格式\n";

        }

        $msg .= question_class_validate_strname_from_filter($filter);



        return $msg;

    },

    'verify_edit_row' => static function (int $formPKey, array $parent): bool {

        $row = crud_fetch_one(

            'SELECT PKey FROM question_class WHERE PKey = :pk AND Question_PKey = :qpk LIMIT 1',

            ['pk' => $formPKey, 'qpk' => (int)$parent['Question_PKey']]

        );



        return $row !== null;

    },

    'save'           => static function (array $parent, int $formPKey, array $filter, string $loginId): array {

        return question_class_save_multilang(

            (int)$parent['Question_PKey'],

            $formPKey,

            $filter,

            $loginId

        );

    },

]);

