<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/_form_data.php';

$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'epaper_list');
$table_name  = (string)($detailConfig['master'] ?? 'epaper');
$PKName      = 'PKey';

$crud_cfg = crud_cfg($table_name, 'PKey');
crud_process_list_actions($crud_cfg);

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

[$PDO_Cond, $Cond_Array] = crud_module_where();

$kwPlaceholder = '請輸入 E-Mail 搜尋';
$Keywords = epaper_list_apply_keyword_search(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    $kwPlaceholder
);
if ($Keywords === '') {
    $Keywords = $kwPlaceholder;
}

$openDateSearch = crud_list_apply_opendate_range(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    'dtDate'
);

$Total = crud_fetch_scalar(
    "SELECT COUNT({$PKName}) AS Total FROM {$table_name} {$PDO_Cond}",
    $Cond_Array,
    'Total'
);
$defaultPageSize = (int)($detailConfig['page_size'] ?? 15);
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
$listGridClass = manage_list_grid_class('epaper-list');

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
                                        <label class="inputLabel" for="OpenDate">訂閱期間（起）</label>
                                        <div class="inputWrapper">
                                            <input type="date" name="OpenDate" id="OpenDate"
                                                value="<?php echo e((string)($openDateSearch['OpenDate'] ?? '')); ?>"
                                                class="formInput editView__dateInput">
                                        </div>
                                    </div>
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="EndDate">訂閱期間（迄）</label>
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
                                        <label class="inputLabel" for="Keywords">關鍵字</label>
                                        <div class="inputWrapper">
                                            <input type="text" name="Keywords" id="Keywords"
                                                value="<?php echo e($Keywords); ?>"
                                                placeholder="<?php echo e($kwPlaceholder); ?>"
                                                class="formInput"
                                                data-manage-action="list-search"
                                                data-form-id="form1"
                                                data-work-file="<?php echo e($WorkFile ?? ''); ?>"
                                                data-default-keywords="<?php echo e($kwPlaceholder); ?>">
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
                            <?php
                            $showListSort = false;
                            $showListUpload = false;
                            require_once '../_select.php';
                            ?>
                            <button type="button" class="btnStyle btnStyle--outline btnStyle--sm"
                                data-manage-action="epaper-export" data-form-id="form1">
                                <i class="bi bi-file-earmark-excel"></i> 匯出
                            </button>

                            <?php require_once '_list.php'; ?>

                            <?php
                            echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;
                            echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('Total', $i) . PHP_EOL;
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
                            <li>列表依建立日期（由新至舊）排序。</li>
                            <li>匯出 Excel 依目前搜尋條件產生名單。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
                    </form>

<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
