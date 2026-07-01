<?php 
$Module_D_PKey = 0;
$Module_PKey = 0;
$manNo = 0;
$subNo = 0;
if(! empty($_SESSION['manNo'])){
	$manNo = $_SESSION['manNo'];
}
if(! empty($_SESSION['subNo'])){
	$subNo = $_SESSION['subNo'];
}

if (isset($filter_array['manNo'])&&is_numeric($filter_array['manNo'])){
	$manNo = $filter_array['manNo'];
	if ($manNo != $filter_array['manNo']){
		unset($_SESSION["subNo"]);
	}
	$_SESSION['manNo'] = $manNo;
}

$name = '';
switch($page_link){
	case 'add.php':
	$name = '－新增';
	break;
	case 'update.php':
	$name = '－編輯';
	break;
	default:
	$name = '';
	break;
}

//取出主標題
$Cond_Array['PKey'] = $manNo;
$sql = 'Select * from module_p Where PKey= :PKey';
$rs = new recordset($sql,$Cond_Array);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
	echo '<pre>';
	print_r($result);
	echo '</pre>';
	exit;
}
if (! $rs->eof){
	$Module_PKey = $rs->field('PKey');
	$manNo = $rs->field('PKey');
	$Module_Name = $rs->field('strName');
	$Layer = $rs->field('intLayer');
	$Colum = $rs->field("intColum");
	$intUse = $rs->field("intUse");
	$intList = $rs->field('intList');
	$intDetail = $rs->field('intDetail');
	$MaxQ = $rs->field("MaxQ");
	$subname=$Module_Name."：".$Module_Name.$mgw1;
	if($Layer < 1){
	$subname=$Module_Name.$mgw1.$name;
	}
}
unset($Cond_Array);

//取預設子單元
if (empty($subNo)){
	$Cond_Array['Module_PKey'] = $Module_PKey;
	$sql = 'Select * from module_d Where Module_PKey = :Module_PKey Order By Sort';
	$rs = new recordset($sql,$Cond_Array);
	//判斷有無錯誤訊息
	$SQL_Error = $rs->getErrorMessage();
	if(!empty($SQL_Error)){
		//寫入資料庫存取錯誤記錄
		$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
		echo '<pre>';
		print_r($result);
		echo '</pre>';
		exit;
	}
	if (! $rs->eof){
		$Module_PKey = $rs->field('Module_PKey');
		$manNo = $rs->field('Module_PKey');
		$subNo = $rs->field('PKey');
	}
	$rs->close();
	unset($Cond_Array);
}

if (! empty($filter_array['subNo']) && is_numeric($filter_array['subNo'])){
	$subNo = $filter_array['subNo'];
}

//取出子標題
if(! empty($subNo)){
	$Cond_Array['PKey'] = $subNo;
	$sql = 'Select * from module_d Where PKey= :PKey ';
	$rs = new recordset($sql,$Cond_Array);
	//判斷有無錯誤訊息
	$SQL_Error = $rs->getErrorMessage();
	if(!empty($SQL_Error)){
		//寫入資料庫存取錯誤記錄
		$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
		echo '<pre>';
		print_r($result);
		echo '</pre>';
		exit;
	}
	if (! $rs->eof) {
		$Module_D_PKey = (int)$rs->field('PKey');
		$Module_PKey = (int)$rs->field('Module_PKey');
		$manNo = (int)$rs->field('Module_PKey');
		$subNo = (int)$rs->field('PKey');
		if ($Layer > 1) {
			$subname = (string)$rs->field('strName') . $mgw1 . $name;
		}
	}
	$rs->close();
	unset($Cond_Array);
}

//取出子單元的css名稱
$Class_Name = array();
$i = 0;
$Cond_Array['Module_PKey'] = $Module_PKey;
$sql = 'Select strName from module_d Where Module_PKey = :Module_PKey Order By Sort';
$rs = new recordset($sql,$Cond_Array);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
	echo '<pre>';
	print_r($result);
	echo '</pre>';
	exit;
}
while (! $rs->eof){
	$i++;
	$Class_Name[$i] = $rs->field('strName');
	if ($i==$Layer){
		break;
	}
$rs->movenext();
}
$rs->close();
unset($Cond_Array);

$Module_List = array();
if(!empty($_SESSION['FunctionID'])){
	$Module_List = explode(',',$_SESSION['FunctionID']);
}

$GLOBALS['Module_PKey']   = $Module_PKey ?? 0;
$GLOBALS['Module_Name']   = $Module_Name ?? '未知模組';
$GLOBALS['Module_D_PKey'] = (int)($Module_D_PKey ?? 0);
$GLOBALS['Layer']         = (int)($Layer ?? 0);
$GLOBALS['manNo']         = (int)($manNo ?? 0);
$GLOBALS['subNo']         = (int)($subNo ?? 0);
$GLOBALS['subname']       = (string)($subname ?? '');
$GLOBALS['Class_Name']    = is_array($Class_Name ?? null) ? $Class_Name : [];
$GLOBALS['mgw1']          = (string)($mgw1 ?? '管理');

if (! isset($Login_ID)|| ($Login_ID != 'Admin' && !in_array($Module_PKey,$Module_List))){
	$denyMsg = '無進入[' . ($Module_Name ?? '') . ']權限!';
	if (function_exists('manage_alert_script')) {
		manage_alert_script($denyMsg, '../index.php');
	}
	echo manage_inline_script(
		'alert(' . json_encode($denyMsg, JSON_UNESCAPED_UNICODE) . ');'
		. 'location.href=' . json_encode('../index.php', JSON_UNESCAPED_UNICODE) . ';'
	);
	exit;
}
?>