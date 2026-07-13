<?php
declare(strict_types=1);
/**
 * 訂單／購物相關函式（後台 order 模組使用）
 */

if (!function_exists('cart_label_maps')) {
    /**
     * 訂單狀態／付款／發票對照表
     *
     * @return array{
     *   flow: array<int,string>,
     *   pay: array<int,string>,
     *   invoice: array<int,string>
     * }
     */
    function cart_label_maps(): array
    {
        static $maps = null;
        if ($maps === null) {
            $maps = [
                'flow' => [
                    0 => '交易失敗',
                    1 => '等待付款',
                    2 => '處理中',
                    3 => '已出貨',
                    4 => '訂單取消',
                ],
                'pay' => [
                    1 => '線上刷卡',
                    2 => 'ATM轉帳',
                    3 => '超商條碼',
                    4 => '宅配貨到付款',
                ],
                'invoice' => [
                    1 => '未開立',
                    2 => '開立中',
                    3 => '已開立',
                    4 => '已作廢',
                ],
            ];
        }

        return $maps;
    }
}

if (!function_exists('cart_label')) {
    /** 依對照表取標籤文字 */
    function cart_label(string $mapKey, int|string $num, string $default = ''): string
    {
        $maps = cart_label_maps();
        $map = $maps[$mapKey] ?? [];
        $code = (int)$num;

        return $map[$code] ?? $default;
    }
}

if (!function_exists('FlowState')) {
    /** 回傳處理進度名稱 */
    function FlowState(int|string $num): string
    {
        return cart_label('flow', $num, '等待付款');
    }
}

if (!function_exists('PayType')) {
    /** 取得付款方式 */
    function PayType(int|string $num): string
    {
        return cart_label('pay', $num, '');
    }
}

if (!function_exists('Invoice_Type')) {
    /** 發票開立狀態 */
    function Invoice_Type(int|string $num): string
    {
        return cart_label('invoice', $num, '未開立');
    }
}

if (!function_exists('cart_sql_log')) {
    /** 組出寫入 order_h 用的 SQL 記錄字串 */
    function cart_sql_log(dbPDO $pdo, array $data, string $extra = ''): string
    {
        $payload = function_exists('array_to_string')
            ? array_to_string($data)
            : (string)json_encode($data, JSON_UNESCAPED_UNICODE);

        return $pdo->getLastSql() . "\n" . $payload . $extra;
    }
}

if (!function_exists('order_history')) {
    /** 寫入訂單異動記錄（order_h） */
    function order_history(
        int $orderPKey,
        string $orderNo,
        string $sqlCommand,
        string $strLink,
        string $userId
    ): void {
        if ($orderPKey <= 0 || !function_exists('chkTable') || !chkTable('order_h')) {
            return;
        }

        $ip = function_exists('UserIP') ? UserIP() : '';
        $data = [
            'Order_PKey' => SqlFilter($orderPKey, 'int'),
            'OrderNo'    => SqlFilter($orderNo, 'tab'),
            'strLink'    => SqlFilter($strLink, 'tab'),
            'SqlCommand' => SqlFilter($sqlCommand, 'tab'),
            'UserIP'     => SqlFilter($ip, 'tab'),
            'UserID'     => SqlFilter($userId, 'tab'),
            'dtDate'     => date('Y-m-d H:i:s'),
        ];
        if (function_exists('crud_filter_row_for_table')) {
            $data = crud_filter_row_for_table('order_h', $data);
        }

        $pdo = new dbPDO();
        $pdo->insert('order_h', $data);
        $err = $pdo->getErrorMessage();
        $sqlLog = cart_sql_log($pdo, $data);
        $pdo->close();

        if ($err !== '' && function_exists('sql_error')) {
            sql_error($sqlLog, $err, (string)($GLOBALS['WorkFile'] ?? 'cart.php'), $userId !== '' ? $userId : 'system');
        }
    }
}

