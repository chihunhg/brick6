<?php

$classLabel = (string)($Class_Name[1] ?? '類別');
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$langTotal = investor_lang_count();
$PhotoS = is_array($PhotoS ?? null) ? $PhotoS : [];
$Ext = is_array($Ext ?? null) ? $Ext : [];
$show_type = (int)($show_type ?? 2);
$show_year = (int)($show_year ?? 0);
$year = (int)($year ?? 0);
$managePhotoContentSlotEnd = 7;
$managePhotoSlotMax = 10;

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

    $('#Class2').on('change', function() {
        investorFetchShowYear();
        investorFetchSort();
    });
    $('#Class1, #Class3, #year').on('change', investorFetchSort);

    <?php if ((int)($show_year ?? 0) === 1) { ?>
    $('#tr_year').show();
    <?php } else { ?>
    $('#tr_year').hide();
    <?php } ?>

    var fileMaxKB = 20000;
    var fileAccept = '.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar';
    for (var t = 1; t <= Math.min(3, <?php echo (int)$langTotal; ?>); t++) {
        (function(i) {
            var slot = <?php echo json_encode([
                1 => investor_file_slot_for_lang(1),
                2 => investor_file_slot_for_lang(2),
                3 => investor_file_slot_for_lang(3),
            ], JSON_UNESCAPED_UNICODE); ?>[i];
            if (!slot) return;
            $('#intFileLink' + i + '_1, #intFileLink' + i + '_2').on('change', function() {
                investorToggleFileInputs(i, slot);
            });
            var inputEl = document.getElementById('Photo' + slot);
            if (inputEl) {
                inputEl.setAttribute('accept', fileAccept);
            }
            $('#Photo' + slot).on('change', function() {
                if (typeof window.checkFile === 'function') {
                    window.checkFile('Photo' + slot, fileMaxKB, 'file');
                }
            });
            investorToggleFileInputs(i, slot);
        })(t);
    }

    <?php
    manage_echo_photo_delete_init_script(
        manage_photo_delete_slots_for_range($PhotoS, 2, (int)$managePhotoContentSlotEnd)
    );
    $fileDeleteSlots = [];
    for ($fs = 8; $fs <= 10; $fs++) {
        if (!empty($PhotoS[$fs])) {
            $fileDeleteSlots[$fs] = (int)$PhotoS[$fs];
        }
    }
    manage_echo_photo_delete_init_script($fileDeleteSlots, 'file');
    ?>
});

function investorSetSectionEnabled(selector, enabled) {
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
    var showFile = type === 3;

    $('#tr_img').toggle(showImg);
    investorSetSectionEnabled('#tr_img', showImg);

    for (var i = 1; i <= totalLang; i++) {
        $('#tr_strLinkUrl' + i).toggle(showLink);
        investorSetSectionEnabled('#tr_strLinkUrl' + i, showLink);

        $('#tr_Movielink' + i).toggle(showContent);
        investorSetSectionEnabled('#tr_Movielink' + i, showContent);

        $('#tr_file' + i).toggle(showFile);
        investorSetSectionEnabled('#tr_file' + i, showFile);

        for (var j = 1; j <= 6; j++) {
            $('#tr_contents' + j + i).toggle(showContent);
            investorSetSectionEnabled('#tr_contents' + j + i, showContent);
        }
    }
}

function investorFetchShowYear() {
    var class2 = $('#Class2').val() || 0;
    if (!class2) {
        $('#show_year').val(0);
        $('#tr_year').hide();
        return;
    }
    $.post('../ajax/get_c2_field.php', { Class2: class2 }, function(reData) {
        var showYear = parseInt(reData, 10) || 0;
        $('#show_year').val(showYear);
        $('#tr_year').toggle(showYear === 1);
    });
}

function investorFetchSort() {
    var class1 = $('#Class1').val() || 0;
    if (!class1) return;
    var data = {
        Module_PKey: <?php echo (int)($Module_PKey ?? 0); ?>,
        Class1: class1,
        year: $('#year').val() || 0
    };
    <?php if ((int)($Layer ?? 1) > 2) { ?>data.Class2 = $('#Class2').val() || 0;<?php } ?>
    <?php if ((int)($Layer ?? 1) > 3) { ?>data.Class3 = $('#Class3').val() || 0;<?php } ?>
    $.post('../ajax/get_investor_sort.php', data, function(sText) {
        var sort = parseInt(sText, 10);
        if (sort > 0) {
            $('#Sort').val(sort);
        }
    });
}

