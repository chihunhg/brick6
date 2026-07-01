<?php
require('../_inc.php');
require_once dirname(__DIR__, 2) . '/include/json_response.php';

// -------- 讀取/驗證參數 --------
// $filter_array 由你的框架前置處理（_inc.php）產生；若不存在則後援 $_REQUEST
$fa = isset($filter_array) && is_array($filter_array) ? $filter_array : $_REQUEST;

$PKey         = isset($fa['PKey'])         && is_numeric($fa['PKey'])         ? (int)$fa['PKey']         : 0;
$Module_PKey  = isset($fa['Module_PKey'])  && is_numeric($fa['Module_PKey'])  ? (int)$fa['Module_PKey']  : 0;
$RType        = isset($fa['RType'])        && is_string($fa['RType'])         ? trim($fa['RType'])       : '';

// 僅允許兩種型別
if ($RType === '') {
    json_out(['success' => true, 'data' => []]); // 空類型 → 回空集合
}
if (!in_array($RType, ['Class2','Class3'], true)) {
    json_out(['success' => false, 'error' => 'Invalid RType'], 400);
}

// module 必填
if ($Module_PKey <= 0) {
    json_out(['success' => false, 'error' => 'Module_PKey is required'], 400);
}

// 僅主檔 dbclass2 / dbclass3（與 crud_fetch_class_options 相同，不用 *_lang）
$rows = [];
try {
    if ($RType === 'Class2') {
        if ($PKey <= 0) {
            json_out(['success' => false, 'error' => 'Class1_PKey is required'], 400);
        }
        $level      = 2;
        $parentPKey = $PKey;
    } else {
        if ($PKey <= 0) {
            json_out(['success' => false, 'error' => 'Class2_PKey is required'], 400);
        }
        $level      = 3;
        $parentPKey = $PKey;
    }

    if (!function_exists('crud_fetch_class_options')) {
        json_out(['success' => false, 'error' => 'crud_fetch_class_options unavailable'], 500);
    }

    $options = crud_fetch_class_options($level, $Module_PKey, $parentPKey);
    foreach ($options as $opt) {
        $rows[] = [
            'ID'   => (int)($opt['PKey'] ?? 0),
            'Name' => (string)($opt['strName'] ?? ''),
        ];
    }

    json_out(['success' => true, 'data' => $rows]);

} catch (Throwable $e) {
    // 後端記錄詳細錯誤
    error_log('[ajax.class] ' . $e->getMessage());
    json_out(['success' => false, 'error' => 'Internal server error'], 500);
}
