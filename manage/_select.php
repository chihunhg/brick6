<!-- Toolbar -->
<div class="tableToolbar">
	<div class="tableToolbar__left">
		<input name="button" id="selAll" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="全選" data-manage-action="select-all">
		<input name="button2" id="delAll" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="取消全選" data-manage-action="select-none">
		<div class="tableToolbar__divider"></div>

		<div class="tableToolbar__batchActions">
			<button type="button" class="badge tableToolbar__batchBtn tableToolbar__batchBtnDelete" data-manage-action="batch" data-batch="delete">
				<i class="bi bi-trash"></i> 刪除
			</button>
			<button type="button" class="badge tableToolbar__batchBtn tableToolbar__batchBtnPublish" data-manage-action="batch" data-batch="publish">
				<i class="bi bi-check-circle"></i> 批次發佈
			</button>
			<button type="button" class="badge tableToolbar__batchBtn tableToolbar__batchBtnArchive" data-manage-action="batch" data-batch="archive">
				<i class="bi bi-archive"></i> 批次下架
			</button>
			<div class="tableToolbar__divider"></div>
		</div>

		<button type="button" id="update-sort-btn" class="btnStyle btnStyle--updateSort" data-manage-action="update-sort">
			<i class="bi bi-arrow-down-up"></i> 確認更新排序
		</button>
	</div>
	<?php if (!isset($showListAdd) || $showListAdd) { ?>
	<button type="button" class="btnStyle btnStyle--sm btnStyle--sub" data-manage-action="manage-update" data-page="add.php" data-pkey="">
		<i class="bi bi-plus-lg"></i> 新增
	</button>
	<?php } ?>
</div>
<input type="hidden" name="Action" id="Action">
