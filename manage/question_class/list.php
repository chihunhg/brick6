<?php



declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$listCtx = manage_child_list_prepare([

    'config'         => $detailConfig,

    'parent_resolve' => 'question_class_resolve_question_pkey',

    'parent_load'    => 'question_class_load_parent',

    'parent_fail_msg'=> '查無問卷資料!',

    'parent_fail_url'=> '../question/list.php',

    'crud_fk'        => 'Question_PKey',

    'expand_list'    => false,

    'list_where'     => static function (array $parent): array {

        $qpk = (int)$parent['Question_PKey'];



        return [

            ' WHERE Question_PKey = :Question_PKey',

            ['Question_PKey' => $qpk],

        ];

    },

    'delete_handler' => static function (array $ids): void {

        foreach ($ids as $id) {

            question_class_delete_related_rows((int)$id);

        }

    },

    'expose_parent'  => static function (array $parent): void {

        global $Question_PKey, $Question_Name;

        $Question_PKey = (int)$parent['Question_PKey'];

        $Question_Name = (string)$parent['Question_Name'];

    },

    'breadcrumbs'    => static function (array $parent): array {

        global $Module_Name, $manNo;



        return [

            ['label' => '單元管理'],

            ['label' => (string)($Module_Name ?? '')],

            ['label' => '問卷管理', 'href' => '../question/list.php?manNo=' . urlencode((string)($manNo ?? ''))],

            ['label' => (string)$parent['Question_Name']],

        ];

    },

    'page_title'     => static fn(array $parent): string => (string)$parent['Question_Name'] . '－類別管理',

    'list_back_url'  => static function (): string {

        global $manNo;



        return '../question/list.php?manNo=' . urlencode((string)($manNo ?? ''));

    },

    'back_label'     => '回問卷列表',

    'add_url'        => static fn(array $parent): string => 'add.php?Question_PKey=' . (int)$parent['Question_PKey'],

    'hidden_fields'  => static function (array $parent, array $ctx): array {

        global $manNo, $subNo;



        return [

            'Question_PKey' => (int)$parent['Question_PKey'],

            'PKey'          => (int)$parent['Question_PKey'],

            'manNo'         => $manNo ?? '',

            'subNo'         => $subNo ?? '',

            'Page'          => $ctx['tPage'],

            'PageSize'      => $ctx['tPageSize'],

        ];

    },

]);



manage_child_list_render($listCtx, __DIR__);

