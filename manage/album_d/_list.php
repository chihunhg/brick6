<?php
declare(strict_types=1);

if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}

$PKName = (string)($PKName ?? 'PKey');
$Album_PKey = (int)($Album_PKey ?? 0);
$gridClass = manage_list_grid_class('album-d');
?>
<div class="tableHeader <?php echo e($gridClass); ?>">
    <div class="textCenter">選取</div>
    <div class="textCenter">順序</div>
    <div class="textCenter">縮圖</div>
    <div>圖說</div>
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
    $rowPKey = (int)($row[$PKName] ?? 0);
    $rowSort = (int)($row['Sort'] ?? 0);
    $thumbUrl = album_d_thumb_url($row);
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($gridClass); ?>">
            <div class="flex flex--jtCenter">
                <label class="checkboxWrapper">
                    <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox">
                </label>
            </div>
            <div class="flex flex--jtCenter">
                <input type="text" name="Sort<?php echo $i; ?>" value="<?php echo $rowSort; ?>"
                    maxlength="4" class="tableRow__sortInput" size="4">
                <input type="hidden" name="PKey<?php echo $i; ?>" value="<?php echo $rowPKey; ?>">
                <input type="hidden" name="O_Sort<?php echo $i; ?>" value="<?php echo $rowSort; ?>">
            </div>
            <div class="textCenter">
                <?php if ($thumbUrl !== '') { ?>
                <img src="<?php echo e($thumbUrl); ?>?<?php echo time(); ?>" alt=""
                    class="tableRow__thumb">
                <?php } else { ?>
                —
                <?php } ?>
            </div>
            <div><?php echo e((string)($row['PhotoM'] ?? '')); ?></div>
            <div class="textCenter">
                <span class="dateSpan"><?php echo Date_EN($row['dtUDate'] ?? '', 0); ?></span>
            </div>
            <div class="flex flex--jtCenter">
                <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"
                    data-manage-action="manage-update"
                    data-page="update.php?Album_PKey=<?php echo $Album_PKey; ?>"
                    data-pkey="<?php echo $rowPKey; ?>">
                    <i class="bi bi-pencil-square"></i> 編輯
                </button>
            </div>
        </div>
    </div>
<?php } ?>
</div>
