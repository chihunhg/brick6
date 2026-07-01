<?php

$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;

?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
function login(theForm) {
  theForm.submit()
}

function fieldCheck0(theForm) {
	if (typeof loading === 'function') {
		loading(1);
	}
	var array = [];
	var errors = [];
	var view = [];
	var totalLang = parseInt($('#Total_lang').val(), 10) || 0;
	var lang = false;

	for (var i = 1; i <= totalLang; i++) {
		if ($('#Show' + i).prop('checked')) {
			lang = true;
			break;
		}
	}
	if (!lang) {
		array.push('Show1');
		errors.push('顯示語系請至少勾選一個');
		view.push(1);
	}

	if ($('#Sort').length > 0) {
		var sortEl = document.getElementById('Sort');
		var sortVal = (sortEl && sortEl.value ? sortEl.value : '').trim();
		if (!/^\d+$/.test(sortVal)) {
			array.push('Sort');
			errors.push('順序不是數字');
		}
	}

	if ($('#Class1').length > 0 && $('#Class1').val() === '') {
		errors.push('<?php echo e((string)($Class_Name[1] ?? '類別')); ?>名稱請選擇');
		array.push('Class1');
	}

	for (var j = 1; j <= totalLang; j++) {
		if ($('#Show' + j).prop('checked') && $.trim($('#strName' + j).val()) === '') {
			array.push('strName' + j);
			errors.push('標題空白（語系 ' + j + '）');
			view.push(j);
		}
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
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">
                                    顯示語系 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="button" type="button" class="btn btn-outline-secondary" value="全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="all">
                                        <input name="button2" type="button" class="btn btn-outline-secondary" value="取消全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="none">
                                        <?php for($i=1;$i<=count($array_lang);$i++){?>
                                            <label for="Show<?php echo $i?>">
                                                <input name="Show<?php echo $i?>" type="checkbox" id="Show<?php echo $i?>" value="Y"<?php if (class1_lang_is_show_on($isShow[$i] ?? '')) { echo ' checked'; } ?>
                                                data-manage-action="class1-lang-toggle" data-lang-index="<?php echo $i?>" />
                                            <?php echo $array_lang[$i]?>
                                            </label>
                                        <?php } ?>
                                        <span id="Lang_txt" class="red"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">
                                        順序 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="Sort" id="Sort" type="number" inputmode="numeric"
                                            min="0" step="1" class="formInput editView__sortInput"
                                            value="<?php echo $Sort; ?>" maxlength="4" autocomplete="off">
                                        <span id="Sort_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                                <?php if ($Layer > 1): ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Class1"><?php echo $Class_Name[1]?>名稱 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <select name="Class1" id="Class1" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php
                                            $sql = 'Select PKey, strName From dbclass1 Where Module_PKey= :Module_PKey Order By Sort';
                                            $rs1 = new recordset($sql, ['Module_PKey' => (int)$Module_PKey]);
                                            while(! $rs1->eof){
                                            ?>
                                            <option value="<?php echo $rs1->field('PKey')?>" <?php if(strval($Class1)==strval($rs1->field('PKey'))) {echo "selected=\"selected\"";}?>><?php echo $rs1->field('strName')?></option>
                                            <?php
                                            $rs1->movenext();
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif;?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Upload">上下架</label>
                                    <div class="col--10">
                                        <select name="Upload" id="Upload" class="formSelect">
                                            <option value="Yes"<?php echo $Upload === 'Yes' ? ' selected' : ''; ?>>上架</option>
                                            <option value="No"<?php echo $Upload === 'No' ? ' selected' : ''; ?>>下架</option>
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

                        <?php require_once '../_submit.php'; ?>
                        </form>
                    </section>

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>網站前台顯示順序，依照「順序」由小至大排序；順序相同，依照「修改日期」由新至舊排序。</li>
                            <li>各語系標題儲存於語系資料表；主檔標題同步為語系 1 的標題。</li>
                        </ul>
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
