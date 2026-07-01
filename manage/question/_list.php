<?php

declare(strict_types=1);

if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}

if (!isset($detailConfig) && is_file(__DIR__ . '/_config.php')) {
    $detailConfig = require __DIR__ . '/_config.php';
}
require_once __DIR__ . '/../_list_lang_bootstrap.php';

$PKName = (string)($PKName ?? 'PKey');
$listShowLangColumn = (bool)($listShowLangColumn ?? false);
$listLangMap = is_array($listLangMap ?? null) ? $listLangMap : [];

$gridClass = 'tableGrid--question';
$gridClass = manage_list_grid_with_lang($gridClass, $listShowLangColumn);
$reportCountMap = is_array($reportCountMap ?? null) ? $reportCountMap : [];
?>
<div class="tableHeader <?php echo e($gridClass); ?>">
    <div class="textCenter">選取</div>
	<div class="textCenter">順序</div>
    <div>問卷代號</div>
    <div>問卷主題</div>
    <?php manage_list_render_lang_header($listShowLangColumn); ?>
    <div class="textCenter">上下架</div>
    <div class="textCenter">修改日期</div>
    <div class="textCenter">操作</div>
    <div class="textCenter">問卷分類</div>
    <div class="textCenter">匯出</div>
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
    $rowNo   = (string)($row['strNo'] ?? '');
    $reportCount = (int)($reportCountMap[$rowPKey] ?? 0);
    $uploadYes = (($row['Upload'] ?? '') === 'Yes');
    $activeClass = $uploadYes ? '--active' : '--inactive';
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($gridClass); ?>">
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
            <div><?php echo e($rowNo); ?></div>
            <div><?php echo e($rowName); ?></div>
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
            <div class="textCenter">
                <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"
                    data-manage-action="manage-update"
                    data-page="../question_class/list.php"
                    data-pkey="<?php echo $rowPKey; ?>">
                    設定
                </button>
            </div>
            <div class="textCenter">
                <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"
                    onclick="window.open('output.php?PKey=<?php echo $rowPKey; ?>', '_blank');">
                    匯出(<?php echo $reportCount; ?>)
                </button>
            </div>
        </div>
    </div>
<?php } ?>
</div>
