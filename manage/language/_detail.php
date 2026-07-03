<?php
declare(strict_types=1);

$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;

if (!isset($layout_page_title) || $layout_page_title === '') {
    $layout_page_title = '語系設定';
}
?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
$(function() {
    // 語系表單僅 strName / Upload
});

function fieldCheck0(theForm) {
    if (typeof loading === 'function') {
        loading(1);
    }
    var errors = [];
    var focusField = '';

    if ($.trim($('#strName').val()) === '') {
        errors.push('語系名稱不可空白');
        focusField = 'strName';
    }

    if (errors.length) {
        return window.manageFormValidationFail(errors, {
            focusField: focusField,
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
                        <form action="addin.php" method="post"
                            name="form1" id="form1" data-manage-validate="fieldCheck0">

                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"></ul>
                            </div>
                        </div>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">語系設定</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="strName">
                                        語系名稱 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10">
                                        <input name="strName" type="text" id="strName" class="formInput" maxlength="50"
                                            value="<?php echo e((string)($strName ?? '')); ?>"
                                            placeholder="例：中文、英文、簡中、日文">
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

                        <?php require_once '../_submit.php'; ?>
                        </form>
                    </section>

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>語系名稱供後台分頁與 AI 產文語系判斷使用（如：中文、英文、簡中、日文）。</li>
                            <li>下架後該語系不會出現在後台編輯分頁；既有資料仍保留於資料庫。</li>
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
