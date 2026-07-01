<?php

declare(strict_types=1);

/**
 * 子模組 add.php / update.php / addin.php 共用
 */

if (!function_exists('manage_child_parent_guard')) {
    /**
     * @param array{
     *   parent_resolve: callable|string,
     *   parent_load: callable|string,
     *   parent_fail_msg?: string,
     *   parent_fail_url?: string,
     *   fail_mode?: 'alert'|'form_error',
     * } $opts
     * @return array<string,mixed>
     */
    function manage_child_parent_guard(array $opts): array
    {
        $parentKey = (int)manage_child_call($opts['parent_resolve']);
        $parent = (array)manage_child_call($opts['parent_load'], $parentKey);
        if (!($parent['ok'] ?? false)) {
            $failUrl = (string)($opts['parent_fail_url'] ?? 'list.php');
            $failMsg = (string)($opts['parent_fail_msg'] ?? '查無資料');
            if (($opts['fail_mode'] ?? 'alert') === 'form_error') {
                crud_form_error_redirect($failMsg, $failUrl);
            } else {
                manage_alert_script($failMsg, $failUrl);
            }
            exit;
        }

        return $parent;
    }
}

if (!function_exists('manage_child_form_add_prepare')) {
    /**
     * @param array{
     *   config?: array,
     *   csrf_key?: string,
     *   parent_resolve: callable|string,
     *   parent_load: callable|string,
     *   parent_fail_msg?: string,
     *   parent_fail_url?: string,
     *   form_init: callable,
     *   form_apply_parent: callable,
     *   copy_prepare?: callable,
     *   breadcrumbs: callable,
     *   work_file?: string,
     * } $opts
     * @return array{
     *   parent: array<string,mixed>,
     *   breadcrumbs: list<array<string,string>>,
     *   csrf_token: string,
     *   layout_page_title: string,
     * }
     */
    function manage_child_form_add_prepare(array $opts): array
    {
        $detailConfig = (array)($opts['config'] ?? []);
        $parent = manage_child_parent_guard($opts);

        $__csrf_key = (string)($opts['csrf_key'] ?? $detailConfig['csrf'] ?? '');
        $csrf_token = crud_csrf_ensure_page($__csrf_key);

        manage_child_call($opts['form_init']);
        manage_child_call($opts['form_apply_parent'], $parent);

        $GLOBALS['WorkFile'] = (string)($opts['work_file'] ?? 'add.php');

        if (isset($opts['copy_prepare']) && is_callable($opts['copy_prepare'])) {
            $opts['copy_prepare']($parent);
        }

        $breadcrumbs = (array)manage_child_call($opts['breadcrumbs'], $parent);
        $layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

        return [
            'parent'            => $parent,
            'breadcrumbs'       => $breadcrumbs,
            'csrf_token'        => $csrf_token,
            'layout_page_title' => $layout_page_title,
        ];
    }
}

if (!function_exists('manage_child_form_update_prepare')) {
    /**
     * @param array{
     *   config?: array,
     *   csrf_key?: string,
     *   parent_resolve: callable|string,
     *   parent_load: callable|string,
     *   parent_fail_msg?: string,
     *   parent_fail_url?: string,
     *   return_url: callable,
     *   form_init: callable,
     *   form_apply_parent: callable,
     *   form_load: callable,
     *   breadcrumbs: callable,
     *   work_file?: string,
     * } $opts
     * @return array{
     *   parent: array<string,mixed>,
     *   breadcrumbs: list<array<string,string>>,
     *   csrf_token: string,
     *   layout_page_title: string,
     * }
     */
    function manage_child_form_update_prepare(array $opts): array
    {
        $detailConfig = (array)($opts['config'] ?? []);
        $parent = manage_child_parent_guard($opts);

        $__csrf_key = (string)($opts['csrf_key'] ?? $detailConfig['csrf'] ?? '');
        $csrf_token = crud_csrf_ensure_page($__csrf_key);

        $returnUrl = (string)manage_child_call($opts['return_url'], $parent);
        $editPKey = manage_request_pkey();
        if ($editPKey <= 0) {
            manage_alert_script('參數錯誤：PKey 無效', $returnUrl);
            exit;
        }

        manage_child_call($opts['form_init']);
        manage_child_call($opts['form_apply_parent'], $parent);

        $loaded = (bool)manage_child_call($opts['form_load'], $editPKey, $parent);
        if (!$loaded) {
            manage_alert_script('查無要修改資料!', $returnUrl);
            exit;
        }

        $GLOBALS['WorkFile'] = (string)($opts['work_file'] ?? 'update.php');

        $breadcrumbs = (array)manage_child_call($opts['breadcrumbs'], $parent);
        $layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);

        return [
            'parent'            => $parent,
            'breadcrumbs'       => $breadcrumbs,
            'csrf_token'        => $csrf_token,
            'layout_page_title' => $layout_page_title,
        ];
    }
}

if (!function_exists('manage_child_addin_run')) {
    /**
     * @param array{
     *   config?: array,
     *   csrf_key?: string,
     *   parent_resolve: callable|string,
     *   parent_load: callable|string,
     *   parent_fail_msg?: string,
     *   parent_fail_url?: string,
     *   return_url: callable,
     *   validate: callable,
     *   verify_edit_row?: callable,
     *   save: callable,
     * } $opts
     */
    function manage_child_addin_run(array $opts): void
    {
        $detailConfig = (array)($opts['config'] ?? []);
        $csrfKey = (string)($opts['csrf_key'] ?? $detailConfig['csrf'] ?? '');
        crud_csrf_verify_form($csrfKey);

        global $filter_array;

        $WorkFile = (string)($_SERVER['PHP_SELF'] ?? 'addin.php');
        $Login_ID = (string)($_SESSION['Login_ID'] ?? '');
        $GLOBALS['WorkFile'] = $WorkFile;
        $GLOBALS['Login_ID'] = $Login_ID;

        $parent = manage_child_parent_guard(array_merge($opts, ['fail_mode' => 'form_error']));

        $formPKey = safe_int($filter_array['PKey'] ?? 0);
        $returnUrl = (string)manage_child_call($opts['return_url'], $parent);

        $MSG = (string)manage_child_call($opts['validate'], $parent, $filter_array);
        if ($MSG !== '') {
            crud_form_error_redirect($MSG, $returnUrl);
        }

        if ($formPKey > 0 && isset($opts['verify_edit_row']) && is_callable($opts['verify_edit_row'])) {
            if (!(bool)$opts['verify_edit_row']($formPKey, $parent)) {
                crud_form_error_redirect('查無要修改資料', $returnUrl);
            }
        }

        try {
            $result = (array)manage_child_call($opts['save'], $parent, $formPKey, $filter_array, $Login_ID);
            manage_alert_script((string)($result['action'] ?? '儲存成功!'), $returnUrl);
            exit;
        } catch (Throwable $e) {
            if (function_exists('sql_error')) {
                sql_error(
                    '',
                    $e->getMessage(),
                    $WorkFile,
                    $Login_ID !== '' ? $Login_ID : 'system',
                    $e->getFile(),
                    $e->getLine()
                );
            }
            crud_form_error_redirect('資料寫入失敗', $returnUrl);
        }
    }
}
