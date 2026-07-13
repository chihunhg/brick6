<?php
declare(strict_types=1);
// _list.php — 訂單列表列渲染

if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}

$listGridClass = (string)($listGridClass ?? manage_list_grid_class('order-list'));
$PKName = (string)($PKName ?? 'PKey');
?>
<div class="tableHeader <?php echo e($listGridClass); ?>">
    <div class="textCenter">選取</div>
    <div class="textCenter">訂單日期</div>
    <div>訂單編號</div>
    <div>收件人</div>
    <div>付款方式</div>
    <div>處理進度</div>
    <div class="textCenter">訂單金額</div>
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
    $orderNo = (string)($row['OrderNo'] ?? '');
    $recipient = (string)($row['strName'] ?? '');
    $intPay = (int)($row['intPay'] ?? 0);
    $intState = (int)($row['intState'] ?? 0);
    $totalPrice = (int)($row['TotalPrice'] ?? 0);
    $dtDate = function_exists('Date_EN') ? Date_EN($row['dtDate'] ?? '', 1) : (string)($row['dtDate'] ?? '');
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($listGridClass); ?>">
            <div class="flex flex--jtCenter">
                <label class="checkboxWrapper">
                    <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox">
                </label>
            </div>
            <div class="textCenter">
                <span class="dateSpan"><?php echo e((string)$dtDate); ?></span>
            </div>
            <div><?php echo e($orderNo); ?></div>
            <div><?php echo e($recipient); ?></div>
            <div><?php echo e(PayType($intPay)); ?></div>
            <div><?php echo e(FlowState($intState)); ?></div>
            <div class="textCenter"><?php echo e(number_format($totalPrice)); ?></div>
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
