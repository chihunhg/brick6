<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';

$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'album_list');
$table_name  = (string)($detailConfig['master'] ?? 'album');
$PKName      = 'PKey';
$FKName      = (string)($detailConfig['fk'] ?? 'Album_PKey');

$uploadBase = crud_upload_base();
$upload_foder = $upload_foder ?? $uploadBase;

$crud_cfg = crud_cfg($table_name, $FKName, ['upload_base' => $uploadBase]);
crud_process_list_actions($crud_cfg);

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

$Layer  = (int)($Layer ?? 1);
$Class1 = (int)($Class1 ?? 0);
$Class2 = (int)($Class2 ?? 0);
$Class3 = (int)($Class3 ?? 0);
$Class4 = (int)($Class4 ?? 0);

[$PDO_Cond, $Cond_Array] = crud_module_where();
crud_list_apply_class_filters(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    $Layer,
    $Class1,
    $Class2,
    $Class3,
    $Class4
);

$kwPlaceholder = '請輸入標題搜尋';
$Keywords = crud_list_apply_keyword_search(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    'strName',
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

$sql = "SELECT * FROM {$table_name} {$PDO_Cond} ORDER BY Sort ASC, dtUDate DESC LIMIT "
    . (int)$tPageSize . ' OFFSET ' . (int)$offset;
$listRows = crud_fetch_all($sql, $Cond_Array);

$i = 0;

/** 列表開合欄：true 顯示，預設 false */
$list_show_expand_row = $list_show_expand_row ?? false;
manage_list_expand_enabled($list_show_expand_row);

$clearUrl = ($WorkFile ?? 'list.php')
    . '?manNo=' . urlencode((string)($manNo ?? ''))
    . '&subNo=' . urlencode((string)($subNo ?? ''));

$layout_container_class = 'container container--full';
?>
<?php require_once '../_layout_head.php'; ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <form action="" method="post" name="form1" id="form1" data-upload-url="_upload.php">
                    <div id="view-list">
                        <div class="card filterWrap">
                            <div class="filterWrap__content">
                                <div class="filterWrap__grid">
                                    <?php $searchAutoSubmit = true; require_once '../_search.php'; ?>
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="Keywords">關鍵字搜尋</label>
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
                            <?php require_once '../_select.php'; ?>
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
                            <li>網站前台顯示順序，依照「順序」由小至大排序；順序相同，依照「修改日期」由新至舊排序。</li>
                            <li>複製功能僅能複製文字，圖片需重新上傳。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
                    </form>

<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
