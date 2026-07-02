<?php

$classLabel = (string)($Class_Name[1] ?? '類別');
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$showHomeField = manage_module_show_detail_field('home');
$showInterviewField = manage_module_show_detail_field('interview');
$showListField = manage_module_show_detail_field('list');
$detailConfig = is_array($detailConfig ?? null) ? $detailConfig : (is_file(__DIR__ . '/_config.php') ? require __DIR__ . '/_config.php' : []);
$__imgSlotFallback = 8;
require dirname(__DIR__) . '/_detail_img_slot_init.php';
$PhotoS = is_array($PhotoS ?? null) ? $PhotoS : [];
$Ext = is_array($Ext ?? null) ? $Ext : [];
$productRelations = is_array($productRelations ?? null) ? $productRelations : [];
$Accessory_Total = (int)($Accessory_Total ?? count($productRelations));
$__productRelJs = __DIR__ . '/../js/product-relation.js';
$__productRelJsVer = is_file($__productRelJs) ? (string)filemtime($__productRelJs) : '1';
$formFlashErrors = function_exists('manage_pull_form_flash_errors')
    ? manage_pull_form_flash_errors()
    : [];

?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
$(function() {
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
});


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
                            name="form1" id="form1" data-manage-validate="fieldCheck0"
                            data-man-no="<?php echo (int)($manNo ?? $GLOBALS['manNo'] ?? 0); ?>"
                            data-product-relation-autocomplete="product_relation_autocomplete.php"
                            data-product-relation-del="_del_relation.php"
                            data-product-relation-exclude-pkey="<?php
                            echo ($page_link ?? '') === 'update.php' ? (int)($Update_PKey ?? 0) : 0;
                            ?>">

                        <div class="errorArea<?php echo empty($formFlashErrors) ? ' is-hidden' : ''; ?>" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"><?php
                                foreach ($formFlashErrors as $flashMsg) {
                                    echo '<li>' . e((string)$flashMsg) . '</li>';
                                }
                                ?></ul>
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
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="strNo">
                                        型號</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="strNo" id="strNo" class="formInput"
                                            value="<?php echo e((string)($strNo ?? '')); ?>" maxlength="50" autocomplete="off">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="query">關聯產品</label>
                                    <div class="col--10">
                                        <div class="link-pd">
                                            <div class="enter">
                                                <input type="text" id="query" name="query" class="formInput"
                                                    placeholder="請輸入品名，選取品名完成新增動作。"
                                                    style="max-width:50%" autocomplete="off">
                                            </div>
                                            <div class="box">
                                                <ul id="item1_list">
                                                <?php
                                                $relIdx = 0;
                                                foreach ($productRelations as $rel) {
                                                    $relIdx++;
                                                    $rowPKey = (int)($rel['rowPKey'] ?? 0);
                                                    $targetPKey = (int)($rel['targetPKey'] ?? 0);
                                                    $relName = (string)($rel['strName'] ?? '');
                                                ?>
                                                    <li id="item1_<?php echo $relIdx; ?>">
                                                        <button type="button" class="link-pd__tag"
                                                            data-manage-action="product-relation-remove"
                                                            data-relation-index="<?php echo $relIdx; ?>"><?php echo e($relName); ?></button>
                                                        <input name="PKey<?php echo $relIdx; ?>" type="hidden"
                                                            id="PKey<?php echo $relIdx; ?>" value="<?php echo $rowPKey; ?>" />
                                                        <input name="Accessory_Name<?php echo $relIdx; ?>"
                                                            id="Accessory_Name<?php echo $relIdx; ?>" type="hidden"
                                                            value="<?php echo e($relName); ?>" />
                                                        <input name="Accessory<?php echo $relIdx; ?>"
                                                            id="Accessory<?php echo $relIdx; ?>" type="hidden"
                                                            value="<?php echo $targetPKey; ?>" />
                                                    </li>
                                                <?php } ?>
                                                </ul>
                                                <input name="Accessory_Total" type="hidden" id="Accessory_Total"
                                                    value="<?php echo $relIdx; ?>" />
                                            </div>
                                        </div>
                                        <p class="notes" style="margin-top:0.5rem">僅能關聯同單元且已上架的產品；儲存表單後寫入資料庫。</p>
                                    </div>
                                </div>
                                <?php if ($showHomeField) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Home">首頁呈現</label>
                                    <div class="col--10">
                                    <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                    <input name="Home" type="checkbox" id="Home" value="Yes" <?php if (!empty($Home) && $Home=='Yes'){ echo 'checked="checked"';}?> />是，首頁區塊呈現 </label>
                                    </div>
                                </div>
                                <?php } ?>
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
                                <h4 class="editView__sectionTitle">內容區塊</h4>
                                <?php if ($showListField) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">列表圖</label>
                                    <div class="col--10 inputGroup">
                                        <?php
                                        $n = 1;
                                        $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                        manage_render_upload_image_slot($n, $isAdd, $photoPath, (int)($PhotoS[$n] ?? 0));
                                        ?>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <li>圖片：寬750px，高不限。</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <?php if ($manageImageSlotEnd >= max(2, (int)$managePhotoSlotStart)) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">圖片</label>
                                    <div class="col--10 inputGroup">
                                        <?php for ($n = max(2, (int)$managePhotoSlotStart); $n <= $manageImageSlotEnd; $n++) {
                                            $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                            manage_render_upload_image_slot($n, $isAdd, $photoPath, (int)($PhotoS[$n] ?? 0));
                                        } ?>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <li>圖片：寬800px，高不限。</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <?php if ($manageFileSlotFrom <= $managePhotoSlotMax) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">檔案</label>
                                    <div class="col--10 inputGroup">
                                        <?php for ($n = $manageFileSlotFrom; $n <= $managePhotoSlotMax; $n++) {
                                            $filePath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                            $ext = (string)($Ext[$n] ?? manage_file_ext_from_path($filePath));
                                            manage_render_upload_document_slot(
                                                $n,
                                                $isAdd,
                                                $filePath,
                                                (int)($PhotoS[$n] ?? 0),
                                                $ext
                                            );
                                        } ?>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <?php echo $remark_file1; ?>
                                            </ul>
                                        </div>
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
                                    <?php require dirname(__DIR__) . '/_detail_lang_seo_fields.php'; ?>
                                    <?php if ($showInterviewField) { ?>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Interview<?php echo $i?>">簡述</label>
                                        <div class="col--10">
                                            <textarea name="Interview<?php echo $i?>" id="Interview<?php echo $i?>"
                                                class="formInput" style="height:100px"><?php echo e((string)($Interview[$i] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="formGrid">
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
                                    <div class="formGrid">
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
    <?php echo script_src_tag('../js/product-relation.js?ver=' . $__productRelJsVer); ?>
</body>
</html>
