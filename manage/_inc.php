<?php
session_start();  //啟動 session
/* ---- 輸出編碼 & 快取 ---- */
if (!empty($manage_binary_export)) {
	// Excel／二進位下載：勿送 text/html，由 output.php 設定 Content-Type
} else {
	header('Content-Type: text/html; charset=utf-8');
	if (!empty($manage_csp_editor)) {
		header('Cache-Control: no-store, no-cache, must-revalidate, private');
	} else {
		header('Cache-Control: private');
	}
}
date_default_timezone_set('Asia/Taipei');

require_once dirname(dirname(__FILE__)).'/include/host.php';//引入文件（須先載入 .env）
app_configure_error_display();
require_once dirname(dirname(__FILE__)).'/include/Conn.php';//引入文件
require_once dirname(dirname(__FILE__)).'/include/dbclass.php';//引入文件
require_once dirname(dirname(__FILE__)).'/include/Function.php';//引入文件
require_once dirname(dirname(__FILE__)).'/include/log.php';//引入文件
require_once dirname(dirname(__FILE__)).'/include/sec.php';//引入文件
send_frame_options_header('DENY');
if (empty($manage_binary_export)) {
	if (!empty($manage_csp_editor)) {
		send_manage_editor_security_headers();
	} else {
		send_manage_security_headers();
	}
}

//後台模組使用
require_once dirname(dirname(__FILE__)).'/include/common.php';//引入文件

// *** Include the class 載入縮圖元件
require_once dirname(dirname(__FILE__)).'/include/image.php';

//語系設定
//$array_lang = array(1=>'英文',2=>'繁中',3=>'簡中');
$array_lang = array();
$i = 0;
$Cond_Array['Upload'] = 'Yes';
$sql ='Select * from language Where Upload= :Upload Order By Sort';
$rs = new recordset($sql,$Cond_Array);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
	
	app_show_bootstrap_error($result);
	exit;
}
while (! $rs->eof){
	$i++;
	$array_lang[$i] = $rs->field('strName');
$rs->movenext();
}
unset($Cond_Array);
//語系（須於載入 _code_lang.php 前設定）
$this_lang = 1;
if (!empty($filter_array['this_lang']) && is_numeric((string)$filter_array['this_lang'])) {
	$this_lang = (int)$filter_array['this_lang'];
}

include_once dirname(dirname(__FILE__)).'/_code_lang.php';

//網站設定
$sql = 'Select * From webset ';
$rs = new recordset($sql);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
	app_show_bootstrap_error($result);
	exit;
}
if(! $rs->eof){
	$WebName = $rs->field('strName');
	$Web_Name = $rs->field('strName');
	$Web_Mail = $rs->field('EMail');
	$Web_Secure = $rs->field('Secure');	
}

//刪除log
switch($Web_Secure){
	case 1://免費專業方案
		$log_day = 10;//Log 記錄保留天數
		$Password_Match = 2;//密碼複雜度符合數目
		$Password_Default = false;//預設密碼變更
		$Password_Repeat = false;//密碼變更不可重複
		$Password_change = false;//密碼強迫變更
		$Password_day = 180;//密碼強迫變更天數
		$Acount_Lock = false;//帳戶鎖定機制
		break;
	case 2://付費高階方案
		$log_day = 30;//Log 記錄保留天數
		$Password_Match = 3;//密碼複雜度符合數目
		$Password_Default = true;//預設密碼變更
		$Password_Repeat = false;//密碼變更不可重複
		$Password_change = false;//密碼強迫變更
		$Password_day = 180;//密碼強迫變更天數
		$Acount_Lock = true;//帳戶鎖定機制
		break;
	case 3://公家普級方案
		$log_day = 90;//Log 記錄保留天數
		$Password_Match = 4;//密碼複雜度符合數目
		$Password_Default = true;//預設密碼變更
		$Password_Repeat = true;//密碼變更不可重複
		$Password_change = true;//密碼強迫變更
		$Password_day = 180;//密碼強迫變更天數
		$Acount_Lock = true;//帳戶鎖定機制
		break;
	default:
		$log_day = 30;//Log 記錄保留天數
		$Password_Match = 3;//密碼複雜度符合數目
		$Password_Default = true;//預設密碼變更
		$Password_Repeat = false;//密碼變更不可重複
		$Password_change = false;//密碼強迫變更
		$Password_day = 180;//密碼強迫變更天數
		$Acount_Lock = true;//帳戶鎖定機制
		break;
}
$default_pw = manage_admin_initial_password_from_env() ?? '';
$sql_d = 'delete from managelog Where datediff(\''.date('Y-m-d').'\', dtDate) > '.$log_day;
execute_sql($sql_d);

