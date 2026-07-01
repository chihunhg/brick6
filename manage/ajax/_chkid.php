<?php
require_once("../_inc.php");
require_once dirname(__DIR__, 2) . '/include/json_response.php';

// -------- 讀取/驗證參數 --------
$fa = isset($filter_array) && is_array($filter_array) ? $filter_array : $_REQUEST;

$EMail = isset($fa['EMail']) && is_string($fa['EMail']) ? trim($fa['EMail']) : '';
$RType = isset($fa['RType']) && is_string($fa['RType']) ? strtolower(trim($fa['RType'])) : 'register';

if ($EMail === '') {
    json_out(['success' => false, 'error' => '請輸入【會員帳號】(Email)'], 400);
}
if (!function_exists('CheckMail') || !CheckMail($EMail)) {
    json_out(['success' => false, 'error' => '【會員帳號】格式錯誤'], 400);
}

// -------- 查詢是否已存在 --------
$sql = 'SELECT 1 AS x FROM member WHERE EMail = :EMail LIMIT 1';
$rs  = new recordset($sql, ['EMail' => $EMail]);

// 錯誤處理（不把細節回給前端）
if (method_exists($rs, 'getErrorMessage')) {
    $err = (string)$rs->getErrorMessage();
    if ($err !== '') {
        // 後端記錄詳細錯誤
        if (function_exists('sql_error')) {
            sql_error($sql . PHP_EOL . 'EMail=' . $EMail, $err, $WorkFile ?? __FILE__, 'system');
        } else {
            error_log('[email_check] ' . $err);
        }
        json_out(['success' => false, 'error' => '系統忙碌，請稍後再試'], 500);
    }
}

$exists = !$rs->eof;
if (method_exists($rs, 'close')) $rs->close();

// -------- 依情境回覆 --------
// register：明確回覆是否重複（註冊表單常見需求）
if ($RType === 'register' || $RType === '') {
    if ($exists) {
        json_out(['success' => false, 'error' => '【會員帳號】已被使用']);
    }
    json_out(['success' => true, 'data' => ['available' => true]]);
}

// reset/forgot：避免帳號枚舉（不透露是否存在）
if ($RType === 'reset' || $RType === 'forgot') {
    json_out(['success' => true, 'data' => ['status' => 'ok']]);
}

// 其它未定義類型：預設採保守策略（不枚舉）
json_out(['success' => true, 'data' => ['status' => 'ok']]);
?>
