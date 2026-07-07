<?php
require("_inc.php");

if ( $_REQUEST["Send"] == "OK" ){

	if ( $_POST["pass"] == "89904080" ){

		$_SESSION["isPass"] = 'Y';
		//echo '$_SESSION["isPass"]='.$_SESSION["isPass"];exit;

		//setcookie("isPass",'Y',time()+86400);
		if ( isset($_REQUEST["redirect"]) ){
			location_href($_REQUEST["redirect"]);
		}

		//echo '$_SESSION["isPass"]='.$_SESSION["isPass"].'<br>';
		//exit;

		location_href("index.htm");

	} else {
		// CSP 安全：使用 data 屬性傳遞錯誤訊息
		$error_msg = '驗證通行碼不正確';
		// 將錯誤訊息存到 session，在頁面載入時顯示
		$_SESSION['login_error'] = $error_msg;
		location_href($_SERVER['REQUEST_URI']);
		exit();
	}
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
        <input name="pass" id="pass" type="text" placeholder="請輸入驗證通行碼">
        <button class="btn-style ml-auto mr-auto mt-30" name="Submit" id="Submit" type="button" value="確認送出">確認送出</button>
	    <input type="hidden" name="Send" id="Send">
        <input type="hidden" name="csrf" value="<?php if(isset($CSRF)){echo $CSRF;}?>" />
    </form>
</section>

<?php echo script_src_tag($web_url . 'js/default-page.js?ver=' . filemtime('js/default-page.js')); ?>
</body>
</html>
