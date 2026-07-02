<?php

if (!isset($detailConfig) && is_file(__DIR__ . '/_config.php')) {
    $detailConfig = require __DIR__ . '/_config.php';
}
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$showSortField = (bool)($detailConfig['has_sort'] ?? true);
$showInterviewField = manage_module_show_detail_field('interview');
$showListField = manage_module_show_detail_field('list');
$__imgSlotFallback = 1;
$__imgSlotImageOnly = true;
require dirname(__DIR__) . '/_detail_img_slot_init.php';
if (!$showListField) {
    $managePhotoSlotMax = 0;
    $managePhotoSlotStart = 0;
}
$PhotoS = is_array($PhotoS ?? null) ? $PhotoS : [];
$Contents = is_array($Contents ?? null) ? $Contents : [];

?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
$(function() {
	<?php if ($showListField) {
	manage_echo_photo_delete_init_script(
		manage_photo_delete_slots_for_range($PhotoS, 1, 1)
	);
	} ?>
	if ($.fn && typeof $.fn.maxlength === 'function' && <?php echo $showInterviewField ? 'true' : 'false'; ?>) {
		var Total = parseInt($('#Total_lang').val(), 10) || 0;
		for (var i = 1; i <= Total; i++) {
			$('#Interview' + i).maxlength({ maxCharacters: 500, slider: true });
		}
	}
});

