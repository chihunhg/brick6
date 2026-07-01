<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$FKName = (string)($detailConfig['fk'] ?? 'Album_PKey');

$uploadBase = crud_upload_base();



$listCtx = manage_child_list_prepare([

    'config'         => $detailConfig,

    'parent_resolve' => 'album_d_resolve_album_pkey',

    'parent_load'    => 'album_d_load_parent',

    'parent_fail_msg'=> '查無相簿資料!',

    'parent_fail_url'=> '../album/list.php',

    'crud_fk'        => $FKName,

    'crud_extra'     => [

        'upload_base' => $uploadBase,

        'img_table'   => '',

        'msg_table'   => '',

        'lang_table'  => '',

    ],

    'order_by'       => 'Sort ASC, dtUDate DESC',

    'list_where'     => static function (array $parent): array {

        return album_d_list_where((int)$parent['Album_PKey']);

    },

    'delete_handler' => static function (array $ids): void {

        album_d_delete_row_files($ids);

    },

    'expose_parent'  => static function (array $parent): void {

        global $Album_PKey, $Album_Name, $uploadBase;

        $Album_PKey = (int)$parent['Album_PKey'];

        $Album_Name = (string)$parent['Album_Name'];

        $uploadBase = crud_upload_base();

    },

    'breadcrumbs'    => static function (array $parent): array {

        global $Module_Name, $manNo;



        return [

            ['label' => '單元管理'],

            ['label' => (string)($Module_Name ?? '')],

            ['label' => '相簿管理', 'href' => '../album/list.php?manNo=' . urlencode((string)($manNo ?? ''))],

            ['label' => (string)$parent['Album_Name']],

        ];

    },

    'page_title'     => static fn(array $parent): string => (string)$parent['Album_Name'] . '－相片管理',

    'list_back_url'  => static function (): string {

        global $manNo;



        return '../album/list.php?manNo=' . urlencode((string)($manNo ?? ''));

    },

    'back_label'     => '回相簿列表',

    'add_url'        => static fn(array $parent): string => 'add.php?Album_PKey=' . (int)$parent['Album_PKey'],

    'hidden_fields'  => static function (array $parent, array $ctx): array {

        global $manNo, $subNo;



        return [

            'Album_PKey' => (int)$parent['Album_PKey'],

            'PKey'       => (int)$parent['Album_PKey'],

            'manNo'      => $manNo ?? '',

            'subNo'      => $subNo ?? '',

            'Page'       => $ctx['tPage'],

            'PageSize'   => $ctx['tPageSize'],

        ];

    },

    'notes_html'     => '<div class="notes notes--lg">'

        . '<div class="notes__header"><i class="bi bi-info-circle notes__icon"></i> 系統備註</div>'

        . '<ul class="notes__list">'

        . '<li>排序依照「順序」由小至大；順序相同則依修改日期由新至舊。</li>'

        . '<li>新增可一次上傳最多 10 張圖片；編輯僅能修改單筆相片。</li>'

        . '</ul></div><div class="notes__spacer"></div>',

]);



manage_child_list_render($listCtx, __DIR__);

