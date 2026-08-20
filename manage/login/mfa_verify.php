<?php
declare(strict_types=1);

require_once '../_inc.php';

$Module_Name = '雙因素驗證';

if (!manage_mfa_pending_valid()) {
    location_href($web_root . 'manage/login/index.php');
    exit;
}

$__csrf_key = 'mfa_verify_form';
$csrf_token = crud_csrf_ensure_page($__csrf_key);
$mfaPKey = (int)($_SESSION['MFA_User_PKey'] ?? 0);
$mfaLoginId = (string)($_SESSION['MFA_strID'] ?? '');
$errorMsg = '';

if (isset($filter_array['Action']) && (string)$filter_array['Action'] === 'cancel') {
    crud_csrf_verify($__csrf_key);
    manage_history(3, $Module_Name, '取消 MFA 驗證', $WorkFile ?? 'mfa_verify.php', $mfaLoginId, '使用者取消');
    manage_mfa_clear_pending_login();
    session_destroy();
    location_href($web_root . 'manage/login/index.php');
    exit;
}

if (isset($filter_array['Submit']) && (string)$filter_array['Submit'] === '送出') {
    crud_csrf_verify($__csrf_key);

    $code = trim((string)($filter_array['mfa_code'] ?? ''));
    $attempts = (int)($_SESSION['MFA_Attempts'] ?? 0);

    if ($code === '') {
        $errorMsg = '請輸入驗證碼';
    } elseif ($attempts >= 5) {
        manage_history(3, $Module_Name, 'MFA 失敗次數過多', $WorkFile ?? 'mfa_verify.php', $mfaLoginId, '鎖定');
        manage_mfa_clear_pending_login();
        session_destroy();
        echo manage_inline_script(
            'alert(' . json_encode('驗證失敗次數過多，請重新登入', JSON_UNESCAPED_UNICODE) . ');location.href='
            . json_encode($web_root . 'manage/login/index.php', JSON_UNESCAPED_SLASHES) . ';'
        );
        exit;
    } elseif (manage_mfa_verify_for_user($mfaPKey, $code)) {
        try {
            manage_mfa_complete_login();
        } catch (Throwable $e) {
            $errorMsg = '驗證逾時，請重新登入';
            manage_mfa_clear_pending_login();
        }
        if ($errorMsg === '') {
            manage_history(3, $Module_Name, 'TOTP 驗證成功', $WorkFile ?? 'mfa_verify.php', $mfaLoginId, '登入成功');
            location_href(manage_mfa_post_login_url(manage_mfa_fetch_user_by_login($mfaLoginId)));
            exit;
        }
    } else {
        $_SESSION['MFA_Attempts'] = $attempts + 1;
        $remaining = max(0, 5 - (int)$_SESSION['MFA_Attempts']);
        $errorMsg = '驗證碼錯誤，剩餘 ' . $remaining . ' 次';
        manage_history(3, $Module_Name, 'TOTP 驗證失敗', $WorkFile ?? 'mfa_verify.php', $mfaLoginId, $errorMsg);
    }
}

$pageTitle = htmlspecialchars((string)$WebName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '｜雙因素驗證';
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
<meta charset="UTF-8">
<title><?= e($pageTitle) ?></title>
<link rel="icon" href="<?= e_attr(site_favicon_href()) ?>" type="image/x-icon">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
function fieldCheck0(theForm) {
    var $active = $(document.activeElement);
    if ($active.attr('name') === 'Action') {
        if (typeof loading === 'function') loading(1);
        return true;
    }
    loading(1);
    var errors = [];
    var fields = [];
    var code = $.trim($('#mfa_code').val());
    if (code === '') {
        errors.push('請輸入驗證碼');
        fields.push('mfa_code');
    } else if (!/^[0-9A-Za-z-]{6,16}$/.test(code.replace(/\s+/g, ''))) {
        errors.push('驗證碼格式不正確');
        fields.push('mfa_code');
    }
    if (errors.length) {
        return window.manageFormValidationFail(errors, {
            focusField: fields[0],
            form: theForm
        });
    }
    return window.manageFormValidationOk(theForm);
}
<?php echo script_close(); ?>
</head>
<body class="loginPage">
    <?php require_once '../_header.php'; ?>
    <div class="wrap login">
        <form action="" method="post" name="form1" id="form1" data-manage-validate="fieldCheck0">
            <?php if ($errorMsg !== ''): ?>
            <div class="errorArea" id="formErrorArea" aria-live="polite">
                <div class="errorArea__header">錯誤訊息</div>
                <div class="errorArea__body">
                    <ul id="formErrorList"><li><?= e($errorMsg) ?></li></ul>
                </div>
            </div>
            <?php else: ?>
            <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                <div class="errorArea__header">錯誤訊息</div>
                <div class="errorArea__body">
                    <ul id="formErrorList"></ul>
                </div>
            </div>
            <?php endif; ?>
            <div class="box">
                <h1><?= e((string)$WebName) ?><span>雙因素驗證</span></h1>
                <p class="loginRecaptchaHint">帳號 <strong><?= e($mfaLoginId) ?></strong> 已通過密碼驗證，請開啟 Authenticator App 輸入 6 位數驗證碼，或輸入備用碼。</p>
                <div class="item">
                    <label for="mfa_code">驗證碼</label>
                    <input type="text" name="mfa_code" id="mfa_code" class="formInput" inputmode="numeric" autocomplete="one-time-code" maxlength="16" autofocus placeholder="6 位數或備用碼">
                </div>
                <button type="submit" name="Submit" id="Submit" value="送出" class="full">驗證並登入</button>
                <button type="submit" name="Action" value="cancel" class="full btnStyle--outline" style="margin-top:8px;">取消並返回登入</button>
                <input type="hidden" name="csrf_token" value="<?= e((string)$csrf_token) ?>">
            </div>
        </form>
        <p class="copyright">支援 Google Authenticator、Microsoft Authenticator 等 TOTP App</p>
    </div>
    <?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
