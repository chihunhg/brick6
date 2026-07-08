<?php
/**
 * 問卷複選題選項區塊（questionnaire.php include）
 */
?>
<!--複選題-->
<div class="inputGroup__box">
	<div class="inputGroup__box__input">
		<div class="formMode">
			<?php
			$i = 0;
			$sql = 'Select * From question_answer Where Question_I_PKey= :Question_I_PKey and intType=1 Order By Sort';
			$as1 = new recordset($sql, ['Question_I_PKey' => $rs1->field('PKey')]);
			//判斷有無錯誤訊息
			$SQL_Error = $as1->getErrorMessage();
			if(!empty($SQL_Error)){
				//寫入資料庫存取錯誤記錄
				$result = sql_error($sql.PHP_EOL.array_to_string(array($rs1->field('PKey'))),$SQL_Error,$WorkFile,'system');
				echo '<pre>';
				print_r($result);
				echo '</pre>';
				exit;
			}
			while (! $as1->eof){
				$i++;
			?>
			<div class="form-check">
				<input id="<?php echo 'Q_'.$q.'_'.$a.'_'.$i?>" type="checkbox" class="form-check-input js-q-answer-input" name="<?php echo 'Q_'.$q.'_'.$a.'_'.$i?>" value="<?php echo $as1->field('strName')?>">
				<label for="<?php echo 'Q_'.$q.'_'.$a.'_'.$i?>"><?php echo $as1->field('strName')?></label>
				<?php
				if($rs1->field('Other')=='Yes' && $i==$answer){
				?>
				<input id="<?php echo 'Q_'.$q.'_'.$a.'_'.($i+1)?>" type="checkbox" class="form-check-input js-q-answer-input" name="<?php echo 'Q_'.$q.'_'.$a?>" value="其它">
				<label for="<?php echo 'Q_'.$a.'_'.($i+1)?>"><span>其它</span></label>
				<input class="type-input" type="text" class="input" name="<?php echo 'Q_'.$q.'_'.$a.'_Memo';?>" id="<?php echo 'Q_'.$q.'_'.$a.'_Memo';?>">
				<?php
				}
				?>
			</div>
			<?php
			$as1->movenext();
			}
			$as1->close();
			?>
		</div>
	</div>
	<span id="<?php echo 'Q'.$q.'_'.$a?>_txt" class="errorTxt"></span>
	<input type="hidden" id="<?php echo 'Q'.$q.'_'.$a.'_Sort'?>" name="<?php echo 'Q'.$q.'_'.$a.'_Sort'?>">
	<input type="hidden" id="<?php echo 'Q'.$q.'_'.$a.'_answer'?>" name="<?php echo 'Q'.$q.'_'.$a.'_answer'?>" value="<?php echo $answer?>" >
</div><!-- inputGroup__box END -->
<script<?= csp_script_nonce_attr() ?>>
(function ($) {
	var prefix = 'Q_<?php echo $q.'_'.$a?>';
	var total = <?php echo (int)($i + 1); ?>;
	function updateSort() {
		var str = [];
		for (var n = 1; n <= total; n++) {
			if ($('#' + prefix + '_' + n).prop('checked')) {
				str.push(n);
				$('#' + prefix + '_' + n + '_Memo').prop('readOnly', false);
			} else {
				$('#' + prefix + '_' + n + '_Memo').prop('readOnly', true);
				$('#' + prefix + '_' + n + '_Memo').val('');
			}
		}
		$('#Q<?php echo $q.'_'.$a?>_Sort').val(str.toString());
	}
	for (var n = 1; n <= total; n++) {
		$('#' + prefix + '_' + n).on('change', updateSort);
	}
})(jQuery);
</script>