$ImageTypeLimit='.GIF.JPG.PNG';
$FileTypeLimit='.JPG.GIF.PNG.PDF.DOC.DOCX.PPT.XLS.XLSX.TXT.ZIP.RAR';
$ExcelLimit='.XLS.XLSX';
$pageTitle1='TSG';
$pageTitle2 = '後端管理系統';
$pageName = $pageName ?? 'index';
$pageTitle = $pageTitle ?? '';
$meta_d='';
$meta_k='';
$subitem = '';
$manNo = 0;
$subNo = 0;
$Module_PKey = 0;
$Module_Name = '';
$ModuleNo = 0;
$home='導覽頁';
$url_link='<img src="../images/icon01.gif" hspace="5"  border="0" align="absmiddle">';
$icon='&nbsp;&#187;&nbsp;';
$line_color='#f7f7f7' ;
$mgw1='管理';
$mgw2='新增/修改';
$mgw3='預覽';
$m98 = '單元版型';
$m83 = '版型圖示';

//權限管理
$s1='首頁單元設定';
$s2='基本資料設定';
$s3='語系設定';
$s5='變更密碼';
$divview='none';

//預設變數
$Keywords = '';
$Class1 = 0;
$Class2 = 0;
$Class3 = 0;
$intLocal = 0;
$OpenDate = '';
$EndDate = '';
$NoEndDate = 0;
$Update_PKey = 0;
$Company_PKey = 0;
$Description = '';
$Sort = 0;
$strName = '';
$Interview = '';
$Movielink = '';
$Target = '';
$strLink = '';
$Home = '';
$Upload = '';
$Contents = '';
$Contents1 = '';
$Contents2 = '';
$Contents3 = '';
$Contents4 = '';
$Contents5 = '';
$Contents6 = '';
$PhotoM = '';
$PhotoM1 = '';
$PhotoM2 = '';
$PhotoM3 = '';
$PhotoM4 = '';
$PhotoM5 = '';
$PhotoM6 = '';
$Class_Name = array();

$remark_view1 = '預覽功能說明：<br> ※當管理者登入網站後台，即為開放管理者之預覽權限，因此在前台仍可見到下架的資料。<br> ※若對資料顯示有疑慮，可將管理者系統登出或另開無痕瀏覽器檢視，該畫面即為非管理者瀏覽之畫面。 ';
$remark_view2 = '<li>'.$remark_view1.'</li>';//列表-備註-顯示 
$remark_view3 =
' <div class="ttpShowZone">
	<button type="button" class="ttpShowZone__trigger" data-manage-action="preview-help-toggle"
		aria-label="預覽功能說明" aria-expanded="false">
		<i class="bi bi-question-circle-fill" aria-hidden="true"></i>
	</button>
	<div class="ttpShow ttpShow--right" role="dialog" aria-hidden="true">
		'.$remark_view1.'
		<button type="button" class="ttpShow__closeBtn" data-manage-action="preview-help-close" aria-label="關閉">X</button>
	</div>
</div>';

$today=date('Y/m/d');
$years = date('Y'); //用date()函式取得目前年份格式0000
$months = date('m'); //用date()函式取得目前月份格式00
$days = date('d'); //用date()函式取得目前日期格式00
$day = date('Y/m/d',mktime(0,0,0,$months+1,$days,$years));

$Keywords = '';
$Action = '';
$remark_pic='<li>圖檔格式只接受JPG,GIF,PNG的檔案，檔案大小限2MB以內。</li>';
$remark_save='<li>檔案名稱請以英數字命名。</li>';
$remark_save2='<li>上傳檔案請以英數字命名，檔案大小限6MB以內。</li>';
$remark_file1='<li>檔案格式只接受JPG,GIF,PDF,DOC,DOCX,PPT,PPTX,XLS,XLSX,TXT,ZIP,RAR的檔案</li>';
$remark_file2='<li>檔案格式只接受PDF。</li>';
$remark_view = '<li>預覽功能說明：<br>
                1. 後台已登入，不管該筆下架與否，皆可瀏覽。<br>
                2. 若要前台列表不顯示，且要將連結提供給其他人，將【列表顯示】勾勾移除，但仍需維持【上架】狀態。<br>
                ※提醒：若該筆於後台下架，則前台不顯示。
                </li>';

