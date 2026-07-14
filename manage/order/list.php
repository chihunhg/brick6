<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/_form_data.php';

$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'order_list');
$table_name  = (string)($detailConfig['master'] ?? 'order_p');
$PKName      = 'PKey';
$FKName      = (string)($detailConfig['fk'] ?? 'Order_PKey');

unset($_SESSION['PKey_' . ($ModuleNo ?? '')]);

$crud_cfg = crud_cfg($table_name, $FKName, [
    'lang_table' => 'order_d',
]);
crud_process_list_actions($crud_cfg);

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

[$PDO_Cond, $Cond_Array, $searchMeta] = order_list_build_where($filter_array ?? []);
$Keywords = (string)($searchMeta['Keywords'] ?? '請輸入收件人或訂單編號搜尋');
$openDateSearch = is_array($searchMeta['dateSearch'] ?? null) ? $searchMeta['dateSearch'] : [];
$intState = (int)($searchMeta['intState'] ?? 0);

$Total = crud_fetch_scalar(
    "SELECT COUNT({$PKName}) AS Total FROM {$table_name} {$PDO_Cond}",
    $Cond_Array,
    'Total'
);
$defaultPageSize = (int)($detailConfig['page_size'] ?? 30);
$tPageSize = crud_list_page_size($filter_array ?? [], $defaultPageSize);
['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = crud_paginate(
    $Total,
    $tPageSize,
    $filter_array['Page'] ?? null
);

$sql = "SELECT * FROM {$table_name} {$PDO_Cond} ORDER BY {$PKName} DESC LIMIT "
    . (int)$tPageSize . ' OFFSET ' . (int)$offset;
$listRows = crud_fetch_all($sql, $Cond_Array);

$i = 0;
$list_show_expand_row = false;
manage_list_expand_enabled($list_show_expand_row);
$listGridClass = manage_list_grid_class('order-list');

$clearUrl = ($WorkFile ?? 'list.php')
    . '?manNo=' . urlencode((string)($manNo ?? ''))
    . '&subNo=' . urlencode((string)($subNo ?? ''));

$layout_container_class = manage_list_layout_container_class($detailConfig);
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
                                        <label class="inputLabel" for="OpenDate">訂單期間（起）</label>
                                        <div class="inputWrapper">
                                            <input type="date" name="OpenDate" id="OpenDate"
                                                value="<?php echo e((string)($openDateSearch['OpenDate'] ?? '')); ?>"
                                                class="formInput editView__dateInput">
                                        </div>
                                    </div>
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="EndDate">訂單期間（迄）</label>
                                        <div class="inputWrapper">
                                            <input type="date" name="EndDate" id="EndDate"
                                                value="<?php echo e((string)($openDateSearch['EndDate'] ?? '')); ?>"
                                                class="formInput editView__dateInput"
                                                aria-describedby="listDateRangeError">
                                            <span id="listDateRangeError" class="input__errorTxt" role="alert"><?php
                                                echo e((string)($openDateSearch['error'] ?? ''));
                                            ?></span>
                                        </div>
                                    </div>
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="intState">處理進度</label>
                                        <div class="inputWrapper">
                                            <select name="intState" id="intState" class="formSelect">
                                                <option value="">全部顯示</option>
                                                <?php for ($st = 1; $st <= 4; $st++) { ?>
                                                <option value="<?php echo $st; ?>"<?php
                                                    if ($intState === $st) {
                                                        echo ' selected="selected"';
                                                    }
                                                ?>><?php echo e(FlowState($st)); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="Keywords">智慧語意搜尋</label>
                                        <div class="inputWrapper">
                                            <input type="text" name="Keywords" id="Keywords"
                                                value="<?php echo e($Keywords); ?>"
                                                placeholder="<?php echo e('請輸入收件人或訂單編號搜尋'); ?>"
                                                class="formInput"
                                                data-manage-action="list-search"
                                                data-form-id="form1"
                                                data-work-file="<?php echo e($WorkFile ?? ''); ?>"
                                                data-default-keywords="<?php echo e('請輸入收件人或訂單編號搜尋'); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="filterWrap__actions">
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
                            <button type="button" class="btnStyle btnStyle--outline btnStyle--sm"
                                data-manage-action="order-export" data-form-id="form1">
                                <i class="bi bi-file-earmark-excel"></i> 匯出訂單
                            </button>

                            <?php require_once '_list.php'; ?>

                            <?php
                            echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;
                            echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('Total', $i) . PHP_EOL;
                            echo hiddenNumeric('PKey', $PKey ?? '') . PHP_EOL;
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
                            <li>列表依訂單編號（由大至小）排序。</li>
                            <li>匯出訂單依目前搜尋條件產生 Excel。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
                    </form>

<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
