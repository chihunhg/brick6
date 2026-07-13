<?php
declare(strict_types=1);

$listGridClass = (string)($listGridClass ?? manage_list_grid_class('epaper-list'));
$PKName = (string)($PKName ?? 'PKey');
if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}
?>
<div class="tableHeader <?php echo e($listGridClass); ?>">
    <div class="textCenter">選取</div>
    <div>E-Mail</div>
    <div class="textCenter">加入日期</div>
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
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($listGridClass); ?>">
            <div class="flex flex--jtCenter">
                <label class="checkboxWrapper">
                    <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox">
                </label>
            </div>
            <div><?php echo e((string)($row['EMail'] ?? '')); ?></div>
            <div class="textCenter">
                <span class="dateSpan"><?php echo Date_EN($row['dtDate'] ?? '', 1); ?></span>
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