//產生選單資料
$Array_MU_PKey = array();//單元主鍵
$Array_MU_Name = array();//單元名稱
$Array_MU_Link = array();//前台單元連結
//單元設定
$Cond_Array['intType'] = 1;
$Cond_Array['Upload'] = 'Yes';
$sql = 'Select * from module_p Where intType= :intType and Upload= :Upload Order By PKey';
$rs = new recordset($sql,$Cond_Array);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
	app_show_bootstrap_error($result);
	exit;
}
while (! $rs->eof){
	$i = $rs->field('PKey');
	$Array_MU_PKey[$i] = $rs->field('PKey');
	$Array_MU_Name[$i] = $rs->field('strName');
	$Array_MU_Link[$i] = $rs->field('strLink');
	$Array_MU_PageSize[$i] = $rs->field('PageSize');
$rs->movenext();
}
$rs->close();
unset($Cond_Array);
$Array_MU_Name[97] = '語系設定';
$Array_MU_Link[97] = 'language';
$Array_MU_Name[98] = '單元設定';
$Array_MU_Link[98] = 'module';

/**
 * 後台「共用選用欄位」顯示開關（依 module_p.PKey，即網址 manNo／$Module_PKey）
 *
 * 【用途】
 * 同一套 _detail.php／addin.php 可服務多個單元，由此陣列決定各單元是否顯示
 * 「首頁呈現、簡述、列表圖、標籤」等共用欄位，無須每單元複製一份表單。
 * 判斷函式：manage_module_show_detail_field($field)
 *
 * 【鍵值說明】
 * - '_default'：未列出的 Module_PKey 套用此預設（通常全 false）
 * - 數字鍵     ：module_p 資料表 PKey（後台單元設定中的主鍵，同 manNo）
 *
 * 【欄位鍵 $field】
 * - home       ：首頁呈現（主表 Home 欄；列表可顯示「首頁」欄）
 * - interview  ：簡述（Interview1、Interview2… 多語系 textarea）
 * - list       ：內容明細「列表圖／Photo1」（paper、news、product 等明細頁）
 * - list_class ：分類模組「列表圖」（class1/2/3 用 list_class，可與 list 分開）
 * - tag        ：關聯標籤（需 _config tag_relation_parent_col；寫入 tag_d）
 *
 * 【新增單元步驟】
 * 1. 後台「單元設定」建立 module_p 後，記下 PKey（即 manNo）
 * 2. 於下方陣列新增：PKey => ['home'=>bool, 'interview'=>bool, 'list'=>bool, 'list_class'=>bool, 'tag'=>bool]
 * 3. 未新增者走 _default（欄位不顯示）；單元 _detail.php 已用 manage_module_show_detail_field() 包住者會自動生效
 * 4. 若 addin.php 有依 $showTagField 等寫入，須與此處 true 一致，否則表單無欄位卻仍寫 DB
 *
 * 【程式引用位置（範例）】
 * - _detail.php：$showHomeField = manage_module_show_detail_field('home');
 * - _list.php  ：$showHomeColumn = manage_module_show_detail_field('home');
 * - addin.php  ：標籤儲存前 if (manage_module_show_detail_field('tag')) { … }
 *
 * 【注意】
 * - 僅控制「共用選用欄位」；單元自訂欄位請直接改該單元 _detail.php／addin.php
 * - list 與 list_class 可不同時為 true（明細有圖、類別列表無圖）
 * - weblink(dbweb)、video 等亦透過此表依 Module_PKey 開關 interview／list／home
 */
$manage_module_detail_fields = [
    '_default' => [
        'home'       => false,
        'interview'  => false,
        'list'       => false,
        'list_class' => false,
        'tag'        => false,
    ],
    // 鍵 = module_p.PKey（manNo）；依實際單元增刪：
    7 => ['home' => true, 'interview' => true, 'list' => true, 'list_class' => false, 'tag' => true],
    8 => ['home' => true, 'interview' => true, 'list' => true, 'list_class' => false, 'tag' => true],
    9 => ['home' => false, 'interview' => false, 'list' => true, 'list_class' => false, 'tag' => false],
    10 => ['home' => true, 'interview' => true, 'list' => true, 'list_class' => false, 'tag' => true],
    13 => ['home' => false, 'interview' => false, 'list' => true, 'list_class' => false, 'tag' => false],
    17 => ['home' => false, 'interview' => false, 'list' => true, 'list_class' => false, 'tag' => false],
    18 => ['home' => false, 'interview' => false, 'list' => true, 'list_class' => false, 'tag' => false],
    20 => ['home' => false, 'interview' => false, 'list' => true, 'list_class' => false, 'tag' => false],
];

