<?php
declare(strict_types=1);

/**
 * 前台預覽密碼閘道（default.php）
 *
 * 密碼：.env MAINTENANCE_GATE_PASSWORD
 * 通過後寫入 Session，redirect 僅允許站內相對路徑（safe_redirect）。
 */
require('_inc.php');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string)($_POST['Send'] ?? '') === 'OK') {
    $expectedPass  = maintenance_gate_password_from_env();
    $submittedPass = (string)($_POST['pass'] ?? '');

    if ($expectedPass === null) {
        error_log('[brick6] MAINTENANCE_GATE_PASSWORD 未於 .env 設定，維護閘道無法驗證。');
        $_SESSION['login_error'] = '系統設定未完成，請聯絡管理員';
        $selfPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        safe_redirect(is_string($selfPath) && $selfPath !== '' ? $selfPath : '/default.php');
    }

    if (hash_equals($expectedPass, $submittedPass)) {
        $_SESSION['isPass'] = 'Y';

        $redirect = trim((string)($_POST['redirect'] ?? ''));
        if ($redirect !== '') {
            safe_redirect($redirect);
        }

        safe_redirect('index.htm');
    }

    $_SESSION['login_error'] = '驗證通行碼不正確';
    $selfPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    safe_redirect(is_string($selfPath) && $selfPath !== '' ? $selfPath : '/default.php');
}
?>

<!DOCTYPE html>
<html <?php echo $lang; ?>>
<head>
<?php require("_in_code_head.php"); ?>
<?php require("_in_javascript.php"); ?>
<link href="<?php echo $web_url; ?>css/style.css?ver=<?php echo filemtime('css/style.css') ?>" rel="stylesheet" charset="utf-8">
</head>

<body <?php if (!empty($bodytxt)) {echo $bodytxt;} ?> class="body-no-padding default-page" <?php if (isset($_SESSION['login_error'])) { echo 'data-error-msg="' . htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8') . '"'; unset($_SESSION['login_error']); } ?>>
<section class="setting-box">
    <figure><img src="<?php echo $web_url; ?>images/default/index-default.png" alt="網站維護中"></figure>
	<form name="form1" id="form1" method="post" action="" class="login-root">
        <input name="pass" id="pass" type="password" placeholder="請輸入驗證通行碼" autocomplete="current-password">
        <button class="btn-style ml-auto mr-auto mt-30" name="Submit" id="Submit" type="button" value="確認送出">確認送出</button>
	    <input type="hidden" name="Send" id="Send">
        <input type="hidden" name="csrf" value="<?php if(isset($CSRF)){echo $CSRF;}?>" />
    </form>
</section>

<?php echo script_src_tag($web_url . 'js/default-page.js?ver=' . filemtime('js/default-page.js')); ?>
</body>
</html>
