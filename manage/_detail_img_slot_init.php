<?php
declare(strict_types=1);
/**
 * 自 _config.php 初始化圖片／檔案槽變數（對應 product/_detail.php）
 *
 * 引入前可選設定：
 *   $__imgSlotFallback = 7;
 *   $__imgSlotImageOnly = false;
 *   $__imgSlotShowListField = null;
 */
$__imgSlotFallback = (int)($__imgSlotFallback ?? 7);
$__imgSlotImageOnly = (bool)($__imgSlotImageOnly ?? false);
$detailConfig = is_array($detailConfig ?? null) ? $detailConfig : [];
if ($detailConfig === [] && is_file(__DIR__ . '/_config.php')) {
    $detailConfig = require __DIR__ . '/_config.php';
}
foreach (manage_detail_init_img_slot_view(
    $detailConfig,
    $__imgSlotFallback,
    $__imgSlotImageOnly,
    $__imgSlotShowListField ?? null
) as $__k => $__v) {
    $$__k = $__v;
}
unset($__k, $__v);
