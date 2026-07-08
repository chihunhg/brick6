<?php
/**
 * 問卷日期欄位區塊（questionnaire.php include）
 */
?>
<!--日期開放題-->
<div class="inputGroup__box">
	<div class="inputGroup__box__input">
		<input type="date" name="<?php echo 'Q_'.$q.'_'.$a?>" id="<?php echo 'Q_'.$q.'_'.$a?>" class="input" style="width:100%">
		<span id="<?php echo 'Q_'.$q.'_'.$a?>_txt" class="errorTxt"></span>
	</div>
</div><!-- inputGroup__box END -->
