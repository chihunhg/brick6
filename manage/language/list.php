<?php
declare(strict_types=1);

require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
manage_detail_set_config($detailConfig);

require_once '_form_data.php';
language_require_admin();

$manNo = 97;
$GLOBALS['manNo'] = $manNo;

$listCsrfKey = (string)($detailConfig['list_csrf'] ?? 'language_list');
$table_name  = (string)($detailConfig['master'] ?? 'language');

$uploadBase = crud_upload_base();
$crud_cfg   = crud_cfg($table_name, 'PKey', ['upload_base' => $uploadBase]);

crud_process_list_actions($crud_cfg);

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

$listRows = crud_fetch_all(
    'SELECT * FROM `' . $table_name . '` ORDER BY Sort ASC, PKey ASC',
    []
);

$i = 0;
$list_show_expand_row = false;
manage_list_expand_enabled($list_show_expand_row);
$listGridClass = manage_list_grid_class('language');

$breadcrumbs = [
    ['label' => '系統管理'],
    ['label' => '語系設定'],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);
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
                                <div class="textCenter">選取</div>
                                <div class="textCenter">順序</div>
                                <div>語系名稱</div>
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
                                            <input type="text" name="Sort<?php echo $i; ?>" id="Sort<?php echo $i; ?>"
                                                value="<?php echo $rowSort; ?>" maxlength="4"
                                                class="tableRow__sortInput" size="4">
                                            <input name="PKey<?php echo $i; ?>" type="hidden" id="PKey<?php echo $i; ?>"
                                                value="<?php echo $rowPKey; ?>">
                                            <input name="O_Sort<?php echo $i; ?>" type="hidden" id="O_Sort<?php echo $i; ?>"
                                                value="<?php echo $rowSort; ?>">
                                        </div>
                                        <div><?php echo e((string)($row['strName'] ?? '')); ?></div>
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
                            echo hiddenNumeric('manNo', $manNo) . PHP_EOL;
                            ?>
                        </div>
                    </div>

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>僅「上架」語系會載入後台 $array_lang 與前台多語系分頁。</li>
                            <li>順序由小至大排列；修改順序後請執行「儲存順序」。</li>
                        </ul>
                    </section>
                    <div class="notes__spacer"></div>
                    </form>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
