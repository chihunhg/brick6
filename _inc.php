<?php
declare(strict_types=1);
session_start();  //啟動 session

// ---- 時區與輸出編碼 / Cache ----
date_default_timezone_set('Asia/Taipei');
header('Content-Type: text/html; charset=utf-8');
// private 可讓瀏覽器快取，但不會被 proxy 共用；若不希望快取可改 no-store
header('Cache-Control: private');

// ---- 錯誤顯示（依環境）----
$env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production';
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
if ($env === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
}

// ---- 引入共用 ----
require_once dirname(__FILE__).'/include/host.php';
require_once dirname(__FILE__).'/include/Conn.php';
require_once dirname(__FILE__).'/include/dbclass.php';
require_once dirname(__FILE__).'/include/Function.php';
require_once dirname(__FILE__).'/include/log.php';
require_once dirname(__FILE__).'/include/sec.php';
require_once dirname(__FILE__).'/include/crud_helpers.php';
require_once dirname(__FILE__).'/include/frontend_helpers.php';
require_once dirname(__FILE__).'/include/frontend_visit_log.php';
send_frame_options_header('DENY');
send_security_headers();
// ---- 載入語系檔 ----
require_once dirname(__FILE__).'/_code_lang.php';
// reCAPTCHA：優先 .env（與後台 manage/login 相同）
if (recaptcha_site_key() !== '') {
	$google_web_key = recaptcha_site_key();
}
if (recaptcha_secret_key() !== '') {
	$google_chk_key = recaptcha_secret_key();
}
// ---- 基本變數 ----
//語系
$this_lang = 1;//預設
if ( !empty($filter_array['this_lang']) && is_numeric($filter_array['this_lang']) ){
	$this_lang = $filter_array['this_lang'];
}
$WorkFile   = basename(__FILE__);
$upload_forder = 'Upload/';
// 先初始化，避免未定義 Notice
$m_description = '';
$m_keywords    = '';
/*
 * 前台共用單元 Module_PKey 定義：include/frontend_modules.php
 * 程式請用 frontend_module_pkey('news') 等，勿直接寫數字。
 */
//產生選單資料
$Array_MU_PKey = array();//單元主鍵
$Array_MU_Name = array();//單元名稱
$Array_MU_Link = array();//前台單元連結
$Array_MU_DLink = array();//前台明細連結
$Array_MU_List = array();//列表版面代碼
$Array_MU_Deatail = array();//內頁版面代碼
$Array_MU_Layer = array();//單元層級
$Array_MU_Description = array();//內頁版面代碼
$Array_MU_Keywords = array();//單元層級
$Array_MU_SeoTitle = array();//單元 SEO 標題（lang.Title，無則 strName）

$sql = 'Select * from view_module_lang Where intPage= 2 and intLang= :intLang and Upload=\'Yes\' and PageLink <> \'\' Order By PKey';
$rs = new recordset($sql,['intLang'=>$this_lang]);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql.PHP_EOL.array_to_string(array($this_lang)),$SQL_Error,$WorkFile,'system');
	echo '<pre>';
	print_r($result);
	echo '</pre>';
	exit;
}
while (! $rs->eof){
	$i = $rs->field("PKey");
	$Array_MU_PKey[$i] = $rs->field("PKey");
	$Array_MU_Name[$i] = $rs->field("strName");
	$Array_MU_SeoTitle[$i] = crud_lang_seo_title([
		'Title'   => (string)($rs->field('Title') ?? ''),
		'strName' => (string)$rs->field('strName'),
	]);
	$Array_MU_Link[$i] = $rs->field("PageLink");
	$Array_MU_List[$i] = $rs->field("intList");
	$Array_MU_DLink[$i] = str_ireplace('.htm','_detail',$rs->field("PageLink"));
	$Array_MU_Deatail[$i] = $rs->field("intDetail");
	$Array_MU_Description[$i] = $m_description;
	if($rs->field("Description")!= ''){
		$Array_MU_Description[$i] = $rs->field("Description");
	}
	$Array_MU_Keywords[$i] = $m_keywords;
	if($rs->field("Keywords")!= ''){
		$Array_MU_Keywords[$i] = $rs->field("Keywords");
	}
	$Array_MU_Layer[$i] = $rs->field("intLayer");
$rs->movenext();
}
$rs->close();

//取出網站設定值
$cond_array = array();
$cond_array['intLang'] = SqlFilter($this_lang,"int");
$sql  = ' Select *  from webset Where intLang= :intLang  ';
$rs = new recordset($sql,$cond_array);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if ( !empty($SQL_Error) ){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql,$SQL_Error,$_SERVER['PHP_SELF'],'system');
	echo '<pre>';
	print_r($result);
	echo '</pre>';
	exit;
}
if ( !$rs->eof ){
	$pageTitle = $rs->field("strName");
	$pageTitle2 = $rs->field("strName");
	$web_title = $rs->field("strName");
	
	$m_keywords = $rs->field("Keywords");
	$m_description = $rs->field("Description");
	
	$Web_Name = $rs->field("strName");
	$Web_Tel = $rs->field("Tel");
	$Web_Fax = $rs->field("Fax");
	$Web_EMail = $rs->field("ToMail");
	$Web_Address = $rs->field("Address");
	//$Web_GoogleMap1 = $rs->field("GoogleMap1");
	//$Web_GoogleMap2 = $rs->field("GoogleMap2");
	
	$Web_Facebook = $rs->field("Facebook");
	$Web_LINE = $rs->field("LINE");
	$Web_Twitter = $rs->field("Twitter");
	$Web_IG = $rs->field("IG");
	$Web_linkedin = $rs->field("linkedin");
	$Web_Youtube = $rs->field("Youtube");
	$Web_Wechat = $rs->field("Wechat");
	
	$Web_gaCode = $rs->field("gaCode");
	//$Web_gtmCode = $rs->field("gtmCode");
	
	$ToMail = $rs->field("ToMail");
	//$ToMail2 = $rs->field("ToMail2");
	
	//通知信
	$m_title = $pageTitle2;
	$m_from_mail = $rs->field("FromMail");
	$m_to_mail = $rs->field("ToMail");
}
$rs->close();
unset($cond_array);

//清空 $_SESSION['form_error']
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// ---- CSRF：前台表單（contact / events 等）----
$__csrf_key = 'web_form';
if (!is_array($_SESSION['csrf'] ?? null)) {
    $_SESSION['csrf'] = [];
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['csrf'][$__csrf_key])) {
    $_SESSION['csrf'][$__csrf_key] = bin2hex(random_bytes(32));
}
$csrf_token = (string)($_SESSION['csrf'][$__csrf_key] ?? '');
?>