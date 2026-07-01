<?php
declare(strict_types=1);

require_once '../_inc.php';

$subitem     = 's5';
$Module_Name = '密碼變更';

$__csrf_key = $__csrf_key ?? 'manage_form';
$csrf_token = crud_csrf_ensure_page($__csrf_key);

if (!isset($GLOBALS['PW_Match']) || !is_int($GLOBALS['PW_Match'])) {
    $GLOBALS['PW_Match'] = 2;
}

if (isset($filter_array['Submit']) && $filter_array['Submit'] === '送出') {
    crud_csrf_verify($__csrf_key);

    $strPW = trim((string)($filter_array['strPW'] ?? ''));
    $repw  = trim((string)($filter_array['repw'] ?? ''));

    $MSG = crud_validate_password_complexity($strPW, (int)$GLOBALS['PW_Match']);
    if ($repw !== $strPW) {
        $MSG .= "【新密碼和確認新密碼】不符\n";
    }

    if ($MSG !== '') {
        crud_form_error_redirect("發生錯誤，請填寫下列欄位\n" . $MSG, $WorkFile ?? 'chgpw.php');
    }

    $loginId = (string)($_SESSION['Login_ID'] ?? '');
    if ($loginId === '') {
        crud_form_error_redirect('登入逾時，請重新登入', $WorkFile ?? 'chgpw.php');
    }

    crud_change_own_password($loginId, $strPW, (string)($_SESSION['UserName'] ?? ''));

    manage_alert_script('密碼成功更新，下次請用新密碼登入', $WorkFile ?? 'chgpw.php');
    exit;
}

$pwMatch = (int)($GLOBALS['PW_Match'] ?? 2);
if ($pwMatch < 2) {
    $pwMatch = 2;
}

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => '帳號管理'],
    ['label' => '變更密碼'],
];
$layout_page_title = '變更密碼';

