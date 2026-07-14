<?php
require_once("_inc.php");
if (strtolower($filter_array['Action']) == 'logout'){
	session_destroy();
	manage_alert_script('你已執行登出動作', 'login/index.php');
	exit;
}

manage_bootstrap_admin_account();

If ($_SESSION['Manage'] == 'Yes' && strlen($_SESSION["Login_ID"]) > 0){
	location_href('login/login.php');
	exit();
} else{
	location_href('login/index.php');
	exit();
}

$mainitem="index";
$subname=$pageTitle2; 
$is_list="1";
$unitprocess=$url_link.$pageTitle2;
