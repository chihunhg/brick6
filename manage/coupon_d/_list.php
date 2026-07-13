<?php
declare(strict_types=1);

$listGridClass = (string)($listGridClass ?? manage_list_grid_class('coupon-d-list'));
$PKName = (string)($PKName ?? 'PKey');
if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}
?>
<div class="tableHeader <?php echo e($listGridClass); ?>">
    <div class="textCenter">選取</div>
    <div>折抵訂單</div>
    <div class="textCenter">折抵金額</div>
    <div>會員 E-mail</div>
    <div>會員姓名</div>
    <div class="textCenter">發送日期</div>
</div>

<div class="tableRow">
<?php
if ($listRows === []) {
    echo '<p class="listEmpty">暫無資料</p>';
}
foreach ($listRows as $row) {
    $i++;
    $rowPKey = (int)($row[$PKName] ?? $row['PKey'] ?? 0);
    $orderNo = trim((string)($row['OrderNo'] ?? ''));
    $chkDisabled = $orderNo !== '' ? ' disabled="disabled"' : '';
    $price = (int)($row['Price'] ?? 0);
    $email = (string)($row['Email'] ?? $row['EMail'] ?? '');
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($listGridClass); ?>">
            <div class="flex flex--jtCenter">
                <label class="checkboxWrapper">
                    <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox"<?php echo $chkDisabled; ?>>
                </label>
            </div>
            <div><?php echo e($orderNo); ?></div>
            <div class="textCenter"><?php echo e(number_format($price)); ?></div>
            <div><?php echo e($email); ?></div>
            <div><?php echo e((string)($row['Member_Name'] ?? '')); ?></div>
            <div class="textCenter">
                <span class="dateSpan"><?php echo e(coupon_date_for_list($row['dtDate'] ?? '')); ?></span>
            </div>
        </div>
    </div>
<?php } ?>
</div>
