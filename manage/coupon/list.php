<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/_form_data.php';

$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'coupon_list');
$table_name  = (string)($detailConfig['master'] ?? 'coupon_p');
$PKName      = 'PKey';
$FKName      = (string)($detailConfig['fk'] ?? 'Coupon_PKey');

$crud_cfg = crud_cfg($table_name, $FKName, ['lang_table' => 'coupon_d']);
crud_process_list_actions($crud_cfg);

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

[$PDO_Cond, $Cond_Array] = crud_module_where();
$sortCol = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($detailConfig['sort_column'] ?? 'OpenDate'));
$sortDir = strtoupper((string)($detailConfig['sort_direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

$Total = crud_fetch_scalar(
    "SELECT COUNT({$PKName}) AS Total FROM {$table_name} {$PDO_Cond}",
    $Cond_Array,
    'Total'
);
$tPageSize = crud_list_page_size($filter_array ?? [], 15);
['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = crud_paginate(
    $Total,
    $tPageSize,
    $filter_array['Page'] ?? null
);

$sql = "SELECT * FROM {$table_name} {$PDO_Cond} ORDER BY {$sortCol} {$sortDir} LIMIT "
    . (int)$tPageSize . ' OFFSET ' . (int)$offset;
$listRows = crud_fetch_all($sql, $Cond_Array);

$i = 0;
$list_show_expand_row = false;
manage_list_expand_enabled($list_show_expand_row);
$listGridClass = manage_list_grid_class('coupon-list');

$layout_container_class = manage_list_layout_container_class($detailConfig);
?>
<?php require_once '../_layout_head.php'; ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <form action="" method="post" name="form1" id="form1">
                    <div id="view-list">
                        <div class="card">
                            <?php
                            $showListSort = false;
                            require_once '../_select.php';
                            ?>

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
                            <li>排序：開始日期（由新至舊）。</li>
                            <li>「序號明細」可匯出該活動底下 coupon_d 優惠代碼。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
                    </form>

<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
