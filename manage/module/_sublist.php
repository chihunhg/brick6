<?php
declare(strict_types=1);
/**
 * AJAX：依階層輸出子單元名稱欄位（嵌入 #subList）
 */
require_once '../_inc.php';

$detailConfig = require __DIR__ . '/_config.php';
if (function_exists('manage_detail_set_config')) {
    manage_detail_set_config($detailConfig, true);
}

header('Content-Type: text/html; charset=UTF-8');

global $filter_array;

$PKey = safe_int($filter_array['PKey'] ?? $filter_array['Module_PKey'] ?? 0);
$intLayer = safe_int($filter_array['Layer'] ?? $filter_array['intLayer'] ?? 0);

if ($intLayer <= 1) {
    echo '<ul class="moduleLayerList__items"></ul>';
    exit;
}

?>
<ul class="moduleLayerList__items">
<?php
for ($i = 1; $i <= $intLayer; $i++) {
    $layerVal = '第' . $i . '階';
    if ($PKey > 0 && function_exists('chkTable') && chkTable('module_d')) {
        $row = crud_fetch_one(
            'SELECT strName FROM module_d WHERE Module_PKey = :fk AND Sort = :sort LIMIT 1',
            ['fk' => $PKey, 'sort' => $i],
            true
        );
        if ($row !== null && ($row['strName'] ?? '') !== '') {
            $layerVal = (string)$row['strName'];
        }
    }
    $fieldId = 'subName' . $i;
    ?>
    <li class="moduleLayerList__item">
        <label class="inputLabel" for="<?php echo e($fieldId); ?>">第 <?php echo $i; ?> 階名稱</label>
        <input type="text" name="<?php echo e($fieldId); ?>" id="<?php echo e($fieldId); ?>"
            class="formInput" value="<?php echo e($layerVal); ?>" maxlength="20">
        <span id="<?php echo e($fieldId); ?>_txt" class="input__errorTxt"></span>
    </li>
    <?php
}
?>
</ul>
