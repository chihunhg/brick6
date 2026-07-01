<div class="editView__footer">
	<button type="button" data-manage-action="return-list" data-return-url="../_return_list.php" class="btnStyle btnStyle--outline">關閉</button>
	<button type="submit" name="Submit" id="Submit" value="送出" class="btnStyle"><i class="bi bi-save"></i> 送出</button>

  <?php if (!stristr($WorkFile, 'add.php')): ?>
    <?= hiddenNumeric('PKey', (string)(int)($Update_PKey ?? $filter_array['PKey'] ?? 0)).PHP_EOL ?>
  <?php endif; ?>

  <?= hiddenNumeric('Copy_PKey', $filter_array['PKey'] ?? 0).PHP_EOL ?>
  <?= hiddenNumeric('Page', manage_list_search_filter_value($filter_array ?? [], 'Page')).PHP_EOL ?>
  <?= hiddenNumeric('PageSize', manage_list_search_filter_value($filter_array ?? [], 'PageSize')).PHP_EOL ?>
  <?= hiddenNumeric('manNo', $manNo ?? '').PHP_EOL ?>
  <?= hiddenNumeric('subNo', $subNo ?? '').PHP_EOL ?>
  <?= hiddenText('Send', '').PHP_EOL ?>
  <?= hiddenText('Q_OpenDate', manage_list_search_filter_value($filter_array ?? [], 'OpenDate')).PHP_EOL ?>
  <?= hiddenText('Q_EndDate', manage_list_search_filter_value($filter_array ?? [], 'EndDate')).PHP_EOL ?>
  <?= hiddenYesNo('Q_Upload', manage_list_search_filter_value($filter_array ?? [], 'Upload')).PHP_EOL ?>
  <?= hiddenNumeric('Q_intLocal', manage_list_search_filter_value($filter_array ?? [], 'intLocal')).PHP_EOL ?>
  <?= hiddenText('Q_Keywords', manage_list_search_filter_value($filter_array ?? [], 'Keywords')).PHP_EOL ?>
  <?php
	for ((int) $i = 1; $i < 5; $i++) {
		echo hiddenNumeric('Q_Class' . $i, manage_list_search_filter_value($filter_array ?? [], 'Class' . $i)) . PHP_EOL;
	}
  ?>
  <?= hiddenNumeric('Q_intState', $filter_array['intState'] ?? '').PHP_EOL ?>
  <?= hiddenNumeric('Q_intPay', $filter_array['intPay'] ?? '').PHP_EOL ?>
  <?= hiddenNumeric('Q_intType', $filter_array['intType'] ?? '').PHP_EOL ?>
  <?= hiddenNumeric('Q_intUse', $filter_array['intUse'] ?? '').PHP_EOL ?>
  <?php
  $__listReturn = function_exists('manage_return_list_path') ? manage_return_list_path('list.php') : 'list.php';
  $__listModule = function_exists('manage_return_list_module_dir') ? manage_return_list_module_dir() : '';
  ?>
  <?= hiddenText('list', $__listReturn).PHP_EOL ?>
  <?= hiddenText('list_module', $__listModule).PHP_EOL ?>
  <?= hiddenText('language', '').PHP_EOL ?>
  <?= hiddenText('csrf_token', $csrf_token ?? '').PHP_EOL ?>
  <?= hiddenNumeric('Total_lang', !empty($array_lang) ? count($array_lang) : '').PHP_EOL ?>
  <?php if (!empty($managePhotoSlotMax) && (int)$managePhotoSlotMax > 0) { ?>
  <?= hiddenNumeric('PhotoSlotMax', (int)$managePhotoSlotMax).PHP_EOL ?>
  <?php } ?>
</div>

  <div class="load-wrapp" id="Submit_Close" style="display:none;">
    <div class="loading">
      <div class="spinner">
        <div class="bubble-1"></div>
        <div class="bubble-2"></div>
      </div>
      <span>表單送出中，請稍候</span>
    </div>
  </div>
  
