<?php
declare(strict_types=1);
/**
 * 列表智慧語意搜尋欄位
 * 需由父頁提供：$Keywords、$kwPlaceholder；選填 $WorkFile
 */
if (!function_exists('manage_semantic_search_label')) {
    require_once dirname(__DIR__) . '/../include/manage_semantic_search_helpers.php';
}
$semanticSearchLabel = manage_semantic_search_label();
?>
<div class="inputGroup">
    <label class="inputLabel" for="Keywords"><?php echo e($semanticSearchLabel); ?></label>
    <div class="inputWrapper">
        <input type="text" name="Keywords" id="Keywords"
            value="<?php echo e((string)($Keywords ?? '')); ?>"
            placeholder="<?php echo e((string)($kwPlaceholder ?? '請輸入搜尋內容')); ?>"
            class="formInput"
            data-manage-action="list-search"
            data-form-id="form1"
            data-work-file="<?php echo e((string)($WorkFile ?? '')); ?>"
            data-default-keywords="<?php echo e((string)($kwPlaceholder ?? '')); ?>">
    </div>
</div>