require_once '../_layout_head.php';
?>
<?php echo script_open(); ?>
function fieldCheck0(theForm) {
    if (typeof loading === 'function') {
        loading(1);
    }
    var errors = [];
    var fields = [];
    var strPW = $.trim($('#strPW').val());
    var repw = $.trim($('#repw').val());
    var minComplexity = <?php echo $pwMatch; ?>;

    var checkPasswordRules = function () {
        if (strPW.length < 8 || strPW.length > 20) {
            errors.push('密碼長度需為8~20碼');
            fields.push('strPW');
            return;
        }
        var complexity = 0;
        if (/[a-z]/.test(strPW)) complexity++;
        if (/[A-Z]/.test(strPW)) complexity++;
        if (/[0-9]/.test(strPW)) complexity++;
        if (/[~!@#$%^&*()\-_=+{};:<,.>?]/.test(strPW)) complexity++;

        if (complexity < minComplexity) {
            var need = [];
            if (!/[a-z]/.test(strPW)) need.push('小寫英文字母');
            if (!/[A-Z]/.test(strPW)) need.push('大寫英文字母');
            if (!/[0-9]/.test(strPW)) need.push('數字');
            if (!/[~!@#$%^&*()\-_=+{};:<,.>?]/.test(strPW)) need.push('特殊符號');
            errors.push('密碼需符合複雜度規則（至少 ' + minComplexity + ' 種），缺少：' + need.join('、'));
            fields.push('strPW');
        }
    };

    if (strPW === '') {
        errors.push('新密碼不可空白');
        fields.push('strPW');
    } else {
        checkPasswordRules();
    }

    if (repw === '') {
        errors.push('確認新密碼不可空白');
        fields.push('repw');
    } else if (repw !== strPW) {
        errors.push('新密碼和確認新密碼不同');
        fields.push('repw');
    }

    if (errors.length > 0) {
        return window.manageFormValidationFail(errors, {
            focusField: fields[0],
            form: theForm
        });
    }
    return window.manageFormValidationOk(theForm);
}

function genStrongPassword(minComplexity, minLen, maxLen) {
    minComplexity = Math.max(2, Math.min(4, +minComplexity || 3));
    minLen = Math.max(8, +minLen || 12);
    maxLen = Math.max(minLen, +maxLen || 20);
    var sets = {
        U: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        L: 'abcdefghijklmnopqrstuvwxyz',
        D: '0123456789',
        S: '~!@#$%^&*()-_=+{};:<,.>?'
    };
    var pick = function (k) {
        var pool = sets[k];
        var idx = crypto.getRandomValues(new Uint32Array(1))[0] % pool.length;
        return pool[idx];
    };
    var keys = Object.keys(sets);
    keys.sort(function () {
        return (crypto.getRandomValues(new Uint32Array(1))[0] % 2) ? 1 : -1;
    });
    var needKeys = keys.slice(0, minComplexity);
    var chars = [];
    needKeys.forEach(function (k) {
        chars.push(pick(k));
    });
    var targetLen = Math.min(maxLen, Math.max(minLen, 14));
    var all = Object.values(sets).join('');
    while (chars.length < targetLen) {
        var idx = crypto.getRandomValues(new Uint32Array(1))[0] % all.length;
        chars.push(all[idx]);
    }
    for (var i = chars.length - 1; i > 0; i--) {
        var j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
        var t = chars[i];
        chars[i] = chars[j];
        chars[j] = t;
    }
    return chars.join('');
}

document.addEventListener('DOMContentLoaded', function () {
    var btnGen = document.getElementById('btn-gen-pw');
    if (btnGen) {
        btnGen.addEventListener('click', function () {
            try {
                var pw = genStrongPassword(<?php echo $pwMatch; ?>, 12, 20);
                var input = document.getElementById('strPW');
                if (input) {
                    input.value = pw;
                }
            } catch (e) {
                alert('瀏覽器不支援安全亂數，請手動輸入密碼。');
            }
        });
    }
});
<?php echo script_close(); ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <form action="" method="post" name="form1" id="form1" novalidate data-manage-validate="fieldCheck0">
                        <input type="hidden" name="u" value="ok">
                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"></ul>
                            </div>
                        </div>
                        <div class="table-container">
                            <table cellspacing="0" cellpadding="0" width="100%" border="0" class="detail">
                                <tr>
                                    <td>管理者名稱</td>
                                    <td><?php echo e((string)($_SESSION['UserName'] ?? '')); ?></td>
                                </tr>
                                <tr>
                                    <td>新密碼<span class="inputLabel__required">*</span></td>
                                    <td>
                                        <div class="passwordInput">
                                            <input type="password" name="strPW" id="strPW" class="formInput" maxlength="20"
                                                placeholder="建議12碼以上，含大小寫/數字/符號（系統規則：8~20碼）"
                                                autocomplete="new-password">
                                            <i class="bi bi-eye-slash-fill password-icon eyeIcon"
                                                role="button"
                                                tabindex="0"
                                                data-manage-action="toggle-password"
                                                data-target-id="strPW"
                                                aria-label="顯示或隱藏密碼"></i>
                                        </div>
                                        <button type="button" id="btn-gen-pw" class="btnStyle btnStyle--outline btnStyle--sm" style="margin-top:6px;">產生強密碼</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>確認新密碼<span class="inputLabel__required">*</span></td>
                                    <td>
                                        <div class="passwordInput">
                                            <input type="password" name="repw" id="repw" class="formInput" value="" maxlength="20"
                                                placeholder="請再次輸入新密碼"
                                                autocomplete="new-password">
                                            <i class="bi bi-eye-slash-fill password-icon eyeIcon"
                                                role="button"
                                                tabindex="0"
                                                data-manage-action="toggle-password"
                                                data-target-id="repw"
                                                aria-label="顯示或隱藏密碼"></i>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <?php require_once '../_submit.php'; ?>
                    </form>

                    <div class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>密碼長度為 8~20 碼。</li>
                            <li>提升密碼複雜度，需符合下列最少<?php echo $pwMatch; ?>種規則：</li>
                            <li>英文大寫字元 (A 到 Z)</li>
                            <li>英文小寫字元 (a 到 z)</li>
                            <li>10 個基本數字 (0 到 9)</li>
                            <li>非英文字母字元 (例如 ~ ! @ # $ % ^ & * ( ) - _ = + { } ; : &lt; , . &gt; ? )</li>
                            <li>因安全性考量，密碼會經由程式重新編碼保護，網管人員無法存取變更後的新密碼，請妥善保管新設定的密碼。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
