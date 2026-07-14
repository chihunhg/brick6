<?php
declare(strict_types=1);

$classLabel = (string)($Class_Name[1] ?? '類別');
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$showHomeField = manage_module_show_detail_field('home');
/** 歷史沿革固定有列表圖（不依賴 module_p 開關） */
$showListField = true;
$detailConfig = is_array($detailConfig ?? null) ? $detailConfig : (is_file(__DIR__ . '/_config.php') ? require __DIR__ . '/_config.php' : []);
$__imgSlotFallback = 1;
$__imgSlotImageOnly = true;
require dirname(__DIR__) . '/_detail_img_slot_init.php';
if (!$showListField) {
    $managePhotoSlotMax = 0;
    $managePhotoSlotStart = 0;
}
$PhotoS = is_array($PhotoS ?? null) ? $PhotoS : [];
$intYear = (int)($intYear ?? date('Y'));
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
		manage_photo_delete_slots_for_range($PhotoS, (int)$managePhotoSlotStart, (int)$managePhotoSlotMax)
	);
	} ?>
});

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

	var yearVal = ($('#intYear').val() || '').toString().trim();
	if (!/^\d{4}$/.test(yearVal)) {
		array.push('intYear');
		errors.push('年份須為四位數字');
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
                                    <label class="col--2 inputLabel editView__formLabel">
                                    顯示語系 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="button" type="button" class="btn btn-outline-secondary" value="全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="all">
                                        <input name="button2" type="button" class="btn btn-outline-secondary" value="取消全選"
                                            data-manage-action="class1-lang-select" data-lang-mode="none">
                                        <?php for ($i = 1; $i <= count($array_lang); $i++) { ?>
                                            <label for="Show<?php echo $i; ?>">
                                                <input name="Show<?php echo $i; ?>" type="checkbox" id="Show<?php echo $i; ?>" value="Y"<?php if (class1_lang_is_show_on($isShow[$i] ?? '')) { echo ' checked'; } ?>
                                                data-manage-action="class1-lang-toggle" data-lang-index="<?php echo $i; ?>" />
                                            <?php echo e((string)$array_lang[$i]); ?>
                                            </label>
                                        <?php } ?>
                                        <span id="Lang_txt" class="red"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="intYear">
                                        年份 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="intYear" id="intYear" type="number" inputmode="numeric"
                                            min="1900" max="2100" step="1" class="formInput editView__sortInput"
                                            value="<?php echo (int)$intYear; ?>" maxlength="4" autocomplete="off">
                                        <span id="intYear_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                                <?php if ($Layer > 1) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Class1"><?php echo e($classLabel); ?>名稱 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <select name="Class1" id="Class1" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php
                                            $sql = 'SELECT PKey, strName FROM dbclass1 WHERE Module_PKey = :Module_PKey ORDER BY Sort';
                                            $rs1 = new recordset($sql, ['Module_PKey' => (int)$Module_PKey]);
                                            while (!$rs1->eof) {
                                            ?>
                                            <option value="<?php echo (int)$rs1->field('PKey'); ?>"<?php if ((string)$Class1 === (string)$rs1->field('PKey')) { echo ' selected="selected"'; } ?>><?php echo e((string)$rs1->field('strName')); ?></option>
                                            <?php
                                            $rs1->movenext();
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <?php } ?>
                                <?php if ($showHomeField) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Home">首頁呈現</label>
                                    <div class="col--10">
                                    <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                    <input name="Home" type="checkbox" id="Home" value="Yes" <?php if (!empty($Home) && $Home === 'Yes') { echo 'checked="checked"'; } ?> />是，首頁區塊呈現 </label>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Upload">上下架</label>
                                    <div class="col--10">
                                        <select name="Upload" id="Upload" class="formSelect">
                                            <option value="Yes"<?php echo ($Upload ?? 'Yes') === 'Yes' ? ' selected' : ''; ?>>上架</option>
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
                                <h4 class="editView__sectionTitle">內容區塊</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">列表圖</label>
                                    <?php $n = 1; ?>
                                    <div class="col--10 inputGroup">
                                        <div class="uploadBox w--auto">
                                            <p class="inputLabel">圖片上傳</p>
                                            <?php
                                            $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                            ?>
                                            <div class="uploadBox__picBx">
                                                <img id="preview<?php echo $n; ?>" alt=""
                                                    style="max-width:150px;max-height:150px;"
                                                    <?php if ($photoPath !== '') { ?>
                                                    src="../../Upload/<?php echo e($photoPath); ?>?<?php echo time(); ?>"
                                                    <?php } ?>>
                                                <div id="size<?php echo $n; ?>"></div>
                                                <span id="Photo<?php echo $n; ?>_txt" class="red"></span>
                                                <?php if (manage_photo_slot_show_delete($isAdd, $photoPath)) {
                                                    manage_render_photo_delete_button($n);
                                                } ?>
                                            </div>
                                            <div class="uploadBox__fileBx">
                                                <label for="Photo<?php echo $n; ?>">
                                                    選擇檔案
                                                    <input name="Photo<?php echo $n; ?>" type="file" accept="image/jpeg,image/gif,image/png"
                                                        id="Photo<?php echo $n; ?>" size="30"
                                                        data-check-file="Photo<?php echo $n; ?>,2000,img">
                                                    <input name="intType<?php echo $n; ?>" type="hidden" id="intType<?php echo $n; ?>" value="1">
                                                </label>
                                            </div>
                                        </div>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <li>建議寬度 750px，高度不限。</li>
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
                                        <div class="col--10">
                                            <input name="strName<?php echo $i; ?>" type="text"
                                                id="strName<?php echo $i; ?>" class="formInput"
                                                value="<?php echo e((string)($strName[$i] ?? '')); ?>" maxlength="50">
                                        </div>
                                    </div>
                                    <?php require dirname(__DIR__) . '/_detail_lang_seo_fields.php'; ?>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel">內容</label>
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

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>前台顯示順序依「年份」由新至舊；同年份則以修改日期由新至舊。</li>
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