if (!function_exists('manage_module_show_detail_field')) {
    /**
     * @param string $field home|interview|list|list_class|tag
     */
    function manage_module_show_detail_field(string $field): bool {
        global $manage_module_detail_fields, $Module_PKey;

        $field = strtolower(trim($field));
        $defaults = $manage_module_detail_fields['_default'] ?? [
            'home'       => false,
            'interview'  => false,
            'list'       => false,
            'list_class' => false,
            'tag'        => false,
        ];
        $mpk = (int)($Module_PKey ?? 0);
        if ($mpk <= 0) {
            return !empty($defaults[$field]);
        }
        $cfg = $manage_module_detail_fields[$mpk] ?? $defaults;
        if (array_key_exists($field, $cfg)) {
            return (bool)$cfg[$field];
        }
        return !empty($defaults[$field]);
    }
}

if(empty($_SESSION['logout'])){
	$_SESSION['logout'] = dateadd('sec',1800,time());
	$_SESSION['logout'] = date('Y-m-d H:i:s',$_SESSION['logout']);
}
$logout = datediff('s',date('Y-m-d H:i:s'),$_SESSION['logout']);

//取出單元權限
$Show_List = array();
$Edit_List = array();
$Del_List = array();
$Login_PKey = 0;
if(!empty($_SESSION['Login_PKey'])&& is_numeric($_SESSION['Login_PKey'])){
	$Login_PKey = $_SESSION['Login_PKey'];
}
$Cond_Array['PKey'] = $Login_PKey;
$sql = 'Select * from webcontrol Where PKey= :PKey';
$rs = new recordset($sql,$Cond_Array);
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql.PHP_EOL.array_to_string($Cond_Array),$SQL_Error,$WorkFile,'system');
	app_show_bootstrap_error($result);
	exit;
}
if(!$rs->eof){
	$Show_List = explode(',', (string)($rs->field('isShow') ?? ''));
	$Edit_List = explode(',', (string)($rs->field('isEdit') ?? ''));
	$Del_List = explode(',', (string)($rs->field('isDel') ?? ''));
}
$rs->close();
unset($Cond_Array);

if((isset($_SESSION['Manage']) and $_SESSION['Manage'] != 'Yes') || empty($_SESSION['Login_ID'])){
	if(! stristr($WorkFile,'index.php')) {
		if (!empty($manage_binary_export)) {
			require_once dirname(dirname(__FILE__)) . '/include/json_response.php';
			json_out(['success' => false, 'error' => '未登入或登入已逾時，請重新登入'], 401);
		}
		manage_alert_script(
			'後台閒置時間過長已自動登出，請重新登入，謝謝。',
			$web_root . 'manage/login/index.php'
		);
		exit;
	}
}
$Login = '';
if(!empty($_SESSION['Login_ID'])){
	$Login_ID = htmlspecialchars($_SESSION['Login_ID'],ENT_QUOTES);
}

//清空 $_SESSION['form_error']
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// ---- CSRF：登入表單用 token（模組 add/update 會再覆寫 $csrf_token）----
$__csrf_key = 'manage_form';
if (function_exists('crud_csrf_ensure')) {
	$csrf_token = crud_csrf_ensure($__csrf_key);
} else {
	$csrf_token = '';
}

$upload_folder = $PathForder.'/Upload' ?? 'D:/Upload'; // 確保是「正確變數名」與「絕對路徑」
// ★ 由設定提供：$PathForder = 'D:\wamp64\www\allring.tgbf\web_p';
$PathForder = isset($PathForder) ? $PathForder : 'D:/wamp64/www/allring.tgbf/web_p';

// ✅ 用 $PathForder.'/Upload/' 取代硬編碼路徑（並標準化分隔符）
$upload_folder = rtrim($PathForder, "/\\") . DIRECTORY_SEPARATOR . 'Upload';

// 可選：若目錄存在則標準化為 realpath（Windows 會轉反斜線）
if (is_dir($upload_folder)) {
    $upload_folder = realpath($upload_folder);
}

require_once __DIR__ . '/_lang_slots.php';
require_once __DIR__ . '/_form_bag.php';
require_once __DIR__ . '/_child_helpers.php';
require_once __DIR__ . '/_child_list.php';
require_once __DIR__ . '/_child_form.php';

?>
