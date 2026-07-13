<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

require_once __DIR__ . '/_form_data.php';



$filter = is_array($filter_array ?? null) ? $filter_array : [];

$couponPKey = coupon_d_resolve_coupon_pkey($filter);

$parent = coupon_d_load_parent($couponPKey);

if ($parent === null) {

    manage_alert_script(

        '查無優惠券資料',

        coupon_d_parent_list_url($detailConfig, $filter)

    );

    exit;

}



$strName = (string)($parent['strName'] ?? '');

$couponPKey = (int)($parent['PKey'] ?? $couponPKey);

$listUrl = coupon_d_list_back_url($couponPKey, $filter);

$csrfKey = (string)($detailConfig['csrf'] ?? 'coupon_d_import');



if (isset($filter['Submit']) && (string)$filter['Submit'] === '送出') {

    crud_csrf_verify_form($csrfKey);

    $rawEmails = trim((string)($filter['EMail'] ?? ''));

    if ($rawEmails === '') {

        manage_alert_script('會員帳號空白', 'javascript:history.back()');

        exit;

    }

    $emails = coupon_d_parse_email_list($rawEmails);

    $result = coupon_d_import_emails($couponPKey, $emails);

    $msg = '成功匯入共計' . $result['lines'] . '筆；成功' . $result['success'] . '筆；失敗' . $result['failed'] . '筆';

    manage_alert_script($msg, $listUrl);

    exit;

}



$csrf_token = crud_csrf_ensure_page($csrfKey);

$breadcrumbs = [

    ['label' => (string)($Module_Name ?? '折價券明細')],

    ['label' => '入會折價券', 'href' => coupon_d_parent_list_url($detailConfig, $filter)],

    ['label' => $strName, 'href' => $listUrl],

    ['label' => '匯入'],

];

$layout_page_title = $strName . '－匯入';

$layout_container_class = 'container';

?>

<?php require_once '../_layout_head.php'; ?>

</head>



<?php require_once '../_layout_body_open.php'; ?>

                    <?php require_once '../_breadcrumbs.php'; ?>



                    <div class="editView">

                        <div class="card">

                            <div class="editView__section">

                                <h2 class="editView__title">Excel 匯入</h2>

                                <form action="loadin.php" method="post" enctype="multipart/form-data"

                                    name="formExcel" id="formExcel" novalidate

                                    data-manage-validate="couponDExcelCheck">

                                    <div class="editView__grid">

                                        <div class="inputGroup inputGroup--full">

                                            <label class="inputLabel" for="Photo1">匯入檔案</label>

                                            <div class="inputWrapper">

                                                <input type="file" name="Photo1" id="Photo1"

                                                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"

                                                    class="formInput">

                                                <span id="Photo1_txt" class="input__errorTxt" role="alert"></span>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="editView__actions">

                                        <a href="<?php echo e($listUrl); ?>" class="btnStyle btnStyle--outline">

                                            回列表頁

                                        </a>

                                        <button type="submit" class="btnStyle --isAnim" name="Submit" value="匯入">

                                            確定匯入

                                        </button>

                                    </div>

                                    <?php

                                    echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;

                                    echo hiddenNumeric('Coupon_PKey', $couponPKey) . PHP_EOL;

                                    echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;

                                    echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;

                                    ?>

                                </form>

                            </div>

                        </div>



                        <div class="card" style="margin-top:1rem;">

                            <div class="editView__section">

                                <h2 class="editView__title">手動輸入帳號</h2>

                                <form action="" method="post" name="formManual" id="formManual" novalidate

                                    data-manage-validate="couponDManualCheck">

                                    <div class="editView__grid">

                                        <div class="inputGroup inputGroup--full">

                                            <label class="inputLabel" for="EMail">會員帳號</label>

                                            <div class="inputWrapper">

                                                <textarea name="EMail" id="EMail" rows="5" class="formInput"

                                                    placeholder="請輸入會員帳號，多筆以分號分隔"></textarea>

                                                <span id="EMail_txt" class="input__errorTxt" role="alert"></span>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="editView__actions">

                                        <a href="<?php echo e($listUrl); ?>" class="btnStyle btnStyle--outline">

                                            回列表頁

                                        </a>

                                        <button type="submit" class="btnStyle --isAnim" name="Submit" value="送出">

                                            確定送出

                                        </button>

                                    </div>

                                    <?php

                                    echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;

                                    echo hiddenNumeric('Coupon_PKey', $couponPKey) . PHP_EOL;

                                    echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;

                                    echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;

                                    ?>

                                </form>

                            </div>

                        </div>

                    </div>



                    <div class="notes notes--lg">

                        <div class="notes__header">

                            <i class="bi bi-info-circle notes__icon"></i> 系統備註

                        </div>

                        <ul class="notes__list">

                            <li>Excel 第一欄為會員 E-mail，第一列為標題列（從第二列開始讀取）。</li>

                            <li>手動匯入多筆帳號請以分號（;）分隔。</li>

                            <li>已存在於此活動的會員或查無帳號者，將計入失敗筆數。</li>

                        </ul>

                    </div>

                    <div class="notes__spacer"></div>



<?php require_once '../_layout_body_close.php'; ?>

<?php require_once '../_in_code_bottom.php'; ?>

</body>

</html>

