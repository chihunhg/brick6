<?php

$classLabel = (string)($Class_Name[1] ?? '類別');
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$showHomeField = manage_module_show_detail_field('home');
$showInterviewField = manage_module_show_detail_field('interview');
$showListField = manage_module_show_detail_field('list');
$detailConfig = is_array($detailConfig ?? null) ? $detailConfig : (is_file(__DIR__ . '/_config.php') ? require __DIR__ . '/_config.php' : []);
$showSortField = (bool)($detailConfig['has_sort'] ?? true);
$showTagField = manage_module_show_detail_field('tag')
    && (string)($detailConfig['tag_relation_parent_col'] ?? '') !== '';
$tagRelations = is_array($tagRelations ?? null) ? $tagRelations : [];
$Tag_Total = (int)($Tag_Total ?? count($tagRelations));
$tagModulePKey = function_exists('tag_relation_resolve_module_pkey')
    ? tag_relation_resolve_module_pkey($detailConfig)
    : 0;
$__tagRelJs = __DIR__ . '/../js/tag-relation.js';
$__tagRelJsVer = is_file($__tagRelJs) ? (string)filemtime($__tagRelJs) : '1';
$__imgSlotFallback = 7;
require dirname(__DIR__) . '/_detail_img_slot_init.php';
$PhotoS = is_array($PhotoS ?? null) ? $PhotoS : [];
$PhotoM = is_array($PhotoM ?? null) ? $PhotoM : [];
$Ext = is_array($Ext ?? null) ? $Ext : [];
$show_type = (int)($show_type ?? 2);

?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
$(function() {
	is_show(<?php echo (int)$show_type; ?>);

	$('input[name="show_type"]').on('change', function() {
		is_show($(this).val());
	});

	<?php
    manage_echo_detail_img_slot_delete_scripts(
        $PhotoS,
        (int)$managePhotoSlotStart,
        (int)$manageImageSlotEnd,
        (int)$manageFileSlotFrom,
        (int)$managePhotoSlotMax
    );
	?>
	// jquery.maxlength 外掛在部分站台未部署，缺少時直接略過避免報錯
	if ($.fn && typeof $.fn.maxlength === 'function') {
	<?php if ($showInterviewField) { ?>
		var Total = parseInt($('#Total_lang').val(), 10) || 0;
		for (var i = 1; i <= Total; i++) {
			$('#Interview' + i).maxlength({
				maxCharacters: 400,
				slider: true
			});
		}
	<?php } ?>
	}
	$('#OpenDate').on('focus click', function() {
		$('#NoOpenDate2').prop('checked', true);
	});
	$('#EndDate').on('focus click', function() {
		$('#NoEndDate2').prop('checked', true);
	});
});

function newsSetSectionEnabled(selector, enabled) {
	var $el = $(selector);
	if (!$el.length) {
		return;
	}
	$el.find('input,select,textarea,button').prop('disabled', !enabled);
}

function is_show(type) {
	type = parseInt(type, 10) || 2;
	var totalLang = parseInt($('#Total_lang').val(), 10) || 0;
	var showImg = type === 2;
	var showLink = type === 1;
	var showContent = type === 2;

	$('#tr_img').toggle(showImg);
	newsSetSectionEnabled('#tr_img', showImg);

	for (var i = 1; i <= totalLang; i++) {
		$('#tr_strLinkUrl' + i).toggle(showLink);
		newsSetSectionEnabled('#tr_strLinkUrl' + i, showLink);

		$('#tr_Movielink' + i).toggle(showContent);
		newsSetSectionEnabled('#tr_Movielink' + i, showContent);

		for (var j = 1; j <= 6; j++) {
			$('#tr_contents' + j + i).toggle(showContent);
			newsSetSectionEnabled('#tr_contents' + j + i, showContent);
		}
	}
}


