<?php
$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$addPhotoSlots = (int)($addPhotoSlots ?? 10);
if ($addPhotoSlots < 1) {
    $addPhotoSlots = 10;
}
$albumDForm = is_array($GLOBALS['album_d_form'] ?? null) ? $GLOBALS['album_d_form'] : [];
$Album_PKey = (int)($albumDForm['Album_PKey'] ?? 0);
$Album_Name = (string)($albumDForm['Album_Name'] ?? '');
$Update_PKey = (int)($albumDForm['Update_PKey'] ?? ($Update_PKey ?? 0));
$Sort = (int)($albumDForm['Sort'] ?? ($Sort ?? 0));
$PhotoM = (string)($albumDForm['PhotoM'] ?? ($PhotoM ?? ''));
$photoPath = (!$isAdd) ? (string)($albumDForm['Photo1'] ?? ($Photo1 ?? '')) : '';
$PhotoS = [];
if (!$isAdd && $Update_PKey > 0) {
    $PhotoS[1] = $Update_PKey;
}
?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
$(function() {
    <?php if (!$isAdd) {
        manage_echo_photo_delete_init_script(
            manage_photo_delete_slots_for_range($PhotoS, 1, 1)
        );
    } ?>
});

function fieldCheck0(theForm) {
    if (typeof loading === 'function') {
        loading(1);
    }
    var errors = [];
    var array = [];

    <?php if ($isAdd) { ?>
    var total = 0;
    for (var i = 1; i <= <?php echo $addPhotoSlots; ?>; i++) {
        var src = $('#preview' + i).prop('src') || '';
        if (src !== '') {
            total++;
        }
    }
    if (total === 0) {
        errors.push('請至少上傳一張圖片');
        array.push('Photo1');
    }
    <?php } else { ?>
    var sortVal = ($('#Sort').val() || '').trim();
    if (!/^\d+$/.test(sortVal)) {
        errors.push('順序不是數字');
        array.push('Sort');
    }
    var previewSrc = $('#preview1').prop('src') || '';
    if (previewSrc === '') {
        errors.push('請選擇圖片檔案');
        array.push('Photo1');
    }
    <?php } ?>

    if (errors.length) {
        return window.manageFormValidationFail(errors, { focusField: array[0], form: theForm });
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
                            <div class="errorArea__body"><ul id="formErrorList"></ul></div>
                        </div>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">基本設定</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">相簿名稱</label>
                                    <div class="col--10">
                                        <?php echo e($Album_Name); ?>
                                    </div>
                                </div>
                                <?php if (!$isAdd) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">順序 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="Sort" id="Sort" type="number" min="0" step="1"
                                            class="formInput editView__sortInput"
                                            value="<?php echo (int)($Sort ?? 0); ?>">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="PhotoM">圖說</label>
                                    <div class="col--10">
                                        <input name="PhotoM" type="text" id="PhotoM" class="formInput" maxlength="50"
                                            value="<?php echo e((string)($PhotoM ?? '')); ?>">
                                    </div>
                                </div>
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
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">
                                        圖片<?php if ($isAdd) { ?> <span class="inputLabel__required">*</span><?php } ?>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <?php if ($isAdd) { ?>
                                            <?php for ($n = 1; $n <= $addPhotoSlots; $n++) { ?>
                                        <div class="inputGroup">
                                            <?php manage_render_upload_image_slot($n, true, '', 0); ?>
                                            <label class="inputLabel" for="PhotoM<?php echo $n; ?>">圖說 <?php echo $n; ?></label>
                                            <input name="PhotoM<?php echo $n; ?>" type="text" id="PhotoM<?php echo $n; ?>"
                                                class="formInput" maxlength="50" value="">
                                            <?php if ($n === 1) { ?>
                                            <span id="Photo1_txt" class="input__errorTxt"></span>
                                            <?php } ?>
                                        </div>
                                            <?php } ?>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <li>最多可一次上傳 <?php echo $addPhotoSlots; ?> 張圖片。</li>
                                                <li>圖片：寬 800px 以內，高不限。</li>
                                            </ul>
                                        </div>
                                        <?php } else { ?>
                                        <?php
                                        manage_render_upload_image_slot(
                                            1,
                                            false,
                                            $photoPath,
                                            (int)($PhotoS[1] ?? 0)
                                        );
                                        ?>
                                        <span id="Photo1_txt" class="input__errorTxt"></span>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <li>圖片：寬 800px 以內，高不限。</li>
                                            </ul>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <?php
                        echo hiddenNumeric('Album_PKey', (string)(int)($Album_PKey ?? 0)) . PHP_EOL;
                        if (!$isAdd) {
                            echo hiddenNumeric('PKey', (string)(int)($Update_PKey ?? 0)) . PHP_EOL;
                        }
                        echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
                        echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
                        require_once '../_submit.php';
                        ?>
                        </form>
                    </section>
                </div>
                <?php require_once '../_footer.php'; ?>
            </main>
        </div>
    </div>
    <?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
