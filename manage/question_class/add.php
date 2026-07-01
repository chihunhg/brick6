<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$formCtx = manage_child_form_add_prepare([

    'config'            => $detailConfig,

    'parent_resolve'    => 'question_class_resolve_question_pkey',

    'parent_load'       => 'question_class_load_parent',

    'parent_fail_msg'   => '查無問卷資料!',

    'parent_fail_url'   => '../question/list.php',

    'form_init'         => 'question_class_form_init',

    'form_apply_parent' => static function (array $parent): void {

        question_class_form_apply_parent((int)$parent['Question_PKey'], (string)$parent['Question_Name']);

    },

    'copy_prepare'      => static function (array $parent): void {

        global $filter_array;

        $copyPkey = safe_int($filter_array['PKey'] ?? 0);

        $qpk = (int)$parent['Question_PKey'];

        if ($copyPkey > 0 && question_class_form_load($copyPkey, $qpk)) {

            $GLOBALS['question_class_form']['Update_PKey'] = 0;

            $GLOBALS['question_class_form']['Sort'] = question_class_next_sort($qpk);

            question_class_form_export();

        } else {

            $GLOBALS['question_class_form']['Sort'] = question_class_next_sort($qpk);

            question_class_form_export();

        }

    },

    'breadcrumbs'       => static fn(array $parent): array => question_class_breadcrumbs_for_form(

        (int)$parent['Question_PKey'],

        (string)$parent['Question_Name']

    ),

]);
$csrf_token = $formCtx['csrf_token'];
$breadcrumbs = $formCtx['breadcrumbs'];
$layout_page_title = $formCtx['layout_page_title'];

require_once '_detail.php';

