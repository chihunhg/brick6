<?php
declare(strict_types=1);

require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

require_once '_form_data.php';
module_require_admin();

$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'module2_list');
$table_name  = (string)($detailConfig['master'] ?? 'module_p');
$listIntType = (int)($detailConfig['list_int_type'] ?? 2);
$FKName      = (string)($detailConfig['fk'] ?? 'Module_PKey');

$uploadBase = crud_upload_base();
$crud_cfg   = crud_cfg($table_name, $FKName, [
    'upload_base'    => $uploadBase,
    'img_table'      => '',
    'msg_table'      => '',
    'lang_table'     => (string)($detailConfig['lang'] ?? ''),
    'relation_table' => '',
]);

crud_process_list_actions($crud_cfg, 'crud_module_p_before_delete');

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

$listRows = crud_fetch_all(
    'SELECT * FROM ' . $table_name . ' WHERE intType = :intType ORDER BY Home DESC, Sort ASC, PKey ASC',
    ['intType' => $listIntType]
);
$listRows = module_list_enrich_rows($listRows);

$i = 0;
$list_show_expand_row = $list_show_expand_row ?? false;
manage_list_expand_enabled($list_show_expand_row);
$listGridClass = manage_list_grid_class('module-class');
require_once __DIR__ . '/../_list_lang_bootstrap.php';
$listGridClass = manage_list_grid_with_lang($listGridClass, (bool)($listShowLangColumn ?? false));

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => '美工頁面'],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);
$layout_container_class = manage_list_layout_container_class($detailConfig);
$GLOBALS['layout_container_class'] = $layout_container_class;
?>
<?php require_once '../_layout_head.php'; ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <form action="" method="post" name="form1" id="form1" data-upload-url="_upload.php">
                    <div id="view-list">
                        <div class="card">
                            <?php require_once '../_select.php'; ?>

                            <div class="tableHeader <?php echo e($listGridClass); ?>">
                                <?php if (manage_list_expand_enabled()) { ?>
                                <div class="textCenter">開合</div>
                                <?php } ?>
                                <div class="textCenter">選取</div>
                                <div class="textCenter">順序</div>
                                <div>單元名稱</div>
                                <div class="textCenter">上下架</div>
                                <div class="textCenter">操作</div>
                            </div>

                            <div class="tableRow">
                                <?php
                                if ($listRows === []) {
                                    echo '<p class="listEmpty">暫無資料</p>';
                                }
                                foreach ($listRows as $row) {
                                    $i++;
                                    $rowPKey   = (int)($row['PKey'] ?? 0);
                                    $rowSort   = (int)($row['Sort'] ?? 0);
                                    $homeYes   = (($row['Home'] ?? '') === 'Yes');
                                    $uploadYes = (($row['Upload'] ?? '') === 'Yes');
                                    $activeClass = $uploadYes ? '--active' : '--inactive';
                                    ?>
                                <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
                                    <div class="tableRow__data <?php echo e($listGridClass); ?>">
                                        <div class="flex flex--jtCenter">
                                            <label class="checkboxWrapper">
                                                <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>"
                                                    class="customCheckbox">
                                            </label>
                                        </div>
                                        <div class="flex flex--jtCenter">
                                            <?php if ($homeYes) { ?>
                                                <span class="tableRow__sortReadonly"><?php echo e(AddZero($rowSort)); ?></span>
                                                <input name="Sort<?php echo $i; ?>" type="hidden" id="Sort<?php echo $i; ?>"
                                                    value="<?php echo $rowSort; ?>">
                                            <?php } else { ?>
                                                <input type="text" name="Sort<?php echo $i; ?>" id="Sort<?php echo $i; ?>"
                                                    value="<?php echo $rowSort; ?>" maxlength="4"
                                                    class="tableRow__sortInput" size="4">
                                            <?php } ?>
                                            <input name="PKey<?php echo $i; ?>" type="hidden" id="PKey<?php echo $i; ?>"
                                                value="<?php echo $rowPKey; ?>">
                                            <input name="O_Sort<?php echo $i; ?>" type="hidden" id="O_Sort<?php echo $i; ?>"
                                                value="<?php echo $rowSort; ?>">
                                        </div>
                                        <div>
                                            <?php echo e((string)($row['strName'] ?? '')); ?>
                                            <?php if ($homeYes) { ?>
                                                <span class="badge notes__badge" title="首頁單元">首頁</span>
                                            <?php } ?>
                                        </div>
                                        <div class="textCenter">
                                            <button type="button"
                                                class="toggleSwitch <?php echo e($activeClass); ?>"
                                                data-manage-action="toggle-upload"
                                                data-pkey="<?php echo $rowPKey; ?>"
                                                data-upload="<?php echo $uploadYes ? 'Yes' : 'No'; ?>"
                                                data-upload-url="_upload.php"
                                                aria-pressed="<?php echo $uploadYes ? 'true' : 'false'; ?>"
                                                aria-label="<?php echo $uploadYes ? '下架' : '上架'; ?>">
                                                <span class="toggleKnob"></span>
                                            </button>
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
                            echo hiddenNumeric('Total', $i) . PHP_EOL;
                            echo hiddenNumeric('Page', 1) . PHP_EOL;
                            echo hiddenNumeric('PageSize', max(50, $i)) . PHP_EOL;
                            echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
                            ?>
                        </div>
                    </div>

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>本列表僅顯示「美工頁面」（module_p.intType = 2）單元。</li>
                            <li>單元下架後，網站前台不顯示。</li>
                            <li>網站前台顯示順序，依照「單元順序」由小至大排序；首頁單元順序不可編輯。</li>
                        </ul>
                    </section>
                    <div class="notes__spacer"></div>
                    </form>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
