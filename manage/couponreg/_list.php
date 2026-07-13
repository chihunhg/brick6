<?php
declare(strict_types=1);

$listGridClass = (string)($listGridClass ?? manage_list_grid_class('couponreg-list'));
$PKName = (string)($PKName ?? 'PKey');
if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}
?>
<div class="tableHeader <?php echo e($listGridClass); ?>">
    <div class="textCenter">選取</div>
    <div>活動名稱</div>
    <div class="textCenter">金額</div>
    <div class="textCenter">開始日期</div>
    <div class="textCenter">結束日期</div>
    <div class="textCenter">明細</div>
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
    [$openText, $endText] = couponreg_list_period_text($row);
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($listGridClass); ?>">
            <div class="flex flex--jtCenter">
                <label class="checkboxWrapper">
                    <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox">
                </label>
            </div>
            <div><?php echo e((string)($row['strName'] ?? '')); ?></div>
            <div class="textCenter"><?php echo e(number_format((int)($row['Price'] ?? 0))); ?></div>
            <div class="textCenter"><span class="dateSpan"><?php echo e($openText); ?></span></div>
            <div class="textCenter"><span class="dateSpan"><?php echo e($endText); ?></span></div>
            <div class="textCenter">
                <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"
                    data-manage-action="manage-update"
                    data-page="../coupon_d/list.php"
                    data-pkey="<?php echo $rowPKey; ?>">
                    <i class="bi bi-list-ul"></i> 清單
                </button>
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
    </div>
<?php } ?>
</div>
