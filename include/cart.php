<?php
declare(strict_types=1);
/**
 * 訂單／購物相關函式（後台 order 模組使用）
 */

if (!function_exists('FlowState')) {
    /** 回傳處理進度名稱 */
    function FlowState(int|string $num): string
    {
        return match ((int)$num) {
            1       => '已報名，未繳費',
            2       => '已報名，已繳費',
            3       => '取消報名',
            4       => '報名失敗',
            default => '已報名，未繳費',
        };
    }
}

if (!function_exists('PayType')) {
    /** 取得付款方式 */
    function PayType(int|string $num): string
    {
        return match ((int)$num) {
            1       => '線上刷卡',
            2       => 'ATM轉帳',
            3       => '超商條碼',
            4       => '宅配貨到付款',
            default => '',
        };
    }
}

if (!function_exists('Invoice_Type')) {
    /** 發票開立狀態 */
    function Invoice_Type(int|string $num): string
    {
        return match ((int)$num) {
            1       => '未開立',
            2       => '開立中',
            3       => '已開立',
            4       => '已作廢',
            default => '未開立',
        };
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
        if (!function_exists('chkTable') || !chkTable('order_h')) {
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
        $pdo = new dbPDO();
        $pdo->insert('order_h', $data);
        $pdo->close();
    }
}

if (!function_exists('add_invoice')) {
    /** 付款成功後開立發票（更新 order_p、寫入 invoice） */
    function add_invoice(int $orderPKey): void
    {
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

        $pdo = new dbPDO();
        $data = ['intInvoice' => 2];
        $pdo->update('order_p', $data, 'PKey', $pk);
        $sqlU = $pdo->getLastSql() . "\n" . (function_exists('array_to_string') ? array_to_string($data) : '') . 'PKey=' . $pk;
        $pdo->close();
        order_history($pk, $orderNo, $sqlU, $workFile, $loginId);

        if (!function_exists('chkTable') || !chkTable('invoice')) {
            return;
        }
        $pdo = new dbPDO();
        $ins = [
            'Order_PKey' => $pk,
            'OrderNo'    => $orderNo,
            'dtDate'     => date('Y-m-d H:i:s'),
        ];
        $pdo->insert('invoice', $ins);
        $sqlU2 = $pdo->getLastSql() . "\n" . (function_exists('array_to_string') ? array_to_string($ins) : '');
        $pdo->close();
        order_history($pk, $orderNo, $sqlU2, $workFile, $loginId);
    }
}

if (!function_exists('order_cvs')) {
    /**
     * 超商物流相關處理（參考版呼叫點保留；實作依站台需求擴充）
     */
    function order_cvs(int $orderPKey): void
    {
        // 預留：狀態變更時觸發超商物流流程
    }
}

if (!function_exists('_replaceChar')) {
    /** 綠界 CheckMacValue 特殊字元置換 */
    function _replaceChar(string $value): string
    {
        $search = ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'];
        $replace = ['-', '_', '.', '!', '*', '(', ')'];
        return str_replace($search, $replace, $value);
    }
}

if (!function_exists('_getMacValue')) {
    /** 產生綠界 CheckMacValue */
    function _getMacValue(string $hashKey, string $hashIv, array $formArray): string
    {
        $encodeStr = 'HashKey=' . $hashKey;
        foreach ($formArray as $key => $value) {
            $encodeStr .= '&' . $key . '=' . $value;
        }
        $encodeStr .= '&HashIV=' . $hashIv;
        $encodeStr = strtolower(urlencode($encodeStr));
        $encodeStr = _replaceChar($encodeStr);
        return md5($encodeStr);
    }
}
