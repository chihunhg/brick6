<?php
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$qcForm = is_array($GLOBALS['question_class_form'] ?? null) ? $GLOBALS['question_class_form'] : [];
$Question_PKey = (int)($qcForm['Question_PKey'] ?? 0);
$Question_Name = (string)($qcForm['Question_Name'] ?? '');
?><!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
function fieldCheck0(theForm) {
	if (typeof loading === 'function') {
		loading(1);
	}
	var errors = [];
	var array = [];
	var view = [];
	var totalLang = parseInt($('#Total_lang').val(), 10) || <?php echo max(1, (int)count($array_lang)); ?>;
	var sortVal = ($('#Sort').val() || '').trim();
	if (!/^\d+$/.test(sortVal)) {
		errors.push('順序不是數字');
		array.push('Sort');
	}
	var hasName = false;
	for (var i = 1; i <= totalLang; i++) {
		if ($.trim($('#strName' + i).val() || '') !== '') {
			hasName = true;
			break;
		}
	}
	if (!hasName) {
		errors.push('類別名稱空白（請至少填寫一個語系）');
		array.push('strName1');
		view.push(1);
	}
	if (errors.length) {
		return window.manageFormValidationFail(errors, {
			focusField: array[0],
			viewTab: view.length ? view[0] : undefined,
			form: theForm
		});
	}
	return window.manageFormValidationOk(theForm);
}
<?php echo script_close(); ?>
</head>

<body <?php if (!empty($bodytxt)) {
    echo $bodytxt;
} ?>>
    <div class="appRoot">
        <?php require_once '../_header.php'; ?>
        <div class="appBody">
            <?php require_once '../_sidebar.php'; ?>

            <main class="mainContent">
                <div class="container">
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <section class="editView">
                        <form action="addin.php" method="post" enctype="multipart/form-data"
                            name="form1" id="form1" data-manage-validate="fieldCheck0">

                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"></ul>
                            </div>
                        </div>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">基本設定</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">問卷名稱</label>
                                    <div class="col--10">
                                        <?php echo e($Question_Name); ?>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">順序 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="Sort" id="Sort" type="number" min="0" step="1"
                                            class="formInput editView__sortInput"
                                            value="<?php echo (int)($Sort ?? 1); ?>">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Upload">上下架</label>
                                    <div class="col--10">
                                        <select name="Upload" id="Upload" class="formSelect">
                                            <option value="Yes"<?php echo ($Upload ?? '') === 'Yes' ? ' selected' : ''; ?>>上架</option>
                                            <option value="No"<?php echo ($Upload ?? '') === 'No' ? ' selected' : ''; ?>>下架</option>
                                        </select>
                                    </div>
                                </div>
                                <?php if (!$isAdd) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">修改日期</label>
                                    <div class="col--10">
                                        <span class="dateSpan"><?php require_once '../_modify.php'; ?></span>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </article>
                        <article class="editView__tabs tabsGp">
                            <ul class="tabsGp__tabs">
                                <?php
                                for ($i = 1; $i <= count($array_lang); $i++) {
                                ?>
                                <li id="tabNav_<?php echo $i; ?>"
                                    class="tabsGp__link --color<?php echo $i; ?>"
                                    data-tab-target="tabCon_<?php echo $i; ?>">
                                    <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                </li>
                                <?php } ?>
                            </ul>
                            <div class="tabsGp__body">
                                <?php
                                for ($i = 1; $i <= count($array_lang); $i++) {
                                ?>
                                <div id="tabCon_<?php echo $i; ?>" class="tabContent --color<?php echo $i; ?>">
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="strName<?php echo $i; ?>">
                                            標題 <span class="inputLabel__required">*</span>
                                        </label>
                                        <div class="col--10">
                                            <input name="strName<?php echo $i; ?>" type="text"
                                                id="strName<?php echo $i; ?>" class="formInput"
                                                value="<?php echo e((string)($strName[$i] ?? '')); ?>">
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </article>

                        <?php
                        echo hiddenNumeric('Question_PKey', (string)$Question_PKey) . PHP_EOL;
                        if (!$isAdd) {
                            echo hiddenNumeric('PKey', (string)(int)($Update_PKey ?? 0)) . PHP_EOL;
                        }
                        echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
                        echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
                        require_once '../_submit.php';
                        ?>
                        </form>
                    </section>
                    <div class="notes__spacer"></div>
                </div>
                <?php require_once '../_footer.php'; ?>
            </main>
        </div>
    </div>

    <?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
