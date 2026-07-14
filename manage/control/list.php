<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';

$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'control_list');
$table_name  = (string)($detailConfig['master'] ?? 'webcontrol');
$PKName      = 'PKey';
$FKName      = 'PKey';

$uploadBase = crud_upload_base();
$crud_cfg   = crud_cfg($table_name, $FKName, ['upload_base' => $uploadBase]);
crud_process_list_actions($crud_cfg);

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

require_once __DIR__ . '/_form_data.php';

$PDO_Cond = ' WHERE intType = 0';
$Cond_Array = [];

$kwPlaceholder = '請輸入名稱或帳號搜尋';
$Keywords = control_list_apply_keyword_search(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    $kwPlaceholder
);
if ($Keywords === '') {
    $Keywords = $kwPlaceholder;
}

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

$sql = "SELECT * FROM {$table_name} {$PDO_Cond} ORDER BY strID ASC LIMIT "
    . (int)$tPageSize . ' OFFSET ' . (int)$offset;
$listRows = crud_fetch_all($sql, $Cond_Array);

$i = 0;
$list_show_expand_row = false;
manage_list_expand_enabled($list_show_expand_row);
$listGridClass = manage_list_grid_class('control-list');

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => (string)($Module_Name ?? '帳號管理')],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);
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
                                        <label class="inputLabel" for="Keywords">智慧語意搜尋</label>
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

                            <div class="tableHeader <?php echo e($listGridClass); ?>">
                                <div class="textCenter">選取</div>
                                <div class="textCenter">序號</div>
                                <div>管理者名稱</div>
                                <div>帳號</div>
                                <div>權限範圍</div>
                                <div class="textCenter">修改日期</div>
                                <div class="textCenter">操作</div>
                            </div>

                            <div class="tableRow">
                                <?php
                                if ($listRows === []) {
                                    echo '<p class="listEmpty">暫無資料</p>';
                                }
                                foreach ($listRows as $row) {
                                    $i++;
                                    $rowPKey = (int)($row['PKey'] ?? 0);
                                ?>
                                <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
                                    <div class="tableRow__data <?php echo e($listGridClass); ?>">
                                        <div class="flex flex--jtCenter">
                                            <label class="checkboxWrapper">
                                                <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox">
                                            </label>
                                        </div>
                                        <div class="textCenter"><?php echo $i; ?></div>
                                        <div><?php echo e((string)($row['strName'] ?? '')); ?></div>
                                        <div><?php echo e((string)($row['strID'] ?? '')); ?></div>
                                        <div class="controlList__fn"><?php echo e((string)($row['FunctionName'] ?? '')); ?></div>
                                        <div class="textCenter">
                                            <span class="dateSpan"><?php echo Date_EN($row['dtUDate'] ?? '', 0); ?></span>
                                        </div>
                                        <div class="flex flex--jtCenter gap--2">
                                            <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"
                                                data-manage-action="manage-update"
                                                data-page="update.php"
                                                data-pkey="<?php echo $rowPKey; ?>">
                                                <i class="bi bi-pencil-square"></i> 編輯
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>

                            <?php
                            echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;
                            echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('Total', $i) . PHP_EOL;
                            echo hiddenNumeric('Page', $tPage) . PHP_EOL;
                            echo hiddenNumeric('PageSize', $tPageSize) . PHP_EOL;
                            ?>

                            <?php if (file_exists(__DIR__ . '/../_page.php')) {
                                require_once '../_page.php';
                            } ?>
                        </div>
                    </div>

                    <div class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>可新增多組管理者帳號，並設定各帳號可管理的後台單元權限。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
                    </form>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
