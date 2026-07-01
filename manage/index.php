<?php
require_once("_inc.php");
if (strtolower($filter_array['Action']) == 'logout'){
	session_destroy();
	manage_alert_script('你已執行登出動作', 'login/index.php');
	exit;
}

// 依等級產生符合規則的初始密碼（僅建立帳號當下用一次）
//$initial_secret = generate_initial_secret($POLICY, 12, 20);
switch($Web_Secure){
	case 1://免費專業方案
		$initial_secret = 'brick4080';//預設值
		break;
	case 2://付費高階方案
		$initial_secret = 'Brick4080';//預設值
		break;
	case 3://公家普級方案
		$initial_secret = 'Aa@4080';//預設值
		break;
	default:
		$initial_secret = 'Brick4080';//預設值
		break;
}
$sql = "select * From webcontrol Where strID = 'Admin'";	
$rs = new recordset($sql);
if ($rs->eof){
	$sql_query = new dbPDO();
	$table_name = 'webcontrol';

	$data_array['strName'] = '網站管理者';
	$data_array['intType'] = '1';
	$data_array['strID'] = 'Admin';
	$data_array['strPW'] = hash_password($initial_secret);
	$data_array['FunctionID'] = '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15';
	$data_array['UserID'] = 'Admin';
	$data_array['dtUDate'] = date('Y-m-d H:i:s');
	$data_array['dtDate'] = date('Y-m-d H:i:s');
	$sql_query->insert($table_name,$data_array);
	unset($data_array);
	$sql_query->close();
}

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
