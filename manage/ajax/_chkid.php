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

// -------- 查詢是否已存在（EMail 為密文，走 member_email_exists 解密比對）--------
require_once dirname(__DIR__) . '/member/_form_data.php';
try {
    $exists = member_email_exists($EMail, 0);
} catch (Throwable $e) {
    if (function_exists('sql_error')) {
        sql_error('member_email_exists', $e->getMessage(), $WorkFile ?? __FILE__, 'system');
    } else {
        error_log('[email_check] ' . $e->getMessage());
    }
    json_out(['success' => false, 'error' => '系統忙碌，請稍後再試'], 500);
}

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
