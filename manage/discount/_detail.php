<?php
declare(strict_types=1);

discount_detail_export_vars();

$isAdd = (int)($Update_PKey ?? 0) <= 0;
$layout_page_title = (string)($layout_page_title ?? ($isAdd ? '新增優惠折抵' : '編輯優惠折抵'));
$intTypeVal = (int)($intType ?? 1);
if ($intTypeVal !== 2) {
    $intTypeVal = 1;
}
$priceVal = (int)($Price ?? 100);
if ($priceVal <= 0) {
    $priceVal = 100;
}
?>
<?php require_once '../_layout_head.php'; ?>
<?php echo script_open(); ?>
$(function() {
    showDiscountType();
    $('#intType').on('change', showDiscountType);
    $('#OpenDate, #EndDate').on('change', checkDiscountDateOverlap);
});

function showDiscountType() {
    var intType = parseInt($('#intType').val(), 10) || 0;
    if (intType === 2) {
        $('#Type1').hide();
        $('#Type2').show();
    } else {
        $('#Type1').show();
        $('#Type2').hide();
    }
}

function checkDiscountDateOverlap() {
    var openDate = $.trim($('#OpenDate').val());
    var endDate = $.trim($('#EndDate').val());
    var manNo = $.trim($('#manNo').val());
    if (openDate === '' || endDate === '' || manNo === '') {
        return;
    }
    $.ajax({
        type: 'POST',
        url: '_chkid.php',
        data: {
            OpenDate: openDate,
            EndDate: endDate,
            manNo: manNo,
            excludePKey: <?php echo (int)($Update_PKey ?? 0); ?>
        },
        dataType: 'text'
    }).done(function(txt) {
        txt = $.trim(txt);
        if (txt !== '') {
            alert(txt);
            $('#EndDate').val('').focus();
        }
    });
}

function fieldCheck0(theForm) {
    if (typeof loading === 'function') {
        loading(1);
    }
    var errors = [];
    var fields = [];
    var intType = parseInt($('#intType').val(), 10) || 0;

    if ($.trim($('#strName').val()) === '') {
        errors.push('活動名稱空白');
        fields.push('strName');
    }
    if (intType !== 1 && intType !== 2) {
        errors.push('折抵方式請選擇');
        fields.push('intType');
    }
    if (intType === 1) {
        if ($.trim($('#BuyQ').val()) === '' || !/^\d+$/.test($.trim($('#BuyQ').val())) || parseInt($('#BuyQ').val(), 10) <= 0) {
            errors.push('數量不是數字');
            fields.push('BuyQ');
        }
    }
    if (intType === 2) {
        if ($.trim($('#BuyPrice').val()) === '' || !/^\d+$/.test($.trim($('#BuyPrice').val())) || parseInt($('#BuyPrice').val(), 10) <= 0) {
            errors.push('金額不是數字');
            fields.push('BuyPrice');
        }
    }
    if ($.trim($('#Price').val()) === '' || !/^\d+$/.test($.trim($('#Price').val()))) {
        errors.push('折抵金額不是數字');
        fields.push('Price');
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
                                        <input name="strName" type="text" id="strName" class="formInput" maxlength="50"
                                            value="<?php echo e((string)($strName ?? '')); ?>">
                                        <span id="strName_txt" class="input__errorTxt" role="alert"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="intType">折抵方式 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <select name="intType" id="intType" class="formSelect">
                                            <option value="">請選擇</option>
                                            <option value="1"<?php echo $intTypeVal === 1 ? ' selected' : ''; ?>>滿件折抵</option>
                                            <option value="2"<?php echo $intTypeVal === 2 ? ' selected' : ''; ?>>滿額折抵</option>
                                        </select>
                                        <span id="Type_txt" class="input__errorTxt" role="alert"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">折抵方案 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <div id="Type1" class="inputGroup"<?php echo $intTypeVal === 2 ? ' style="display:none;"' : ''; ?>>
                                            <span>購物滿</span>
                                            <input name="BuyQ" type="number" id="BuyQ" class="formInput editView__sortInput"
                                                min="1" step="1" maxlength="2"
                                                value="<?php echo (int)($BuyQ ?? 0) > 0 ? (int)$BuyQ : ''; ?>">
                                            <span>件</span>
                                        </div>
                                        <div id="Type2" class="inputGroup"<?php echo $intTypeVal === 1 ? ' style="display:none;"' : ''; ?>>
                                            <span>購物滿</span>
                                            <input name="BuyPrice" type="number" id="BuyPrice" class="formInput editView__sortInput"
                                                min="1" step="1" maxlength="4"
                                                value="<?php echo (int)($BuyPrice ?? 0) > 0 ? (int)$BuyPrice : ''; ?>">
                                            <span>元</span>
                                        </div>
                                        <span id="Plan_txt" class="input__errorTxt" role="alert"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Price">折抵金額 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10 inputGroup">
                                        <span>折抵運費 $</span>
                                        <input name="Price" type="number" id="Price" class="formInput editView__sortInput"
                                            min="0" step="1" maxlength="4"
                                            value="<?php echo $priceVal; ?>">
                                        <span>元</span>
                                        <span id="Price_txt" class="input__errorTxt" role="alert"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="OpenDate">開始日期 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input type="date" name="OpenDate" id="OpenDate" class="formInput editView__dateInput"
                                            value="<?php echo e((string)($OpenDate ?? '')); ?>">
                                        <input type="hidden" name="oldOpen" id="oldOpen" value="<?php echo e((string)($oldOpen ?? '')); ?>">
                                        <span id="OpenDate_txt" class="input__errorTxt" role="alert"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="EndDate">結束日期 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10">
                                        <input type="date" name="EndDate" id="EndDate" class="formInput editView__dateInput"
                                            value="<?php echo e((string)($EndDate ?? '')); ?>">
                                        <input type="hidden" name="oldEnd" id="oldEnd" value="<?php echo e((string)($oldEnd ?? '')); ?>">
                                        <span id="EndDate_txt" class="input__errorTxt" role="alert"></span>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Interview">折抵說明</label>
                                    <div class="col--10">
                                        <textarea name="Interview" id="Interview" class="formInput" rows="3" maxlength="200"><?php
                                            echo e((string)($Interview ?? ''));
                                        ?></textarea>
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
