<?php
declare(strict_types=1);
/**
 * 訂單模組：列表篩選、載入、狀態更新
 */

require_once dirname(__DIR__, 2) . '/include/cart.php';

if (!function_exists('order_detail_tables')) {
    function order_detail_tables(): array
    {
        return manage_detail_tables();
    }
}

if (!function_exists('order_detail_defaults')) {
    /** @return array<string, mixed> */
    function order_detail_defaults(): array
    {
        return [
            'Update_PKey'    => 0,
            'Order_PKey'     => 0,
            'OrderNo'        => '',
            'strName'        => '',
            'EMail'          => '',
            'Tel'            => '',
            'Mobile'         => '',
            'PostCode'       => '',
            'strCounty'      => '',
            'strCity'        => '',
            'Address'        => '',
            'SendTime'       => '',
            'Invoice'        => '',
            'InvoiceNo'      => '',
            'Title'          => '',
            'Charge'         => 0,
            'Coupon_Price'   => 0,
            'Cute_Price'     => 0,
            'Use_Bonus'      => 0,
            'OrderBonus'     => 0,
            'TotalPrice'     => 0,
            'Flow'           => '',
            'intPay'         => 0,
            'intState'       => 1,
            'Memo'           => '',
            'CVSType'        => '',
            'CVSStoreID'     => '',
            'CVSStoreName'   => '',
            'CVSAddress'     => '',
            'CVSTelephone'   => '',
            'intCarrier'     => '',
            'CarrierType'    => '',
            'donate'         => '',
            'LoveCode'       => '',
            'intInvoice'     => 1,
            'InvoiceNumber'  => '',
            'invoiceDate'    => '',
            'RandomNum'      => '',
            'dtDate'         => '',
            'orderItems'     => [],
            'itemsSubTotal'  => 0,
        ];
    }
}

if (!function_exists('order_detail_export_vars')) {
    function order_detail_export_vars(): void
    {
        foreach ((array)($GLOBALS['order_form_vars'] ?? order_detail_defaults()) as $key => $val) {
            $GLOBALS[$key] = $val;
        }
        $GLOBALS['Update_PKey'] = (int)($GLOBALS['order_form_vars']['Update_PKey'] ?? 0);
    }
}

if (!function_exists('order_detail_init_defaults')) {
    function order_detail_init_defaults(): void
    {
        $GLOBALS['order_form_vars'] = order_detail_defaults();
        order_detail_export_vars();
    }
}

if (!function_exists('order_detail_apply_master')) {
    /** @param array<string, mixed> $row */
    function order_detail_apply_master(array $row): void
    {
        $v = &$GLOBALS['order_form_vars'];
        $fields = [
            'OrderNo', 'strName', 'EMail', 'Tel', 'Mobile', 'PostCode', 'strCounty', 'strCity',
            'Address', 'SendTime', 'Invoice', 'InvoiceNo', 'Title', 'Flow', 'Memo',
            'CVSType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone',
            'intCarrier', 'CarrierType', 'donate', 'LoveCode', 'InvoiceNumber', 'RandomNum',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $row)) {
                $v[$f] = (string)$row[$f];
            }
        }
        $intFields = [
            'Charge', 'Coupon_Price', 'Cute_Price', 'Use_Bonus', 'OrderBonus', 'TotalPrice',
            'intPay', 'intState', 'intInvoice',
        ];
        foreach ($intFields as $f) {
            if (array_key_exists($f, $row)) {
                $v[$f] = (int)$row[$f];
            }
        }
        $v['Order_PKey'] = (int)($row['PKey'] ?? 0);
        if (array_key_exists('strName', $row)) {
            $v['strName'] = (string)$row['strName'];
        }

        if (!empty($row['dtDate'])) {
            $v['dtDate'] = function_exists('Date_EN') ? (string)Date_EN($row['dtDate'], 1) : (string)$row['dtDate'];
        }
        if (!empty($row['CreateTime'])) {
            $v['invoiceDate'] = function_exists('Date_EN') ? (string)Date_EN($row['CreateTime'], 1) : (string)$row['CreateTime'];
        }

        order_detail_export_vars();
    }
}

