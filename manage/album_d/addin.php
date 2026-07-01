<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once '_form_data.php';



$addSlots = max(1, (int)($detailConfig['add_photo_slots'] ?? 10));



manage_child_addin_run([

    'config'         => $detailConfig,

    'parent_resolve' => static function (): int {

        global $filter_array;



        return safe_int($filter_array['Album_PKey'] ?? 0);

    },

    'parent_load'    => 'album_d_load_parent',

    'parent_fail_msg'=> '查無相簿資料',

    'parent_fail_url'=> '../album/list.php',

    'return_url'     => static fn(array $parent): string => album_d_child_return_url((int)$parent['Album_PKey']),

    'validate'       => static function (array $parent, array $filter) use ($addSlots): string {

        global $file_array;

        $file_array = $file_array ?? [];



        return album_d_addin_validate(

            $parent,

            $filter,

            safe_int($filter['PKey'] ?? 0),

            $file_array,

            $addSlots

        );

    },

    'save'           => static function (array $parent, int $formPKey, array $filter, string $loginId) use ($detailConfig): array {

        global $file_array;

        $file_array = $file_array ?? [];



        return album_d_addin_save($parent, $formPKey, $filter, $file_array, $loginId, $detailConfig);

    },

]);

