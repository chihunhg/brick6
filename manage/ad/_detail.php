<?php
declare(strict_types=1);

if (!function_exists('ad_lang_is_show_on')) {
    /** 廣告語系「顯示」勾選值是否為開啟（y/yes/1/true/on） */
    function ad_lang_is_show_on($value): bool {
        $v = strtolower(trim((string)$value));
        return in_array($v, ['y', 'yes', '1', 'true', 'on'], true);
    }
}

$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
if (!isset($langIsShow) && is_array($isShow ?? null)) {
    $langIsShow = $isShow;
}
$langIsShow = is_array($langIsShow ?? null) ? $langIsShow : [];
$strName = is_array($strName ?? null) ? $strName : [];
$Subject = is_array($Subject ?? null) ? $Subject : [];
$Photo = is_array($Photo ?? null) ? $Photo : [];
$PhotoS = is_array($PhotoS ?? null) ? $PhotoS : [];
$Sort = $Sort ?? '';
$Upload = (string)($Upload ?? 'Yes');
$strLink = (string)($strLink ?? '');
$Target = (string)($Target ?? '');
if ($Target === '') {
    $Target = '_blank';
}
$presentMode = (int)($presentMode ?? 1);
if ($presentMode !== 2) {
    $presentMode = 1;
}
$Movielink = (string)($Movielink ?? '');
$remark_pic = (string)($remark_pic ?? '');
$adShowImages = $presentMode !== 2;
$adShowVideo = $presentMode === 2;
$adImageSlots = [
    1 => [
        'label'    => '桌機圖片',
        'required' => true,
        'hints'    => ['圖片建議尺寸：寬1900px × 高900px。'],
    ],
    2 => [
        'label'    => '手機圖片',
        'required' => false,
        'hints'    => ['圖片建議尺寸：寬640px，高不限。'],
    ],
];
$managePhotoSlotMax = max(array_keys($adImageSlots));
?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
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

	for (var j = 1; j <= totalLang; j++) {
		if ($('#Show' + j).prop('checked') && $.trim($('#strName' + j).val()) === '') {
			array.push('strName' + j);
			errors.push('標題空白（語系 ' + j + '）');
			view.push(j);
		}
	}

	var presentMode = adPresentMode();
	if (presentMode === 2) {
		if ($.trim($('#Movielink').val()) === '') {
			array.push('Movielink');
			errors.push('影音連結空白');
		}
	} else {
		var prev1 = document.getElementById('preview1');
		var file1 = document.getElementById('Photo1');
		var hasPreview1 = !!(prev1 && prev1.getAttribute('src'));
		var hasFile1 = !!(file1 && file1.value);
		if (!hasPreview1 && !hasFile1) {
			array.push('Photo1');
			errors.push('請選擇桌機圖片');
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

function adPresentMode() {
	var checked = document.querySelector('input[name="isShow"]:checked');
	return checked ? parseInt(checked.value, 10) : 1;
}

function adTogglePresentMode() {
	var mode = adPresentMode();
	var imgSection = document.getElementById('adSectionImages');
	var videoSection = document.getElementById('adSectionVideo');
	if (imgSection) {
		imgSection.classList.toggle('is-hidden', mode === 2);
	}
	if (videoSection) {
		videoSection.classList.toggle('is-hidden', mode !== 2);
	}
}

$(function () {
	adTogglePresentMode();
	$('input[name="isShow"]').on('change', adTogglePresentMode);

	<?php manage_echo_photo_delete_init_script(
		manage_photo_delete_slots_for_range($PhotoS, 1, (int)$managePhotoSlotMax)
	); ?>
});
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
                            name="form1" id="form1" novalidate data-manage-validate="fieldCheck0">

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
                                    <label class="col--2 inputLabel editView__formLabel">
                                        顯示語系 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup row">
                                        <input name="button" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="all">
                                        <input name="button2" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="取消全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="none">
                                        <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                            <label for="Show<?php echo $i; ?>">
                                                <input name="Show<?php echo $i; ?>" type="checkbox" id="Show<?php echo $i; ?>" value="Y"<?php if (ad_lang_is_show_on($langIsShow[$i] ?? '')) {
                                                    echo ' checked';
                                                } ?>
                                                data-manage-action="class1-lang-toggle" data-lang-index="<?php echo $i; ?>" />
                                                <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                            </label>
                                        <?php } ?>
                                        <span id="Lang_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">
                                        順序 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="Sort" id="Sort" type="number" inputmode="numeric"
                                            min="0" step="1" class="formInput editView__sortInput"
                                            value="<?php echo e((string)$Sort); ?>" maxlength="4" autocomplete="off">
                                        <span id="Sort_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
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

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">連結與呈現</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="strLink">按鈕連結</label>
                                    <div class="col--10 inputGroup">
                                        <input name="strLink" type="text" id="strLink" class="formInput"
                                            value="<?php echo e($strLink); ?>" maxlength="255">
                                        <div class="inputGroup inputGroup--inline row gap--2" style="margin-top:0.5rem;">
                                            <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                                <input name="Target" type="radio" id="Target" value="_blank"<?php echo ($Target === '' || $Target === '_blank') ? ' checked' : ''; ?>>
                                                <span class="editView__radioText">另開視窗 (_blank)</span>
                                            </label>
                                            <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                                <input name="Target" type="radio" id="Target2" value="_self"<?php echo ($Target !== '' && $Target !== '_blank') ? ' checked' : ''; ?>>
                                                <span class="editView__radioText">本頁開啟 (_self)</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">呈現方式</label>
                                    <div class="col--10 inputGroup row gap--2">
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input name="isShow" type="radio" id="adPresent1" value="1"<?php echo $presentMode === 1 ? ' checked' : ''; ?>>
                                            <span class="editView__radioText">圖檔</span>
                                        </label>
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input name="isShow" type="radio" id="adPresent2" value="2"<?php echo $presentMode === 2 ? ' checked' : ''; ?>>
                                            <span class="editView__radioText">影音</span>
                                        </label>
                                    </div>
                                </div>
                                <div id="adSectionVideo" class="formGrid<?php echo $adShowVideo ? '' : ' is-hidden'; ?>">
                                    <label class="col--2 inputLabel editView__formLabel" for="Movielink">影音連結</label>
                                    <div class="col--10 inputGroup">
                                        <span class="formHint">https://www.youtube.com/watch?v=</span>
                                        <input name="Movielink" type="text" id="Movielink" class="formInput w--auto"
                                            value="<?php echo e($Movielink); ?>" maxlength="20" autocomplete="off">
                                        <span id="Movielink_txt" class="input__errorTxt"></span>
                                        <p class="notes" style="margin-top:0.5rem;">
                                            Youtube 連結例：https://www.youtube.com/watch?v=<span class="red">m6CJ4VAjO-0</span>（僅填 v= 後面的代碼）
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article id="adSectionImages" class="editView__body<?php echo $adShowImages ? '' : ' is-hidden'; ?>">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">圖片設定</h4>
                                <?php
                                foreach ($adImageSlots as $n => $slotInfo) {
                                    $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                    ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">
                                        <?php echo e($slotInfo['label']); ?>
                                        <?php if (!empty($slotInfo['required'])) { ?>
                                        <span class="inputLabel__required">*</span>
                                        <?php } ?>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <div class="uploadBox w--auto">
                                            <p class="inputLabel">圖片上傳</p>
                                            <div class="uploadBox__picBx">
                                                <img id="preview<?php echo $n; ?>" alt=""
                                                    style="max-width:150px;max-height:150px;"
                                                    <?php if ($photoPath !== '') { ?>
                                                    src="../../Upload/<?php echo e($photoPath); ?>?<?php echo time(); ?>"
                                                    <?php } ?>>
                                                <div id="size<?php echo $n; ?>"></div>
                                                <span id="Photo<?php echo $n; ?>_txt" class="input__errorTxt"></span>
                                                <?php if ($photoPath !== '') { ?>
                                                <?php manage_render_photo_delete_button($n); ?>
                                                <?php } ?>
                                            </div>
                                            <div class="uploadBox__fileBx">
                                                <label for="Photo<?php echo $n; ?>">
                                                    選擇檔案
                                                    <input name="Photo<?php echo $n; ?>" type="file" accept="image/jpeg,image/gif,image/png"
                                                        id="Photo<?php echo $n; ?>" size="30"
                                                        data-check-file="Photo<?php echo $n; ?>,6000,img">
                                                    <input name="intType<?php echo $n; ?>" type="hidden" id="intType<?php echo $n; ?>" value="1">
                                                </label>
                                            </div>
                                        </div>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <?php foreach ($slotInfo['hints'] as $hint) { ?>
                                                <li><?php echo e($hint); ?></li>
                                                <?php } ?>
                                                <?php if ($n === 1 && $remark_pic !== '') {
                                                    echo $remark_pic;
                                                } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </article>

                        <article class="editView__tabs tabsGp">
                            <ul class="tabsGp__tabs">
                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                <li id="tabNav_<?php echo $i; ?>"
                                    class="tabsGp__link --color<?php echo $i; ?>"
                                    data-tab-target="tabCon_<?php echo $i; ?>">
                                    <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                </li>
                                <?php } ?>
                            </ul>
                            <div class="tabsGp__body">
                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                <div id="tabCon_<?php echo $i; ?>" class="tabContent --color<?php echo $i; ?>">
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="strName<?php echo $i; ?>">
                                            標題 <span class="inputLabel__required">*</span>
                                        </label>
                                        <div class="col--10 inputGroup">
                                            <input name="strName<?php echo $i; ?>" type="text"
                                                id="strName<?php echo $i; ?>" class="formInput"
                                                value="<?php echo e((string)($strName[$i] ?? '')); ?>">
                                            <span id="strName<?php echo $i; ?>_txt" class="input__errorTxt"></span>
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Subject<?php echo $i; ?>">
                                            副標
                                        </label>
                                        <div class="col--10">
                                            <input name="Subject<?php echo $i; ?>" type="text"
                                                id="Subject<?php echo $i; ?>" class="formInput"
                                                value="<?php echo e((string)($Subject[$i] ?? '')); ?>">
                                            <span id="Subject<?php echo $i; ?>_txt" class="input__errorTxt"></span>
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
                            <li>呈現方式為「圖檔」時，桌機圖片必填；為「影音」時須填寫 Youtube 影片代碼。</li>
                            <li>手機圖片為選填；編輯時若已有圖片可不必重新選擇。</li>
                            <li>按鈕連結可搭配「另開視窗」或「本頁開啟」設定。</li>
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
