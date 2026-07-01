<?php

$classLabel = (string)($Class_Name[1] ?? '類別');
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$langTotal = count($array_lang);
$PhotoS = is_array($PhotoS ?? null) ? $PhotoS : [];
$Ext = is_array($Ext ?? null) ? $Ext : [];

?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
$(function() {
    var filedownMaxKB = 6000; // knowledge/product 同樣採用：6000KB
    var filedownAccept = '.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar';

    function formatFileSize(bytes) {
        if (!bytes && bytes !== 0) {
            return '';
        }
        var fileKB = Math.floor(bytes / 1024);
        if (fileKB > 999) {
            var fileMB = Math.round((fileKB / 1000) * 100) / 100;
            return fileMB.toFixed(2) + ' MB';
        }
        return fileKB + ' KB';
    }

    function updateFileSize(idx) {
        var el = document.getElementById('Photo' + idx);
        var file = el && el.files && el.files[0] ? el.files[0] : null;
        var $sizeInput = $('#FileSize' + idx);
        if (!$sizeInput.length) {
            return;
        }
        if (!file) {
            return;
        }
        $sizeInput.val(formatFileSize(file.size));
    }

    function toggleLangLinkInputs(idx) {
        var isLink = $('#intLink' + idx + '_1').prop('checked');
        $('#strLink' + idx).prop('disabled', !isLink);
        $('#Photo' + idx).prop('disabled', isLink);
        $('#FileSize' + idx).prop('disabled', isLink);
        if (!isLink) {
            updateFileSize(idx);
        }
    }
    var totalLang = parseInt($('#Total_lang').val(), 10) || 0;
    for (var t = 1; t <= totalLang; t++) {
        (function(i) {
            $('#intLink' + i + '_1, #intLink' + i + '_2').on('change', function() {
                toggleLangLinkInputs(i);
            });
            var inputEl = document.getElementById('Photo' + i);
            if (inputEl) {
                inputEl.setAttribute('accept', filedownAccept);
            }
            $('#Photo' + i).on('change', function() {
                if (typeof window.checkFile === 'function') {
                    if (!window.checkFile('Photo' + i, filedownMaxKB, 'file')) {
                        return;
                    }
                }
                updateFileSize(i);
            });
            toggleLangLinkInputs(i);
        })(t);
    }
    <?php
    manage_echo_photo_delete_init_script(
        manage_photo_delete_slots_for_range($PhotoS, 1, $langTotal)
    );
    ?>
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
        if (!$('#Show' + j).prop('checked')) {
            continue;
        }
        if ($.trim($('#strName' + j).val()) === '') {
            array.push('strName' + j);
            errors.push('標題空白（語系 ' + j + '）');
            view.push(j);
        }
        if ($('#intLink' + j + '_1').prop('checked')) {
            if ($.trim($('#strLink' + j).val()) === '') {
                array.push('strLink' + j);
                errors.push('連結路徑空白（語系 ' + j + '）');
                view.push(j);
            }
        } else {
            var previewSrc = ($('#preview' + j).attr('src') || '').trim();
            var hasPreview = previewSrc !== '';
            var hasPrefile = $.trim($('#prefile' + j).text()) !== '';
            var hasFile = ($('#Photo' + j).val() || '').length > 0;
            if (!hasPreview && !hasPrefile && !hasFile) {
                array.push('Photo' + j);
                errors.push('請選擇上傳檔案（語系 ' + j + '）');
                view.push(j);
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
                                        <?php for ($i = 1; $i <= $langTotal; $i++) { ?>
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
                                            while (! $rs1->eof) {
                                            ?>
                                            <option value="<?php echo $rs1->field('PKey')?>" <?php if (strval($Class1) == strval($rs1->field('PKey'))) { echo 'selected="selected"'; } ?>><?php echo $rs1->field('strName')?></option>
                                            <?php
                                            $rs1->movenext();
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($Layer > 2): ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Class2"><?php echo $Class_Name[2]?>名稱</label>
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
                                <?php for ($i = 1; $i <= $langTotal; $i++) { ?>
                                <li id="tabNav_<?php echo $i; ?>"
                                    class="tabsGp__link --color<?php echo $i; ?>"
                                    data-tab-target="tabCon_<?php echo $i; ?>">
                                    <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                </li>
                                <?php } ?>
                            </ul>
                            <div class="tabsGp__body">
                                <?php for ($i = 1; $i <= $langTotal; $i++) {
                                    $linkType = (int)($intLink[$i] ?? 2);
                                    if ($linkType <= 0) {
                                        $linkType = 2;
                                    }
                                    $filePath = (!$isAdd) ? (string)($Photo[$i] ?? '') : '';
                                    $ext = (string)($Ext[$i] ?? manage_file_ext_from_path($filePath));
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
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="FileSize<?php echo $i; ?>">
                                        檔案尺寸 
                                        </label>
                                        <div class="col--10">
                                            <input name="FileSize<?php echo $i; ?>" type="text"
                                                id="FileSize<?php echo $i; ?>" class="formInput"
                                                value="<?php echo e((string)($FileSize[$i] ?? '')); ?>">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Contents<?php echo $i?>">簡述</label>
                                        <div class="col--10">
                                            <?php
                                            $editorAiFieldId = 'Contents' . $i;
                                            require dirname(__DIR__) . '/_detail_ckeditor_ai_button.php';
                                            ?>
                                            <textarea name="Contents<?php echo $i?>" id="Contents<?php echo $i?>"
                                                class="ckeditor formInput" style="height:100px"
                                                placeholder="請輸入200字元內的文字"><?php echo e((string)($Contents[$i] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel">下載方式 <span class="inputLabel__required">*</span></label>
                                        <div class="col--10 inputGroup">
                                            <div class="flex flex--itCenter gap--2 editView__radioLabel">
                                                <input name="intLink<?php echo $i?>" id="intLink<?php echo $i?>_1" type="radio" value="1"<?php echo $linkType === 1 ? ' checked' : ''; ?>>
                                                <label for="intLink<?php echo $i?>_1">連結路徑</label>
                                            </div>
                                            <input name="strLink<?php echo $i?>" type="text" id="strLink<?php echo $i?>"
                                                class="formInput" maxlength="255"
                                                value="<?php echo e((string)($strLink[$i] ?? '')); ?>">
                                            <div class="flex flex--itCenter gap--2 editView__radioLabel" style="margin-top:12px;">
                                                <input name="intLink<?php echo $i?>" id="intLink<?php echo $i?>_2" type="radio" value="2"<?php echo $linkType !== 1 ? ' checked' : ''; ?>>
                                                <label for="intLink<?php echo $i?>_2">選擇檔案</label>
                                            </div>
                                            <?php
                                            manage_render_upload_document_slot(
                                                $i,
                                                $isAdd,
                                                $filePath,
                                                (int)($PhotoS[$i] ?? 0),
                                                $ext,
                                                6000
                                            );
                                            ?>
                                            <div class="notes">
                                                <ul class="notes__list">
                                                    <?php echo $remark_file1; ?>
                                                </ul>
                                            </div>
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
                            <li>複製功能僅能複製文字，檔案需重新上傳。</li>
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