if (!function_exists('add_invoice')) {
    /** 付款成功後開立發票（更新 order_p、寫入 invoice） */
    function add_invoice(int $orderPKey): void
    {
        if ($orderPKey <= 0) {
            return;
        }

        $row = crud_fetch_one(
            'SELECT PKey, OrderNo FROM order_p WHERE PKey = :pk LIMIT 1',
            ['pk' => $orderPKey]
        );
        if ($row === null) {
            return;
        }

        $pk = (int)($row['PKey'] ?? 0);
        $orderNo = (string)($row['OrderNo'] ?? '');
        $workFile = (string)($GLOBALS['WorkFile'] ?? '');
        $loginId = (string)($_SESSION['Login_ID'] ?? 'system');
        if ($pk <= 0) {
            return;
        }

        $data = [
            'intInvoice' => SqlFilter(2, 'int'),
        ];
        if (function_exists('crud_filter_row_for_table')) {
            $data = crud_filter_row_for_table('order_p', $data);
        }

        $pdo = new dbPDO();
        $pdo->update('order_p', $data, 'PKey', $pk);
        $err = $pdo->getErrorMessage();
        $sqlU = cart_sql_log($pdo, $data, 'PKey=' . $pk);
        $pdo->close();

        if ($err !== '') {
            if (function_exists('sql_error')) {
                sql_error($sqlU, $err, $workFile !== '' ? $workFile : 'cart.php', $loginId);
            }
            return;
        }
        order_history($pk, $orderNo, $sqlU, $workFile, $loginId);

        if (!function_exists('chkTable') || !chkTable('invoice')) {
            return;
        }

        $ins = [
            'Order_PKey' => SqlFilter($pk, 'int'),
            'OrderNo'    => SqlFilter($orderNo, 'tab'),
            'dtDate'     => date('Y-m-d H:i:s'),
        ];
        if (function_exists('crud_filter_row_for_table')) {
            $ins = crud_filter_row_for_table('invoice', $ins);
        }

        $pdo = new dbPDO();
        $pdo->insert('invoice', $ins);
        $err2 = $pdo->getErrorMessage();
        $sqlU2 = cart_sql_log($pdo, $ins);
        $pdo->close();

        if ($err2 !== '') {
            if (function_exists('sql_error')) {
                sql_error($sqlU2, $err2, $workFile !== '' ? $workFile : 'cart.php', $loginId);
            }
            return;
        }
        order_history($pk, $orderNo, $sqlU2, $workFile, $loginId);
    }
}

if (!function_exists('order_cvs')) {
    /**
     * 超商物流相關處理（參考版呼叫點保留；實作依站台需求擴充）
     */
    function order_cvs(int $orderPKey): void
    {
        if ($orderPKey <= 0) {
            return;
        }
        // 預留：狀態變更為已出貨時觸發超商物流流程
    }
}

if (!function_exists('_replaceChar')) {
    /** 綠界 CheckMacValue 特殊字元置換 */
    function _replaceChar(string $value): string
    {
        static $search = ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'];
        static $replace = ['-', '_', '.', '!', '*', '(', ')'];

        return str_replace($search, $replace, $value);
    }
}

if (!function_exists('_getMacValue')) {
    /**
     * 產生綠界 CheckMacValue（MD5）
     *
     * @param array<string, scalar|null> $formArray
     */
    function _getMacValue(string $hashKey, string $hashIv, array $formArray): string
    {
        unset($formArray['CheckMacValue']);
        ksort($formArray, SORT_STRING);

        $parts = ['HashKey=' . $hashKey];
        foreach ($formArray as $key => $value) {
            $parts[] = (string)$key . '=' . (string)$value;
        }
        $parts[] = 'HashIV=' . $hashIv;

        $encodeStr = strtolower(urlencode(implode('&', $parts)));
        $encodeStr = _replaceChar($encodeStr);

        return md5($encodeStr);
    }
}