if (!function_exists('order_detail_load_items')) {
    function order_detail_load_items(int $orderPKey): void
    {
        $items = crud_fetch_all(
            'SELECT * FROM order_d WHERE Order_PKey = :fk ORDER BY PKey ASC',
            ['fk' => $orderPKey]
        );
        $subTotal = 0;
        foreach ($items as &$item) {
            $qty = (int)($item['Quantity'] ?? 0);
            $price = (int)($item['Price'] ?? 0);
            $item['LineTotal'] = $qty * $price;
            $subTotal += $item['LineTotal'];
        }
        unset($item);
        $GLOBALS['order_form_vars']['orderItems'] = $items;
        $GLOBALS['order_form_vars']['itemsSubTotal'] = $subTotal;
        order_detail_export_vars();
    }
}

if (!function_exists('order_detail_resolve_module_pkey')) {
    function order_detail_resolve_module_pkey(): int
    {
        $mpk = (int)($GLOBALS['Module_PKey'] ?? 0);
        if ($mpk > 0) {
            return $mpk;
        }
        global $filter_array;
        return safe_int($_GET['manNo'] ?? $filter_array['manNo'] ?? 0);
    }
}

if (!function_exists('order_detail_load')) {
    function order_detail_load(int $pkey, ?int $modulePKey = null): bool
    {
        if ($pkey <= 0) {
            return false;
        }
        if ($modulePKey === null || $modulePKey <= 0) {
            $modulePKey = order_detail_resolve_module_pkey();
        }
        $tables = order_detail_tables();
        $master = (string)($tables['master'] ?? 'order_p');
        $moduleCol = (string)($tables['module_pk_col'] ?? 'Module_PKey');

        $row = crud_fetch_one("SELECT * FROM {$master} WHERE PKey = :pk LIMIT 1", ['pk' => $pkey]);
        if ($row === null) {
            return false;
        }
        if ($modulePKey > 0) {
            $rowModule = (int)($row[$moduleCol] ?? 0);
            $manNo = safe_int($_GET['manNo'] ?? ($GLOBALS['filter_array']['manNo'] ?? 0));
            if ($rowModule !== $modulePKey && !($manNo > 0 && $rowModule === $manNo)) {
                return false;
            }
        }
        if (!isset($GLOBALS['order_form_vars'])) {
            order_detail_init_defaults();
        }
        $GLOBALS['order_form_vars']['Update_PKey'] = $pkey;
        order_detail_apply_master($row);
        order_detail_load_items($pkey);
        return true;
    }
}

if (!function_exists('order_list_col')) {
    function order_list_col(string $column, ?string $tableAlias = null): string
    {
        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        if ($col === '') {
            return $column;
        }
        if ($tableAlias !== null && $tableAlias !== '') {
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $tableAlias);
            if ($alias !== '') {
                return $alias . '.' . $col;
            }
        }
        return $col;
    }
}

if (!function_exists('order_list_apply_keyword_search')) {
    function order_list_apply_keyword_search(
        string &$where,
        array &$params,
        array $filter,
        string $placeholder = '請輸入收件人或訂單編號搜尋',
        ?string $tableAlias = null
    ): string {
        $kw = trim(manage_list_search_filter_value($filter, 'Keywords'));
        $kw = mb_substr($kw, 0, 50);
        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        $hasKeyword = $kw !== '' && $kw !== $placeholder;

        if ((!$submitted && !$hasKeyword) || !$hasKeyword) {
            return $placeholder;
        }

        $params['Keyword1'] = $kw;
        $params['Keyword2'] = $kw;
        $nameCol = order_list_col('strName', $tableAlias);
        $noCol = order_list_col('OrderNo', $tableAlias);
        $where .= " AND (LOCATE(:Keyword1, {$nameCol}) > 0 OR LOCATE(:Keyword2, {$noCol}) > 0)";
        return $kw;
    }
}

