<!-- Toolbar -->
<?php
$__listToolbar = manage_list_toolbar_flags(
    is_array($detailConfig ?? null) ? $detailConfig : [],
    [
        'showListAdd'    => isset($showListAdd) ? $showListAdd : null,
        'showListSort'   => isset($showListSort) ? $showListSort : null,
        'showListUpload' => isset($showListUpload) ? $showListUpload : null,
        'showListDelete' => isset($showListDelete) ? $showListDelete : null,
    ]
);
$showListAdd = $__listToolbar['showListAdd'];
$showListSort = $__listToolbar['showListSort'];
$showListUpload = $__listToolbar['showListUpload'];
$showListDelete = $__listToolbar['showListDelete'];
$showBatchActions = $__listToolbar['showBatchActions'];
unset($__listToolbar);
?>
<div class="tableToolbar">
	<div class="tableToolbar__left">
		<input name="button" id="selAll" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="全選" data-manage-action="select-all">
		<input name="button2" id="delAll" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="取消全選" data-manage-action="select-none">
		<div class="tableToolbar__divider"></div>

		<?php if ($showBatchActions) { ?>
		<div class="tableToolbar__batchActions">
			<?php if ($showListDelete) { ?>
			<button type="button" class="badge tableToolbar__batchBtn tableToolbar__batchBtnDelete" data-manage-action="batch" data-batch="delete">
				<i class="bi bi-trash"></i> 刪除
			</button>
			<?php } ?>
			<?php if ($showListUpload) { ?>
			<button type="button" class="badge tableToolbar__batchBtn tableToolbar__batchBtnPublish" data-manage-action="batch" data-batch="publish">
				<i class="bi bi-check-circle"></i> 批次發佈
			</button>
			<button type="button" class="badge tableToolbar__batchBtn tableToolbar__batchBtnArchive" data-manage-action="batch" data-batch="archive">
				<i class="bi bi-archive"></i> 批次下架
			</button>
			<?php } ?>
			<div class="tableToolbar__divider"></div>
		</div>
		<?php } ?>

		<?php if ($showListSort) { ?>
		<button type="button" id="update-sort-btn" class="btnStyle btnStyle--updateSort" data-manage-action="update-sort">
			<i class="bi bi-arrow-down-up"></i> 確認更新排序
		</button>
		<?php } ?>
	</div>
	<?php if ($showListAdd) { ?>
	<button type="button" class="btnStyle btnStyle--sm btnStyle--sub" data-manage-action="manage-update" data-page="add.php" data-pkey="">
		<i class="bi bi-plus-lg"></i> 新增
	</button>
	<?php } ?>
</div>
<input type="hidden" name="Action" id="Action">