//偵測Form裡的各欄位----^_^
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
	var showType = parseInt($('input[name="show_type"]:checked').val(), 10) || 2;

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

	if ($('#strDate').length > 0) {
		var strDateVal = ($('#strDate').val() || '').trim();
		if (strDateVal === '') {
			errors.push('發佈日期空白');
			array.push('strDate');
		}
	}

	if ($('#OpenDate').val() === '' && $('#NoOpenDate2').prop('checked')) {
		errors.push('刊登日期空白');
		array.push('OpenDate');
	}

	if ($('#EndDate').val() === '' && $('#NoEndDate2').prop('checked')) {
		errors.push('下架日期空白');
		array.push('EndDate');
	}

	if ($('#NoEndDate2').prop('checked')) {
		var openVal = $('#OpenDate').val();
		var endVal = $('#EndDate').val();
		if (openVal && endVal) {
			var openD = new Date(String(openVal).replace(/-/g, '/'));
			var endD = new Date(String(endVal).replace(/-/g, '/'));
			if (!isNaN(openD.getTime()) && !isNaN(endD.getTime()) && endD < openD) {
				errors.push('下架日期必需大於刊登日期');
				array.push('EndDate');
			}
		}
	}

	for (var j = 1; j <= totalLang; j++) {
		if (!$('#Show' + j).prop('checked')) {
			continue;
		}
		if ($.trim($('#strName' + j).val()) === '') {
			array.push('strName' + j);
			errors.push('標題空白（語系 ' + j + '）');
			view.push(j);
		}
		if (showType === 1 && $.trim($('#strURL' + j).val()) === '') {
			array.push('strURL' + j);
			errors.push('連結空白（語系 ' + j + '）');
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
                            name="form1" id="form1" data-manage-validate="fieldCheck0"<?php
                            if ($showTagField) {
                                echo ' data-tag-relation-autocomplete="../ajax/tag_relation_autocomplete.php"'
                                    . ' data-tag-relation-del="../ajax/_del_tag_relation.php"'
                                    . ' data-tag-man-no="' . (int)$tagModulePKey . '"';
                            }
                            ?>>

                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"></ul>
                            </div>
                        </div>

                        <article class="editView__body accordionGp">
                            <div class="editView__section accordionBx" data-a11y="true">
                                <h4 class="editView__sectionTitle accordionBx__hd active" tabindex="0"
                                    role="button"
                                    aria-expanded="false"
                                    aria-controls="accordion-panel-1"
                                    id="accordion-header-1">基本設定</h4>
                                <div class="accordionBx__panel"
                                    id="accordion-panel-1"
                                    role="region"
                                    aria-labelledby="accordion-header-1"
                                    aria-hidden="true">
                                    <div class="inner">
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">
                                    顯示語系 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup row">
                                        <input name="button" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="all">
                                        <input name="button2" type="button" class="btnStyle btnStyle--sm btnStyle--outline" value="取消全選"
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
                                    <label class="col--2 inputLabel editView__formLabel">顯示方式</label>
                                    <div class="col--10 inputGroup row">
                                        <label class="editView__radioLabel">
                                            <input name="show_type" type="radio" value="2" <?php echo $show_type === 2 ? 'checked' : ''; ?> /> 內容
                                        </label>
                                        <label class="editView__radioLabel">
                                            <input name="show_type" type="radio" value="1" <?php echo $show_type === 1 ? 'checked' : ''; ?> /> 連結
                                        </label>
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
                                <?php manage_render_strdate_field($strDate ?? ''); ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">刊登日期 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10 inputGroup">
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input type="radio" name="NoOpenDate" id="NoOpenDate1" value="0" <?php if ((string)($NoOpenDate ?? '0') !== '1') { echo 'checked'; } ?>>
                                            <span class="editView__radioText">馬上刊登</span>
                                        </label>
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                        <input type="radio" name="NoOpenDate" id="NoOpenDate2" value="1" <?php if ((string)($NoOpenDate ?? '0') === '1') { echo 'checked'; } ?>>
                                            <input type="date" name="OpenDate" id="OpenDate" value="<?php echo e((string)($OpenDate ?? '')); ?>" class="formInput editView__dateInput">
                                        </label>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">下架日期 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10 inputGroup">
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input type="radio" name="NoEndDate" id="NoEndDate1" value="0" <?php if ((string)($NoEndDate ?? '0') !== '1') { echo 'checked'; } ?>>
                                            <span class="editView__radioText">永不下架</span>
                                        </label>
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                        <input type="radio" name="NoEndDate" id="NoEndDate2" value="1" <?php if ((string)($NoEndDate ?? '0') === '1') { echo 'checked'; } ?>>
                                            <input type="date" name="EndDate" id="EndDate" value="<?php echo e((string)($EndDate ?? '')); ?>" class="formInput editView__dateInput">
                                        </label>
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
                                <?php if ($Layer > 2): ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Upload"><?php echo $Class_Name[2]?>名稱</label>
                                    <div class="col--10">
                                        <select name="Class2" id="Class2" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php
                                            $class2Opts = function_exists('crud_fetch_class_options')
                                                ? crud_fetch_class_options(2, (int)$Module_PKey, (int)$Class1)
                                                : [];
                                            foreach ($class2Opts as $opt) {
                                                $optId = (int)($opt['PKey'] ?? 0);
                                                $optName = (string)($opt['strName'] ?? '');
                                            ?>
                                            <option value="<?php echo $optId; ?>"<?php if ((string)$Class2 === (string)$optId) { echo ' selected="selected"'; } ?>><?php echo e($optName); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($Layer > 3): ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Upload"><?php echo $Class_Name[3]?>名稱</label>
                                    <div class="col--10">
                                        <select name="Class3" id="Class3" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php
                                            $sql = 'Select PKey, strName From dbclass3 Where Class2_PKey= :Class2_PKey Order By Sort';
                                            $rs1 = new recordset($sql, ['Class2_PKey' => (int)$Class2]);
                                            while(! $rs1->eof){
                                            ?>
                                            <option value="<?php echo $rs1->field('PKey')?>" <?php if(strval($Class3)==strval($rs1->field('PKey'))) {echo "selected=\"selected\"";}?>><?php echo $rs1->field('strName')?></option>
                                            <?php
                                            $rs1->movenext();
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif;?>
                                <?php require_once '../_tag_relation_block.php'; ?>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body accordionGp">
                            <div class="editView__section accordionBx">
                                <h4 class="editView__sectionTitle accordionBx__hd active" tabindex="0"
                                    role="button"
                                    aria-expanded="false"
                                    aria-controls="accordion-panel-2"
                                    id="accordion-header-2">文章內容</h4>
                                <div class="accordionBx__panel"
                                    id="accordion-panel-2"
                                    role="region"
                                    aria-labelledby="accordion-header-2"
                                    aria-hidden="true">
                                    <div class="inner">
                                        <article class="editView__tabs tabsGp">
                                            <ul class="tabsGp__tabs">
                                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                                <li id="group2_tabNav_<?php echo $i; ?>"
                                                    class="tabsGp__link --color<?php echo $i; ?>"
                                                    data-tab-target="group2_tabCon_<?php echo $i; ?>">
                                                    <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                                </li>
                                                <?php } ?>
                                            </ul>
                                            <div class="tabsGp__body">
                                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                                <div id="group2_tabCon_<?php echo $i; ?>" class="tabContent --color<?php echo $i; ?>">
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
                                                    <?php if ($showInterviewField) { ?>
                                                    <div class="formGrid">
                                                        <label class="col--2 inputLabel editView__formLabel" for="Interview<?php echo $i; ?>">簡述</label>
                                                        <div class="col--10">
                                                            <textarea name="Interview<?php echo $i; ?>" id="Interview<?php echo $i; ?>"
                                                                class="formInput" style="height:100px"><?php echo e((string)($Interview[$i] ?? '')); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <?php } ?>
                                                    <div class="formGrid" id="tr_strLinkUrl<?php echo $i; ?>">
                                                        <label class="col--2 inputLabel editView__formLabel" for="strURL<?php echo $i; ?>">連結 <span class="inputLabel__required">*</span></label>
                                                        <div class="col--10 inputGroup">
                                                            <label class="editView__radioLabel">
                                                                <input name="intLink<?php echo $i; ?>" id="intLink<?php echo $i; ?>_page" type="radio" value="1"
                                                                    <?php echo (int)($intLink[$i] ?? 2) === 1 ? 'checked' : ''; ?> /> 本頁開啟 (_self)
                                                            </label>
                                                            <label class="editView__radioLabel">
                                                                <input name="intLink<?php echo $i; ?>" id="intLink<?php echo $i; ?>_ext" type="radio" value="2"
                                                                    <?php echo (int)($intLink[$i] ?? 2) !== 1 ? 'checked' : ''; ?> /> 另開視窗 (_blank)
                                                            </label>
                                                            <input name="strURL<?php echo $i; ?>" type="text" class="formInput" id="strURL<?php echo $i; ?>"
                                                                value="<?php echo e((string)($strURL[$i] ?? '')); ?>" maxlength="500">
                                                            <span id="strURL<?php echo $i; ?>_txt" class="input__errorTxt"></span>
                                                        </div>
                                                    </div>
                                                    <div class="formGrid" id="tr_Movielink<?php echo $i; ?>">
                                                        <label class="col--2 inputLabel editView__formLabel" for="Movielink<?php echo $i?>">
                                                            影音連結
                                                        </label>
                                                        <div class="col--10">
                                                            <span>https://www.youtube.com/watch?v=</span>
                                                            <input type="text" name="Movielink<?php echo $i?>"
                                                                id="Movielink<?php echo $i?>" class="formInput w--auto"
                                                                value="<?php echo e((string)($Movielink[$i] ?? '')); ?>">
                                                                <p> (影音連結例：https://www.youtube.com/watch?v=<span class="red">QEWV6fiYaDU</span>) </p>
                                                        </div>
                                                    </div>
                                                    <?php for($n=1;$n<=6;$n++){ ?>
                                                    <div class="formGrid" id="tr_contents<?php echo $n . $i; ?>">
                                                        <label class="col--2 inputLabel editView__formLabel">內容<?php echo $n?></label>
                                                        <div class="col--10">
                                                            <?php
                                                            $editorAiFieldId = 'Contents' . $n . '_' . $i;
                                                            require dirname(__DIR__) . '/_detail_ckeditor_ai_button.php';
                                                            ?>
                                                            <textarea name="Contents<?php echo $n.'_'.$i?>" id="Contents<?php echo $n.'_'.$i?>"
                                                                class="ckeditor formInput"><?php echo e_editor_html((string)($Contents[$n][$i] ?? '')); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <?php } ?>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body accordionGp" id="tr_img">
                            <div class="editView__section accordionBx">
                                <h4 class="editView__sectionTitle accordionBx__hd active" tabindex="0"
                                    role="button"
                                    aria-expanded="false"
                                    aria-controls="accordion-panel-3"
                                    id="accordion-header-3">圖片設定</h4>
                                <div class="accordionBx__panel"
                                    id="accordion-panel-3"
                                    role="region"
                                    aria-labelledby="accordion-header-3"
                                    aria-hidden="true">
                                    <div class="inner">
                                <?php if ($showListField) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">列表圖</label>
                                    <?php $n=1; ?>
                                    <div class="col--10 inputGroup">
                                        <?php
                                        $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                        manage_render_upload_image_slot($n, $isAdd, $photoPath, (int)($PhotoS[$n] ?? 0));
                                        ?>
                                        <label class="inputLabel" for="PhotoM<?php echo $n; ?>">圖說</label>
                                        <input name="PhotoM<?php echo $n; ?>" id="PhotoM<?php echo $n; ?>" type="text"
                                            class="formInput" maxlength="100"
                                            value="<?php echo e((string)($PhotoM[$n] ?? '')); ?>">
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <li>圖片：寬750px，高不限。</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <?php for ($n = 2; $n <= $managePhotoContentSlotEnd; $n++) { ?>
                                <div class="formGrid --picSetting">
                                    <label class="col--2 inputLabel editView__formLabel">內容<?php echo $n-1?></label>
                                    <div class="col--10 inputGroup">
                                        <div class="flex flex--itCenter gap--3">
                                            <span class="inputLabel">呈現方式：</span>
                                            <?php manage_render_content_layout_select($n); ?>
                                        </div>
                                        <?php
                                        $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                        manage_render_upload_image_slot(
                                            $n,
                                            $isAdd,
                                            $photoPath,
                                            (int)($PhotoS[$n] ?? 0),
                                            'img',
                                            2000,
                                            '圖片上傳(' . ($n - 1) . ')',
                                            false
                                        );
                                        ?>
                                        <label class="inputLabel" for="PhotoM<?php echo $n; ?>">圖說</label>
                                        <input name="PhotoM<?php echo $n; ?>" id="PhotoM<?php echo $n; ?>" type="text"
                                            class="formInput" maxlength="100"
                                            value="<?php echo e((string)($PhotoM[$n] ?? '')); ?>">
                                    </div>
                                </div>
                                <?php } ?>
                                <?php require dirname(__DIR__) . '/_detail_file_slots_section.php'; ?>
                                <div class="notes">
                                    <ul class="notes__list">
                                        <li>上圖下文：寬1140px，高不限。</li>
                                        <li>左圖右文：寬760px，高不限。</li>
                                        <li>右圖左文：寬760px，高不限。</li>
                                        <li>下圖上文：寬1140px，高不限。</li>
                                    </ul>
                                </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body accordionGp">
                            <div class="editView__section accordionBx">
                                <h4 class="editView__sectionTitle accordionBx__hd active" tabindex="0"
                                    role="button"
                                    aria-expanded="false"
                                    aria-controls="accordion-panel-4"
                                    id="accordion-header-4">SEO 設定</h4>
                                <div class="accordionBx__panel"
                                    id="accordion-panel-4"
                                    role="region"
                                    aria-labelledby="accordion-header-4"
                                    aria-hidden="true">
                                    <div class="inner">
                                        <article class="editView__tabs tabsGp">
                                            <ul class="tabsGp__tabs">
                                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                                <li id="group1_tabNav_<?php echo $i; ?>"
                                                    class="tabsGp__link --color<?php echo $i; ?>"
                                                    data-tab-target="group1_tabCon_<?php echo $i; ?>">
                                                    <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                                </li>
                                                <?php } ?>
                                            </ul>
                                            <div class="tabsGp__body">
                                                <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                                <div id="group1_tabCon_<?php echo $i; ?>" class="tabContent --color<?php echo $i; ?>">
                                                    <?php require dirname(__DIR__) . '/_detail_lang_seo_fields.php'; ?>
                                                    <?php require dirname(__DIR__) . '/_detail_lang_summary_field.php'; ?>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">上下架顯示</h4>
                                <?php if ($showHomeField) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Home">首頁呈現</label>
                                    <div class="col--10">
                                    <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                    <input name="Home" type="checkbox" id="Home" value="Yes" <?php if (!empty($Home) && $Home=='Yes'){ echo 'checked="checked"';}?> />是，首頁區塊呈現 </label>
                                    </div>
                                </div>
                                <?php } ?>
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

                        <?php require_once '../_submit.php'; ?>
                        </form>
                    </section>

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>網站前台顯示順序，依照「順序」由小至大排序；順序相同，依照「修改日期」由新至舊排序。</li>
                        </ul>
                    </section>
                    <div class="notes__spacer"></div>
                </div>
                <?php require_once '../_footer.php'; ?>
            </main>
        </div>
    </div>

    <?php require_once '../_in_code_bottom.php'; ?>
<?php echo script_open(); ?>
(function () {
	document.querySelectorAll('.tabsGp').forEach(function (group) {
		var first = group.querySelector('.tabsGp__link');
		var targetId = first ? first.getAttribute('data-tab-target') : '';
		if (!first || !targetId) {
			return;
		}
		first.classList.add('--active', 'active');
		var panel = group.querySelector('#' + targetId);
		if (panel) {
			panel.classList.add('--active', 'active');
		}
	});
})();
<?php echo script_close(); ?>
    <?php if ($showTagField) {
        echo script_src_tag('../js/tag-relation.js?ver=' . $__tagRelJsVer);
    } ?>
<?php echo script_open(); ?>
$(function() {
	<?php manage_echo_strdate_picker_init('strDate', $strDate ?? ''); ?>
});
function newsBindDetailTabs() {
	document.querySelectorAll('.tabsGp').forEach(function (group) {
		var navItems = group.querySelectorAll('.tabsGp__link');
		var contents = group.querySelectorAll('.tabContent');
		function activate(nav) {
			navItems.forEach(function (item) {
				item.classList.remove('--active', 'is-active', 'active', 'on');
			});
			contents.forEach(function (content) {
				content.classList.remove('--active', 'is-active', 'active', 'on');
			});
			nav.classList.add('--active', 'is-active', 'active', 'on');
			var targetId = nav.getAttribute('data-tab-target');
			var activeContent = targetId ? group.querySelector('#' + targetId) : null;
			if (activeContent) {
				activeContent.classList.add('--active', 'is-active', 'active', 'on');
			}
			window.setTimeout(function () {
				window.dispatchEvent(new Event('resize'));
			}, 0);
		}
		navItems.forEach(function (nav, index) {
			if (index === 0) {
				activate(nav);
			}
			nav.addEventListener('click', function () {
				activate(nav);
			});
		});
	});
	window.view_ans = function (num) {
		var n = parseInt(String(num || ''), 10);
		var tab = n ? document.getElementById('group2_tabNav_' + n) : null;
		if (tab) {
			tab.click();
		}
	};
	var newsForm = document.querySelector('form[action="addin.php"]');
	if (newsForm) {
		newsForm.addEventListener('submit', function () {
			newsForm.querySelectorAll('textarea[name^="Interview"]').forEach(function (el) {
				el.disabled = false;
			});
		});
	}
}
if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", newsBindDetailTabs);
} else {
	newsBindDetailTabs();
}
<?php echo script_close(); ?>
</body>
</html>
