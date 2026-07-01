<?php 
require_once('_inc.php');

$base = !empty($filter_array['list']) ? (string)$filter_array['list'] : 'list.php';

$backUrl = buildBackUrl($base, $filter_array, [
  'sendText'   => '搜尋',
  'classCount' => 4, // 自動把 Q_Class1..4 映射成 Class1..4
  'numeric' => [
    'Page'         => 'Page',
    'PageSize'         => 'PageSize',
    'intLocal'     => 'Q_intLocal',
    'Brand'        => 'Q_Brand',
    'Serial'       => 'Q_Serial',
    'manNo'        => 'manNo',
    'subNo'        => 'subNo',
    'Album_PKey'   => 'Album_PKey',
    'Product_PKey' => 'Product_PKey',
    'Regarding'    => 'Regarding',
  ],
  'strings' => [
    'Keywords' => 'Q_Keywords',
    'OpenDate' => 'Q_OpenDate',
    'EndDate'  => 'Q_EndDate',
    'Upload'   => 'Q_Upload',
  ],
]);

// 清除 Session 變數（若需要）
// unset($_SESSION['PKey_' . ($ModuleNo ?? '')]);

// 導回（若要 alert 請自行加上）
if (function_exists('manage_inline_script')) {
    echo manage_inline_script('location.href=' . json_encode($backUrl, JSON_UNESCAPED_SLASHES) . ';');
} else {
    echo '<script>location.href=', json_encode($backUrl, JSON_UNESCAPED_SLASHES), ';</script>';
}
