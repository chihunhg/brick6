<?php
declare(strict_types=1);

couponreg_detail_export_vars();

$isAdd = (int)($Update_PKey ?? 0) <= 0;
$layout_page_title = (string)($layout_page_title ?? ($isAdd ? '新增註冊折價券' : '編輯註冊折價券'));
$intTypeVal = (int)($intType ?? 1);
?>
<?php require_once '../_layout_head.php'; ?>
<?php echo script_open(); ?>
function couponregTogglePeriod(typeVal) {
    var isRange = parseInt(typeVal, 10) === 1;
    $('#couponregDateRange').toggle(isRange);
    $('#couponregCustomDays').toggle(!isRange);
}

$(function() {
    couponregTogglePeriod(<?php echo $intTypeVal; ?>);
    $('input[name="intType"]').on('change', function() {
        couponregTogglePeriod($(this).val());
    });
});

function fieldCheck0(theForm) {
    if (typeof loading === 'function') {
        loading(1);
    }
    var errors = [];
    var fields = [];
    var intType = parseInt($('input[name="intType"]:checked').val(), 10) || 1;

    if ($.trim($('#strName').val()) === '') {
        errors.push('活動名稱空白');
        fields.push('strName');
    }
    if ($.trim($('#Price').val()) === '' || !/^\d+$/.test($.trim($('#Price').val()))) {
        errors.push('折抵金額不是數字');
        fields.push('Price');
    }
    if ($.trim($('#BuyPrice').val()) === '' || !/^\d+$/.test($.trim($('#BuyPrice').val()))) {
        errors.push('購買金額不是數字');
        fields.push('BuyPrice');
    }
    if (intType === 1) {
        if ($.trim($('#OpenDate').val()) === '') {
            errors.push('開始日期空白');
            fields.push('OpenDate');
        }
        if ($.trim($('#EndDate').val()) === '') {
            errors.push('結束日期空白');
            fields.push('EndDate');
        }
        if ($.trim($('#OpenDate').val()) !== '' && $.trim($('#EndDate').val()) !== ''
            && $.trim($('#OpenDate').val()) > $.trim($('#EndDate').val())) {
            errors.push('結束日期必需大於開始日期');
            fields.push('EndDate');
        }
    } else if ($.trim($('#intDay').val()) === '' || !/^\d+$/.test($.trim($('#intDay').val()))) {
        errors.push('自訂天數不是數字');
        fields.push('intDay');
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
                                <h4 class="editView__sectionTitle">基本設定</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="strName">活動名稱 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="strName" type="text" id="strName" class="formInput"
                                            value="<?php echo e((string)($strName ?? '')); ?>">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Price">折抵金額 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="Price" type="number" id="Price" class="formInput editView__sortInput"
                                            min="0" step="1" value="<?php echo (int)($Price ?? 0); ?>" maxlength="6">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="BuyPrice">購買金額 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="BuyPrice" type="number" id="BuyPrice" class="formInput editView__sortInput"
                                            min="0" step="1" value="<?php echo (int)($BuyPrice ?? 0); ?>" maxlength="6">
                                        <span class="notes">例：訂單金額 &gt;= 購買金額，才能使用優惠券</span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">使用方式</label>
                                    <div class="col--10 inputGroup">
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input name="intType" type="radio" id="intType1" value="1"<?php echo $intTypeVal === 1 ? ' checked' : ''; ?>>
                                            日期區間
                                        </label>
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input name="intType" type="radio" id="intType2" value="2"<?php echo $intTypeVal === 2 ? ' checked' : ''; ?>>
                                            自訂天數
                                        </label>
                                    </div>
                                </div>
                                <div id="couponregDateRange">
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="OpenDate">開始日期 <span class="inputLabel__required">*</span></label>
                                        <div class="col--10">
                                            <input type="date" name="OpenDate" id="OpenDate" class="formInput editView__dateInput"
                                                value="<?php echo e((string)($OpenDate ?? '')); ?>">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="EndDate">結束日期 <span class="inputLabel__required">*</span></label>
                                        <div class="col--10">
                                            <input type="date" name="EndDate" id="EndDate" class="formInput editView__dateInput"
                                                value="<?php echo e((string)($EndDate ?? '')); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div id="couponregCustomDays" class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="intDay">自訂天數 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="intDay" type="number" id="intDay" class="formInput editView__sortInput"
                                            min="0" step="1" value="<?php echo (int)($intDay ?? 0); ?>" maxlength="4">
                                        <span class="notes">開始日期為發送日，到期日為發送日 + 自訂天數（入會禮、生日券等）</span>
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
