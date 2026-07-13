<?php
declare(strict_types=1);

coupon_detail_export_vars();

$isAdd = (int)($Update_PKey ?? 0) <= 0;
$isEdit = !$isAdd;
$layout_page_title = (string)($layout_page_title ?? ($isAdd ? '新增折價券' : '編輯折價券'));
$intTypeVal = (int)($intType ?? 1);
?>
<?php require_once '../_layout_head.php'; ?>
<?php echo script_open(); ?>
$(function() {
    $('#Coupon_Code').on('change', function() {
        var code = $.trim($('#Coupon_Code').val());
        var oldCode = $.trim($('#oldCode').val());
        if (code === '' || code === oldCode) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: '_chkid.php',
            data: { Coupon_Code: code },
            dataType: 'text'
        }).done(function(txt) {
            txt = $.trim(txt);
            if (txt !== '') {
                alert(txt);
                $('#Coupon_Code').val('').focus();
            }
        });
    });
});

function fieldCheck0(theForm) {
    if (typeof loading === 'function') {
        loading(1);
    }
    var errors = [];
    var fields = [];
    var intType = parseInt($('input[name="intType"]:checked').val(), 10) || 1;
    var price = $.trim($('#Price').val());

    if ($.trim($('#strName').val()) === '') {
        errors.push('活動名稱空白');
        fields.push('strName');
    }
    if ($('#Coupon_Code').length && !<?php echo $isEdit ? 'false' : 'true'; ?>) {
        if (!/^[a-z0-9]{1,50}$/i.test($.trim($('#Coupon_Code').val()))) {
            errors.push('活動序號格式錯誤');
            fields.push('Coupon_Code');
        }
    }
    if ($.trim($('#intQ').val()) === '' || !/^\d+$/.test($.trim($('#intQ').val()))) {
        errors.push('序號數量不是數字');
        fields.push('intQ');
    }
    if (price === '' || isNaN(parseInt(price, 10))) {
        errors.push('折抵金額不是數字');
        fields.push('Price');
    } else if (intType === 2 && (parseInt(price, 10) < 50 || parseInt(price, 10) > 100)) {
        errors.push('折抵百分比需介於50~100');
        fields.push('Price');
    }
    if ($.trim($('#BuyPrice').val()) === '' || !/^\d+$/.test($.trim($('#BuyPrice').val()))) {
        errors.push('購買金額不是數字');
        fields.push('BuyPrice');
    }
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
                                    <label class="col--2 inputLabel editView__formLabel" for="Coupon_Code">活動序號 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="Coupon_Code" type="text" id="Coupon_Code" class="formInput"
                                            value="<?php echo e((string)($Coupon_Code ?? '')); ?>"
                                            maxlength="20" placeholder="英文或數字6~20碼"
                                            <?php if ($isEdit) { echo 'readonly="readonly"'; } ?>>
                                        <input type="hidden" name="oldCode" id="oldCode" value="<?php echo e((string)($oldCode ?? '')); ?>">
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="intQ">序號數量 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input name="intQ" type="number" id="intQ" class="formInput editView__sortInput"
                                            min="0" step="1" value="<?php echo (int)($intQ ?? 0); ?>" maxlength="4">
                                        <span class="notes">限輸入數字</span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">折抵方式 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10 inputGroup">
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input name="intType" type="radio" id="intType1" value="1"<?php echo $intTypeVal === 1 ? ' checked' : ''; ?>>
                                            固定金額
                                        </label>
                                        <label class="flex flex--itCenter gap--2 editView__radioLabel">
                                            <input name="intType" type="radio" id="intType2" value="2"<?php echo $intTypeVal === 2 ? ' checked' : ''; ?>>
                                            訂單金額×百分比（50~100）
                                        </label>
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
