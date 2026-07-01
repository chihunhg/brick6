<?php



declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$listCtx = manage_child_list_prepare([

    'config'         => $detailConfig,

    'parent_resolve' => 'question_item_resolve_class_pkey',

    'parent_load'    => 'question_item_load_parent',

    'parent_fail_msg'=> '查無問卷類別資料!',

    'parent_fail_url'=> '../question/list.php',

    'crud_fk'        => 'Question_PKey',

    'list_return_fk' => 'Question_D_PKey',

    'expand_list'    => false,

    'list_where'     => static function (array $parent): array {

        $dpk = (int)$parent['Question_D_PKey'];



        return [

            ' WHERE Question_D_PKey = :Question_D_PKey',

            ['Question_D_PKey' => $dpk],

        ];

    },

    'delete_handler' => static function (array $ids): void {

        foreach ($ids as $id) {

            question_item_delete_related_rows((int)$id);

        }

    },

    'expose_parent'  => static function (array $parent): void {

        global $Question_PKey, $Question_D_PKey, $Question_Name, $Question_Class_Name;

        $Question_PKey       = (int)$parent['Question_PKey'];

        $Question_D_PKey     = (int)$parent['Question_D_PKey'];

        $Question_Name       = (string)$parent['Question_Name'];

        $Question_Class_Name = (string)$parent['Question_Class_Name'];

    },

    'breadcrumbs'    => static function (array $parent): array {

        global $Module_Name, $manNo;

        $classListUrl = question_child_return_url((int)$parent['Question_PKey'], '../question_class/list.php');



        return [

            ['label' => '單元管理'],

            ['label' => (string)($Module_Name ?? '')],

            ['label' => '問卷管理', 'href' => '../question/list.php?manNo=' . urlencode((string)($manNo ?? ''))],

            ['label' => (string)$parent['Question_Name'], 'href' => $classListUrl],

            ['label' => (string)$parent['Question_Class_Name']],

        ];

    },

    'page_title'     => static fn(array $parent): string => (string)$parent['Question_Class_Name'] . '－題目管理',

    'list_back_url'  => static fn(array $parent): string => question_child_return_url(

        (int)$parent['Question_PKey'],

        '../question_class/list.php'

    ),

    'back_label'     => '回類別列表',

    'add_url'        => static fn(array $parent): string => 'add.php?Question_D_PKey=' . (int)$parent['Question_D_PKey'],

    'hidden_fields'  => static function (array $parent, array $ctx): array {

        global $manNo, $subNo;



        return [

            'Question_PKey'   => (int)$parent['Question_PKey'],

            'Question_D_PKey' => (int)$parent['Question_D_PKey'],

            'manNo'           => $manNo ?? '',

            'subNo'           => $subNo ?? '',

            'Page'            => $ctx['tPage'],

            'PageSize'        => $ctx['tPageSize'],

        ];

    },

]);



manage_child_list_render($listCtx, __DIR__);

