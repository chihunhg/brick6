<?php
declare(strict_types=1);

if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}

if (!isset($detailConfig) && is_file(__DIR__ . '/_config.php')) {
    $detailConfig = require __DIR__ . '/_config.php';
}
require_once __DIR__ . '/../_list_lang_bootstrap.php';

$Layer = (int)($Layer ?? 1);
$PKName = (string)($PKName ?? 'PKey');
$listShowLangColumn = (bool)($listShowLangColumn ?? false);
$listLangMap = is_array($listLangMap ?? null) ? $listLangMap : [];

$gridClass = manage_list_grid_class('paper-l' . max(1, min(5, $Layer)));
$gridClass = manage_list_grid_with_lang($gridClass, $listShowLangColumn);

$classNameCache = [];
$getClassName = static function (int $level, int $pkey) use (&$classNameCache): string {
    if ($pkey <= 0) {
        return '';
    }
    $key = $level . '#' . $pkey;
    if (isset($classNameCache[$key])) {
        return $classNameCache[$key];
    }
    $table = crud_class_table_name($level);
    if ($table === null) {
        return $classNameCache[$key] = '';
    }
    $sql = "SELECT strName FROM {$table} WHERE PKey = :PKey";
    $rows = crud_fetch_all($sql, ['PKey' => $pkey]);
    $name = isset($rows[0]['strName']) ? (string)$rows[0]['strName'] : '';
    return $classNameCache[$key] = $name;
};
?>
<div class="tableHeader <?php echo e($gridClass); ?>">
    <?php if (manage_list_expand_enabled()) { ?>
    <div class="textCenter">開合</div>
    <?php } ?>
    <div class="textCenter">選取</div>
    <div class="textCenter">順序</div>
    <?php for ($lv = 1; $lv <= 4; $lv++) {
        if ($Layer > $lv) { ?>
    <div><?php echo e($Class_Name[$lv] ?? ('分類' . $lv)); ?></div>
    <?php }
    } ?>
    <div>標題</div>
    <?php manage_list_render_lang_header($listShowLangColumn); ?>
    <div class="textCenter">上下架</div>
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
    $rowPKey = (int)($row[$PKName] ?? $row['PKey'] ?? 0);
    $rowSort = (int)($row['Sort'] ?? 0);
    $rowName = (string)($row['strName'] ?? '');
    $uploadYes = (($row['Upload'] ?? '') === 'Yes');
    $activeClass = $uploadYes ? '--active' : '--inactive';

    $c1 = (int)($row['Class1_PKey'] ?? 0);
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($gridClass); ?>">
            <?php if (manage_list_expand_enabled()) { ?>
            <div class="flex flex--jtCenter">
                <button type="button" data-manage-action="expand-row"
                    data-row-id="<?php echo $rowPKey; ?>"
                    class="tableRow__expandBtn" aria-label="展開詳細">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <?php } ?>
            <div class="flex flex--jtCenter">
                <label class="checkboxWrapper">
                    <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox">
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
            <?php for ($lv = 1; $lv <= 4; $lv++) {
                if ($Layer > $lv) {
                    $cVal = (int)($row['Class' . $lv . '_PKey'] ?? 0);
                    ?>
            <div><?php echo e($getClassName($lv, $cVal)); ?></div>
            <?php }
            } ?>
            <div class="listTitle"><?php echo e($rowName); ?></div>
            <?php manage_list_render_lang_cell($rowPKey, $listShowLangColumn, $listLangMap); ?>
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
            <div class="textCenter">
                <span class="dateSpan"><?php echo Date_EN($row['dtUDate'] ?? '', 0); ?></span>
            </div>
            <div class="flex flex--jtCenter gap--2">
                <button type="button" class="btnIcon" title="複製"
                    data-manage-action="manage-copy"
                    data-page="add.php"
                    data-pkey="<?php echo $rowPKey; ?>">
                    <i class="bi bi-copy"></i>
                </button>
                <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"
                    data-manage-action="manage-update"
                    data-page="update.php"
                    data-pkey="<?php echo $rowPKey; ?>">
                    <i class="bi bi-pencil-square"></i> 編輯
                </button>
            </div>
        </div>
        <?php if (manage_list_expand_enabled()) { ?>
        <div id="detail-<?php echo $rowPKey; ?>" class="tableRow__detail is-collapsed">
            <div class="tableRow__detail">
                <strong>編號：</strong><?php echo $rowPKey; ?>
                <?php if ($Layer > 1 && $c1 > 0) { ?>
                    &nbsp;|&nbsp;<strong><?php echo e($Class_Name[1] ?? '分類'); ?>：</strong><?php echo e($getClassName(1, $c1)); ?>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
<?php } ?>
</div>
