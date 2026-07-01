<?php
declare(strict_types=1);
/**
 * 列表分類篩選（依 $Layer 輸出 Class1~Class4）
 * 需由父頁提供：$Layer, $Module_PKey, $Class_Name, $Class1~$Class4, $WorkFile
 */

$searchLayer = (int)($searchLayer ?? $Layer ?? 0);
if ($searchLayer <= 1) {
    return;
}

$modulePKey = (int)SqlFilter($Module_PKey ?? 0, 'int');
$searchWorkFile = (string)($WorkFile ?? '');

$selected = [
    1 => (int)($Class1 ?? 0),
    2 => (int)($Class2 ?? 0),
    3 => (int)($Class3 ?? 0),
    4 => (int)($Class4 ?? 0),
];

$defaultLabels = [
    1 => '分類',
    2 => '子分類',
    3 => '子子分類',
    4 => '第四層',
];

for ($lv = 1; $lv <= 4; $lv++) {
    if ($searchLayer <= $lv) {
        break;
    }

    $table = crud_class_table_name($lv);
    if ($table === null) {
        continue;
    }

    $parentPKey = ($lv === 1) ? 0 : $selected[$lv - 1];
    $options = crud_fetch_class_options($lv, $modulePKey, $parentPKey);
    $fieldId = 'Class' . $lv;
    $label = (string)($Class_Name[$lv] ?? $defaultLabels[$lv]);
    $current = $selected[$lv];
    $disabled = ($lv > 1 && $parentPKey <= 0);
    ?>
<div class="inputGroup">
    <label class="inputLabel" for="<?php echo e($fieldId); ?>"><?php echo e($label); ?></label>
    <div class="inputWrapper">
        <select name="<?php echo e($fieldId); ?>" id="<?php echo e($fieldId); ?>"
            class="formSelect"
            data-manage-action="search-class-change"
            data-class-level="<?php echo $lv; ?>"
            data-form-id="form1"
            data-work-file="<?php echo e($searchWorkFile); ?>"
            data-auto-search="<?php echo !empty($searchAutoSubmit) ? '1' : '0'; ?>"
            <?php if ($disabled) { echo ' disabled'; } ?>>
            <option value="">全部顯示</option>
            <?php
            $renderedIds = [];
            foreach ($options as $opt) {
                $optId = (int)($opt['PKey'] ?? 0);
                if ($optId <= 0 || isset($renderedIds[$optId])) {
                    continue;
                }
                $renderedIds[$optId] = true;
                $optName = (string)($opt['strName'] ?? '');
                ?>
            <option value="<?php echo $optId; ?>"<?php
                if ($current === $optId) {
                    echo ' selected';
                }
            ?>><?php echo e($optName); ?></option>
            <?php } ?>
        </select>
    </div>
</div>
    <?php
}
