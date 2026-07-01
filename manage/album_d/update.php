<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$formCtx = manage_child_form_update_prepare([

    'config'            => $detailConfig,

    'parent_resolve'    => 'album_d_resolve_album_pkey',

    'parent_load'       => 'album_d_load_parent',

    'parent_fail_msg'   => '查無相簿資料!',

    'parent_fail_url'   => '../album/list.php',

    'return_url'        => static fn(array $parent): string => album_d_child_return_url((int)$parent['Album_PKey']),

    'form_init'         => 'album_d_form_init',

    'form_apply_parent' => static function (array $parent): void {

        album_d_form_apply_parent((int)$parent['Album_PKey'], (string)$parent['Album_Name']);

    },

    'form_load'         => static function (int $editPKey, array $parent): bool {

        return album_d_form_load($editPKey, (int)$parent['Album_PKey']);

    },

    'breadcrumbs'       => static fn(array $parent): array => album_d_breadcrumbs_for_form(

        (int)$parent['Album_PKey'],

        (string)$parent['Album_Name']

    ),

]);
$csrf_token = $formCtx['csrf_token'];
$breadcrumbs = $formCtx['breadcrumbs'];
$layout_page_title = $formCtx['layout_page_title'];

require_once '_detail.php';

