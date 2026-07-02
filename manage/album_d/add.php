<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$formCtx = manage_child_form_add_prepare([

    'config'            => $detailConfig,

    'parent_resolve'    => 'album_d_resolve_album_pkey',

    'parent_load'       => 'album_d_load_parent',

    'parent_fail_msg'   => '查無相簿資料!',

    'parent_fail_url'   => '../album/list.php',

    'form_init'         => 'album_d_form_init',

    'form_apply_parent' => static function (array $parent): void {

        album_d_form_apply_parent((int)$parent['Album_PKey'], (string)$parent['Album_Name']);

    },

    'breadcrumbs'       => static fn(array $parent): array => album_d_breadcrumbs_for_form(

        (int)$parent['Album_PKey'],

        (string)$parent['Album_Name']

    ),

]);
$csrf_token = $formCtx['csrf_token'];
$breadcrumbs = $formCtx['breadcrumbs'];
$layout_page_title = $formCtx['layout_page_title'];

$addPhotoSlots = max(1, (int)($detailConfig['img_slot_max'] ?? $detailConfig['add_photo_slots'] ?? 10));

require_once '_detail.php';