function login(theForm) {
	theForm.submit();
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

	if ($.trim($('#EMail').val()) === '') {
		array.push('EMail');
		errors.push('收件信箱空白');
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

<body <?php if (!empty($bodytxt)) { echo $bodytxt; } ?>>
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
                                    <label class="col--2 inputLabel editView__formLabel">顯示語系 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10 inputGroup">
                                        <input type="button" class="btn btn-outline-secondary" value="全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="all">
                                        <input type="button" class="btn btn-outline-secondary" value="取消全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="none">
                                        <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                        <label for="Show<?php echo $i; ?>">
                                            <input name="Show<?php echo $i; ?>" type="checkbox" id="Show<?php echo $i; ?>" value="Y"
                                                <?php if (class1_lang_is_show_on($isShow[$i] ?? '')) { echo ' checked'; } ?>
                                                data-manage-action="class1-lang-toggle" data-lang-index="<?php echo $i; ?>" />
                                            <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                        </label>
                                        <?php } ?>
                                    </div>
                                </div>
                                <?php if ($showSortField) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">
                                        順序 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="Sort" id="Sort" type="number" inputmode="numeric"
                                            min="0" step="1" class="formInput editView__sortInput"
                                            value="<?php echo (int)($Sort ?? 0); ?>" maxlength="4" autocomplete="off">
                                        <span id="Sort_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="strNo">問卷代號</label>
                                    <div class="col--10">
                                        <input name="strNo" type="text" id="strNo" class="formInput" maxlength="50"
                                            value="<?php echo e((string)($strNo ?? '')); ?>">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="EMail">收件信箱 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <textarea name="EMail" id="EMail" class="formInput" style="height:100px"
                                            placeholder="多組信箱請用「;」分隔"><?php echo e((string)($EMail ?? '')); ?></textarea>
                                        <p class="notes__hint">多組信箱請用「;」分隔</p>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="OpenDate">開始日期</label>
                                    <div class="col--10">
                                        <input type="date" name="OpenDate" id="OpenDate" class="formInput editView__dateInput"
                                            value="<?php echo e((string)($OpenDate ?? '')); ?>">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="EndDate">結束日期</label>
                                    <div class="col--10">
                                        <input type="date" name="EndDate" id="EndDate" class="formInput editView__dateInput"
                                            value="<?php echo e((string)($EndDate ?? '')); ?>">
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

                        <?php if ($showListField) { ?>
                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">問卷圖檔</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">列表圖</label>
                                    <?php $n = 1; ?>
                                    <div class="col--10 inputGroup">
                                        <div class="uploadBox w--auto">
                                            <?php $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : ''; ?>
                                            <div class="uploadBox__picBx">
                                                <img id="preview<?php echo $n; ?>" alt=""
                                                    style="max-width:150px;max-height:150px;"
                                                    <?php if ($photoPath !== '') { ?>
                                                    src="../../Upload/<?php echo e($photoPath); ?>?<?php echo time(); ?>"
                                                    <?php } ?>>
                                                <div id="size<?php echo $n; ?>"></div>
                                                <?php if (manage_photo_slot_show_delete($isAdd, $photoPath)) {
                                                    manage_render_photo_delete_button($n);
                                                } ?>
                                            </div>
                                            <div class="uploadBox__fileBx">
                                                <label for="Photo<?php echo $n; ?>">
                                                    選擇檔案
                                                    <input name="Photo<?php echo $n; ?>" type="file" accept="image/jpeg,image/gif,image/png"
                                                        id="Photo<?php echo $n; ?>" data-check-file="Photo<?php echo $n; ?>,2000,img">
                                                    <input name="intType<?php echo $n; ?>" type="hidden" value="1">
                                                </label>
                                            </div>
                                        </div>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <li>問卷圖：寬1480px，高不限。</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php } ?>

                        <article class="editView__tabs tabsGp">
                            <ul class="tabsGp__tabs">
                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                <li id="tabNav_<?php echo $i; ?>" class="tabsGp__link --color<?php echo $i; ?><?php echo $i === 1 ? ' --active' : ''; ?>"
                                    data-tab-target="tabCon_<?php echo $i; ?>">
                                    <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                </li>
                                <?php } ?>
                            </ul>
                            <div class="tabsGp__body">
                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                <div id="tabCon_<?php echo $i; ?>" class="tabContent --color<?php echo $i; ?><?php echo $i === 1 ? ' --active' : ''; ?>">
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="strName<?php echo $i; ?>">
                                            標題 <span class="inputLabel__required">*</span>
                                        </label>
                                        <div class="col--10">
                                            <input name="strName<?php echo $i; ?>" type="text" id="strName<?php echo $i; ?>"
                                                class="formInput" maxlength="100"
                                                value="<?php echo e((string)($strName[$i] ?? '')); ?>">
                                        </div>
                                    </div>
                                    <?php
                                    $seoDescPlaceholder = '';
                                    require dirname(__DIR__) . '/_detail_lang_seo_fields.php';
                                    ?>
                                    <?php if ($showInterviewField) { ?>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Interview<?php echo $i; ?>">簡述</label>
                                        <div class="col--10">
                                            <textarea name="Interview<?php echo $i; ?>" id="Interview<?php echo $i; ?>"
                                                class="formInput" style="height:100px"><?php echo e((string)($Interview[$i] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Contents1_<?php echo $i; ?>">問卷介紹</label>
                                        <div class="col--10">
                                            <?php
                                            $editorAiFieldId = 'Contents1_' . $i;
                                            require dirname(__DIR__) . '/_detail_ckeditor_ai_button.php';
                                            ?>
                                            <textarea name="Contents1_<?php echo $i; ?>" id="Contents1_<?php echo $i; ?>"
                                                class="ckeditor formInput"><?php echo e_editor_html((string)($Contents[1][$i] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </article>

                        <?php require_once '../_submit.php'; ?>
                        </form>
                    </section>
                </div>
                <?php require_once '../_footer.php'; ?>
            </main>
        </div>
    </div>

    <?php require_once '../_in_code_bottom.php'; ?>
<?php echo script_open(); ?>
$(function() {
	function questionCkeditorIds() {
		var ids = [];
		document.querySelectorAll('textarea.ckeditor[id^="Contents1_"]').forEach(function(el) {
			if (el.id) {
				ids.push(el.id);
			}
		});
		return ids;
	}
	function questionDestroyCkeditors() {
		if (typeof CKEDITOR === 'undefined') {
			return;
		}
		questionCkeditorIds().forEach(function(id) {
			if (CKEDITOR.instances[id]) {
				CKEDITOR.instances[id].destroy(true);
			}
		});
	}
	function questionInitCkeditor(id) {
		if (typeof CKEDITOR === 'undefined' || !id) {
			return;
		}
		var el = document.getElementById(id);
		if (!el) {
			return;
		}
		if (CKEDITOR.instances[id]) {
			CKEDITOR.instances[id].resize();
			return;
		}
		CKEDITOR.replace(id);
	}
	function questionInitActiveTabCkeditor() {
		var $active = $('.tabsGp__tabs li.--active').first();
		var tabId = ($active.attr('id') || '').replace('tabNav_', '');
		if (tabId) {
			questionInitCkeditor('Contents1_' + tabId);
		}
	}
	setTimeout(function() {
		questionDestroyCkeditors();
		questionInitActiveTabCkeditor();
	}, 120);
	$('.tabsGp__tabs li').on('click', function() {
		var tabId = (this.id || '').replace('tabNav_', '');
		setTimeout(function() {
			questionInitCkeditor('Contents1_' + tabId);
		}, 60);
	});
});
<?php echo script_close(); ?>
</body>
</html>
