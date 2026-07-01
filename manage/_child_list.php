<?php

declare(strict_types=1);

/**
 * 子模組 list.php 共用（資料準備 + 版面 shell）
 */

if (!function_exists('manage_child_list_hidden_html')) {
    /**
     * @param array<string, scalar|null> $fields
     */
    function manage_child_list_hidden_html(string $csrfToken, array $fields): string
    {
        $html = hiddenText('csrf_token', e($csrfToken)) . PHP_EOL;
        foreach ($fields as $name => $value) {
            $html .= hiddenNumeric($name, $value) . PHP_EOL;
        }

        return $html;
    }
}

if (!function_exists('manage_child_list_prepare')) {
    /**
     * @param array{
     *   config?: array,
     *   list_csrf_key?: string,
     *   table?: string,
     *   pk_name?: string,
     *   page_size?: int,
     *   order_by?: string,
     *   crud_fk?: string,
     *   crud_extra?: array,
     *   list_return_fk?: string,
     *   sort_back_url?: callable,
     *   parent_resolve: callable|string,
     *   parent_load: callable|string,
     *   parent_fail_msg?: string,
     *   parent_fail_url?: string,
     *   list_where: callable,
     *   delete_handler?: callable,
     *   expand_list?: bool,
     *   breadcrumbs: callable,
     *   page_title: callable,
     *   list_back_url: callable,
     *   back_label: string,
     *   add_url: callable,
     *   hidden_fields: callable,
     *   expose_parent?: callable,
     *   notes_html?: string,
     * } $opts
     * @return array<string,mixed>
     */
    function manage_child_list_prepare(array $opts): array
    {
        global $filter_array, $manNo, $subNo;

        $detailConfig = (array)($opts['config'] ?? []);
        $listCsrfKey = (string)($opts['list_csrf_key'] ?? $detailConfig['list_csrf'] ?? '');
        $table_name = (string)($opts['table'] ?? $detailConfig['master'] ?? '');
        $PKName = (string)($opts['pk_name'] ?? 'PKey');
        $pageSize = (int)($opts['page_size'] ?? 15);
        $orderBy = (string)($opts['order_by'] ?? 'Sort ASC');

        $parentKey = (int)manage_child_call($opts['parent_resolve']);
        $parent = (array)manage_child_call($opts['parent_load'], $parentKey);
        if (!($parent['ok'] ?? false)) {
            manage_alert_script(
                (string)($opts['parent_fail_msg'] ?? '查無資料'),
                (string)($opts['parent_fail_url'] ?? 'list.php')
            );
            exit;
        }

        if (isset($opts['expose_parent']) && is_callable($opts['expose_parent'])) {
            $opts['expose_parent']($parent);
        }

        $crudFk = (string)($opts['crud_fk'] ?? '');
        $crudExtra = (array)($opts['crud_extra'] ?? []);
        $crud_cfg = $crudFk !== ''
            ? crud_cfg($table_name, $crudFk, $crudExtra)
            : crud_cfg($table_name, '', $crudExtra);

        $listReturnFk = (string)($opts['list_return_fk'] ?? $crudFk);
        $listBackUrl = null;
        if (isset($opts['sort_back_url']) && is_callable($opts['sort_back_url'])) {
            $listBackUrl = (string)manage_child_call($opts['sort_back_url'], $parent);
        } elseif ($listReturnFk !== '') {
            $parentPk = (int)($parent[$listReturnFk] ?? 0);
            if ($parentPk > 0) {
                $listBackUrl = manage_child_return_url($parentPk, 'list.php', $listReturnFk);
            }
        }

        $deleteHandler = (isset($opts['delete_handler']) && is_callable($opts['delete_handler']))
            ? $opts['delete_handler']
            : null;
        crud_process_list_actions($crud_cfg, $deleteHandler, $listBackUrl);

        crud_csrf_guard_list($listCsrfKey);
        $csrf_token = crud_csrf_ensure($listCsrfKey);

        [$PDO_Cond, $Cond_Array] = manage_child_call($opts['list_where'], $parent);

        $Total = crud_fetch_scalar(
            "SELECT COUNT({$PKName}) AS Total FROM {$table_name} {$PDO_Cond}",
            $Cond_Array,
            'Total'
        );
        $tPageSize = crud_list_page_size($filter_array ?? [], $pageSize);
        ['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = crud_paginate(
            $Total,
            $tPageSize,
            $filter_array['Page'] ?? null
        );

        $sql = "SELECT * FROM {$table_name} {$PDO_Cond} ORDER BY {$orderBy} LIMIT "
            . (int)$tPageSize . ' OFFSET ' . (int)$offset;
        $listRows = crud_fetch_all($sql, $Cond_Array);

        $i = 0;
        $list_show_expand_row = (bool)($opts['expand_list'] ?? false);
        manage_list_expand_enabled($list_show_expand_row);

        $listCtx = [
            'i'         => $i,
            'tPage'     => $tPage,
            'tPageSize' => $tPageSize,
            'tPageTotal'=> $tPageTotal,
        ];

        return [
            'parent'             => $parent,
            'table_name'         => $table_name,
            'PKName'             => $PKName,
            'csrf_token'         => $csrf_token,
            'listRows'           => $listRows,
            'breadcrumbs'        => manage_child_call($opts['breadcrumbs'], $parent),
            'layout_page_title'  => (string)manage_child_call($opts['page_title'], $parent),
            'listBackUrl'        => (string)manage_child_call($opts['list_back_url'], $parent),
            'listBackLabel'      => (string)($opts['back_label'] ?? '返回'),
            'addUrl'             => (string)manage_child_call($opts['add_url'], $parent),
            'listHiddenHtml'     => manage_child_list_hidden_html(
                $csrf_token,
                (array)manage_child_call($opts['hidden_fields'], $parent, $listCtx)
            ),
            'notes_html'         => (string)($opts['notes_html'] ?? ''),
            'i'                  => $i,
            'tPage'              => $tPage,
            'tPageSize'          => $tPageSize,
            'tPageTotal'         => $tPageTotal,
        ];
    }
}