if (!function_exists('order_list_apply_state_filter')) {
    function order_list_apply_state_filter(
        string &$where,
        array &$params,
        array $filter,
        ?string $tableAlias = null
    ): int {
        $submitted = (isset($filter['Submit']) && $filter['Submit'] === '搜尋')
            || (isset($filter['Send']) && $filter['Send'] === '搜尋');
        $intState = safe_int($filter['intState'] ?? 0);
        if ($submitted && $intState > 0) {
            $params['intState'] = $intState;
            $where .= ' AND ' . order_list_col('intState', $tableAlias) . ' = :intState';
        }
        return $intState;
    }
}

if (!function_exists('order_list_build_where')) {
    /** @return array{0:string,1:array<string,mixed>,2:array<string,mixed>} */
    function order_list_build_where(array $filter, ?string $tableAlias = null): array
    {
        [$where, $params] = crud_module_where($tableAlias);
        $kwPlaceholder = '請輸入收件人或訂單編號搜尋';
        $keywords = order_list_apply_keyword_search($where, $params, $filter, $kwPlaceholder, $tableAlias);
        if ($keywords === '') {
            $keywords = $kwPlaceholder;
        }
        $dateSearch = crud_list_apply_opendate_range(
            $where,
            $params,
            $filter,
            'dtDate',
            $tableAlias
        );
        $intState = order_list_apply_state_filter($where, $params, $filter, $tableAlias);
        return [$where, $params, [
            'Keywords'   => $keywords,
            'dateSearch' => $dateSearch,
            'intState'   => $intState,
        ]];
    }
}

if (!function_exists('order_process_state_update')) {
    /** 編輯頁：變更處理進度 */
    function order_process_state_update(array $filter, int $orderPKey, string $orderNo, int $oldState): bool
    {
        global $Login_ID, $WorkFile;

        $msg = '';
        $newState = safe_int($filter['intState'] ?? 0);
        if ($newState < 1) {
            $msg .= "【處理進度】請選擇\n";
        }
        if ($msg !== '') {
            if (function_exists('manage_alert_script')) {
                manage_alert_script('發生錯誤，請填寫下列欄位\n' . $msg, null, true);
            }
            exit;
        }

        $data = [
            'intState' => SqlFilter($newState, 'int'),
            'dtUDate'  => date('Y-m-d H:i:s'),
            'UserID'   => SqlFilter((string)($Login_ID ?? ''), 'tab'),
        ];
        $pdo = new dbPDO();
        $pdo->update('order_p', $data, 'PKey', $orderPKey);
        $sqlU = $pdo->getLastSql() . "\n"
            . (function_exists('array_to_string') ? array_to_string($data) : '')
            . 'PKey=' . $orderPKey;
        $err = $pdo->getErrorMessage();
        $pdo->close();

        if ($err !== '') {
            if (function_exists('sql_error')) {
                sql_error($sqlU, $err, (string)$WorkFile, 'system');
            }
            if (function_exists('manage_alert_script')) {
                manage_alert_script('資料更新失敗', null, true);
            }
            exit;
        }

        order_history($orderPKey, $orderNo, $sqlU, (string)$WorkFile, (string)($Login_ID ?? 'system'));

        if ($newState !== $oldState) {
            match ($newState) {
                2       => add_invoice($orderPKey),
                3       => order_cvs($orderPKey),
                default => null,
            };
        }

        $GLOBALS['show'] = '狀態已變更!';
        require_once dirname(__DIR__) . '/_return_list.php';
        exit;
    }
}

if (!function_exists('order_export_fetch_rows')) {
    /** @return list<array<string, mixed>> */
    function order_export_fetch_rows(array $filter): array
    {
        if (function_exists('chkTable') && chkTable('view_order_p')) {
            [$where, $params] = order_list_build_where($filter);
            return crud_fetch_all(
                'SELECT * FROM view_order_p ' . $where . ' ORDER BY PKey DESC',
                $params
            );
        }
        [$where, $params] = order_list_build_where($filter, 'p');
        return crud_fetch_all(
            'SELECT p.*, d.strNo, d.ProductNo, d.strName AS ProductName, d.Brand, d.ColorName,'
            . ' d.Price, d.Quantity, d.Barcode'
            . ' FROM order_p p'
            . ' LEFT JOIN order_d d ON d.Order_PKey = p.PKey'
            . ' ' . $where
            . ' ORDER BY p.PKey DESC, d.PKey ASC',
            $params
        );
    }
}
