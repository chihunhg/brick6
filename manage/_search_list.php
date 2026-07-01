<?php
// 目標頁（僅允許相對路徑）
$list = (string)($WorkFile ?? 'list.php');
if ($list === '' || preg_match('#^(?:[a-z]+:)?//#i', $list)) {
    $list = 'list.php';
}

// 基本參數
$params = ['Send' => '搜尋'];

// 小工具：加入數字/字串參數（含 SqlFilter）
$addNum = function(string $outKey, string $inKey) use (&$params, $filter_array) {
    if (!empty($filter_array[$inKey]) && is_numeric($filter_array[$inKey])) {
        $val = function_exists('SqlFilter') ? SqlFilter($filter_array[$inKey], 'int') : (int)$filter_array[$inKey];
        $params[$outKey] = (string)$val;
    }
};
$addStr = function(string $outKey, string $inKey) use (&$params, $filter_array) {
    if (!empty($filter_array[$inKey])) {
        $val = (string)$filter_array[$inKey];
        $val = function_exists('SqlFilter') ? SqlFilter($val, 'str') : $val;
        $params[$outKey] = $val;
    }
};

// 填入參數
$addNum('Page', 'Page');
$addNum('manNo', 'manNo');
$addNum('subNo', 'subNo');

// Class1..Class4 對應 Q_Class1..Q_Class4
for ($i = 1; $i <= 4; $i++) {
    $addNum('Class' . $i, 'Class' . $i);
}

$addNum('Album_PKey',   'Album_PKey');
$addNum('Product_PKey', 'Product_PKey');
$addNum('Brand',        'Brand');
$addNum('Serial',       'Serial');

$addStr('Keywords', 'Keywords');
$addStr('OpenDate', 'OpenDate');
$addStr('EndDate',  'EndDate');
$addStr('Upload',   'Upload');

// 組回網址（RFC3986）
$query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
$url   = $list . ($query ? ('?' . $query) : '');

// 清除 Session
unset($_SESSION['PKey_' . ($ModuleNo ?? '')]);

// 安全輸出 JS（用 json_encode 避免跳脫問題）
$showMsg = (string)($GLOBALS['show'] ?? $show ?? '完成');
if (function_exists('manage_inline_script')) {
    echo manage_inline_script(
        'alert(' . json_encode($showMsg, JSON_UNESCAPED_UNICODE) . ');'
        . 'location.href=' . json_encode($url, JSON_UNESCAPED_UNICODE) . ';'
    );
} else {
    echo '<script>',
         'alert(', json_encode($showMsg, JSON_UNESCAPED_UNICODE), ');',
         'location.href=', json_encode($url, JSON_UNESCAPED_UNICODE), ';',
         '</script>';
}
