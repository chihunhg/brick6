<?php

declare(strict_types=1);



if (!isset($listRows) || !is_array($listRows)) {

    $listRows = [];

}



$PKName = (string)($PKName ?? 'PKey');

$Question_PKey = (int)($Question_PKey ?? 0);

$Question_Name = (string)($Question_Name ?? '');

$gridClass = manage_list_grid_class('question-class');

?>

<div class="tableHeader <?php echo e($gridClass); ?>">

    <div class="textCenter">選取</div>

    <div class="textCenter">順序</div>

    <div>問卷主題</div>

    <div>問卷類別</div>

    <div class="textCenter">修改日期</div>

    <div class="textCenter">操作</div>

    <div class="textCenter">問卷題目</div>

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

    $rowName = trim((string)($row['strName'] ?? ''));

    if ($rowName === '') {

        $rowName = question_class_display_strname($rowPKey);

    }

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

            <div><?php echo e($Question_Name); ?></div>

            <div><?php echo e($rowName); ?></div>

            <div class="textCenter">

                <span class="dateSpan"><?php echo Date_EN($row['dtUDate'] ?? '', 0); ?></span>

            </div>

            <div class="flex flex--jtCenter gap--2 flex--wrap">

                <button type="button" class="btnIcon" title="複製"

                    data-manage-action="manage-copy"

                    data-page="add.php?PKey=<?php echo $rowPKey; ?>&Question_PKey=<?php echo $Question_PKey; ?>"

                    data-pkey="<?php echo $rowPKey; ?>">

                    <i class="bi bi-copy"></i>

                </button>

                <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"

                    data-manage-action="manage-update"

                    data-page="update.php?Question_PKey=<?php echo $Question_PKey; ?>"

                    data-pkey="<?php echo $rowPKey; ?>">

                    <i class="bi bi-pencil-square"></i> 編輯

                </button>

            </div>
            <div class="flex flex--jtCenter">
                <button type="button" class="btnStyle btnStyle--sm btnStyle--outline"

                    data-manage-action="manage-update"

                    data-page="../question_item/list.php?Question_PKey=<?php echo $Question_PKey; ?>"

                    data-pkey="<?php echo $rowPKey; ?>">

                    <i class="bi bi-gear"></i> 設定

                </button>
            </div>

        </div>

    </div>

<?php } ?>

</div>