function investorToggleFileInputs(langIdx, slot) {
    var isLink = $('#intFileLink' + langIdx + '_1').prop('checked');
    $('#strLink' + langIdx).prop('disabled', !isLink);
    $('#Photo' + slot).prop('disabled', isLink);
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

    if ($('#Class1').length > 0 && $('#Class1').val() === '') {
        errors.push('<?php echo e((string)($Class_Name[1] ?? '類別')); ?>名稱請選擇');
        array.push('Class1');
    }

    if ($('#show_year').val() === '1') {
        var yearVal = ($('#year').val() || '').trim();
        if (!/^\d{4}$/.test(yearVal)) {
            errors.push('年度請輸入 4 位數字');
            array.push('year');
        }
    }

    var sortVal = ($('#Sort').val() || '').trim();
    if (!/^\d+$/.test(sortVal)) {
        errors.push('順序不是數字');
        array.push('Sort');
    }

    var fileSlots = <?php echo json_encode([
        1 => investor_file_slot_for_lang(1),
        2 => investor_file_slot_for_lang(2),
        3 => investor_file_slot_for_lang(3),
    ], JSON_UNESCAPED_UNICODE); ?>;

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
        if (showType === 3) {
            if ($('#intFileLink' + j + '_1').prop('checked') && $.trim($('#strLink' + j).val()) === '') {
                array.push('strLink' + j);
                errors.push('連結路徑空白（語系 ' + j + '）');
                view.push(j);
            }
            if ($('#intFileLink' + j + '_2').prop('checked')) {
                var slot = fileSlots[j] || 0;
                var hasPreview = slot && ($('#prefile' + slot).text() !== '' || ($('#preview' + slot).attr('src') || '') !== '');
                var fileEl = slot ? document.getElementById('Photo' + slot) : null;
                var hasFile = fileEl && fileEl.files && fileEl.files.length > 0;
                if (!hasPreview && !hasFile) {
                    if (slot) array.push('Photo' + slot);
                    errors.push('請選擇上傳檔案（語系 ' + j + '）');
                    view.push(j);
                }
            }
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
                                        <?php for ($i = 1; $i <= $langTotal; $i++) { ?>
                                        <label for="Show<?php echo $i; ?>">
                                            <input name="Show<?php echo $i; ?>" type="checkbox" id="Show<?php echo $i; ?>" value="Y"
                                                <?php if (class1_lang_is_show_on($isShow[$i] ?? '')) { echo ' checked'; } ?>
                                                data-manage-action="class1-lang-toggle" data-lang-index="<?php echo $i; ?>" />
                                            <?php echo e($array_lang[$i] ?? ('語系' . $i)); ?>
                                        </label>
                                        <?php } ?>
                                        <span id="Lang_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>

                                <?php if ((int)($Layer ?? 1) <= 2) { ?>
                                <input name="show_year" type="hidden" id="show_year" value="<?php echo (int)$show_year; ?>">
                                <?php } ?>

                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">顯示方式</label>
                                    <div class="col--10 inputGroup">
                                        <label class="editView__radioLabel">
                                            <input name="show_type" type="radio" value="2" <?php echo $show_type === 2 ? 'checked' : ''; ?> /> 內容
                                        </label>
                                        <label class="editView__radioLabel">
                                            <input name="show_type" type="radio" value="1" <?php echo $show_type === 1 ? 'checked' : ''; ?> /> 連結
                                        </label>
                                        <label class="editView__radioLabel">
                                            <input name="show_type" type="radio" value="3" <?php echo $show_type === 3 ? 'checked' : ''; ?> /> 檔案
                                        </label>
                                    </div>
                                </div>

                                <?php if ((int)($Layer ?? 1) > 1) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Class1"><?php echo e($Class_Name[1] ?? '類別'); ?> <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <select name="Class1" id="Class1" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php
                                            $sql = 'SELECT PKey, strName FROM dbclass1 WHERE Module_PKey = :Module_PKey ORDER BY Sort';
                                            $rs1 = new recordset($sql, ['Module_PKey' => (int)$Module_PKey]);
                                            while (!$rs1->eof) {
                                            ?>
                                            <option value="<?php echo (int)$rs1->field('PKey'); ?>"<?php if ((string)$Class1 === (string)$rs1->field('PKey')) { echo ' selected'; } ?>><?php echo e($rs1->field('strName')); ?></option>
                                            <?php $rs1->movenext(); } ?>
                                        </select>
                                    </div>
                                </div>
                                <?php } ?>

                                <?php if ((int)($Layer ?? 1) > 2) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Class2"><?php echo e($Class_Name[2] ?? '類別2'); ?></label>
                                    <div class="col--10">
                                        <select name="Class2" id="Class2" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php
                                            $class2Opts = function_exists('crud_fetch_class_options')
                                                ? crud_fetch_class_options(2, (int)$Module_PKey, (int)$Class1)
                                                : [];
                                            foreach ($class2Opts as $opt) {
                                                $optId = (int)($opt['PKey'] ?? 0);
                                            ?>
                                            <option value="<?php echo $optId; ?>"<?php if ((string)$Class2 === (string)$optId) { echo ' selected'; } ?>><?php echo e((string)($opt['strName'] ?? '')); ?></option>
                                            <?php } ?>
                                        </select>
                                        <input name="show_year" type="hidden" id="show_year" value="<?php echo (int)$show_year; ?>">
                                    </div>
                                </div>
                                <?php } ?>

                                <?php if ((int)($Layer ?? 1) > 3) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Class3"><?php echo e($Class_Name[3] ?? '類別3'); ?></label>
                                    <div class="col--10">
                                        <select name="Class3" id="Class3" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php
                                            $sql = 'SELECT PKey, strName FROM dbclass3 WHERE Class2_PKey = :Class2_PKey ORDER BY Sort';
                                            $rs1 = new recordset($sql, ['Class2_PKey' => (int)$Class2]);
                                            while (!$rs1->eof) {
                                            ?>
                                            <option value="<?php echo (int)$rs1->field('PKey'); ?>"<?php if ((string)$Class3 === (string)$rs1->field('PKey')) { echo ' selected'; } ?>><?php echo e($rs1->field('strName')); ?></option>
                                            <?php $rs1->movenext(); } ?>
                                        </select>
                                    </div>
                                </div>
                                <?php } ?>

                                <div class="formGrid" id="tr_year">
                                    <label class="col--2 inputLabel editView__formLabel" for="year">年度 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="year" type="text" id="year" class="formInput editView__sortInput"
                                            value="<?php echo $year > 0 ? (int)$year : ''; ?>" maxlength="4" inputmode="numeric">
                                        <span id="year_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>

                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">順序 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="Sort" id="Sort" type="number" inputmode="numeric" min="0" step="1"
                                            class="formInput editView__sortInput" value="<?php echo (int)$Sort; ?>" maxlength="4">
                                        <span id="Sort_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>

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

                        <article class="editView__body" id="tr_img">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">內容圖（顯示方式：內容）</h4>
                                <?php for ($n = 2; $n <= $managePhotoContentSlotEnd; $n++) {
                                    $photoPath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                    $labelNum = $n - 1;
                                ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">內容<?php echo $labelNum; ?>圖</label>
                                    <div class="col--10 inputGroup">
                                        <div class="uploadBox w--auto">
                                            <div class="uploadBox__picBx">
                                                <img id="preview<?php echo $n; ?>" alt=""
                                                    style="max-width:150px;max-height:150px;"
                                                    <?php if ($photoPath !== '') { ?>
                                                    src="../../Upload/<?php echo e($photoPath); ?>?<?php echo time(); ?>"
                                                    <?php } ?>>
                                                <div id="size<?php echo $n; ?>"></div>
                                                <span id="Photo<?php echo $n; ?>_txt" class="input__errorTxt"></span>
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
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="notes">
                                    <ul class="notes__list">
                                        <li>內容一圖：高 1200px，寬不限。</li>
                                        <li>內容二～五圖：高 750px，寬 480px。</li>
                                        <li>內容六圖：高 1200px，寬不限。</li>
                                    </ul>
                                </div>
                            </div>
                        </article>

                        <article class="editView__tabs tabsGp">
                            <ul class="tabsGp__tabs">
                                <?php for ($i = 1; $i <= $langTotal; $i++) { ?>
                                <li id="tabNav_<?php echo $i; ?>" class="tabsGp__link --color<?php echo $i; ?>"
                                    data-tab-target="tabCon_<?php echo $i; ?>"><?php echo e((string)($array_lang[$i] ?? '')); ?></li>
                                <?php } ?>
                            </ul>
                            <div class="tabsGp__body">
                                <?php for ($i = 1; $i <= $langTotal; $i++) { ?>
                                <div id="tabCon_<?php echo $i; ?>" class="tabContent --color<?php echo $i; ?>">
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="strName<?php echo $i; ?>">標題 <span class="inputLabel__required">*</span></label>
                                        <div class="col--10">
                                            <input name="strName<?php echo $i; ?>" type="text" class="formInput" id="strName<?php echo $i; ?>"
                                                value="<?php echo e((string)($strName[$i] ?? '')); ?>" maxlength="300">
                                            <span id="strName<?php echo $i; ?>_txt" class="input__errorTxt"></span>
                                        </div>
                                    </div>

                                    <div class="formGrid" id="tr_strLinkUrl<?php echo $i; ?>">
                                        <label class="col--2 inputLabel editView__formLabel" for="strURL<?php echo $i; ?>">連結 <span class="inputLabel__required">*</span></label>
                                        <div class="col--10 inputGroup">
                                            <label class="editView__radioLabel">
                                                <input name="intLink<?php echo $i; ?>" id="intLink<?php echo $i; ?>_page" type="radio" value="1"
                                                    <?php echo (int)($intLink[$i] ?? 2) === 1 ? 'checked' : ''; ?> /> 本頁
                                            </label>
                                            <label class="editView__radioLabel">
                                                <input name="intLink<?php echo $i; ?>" id="intLink<?php echo $i; ?>_ext" type="radio" value="2"
                                                    <?php echo (int)($intLink[$i] ?? 2) !== 1 ? 'checked' : ''; ?> /> 外連
                                            </label>
                                            <input name="strURL<?php echo $i; ?>" type="text" class="formInput" id="strURL<?php echo $i; ?>"
                                                value="<?php echo e((string)($strURL[$i] ?? '')); ?>" maxlength="500">
                                            <span id="strURL<?php echo $i; ?>_txt" class="input__errorTxt"></span>
                                        </div>
                                    </div>

                                    <?php for ($b = 1; $b <= 6; $b++) { ?>
                                    <div class="formGrid" id="tr_contents<?php echo $b . $i; ?>">
                                        <label class="col--2 inputLabel editView__formLabel">內容<?php echo $b; ?></label>
                                        <div class="col--10">
                                            <?php
                                            $editorAiFieldId = 'Contents' . $b . '_' . $i;
                                            require dirname(__DIR__) . '/_detail_ckeditor_ai_button.php';
                                            ?>
                                            <textarea name="Contents<?php echo $b; ?>_<?php echo $i; ?>" id="Contents<?php echo $b; ?>_<?php echo $i; ?>"
                                                class="ckeditor m-textarea"><?php echo (string)($Contents[$b][$i] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <?php } ?>

                                    <div class="formGrid" id="tr_Movielink<?php echo $i; ?>">
                                        <label class="col--2 inputLabel editView__formLabel" for="Movielink<?php echo $i; ?>">影音連結</label>
                                        <div class="col--10">
                                            <span>https://www.youtube.com/watch?v=</span>
                                            <input type="text" name="Movielink<?php echo $i?>"
                                                id="Movielink<?php echo $i?>" class="formInput w--auto"
                                                value="<?php echo e((string)($Movielink[$i] ?? '')); ?>">
                                                <p> (影音連結例：https://www.youtube.com/watch?v=<span class="red">QEWV6fiYaDU</span>) </p>
                                        </div>
                                    </div>

                                    <?php if ($i <= 3) {
                                        $fileSlot = investor_file_slot_for_lang($i);
                                        $filePath = (!$isAdd) ? (string)($Photo[$fileSlot] ?? '') : '';
                                        $fileExt  = (string)($Ext[$fileSlot] ?? '');
                                    ?>
                                    <div class="formGrid" id="tr_file<?php echo $i; ?>">
                                        <label class="col--2 inputLabel editView__formLabel">上傳檔案 <span class="inputLabel__required">*</span></label>
                                        <div class="col--10 inputGroup">
                                            <div class="photo-upload">
                                                <label class="editView__radioLabel">
                                                    <input name="intFileLink<?php echo $i; ?>" id="intFileLink<?php echo $i; ?>_1" type="radio" value="1"
                                                        <?php echo (int)($intLink[$i] ?? 2) === 1 ? 'checked' : ''; ?> /> 連結路徑
                                                </label>
                                                <input name="strLink<?php echo $i; ?>" type="text" class="formInput" id="strLink<?php echo $i; ?>"
                                                    value="<?php echo e((string)($strLink[$i] ?? '')); ?>" maxlength="500">
                                                <span id="strLink<?php echo $i; ?>_txt" class="input__errorTxt"></span>
                                            </div>
                                            <div class="photo-upload">
                                                <label class="editView__radioLabel">
                                                    <input name="intFileLink<?php echo $i; ?>" id="intFileLink<?php echo $i; ?>_2" type="radio" value="2"
                                                        <?php echo (int)($intLink[$i] ?? 2) !== 1 ? 'checked' : ''; ?> /> 上傳檔案
                                                </label>
                                                <?php
                                                manage_render_upload_document_slot(
                                                    $fileSlot,
                                                    $isAdd,
                                                    $filePath,
                                                    (int)($PhotoS[$fileSlot] ?? 0),
                                                    $fileExt,
                                                    20000
                                                );
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>

                                </div>
                                <?php } ?>
                            </div>
                        </article>

                        <?php
                        echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;
                        echo hiddenNumeric('PKey', $Update_PKey ?? 0) . PHP_EOL;
                        echo hiddenNumeric('Total_lang', $langTotal) . PHP_EOL;
                        echo hiddenNumeric('PhotoSlotMax', $managePhotoSlotMax) . PHP_EOL;
                        require_once '../_submit.php';
                        ?>
                        </form>
                    </section>
                </div>
            </main>
        </div>
    </div>
    <?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
