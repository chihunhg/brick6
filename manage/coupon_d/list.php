<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once __DIR__ . '/_form_data.php';



$filter = is_array($filter_array ?? null) ? $filter_array : [];

$couponPKey = coupon_d_resolve_coupon_pkey($filter);

$parent = coupon_d_load_parent($couponPKey);

if ($parent === null) {

    manage_alert_script(

        '查無優惠券資料',

        coupon_d_parent_list_url($detailConfig, $filter)

    );

    exit;

}



$strName = (string)($parent['strName'] ?? '');

$couponPKey = (int)($parent['PKey'] ?? $couponPKey);



$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'coupon_d_list');

$table_name = (string)($detailConfig['master'] ?? 'coupon_d');

$PKName = 'PKey';

$listBackUrl = coupon_d_list_back_url($couponPKey, $filter);



$crud_cfg = crud_cfg($table_name, (string)($detailConfig['parent_fk'] ?? 'Coupon_PKey'));

crud_process_list_actions($crud_cfg, static function (array $ids): void {
    foreach ($ids as $id) {
        $row = crud_fetch_one(
            'SELECT OrderNo FROM coupon_d WHERE PKey = :pk LIMIT 1',
            ['pk' => (int)$id]
        );
        if (is_array($row) && trim((string)($row['OrderNo'] ?? '')) !== '') {
            manage_alert_script('已折抵訂單的明細不可刪除', null, true);
            exit;
        }
    }
}, $listBackUrl);



crud_csrf_guard_list($listCsrfKey);

$csrf_token = crud_csrf_ensure($listCsrfKey);



[$where, $condParams, $Keywords] = coupon_d_build_list_where($couponPKey, $filter);



$Total = coupon_d_count_list_rows($where, $condParams);

$defaultPageSize = (int)($detailConfig['page_size'] ?? 30);

$tPageSize = crud_list_page_size($filter, $defaultPageSize);

['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = crud_paginate(

    $Total,

    $tPageSize,

    $filter['Page'] ?? null

);



$listRows = coupon_d_fetch_list_rows($where, $condParams, $tPageSize, $offset);



$i = 0;

$list_show_expand_row = false;

manage_list_expand_enabled($list_show_expand_row);

$listGridClass = manage_list_grid_class('coupon-d-list');



$parentListUrl = coupon_d_parent_list_url($detailConfig, $filter);

$loadUrl = 'load.php?Coupon_PKey=' . $couponPKey

    . '&manNo=' . urlencode((string)($manNo ?? ''))

    . '&subNo=' . urlencode((string)($subNo ?? ''));

$clearUrl = coupon_d_list_back_url($couponPKey, $filter);



$breadcrumbs = [

    ['label' => (string)($Module_Name ?? '折價券明細')],

    ['label' => '入會折價券', 'href' => $parentListUrl],

    ['label' => $strName . '－清單'],

];

$layout_page_title = $strName . '－清單';

$layout_container_class = manage_list_layout_container_class($detailConfig);

$showListAdd = false;

$showListSort = false;
$showListUpload = false;

?>

<?php require_once '../_layout_head.php'; ?>

</head>



<?php require_once '../_layout_body_open.php'; ?>

                    <?php require_once '../_breadcrumbs.php'; ?>



                    <form action="" method="post" name="form1" id="form1">

                    <div id="view-list">

                        <div class="card filterWrap">

                            <div class="filterWrap__content">

                                <div class="filterWrap__grid">

                                    <div class="inputGroup">

                                        <label class="inputLabel" for="Keywords">關鍵字</label>

                                        <div class="inputWrapper">

                                            <input type="text" name="Keywords" id="Keywords"

                                                value="<?php echo e($Keywords); ?>"

                                                placeholder="<?php echo e('請輸入姓名或訂單編號搜尋'); ?>"

                                                class="formInput"

                                                data-manage-action="list-search"

                                                data-form-id="form1"

                                                data-work-file="<?php echo e($WorkFile ?? ''); ?>"

                                                data-default-keywords="<?php echo e('請輸入姓名或訂單編號搜尋'); ?>">

                                        </div>

                                    </div>

                                </div>

                                <div class="filterWrap__actions">

                                    <a href="<?php echo e($parentListUrl); ?>" class="btnStyle btnStyle--outline">

                                        <i class="bi bi-arrow-left"></i> 回優惠券列表

                                    </a>

                                    <a href="<?php echo e($clearUrl); ?>" class="btnStyle btnStyle--outline">

                                        <i class="bi bi-arrow-counterclockwise"></i> 清除

                                    </a>

                                    <button type="submit" class="btnStyle --isAnim" name="Submit" value="搜尋">

                                        <i class="bi bi-search"></i> 搜尋

                                    </button>

                                </div>

                            </div>

                        </div>



                        <div class="card">

                            <?php require_once '../_select.php'; ?>



                            <div class="flex gap--2" style="margin-bottom:1rem;">

                                <button type="button" class="btnStyle btnStyle--outline btnStyle--sm"

                                    data-manage-action="coupon-d-export"

                                    data-coupon-pkey="<?php echo $couponPKey; ?>">

                                    <i class="bi bi-file-earmark-excel"></i> 匯出名單

                                </button>

                                <button type="button" class="btnStyle btnStyle--sub btnStyle--sm"

                                    data-manage-action="manage-update"

                                    data-page="load.php"

                                    data-pkey="<?php echo $couponPKey; ?>">

                                    <i class="bi bi-upload"></i> 匯入

                                </button>

                            </div>



                            <?php require_once '_list.php'; ?>



                            <?php

                            echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;

                            echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;

                            echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;

                            echo hiddenNumeric('Total', $i) . PHP_EOL;

                            echo hiddenNumeric('PKey', $couponPKey) . PHP_EOL;

                            echo hiddenNumeric('Coupon_PKey', $couponPKey) . PHP_EOL;

                            echo hiddenNumeric('Page', $tPage) . PHP_EOL;

                            echo hiddenNumeric('PageSize', $tPageSize) . PHP_EOL;

                            ?>



                            <?php if (file_exists(__DIR__ . '/../_page.php')) {

                                require_once __DIR__ . '/../_page.php';

                            } ?>

                        </div>

                    </div>



                    <div class="notes notes--lg">

                        <div class="notes__header">

                            <i class="bi bi-info-circle notes__icon"></i> 系統備註

                        </div>

                        <ul class="notes__list">

                            <li>列表依發送紀錄（由新至舊）排序。</li>

                            <li>已折抵訂單的明細不可刪除（勾選欄位已停用）。</li>

                            <li>匯入 Excel 第一欄請填寫會員 E-mail；手動匯入可用分號分隔多筆帳號。</li>

                        </ul>

                    </div>

                    <div class="notes__spacer"></div>

                    </form>



<?php require_once '../_layout_body_close.php'; ?>

<?php require_once '../_in_code_bottom.php'; ?>

</body>

</html>