if (!function_exists('manage_child_list_import_parent_vars')) {
    /**
     * 將 parent 純量欄位匯入目前作用域（供函式內 require _list.php 使用）
     *
     * @param array<string,mixed> $parent
     */
    function manage_child_list_import_parent_vars(array $parent): void
    {
        static $skipKeys = ['ok', 'album_row'];
        foreach ($parent as $key => $val) {
            if (!is_string($key) || in_array($key, $skipKeys, true)) {
                continue;
            }
            if (!is_scalar($val)) {
                continue;
            }
            ${$key} = $val;
            $GLOBALS[$key] = $val;
        }
    }
}

if (!function_exists('manage_child_list_render')) {
  /**
   * @param array<string,mixed> $ctx
   */
    function manage_child_list_render(array $ctx, string $moduleDir): void
    {
        global $listRows, $breadcrumbs, $layout_page_title, $layout_container_class;
        global $listBackUrl, $listBackLabel, $addUrl, $listHiddenHtml, $childListNotesHtml;
        global $PKName, $table_name, $i, $tPage, $tPageSize;

        manage_child_list_import_parent_vars((array)($ctx['parent'] ?? []));

        $parentScalars = (array)($ctx['parent'] ?? []);
        foreach ($parentScalars as $key => $val) {
            if (!is_string($key) || in_array($key, ['ok', 'album_row'], true) || !is_scalar($val)) {
                continue;
            }
            ${$key} = $val;
        }

        $listRows = (array)($ctx['listRows'] ?? []);
        $breadcrumbs = (array)($ctx['breadcrumbs'] ?? []);
        $layout_page_title = (string)($ctx['layout_page_title'] ?? '');
        $layout_container_class = 'container container--full';
        $listBackUrl = (string)($ctx['listBackUrl'] ?? '');
        $listBackLabel = (string)($ctx['listBackLabel'] ?? '返回');
        $addUrl = (string)($ctx['addUrl'] ?? 'add.php');
        $listHiddenHtml = (string)($ctx['listHiddenHtml'] ?? '');
        $childListNotesHtml = (string)($ctx['notes_html'] ?? '');
        $PKName = (string)($ctx['PKName'] ?? 'PKey');
        $table_name = (string)($ctx['table_name'] ?? '');
        $i = (int)($ctx['i'] ?? 0);
        $tPage = (int)($ctx['tPage'] ?? 1);
        $tPageSize = (int)($ctx['tPageSize'] ?? 15);

        $childListModuleDir = $moduleDir;

        require __DIR__ . '/_child_list_shell.php';
    }
}
