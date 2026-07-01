<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$formCtx = manage_child_form_update_prepare([

    'config'            => $detailConfig,

    'parent_resolve'    => static function (): int {

        $dpk = question_item_resolve_class_pkey();

        if ($dpk <= 0) {

            global $filter_array;

            $dpk = safe_int($filter_array['Question_D_PKey'] ?? 0);

        }



        return $dpk;

    },

    'parent_load'       => 'question_item_load_parent',

    'parent_fail_msg'   => '查無問卷類別資料!',

    'parent_fail_url'   => '../question/list.php',

    'return_url'        => static fn(array $parent): string => question_child_return_url(

        (int)$parent['Question_D_PKey'],

        'list.php',

        'Question_D_PKey'

    ),

    'form_init'         => 'question_item_form_init',

    'form_apply_parent' => static function (array $parent): void {

        question_item_form_apply_parent(

            (int)$parent['Question_PKey'],

            (int)$parent['Question_D_PKey'],

            (string)$parent['Question_Name'],

            (string)$parent['Question_Class_Name']

        );

    },

    'form_load'         => static function (int $editPKey, array $parent): bool {

        return question_item_form_load($editPKey, (int)$parent['Question_D_PKey']);

    },

    'breadcrumbs'       => static fn(array $parent): array => question_item_breadcrumbs_for_form(

        (int)$parent['Question_PKey'],

        (string)$parent['Question_Name'],

        (int)$parent['Question_D_PKey'],

        (string)$parent['Question_Class_Name']

    ),

]);
$csrf_token = $formCtx['csrf_token'];
$breadcrumbs = $formCtx['breadcrumbs'];
$layout_page_title = $formCtx['layout_page_title'];

require_once '_detail.php';

