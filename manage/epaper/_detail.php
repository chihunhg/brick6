<?php
declare(strict_types=1);

epaper_detail_export_vars();

$isAdd = (int)($Update_PKey ?? 0) <= 0;
$layout_page_title = (string)($layout_page_title ?? ($isAdd ? '新增訂閱' : '編輯訂閱'));
?>
<?php require_once '../_layout_head.php'; ?>
<?php echo script_open(); ?>
function fieldCheck0(theForm) {
    if (typeof loading === 'function') {
        loading(1);
    }
    var errors = [];
    var fields = [];
    var email = $.trim($('#EMail').val());

    if (email === '') {
        errors.push('E-Mail 空白');
        fields.push('EMail');
    } else if (typeof isEmail === 'function' && !isEmail(email)) {
        errors.push('E-Mail 格式錯誤');
        fields.push('EMail');
    }

    if (errors.length) {
        return window.manageFormValidationFail(errors, { focusField: fields[0], form: theForm });
    }
    return window.manageFormValidationOk(theForm);
}
<?php echo script_close(); ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <section class="editView">
                        <form action="addin.php" method="post" name="form1" id="form1" data-manage-validate="fieldCheck0">
                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body"><ul id="formErrorList"></ul></div>
                        </div>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">訂閱資料</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="EMail">E-Mail <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="EMail" type="email" id="EMail" class="formInput"
                                            value="<?php echo e((string)($EMail ?? '')); ?>"
                                            placeholder="請輸入 Email" autocomplete="email">
                                        <span id="EMail_txt" class="input__errorTxt" role="alert"></span>
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
                        <?php
                        echo hiddenText('csrf_token', e($csrf_token ?? '')) . PHP_EOL;
                        echo hiddenNumeric('PKey', $Update_PKey ?? 0) . PHP_EOL;
                        echo hiddenNumeric('manNo', $manNo ?? ($filter_array['manNo'] ?? '')) . PHP_EOL;
                        echo hiddenNumeric('subNo', $subNo ?? ($filter_array['subNo'] ?? '')) . PHP_EOL;
                        echo hiddenNumeric('Page', $filter_array['Page'] ?? 1) . PHP_EOL;
                        ?>
                        </form>
                    </section>
                    <div class="notes__spacer"></div>

<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
