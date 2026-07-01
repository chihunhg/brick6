<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$secPath = dirname(__DIR__) . '/include/sec.php';
if (is_file($secPath)) {
    require_once $secPath;
}
$helpersPath = dirname(__DIR__) . '/include/crud_helpers.php';
if (is_file($helpersPath)) {
    require_once $helpersPath;
}

// 1) 初始化與輸入來源（避免 Undefined variable）
$filter_array = array_merge($_GET ?? [], $_POST ?? []);

// 2) 列表路徑（例 product/list.php）；不可只用 basename，否則會導到 manage/list.php
$listRaw = (string)($filter_array['list'] ?? 'list.php');
$listModule = (string)($filter_array['list_module'] ?? '');
if (function_exists('manage_return_list_resolve_path')) {
    $list = manage_return_list_resolve_path($listRaw, $listModule);
} elseif (function_exists('manage_return_list_sanitize')) {
    $list = manage_return_list_sanitize($listRaw, $listModule);
} else {
    $list = basename(str_replace('\\', '/', $listRaw));
    if (!preg_match('/^[A-Za-z0-9_-]+\.php$/', $list)) {
        $list = 'list.php';
    }
}

// 3) 起始參數
$params = ['Send' => '搜尋'];

// 4) 小工具：加數字 / 加字串
$addNum = function(string $outKey, string $inKey) use (&$params, $filter_array) {
    if (isset($filter_array[$inKey]) && $filter_array[$inKey] !== '' && is_numeric($filter_array[$inKey])) {
        // 若有 SqlFilter() 就用；否則強制轉 int
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

// 5) Page / PageSize
$addNum('Page', 'Page');
$addNum('PageSize', 'PageSize');

// 6) Class1..Class4 對應 Q_Class1..Q_Class4
for ($i = 1; $i <= 4; $i++) {
    $addNum('Class' . $i, 'Q_Class' . $i);
}

// 7) 其他數字欄位
$addNum('intLocal',     'Q_intLocal');
$addNum('Brand',        'Q_Brand');
$addNum('Serial',       'Q_Serial');
$addNum('manNo',        'manNo');
$addNum('subNo',        'subNo');
$addNum('Album_PKey',   'Album_PKey');
$addNum('Product_PKey', 'Product_PKey');
$addNum('Class1_PKey',  'Class1_PKey');
$addNum('Class2_PKey',  'Class2_PKey');
$addNum('Regarding',    'Regarding');
$addNum('Coupon_PKey',  'Coupon_PKey');

// 8) 字串欄位
$addStr('Keywords', 'Q_Keywords');
$addStr('OpenDate', 'Q_OpenDate');
$addStr('EndDate',  'Q_EndDate');
$addStr('Upload',   'Q_Upload');

// 9) 組回網址（RFC3986 編碼）
$query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
$url   = $list . ($query !== '' ? ('?' . $query) : '');

// 10) 清除 Session 變數（放在輸出前處理）
if (isset($ModuleNo)) {
    unset($_SESSION['PKey_' . $ModuleNo]);
}

// 11) 導回列表（儲存成功有訊息；關閉按鈕無 $actionShow 則直接導向）
$show = isset($actionShow) ? trim((string)$actionShow) : '';
if ($show === '') {
    header('Location: ' . $url);
    exit;
}
if (function_exists('manage_alert_script')) {
    manage_alert_script($show, $url);
}
$js = 'alert(' . json_encode($show, JSON_UNESCAPED_UNICODE) . ');'
    . 'location.href=' . json_encode($url, JSON_UNESCAPED_UNICODE) . ';';
if (function_exists('manage_inline_script')) {
    echo manage_inline_script($js);
} else {
    echo '<script>', $js, '</script>';
}
exit;
