<?php

/**
 * 活動報名表單處理（events 內頁 POST）
 *
 * POST 含 CSRF；驗證後寄信並寫入活動報名相關表。
 */
$pageName    = "p3";
$subPageName = "p3_1";
require_once("_inc.php");

$__csrf_key = 'web_form';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 取得表單傳來的 Token 與 Session 裡的 Token
    $user_token = trim((string)($_POST['csrf_token'] ?? $_POST['csrf'] ?? ''));
    if (!is_array($_SESSION['csrf'] ?? null)) {
        $_SESSION['csrf'] = [];
    }
    $session_token = (string)($_SESSION['csrf'][$__csrf_key] ?? '');

    // 2. 使用 hash_equals 進行抗時序攻擊的比對
    if (!empty($session_token) && hash_equals($session_token, $user_token)) {
        // 驗證成功：清除 Token（落實一次性限制）並繼續處理登入
        unset($_SESSION['csrf'][$__csrf_key]);
    } else {
        // 驗證失敗：可能是偽造請求
        http_response_code(403);
        die("CSRF 驗證失敗，請求無效。");
		location_href('./');
    }
}

//我不是機器人（金鑰來自 .env RECAPTCHA_SECRET，與後台相同）
$secret_key = recaptcha_secret_key();
if ($secret_key !== '') {
	$resToken = trim((string)($_POST['g-recaptcha-response'] ?? ''));
	$verify   = recaptcha_siteverify($secret_key, $resToken, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
	if (empty($verify['success'])) {
		$MSG .= $lang_text['chk_google_code'][$this_lang] . "\\n";
	}
}

//姓名
if ( empty($filter_array['name']) or !is_string($filter_array['name']) ){
	$MSG .= $lang_text["chk_name"][$this_lang]."\\n";

} else {
	$name = SqlFilter($filter_array['name'],"str");
}

//報名人數
if ( empty($filter_array['num']) or !is_string($filter_array['num']) ){
	$MSG .= $lang_text["chk_num"][$this_lang]."\\n";

} else {
	$num = SqlFilter($filter_array['num'],"str");
}

//
if ( empty($filter_array['cellphone']) or !is_string($filter_array['cellphone']) ){
	$MSG .= $lang_text["chk_cellphone"][$this_lang]."\\n";

} else {
	$cellphone = SqlFilter($filter_array['cellphone'],"str");
}

if (
    empty($filter_array['howevent']) ||
    !is_string($filter_array['howevent'])
){
    $MSG .= $lang_text["chk_howevent"][$this_lang]."\\n";
} else {
    $howevent = SqlFilter($filter_array['howevent'], "str");
}

//email
if ( empty($filter_array['email']) or !is_string($filter_array['email']) ){
	$MSG .= $lang_text["chk_email"][$this_lang]."\\n";

} else {
	if ( !CheckMail($filter_array['email']) ){
		$MSG .= $lang_text["chk_email_rule"][$this_lang]."\\n";//請輸入正確的【電子信箱】

	} else {
		$email = SqlFilter($filter_array['email'],"str");
	}
}

//洽詢內容
if ( empty($filter_array['description']) or !is_string($filter_array['description']) ){
	// $MSG .= $lang_text["chk_description"][$this_lang]."\\n";

} else {
	$description = SqlFilter($filter_array['description'],"str");
}

if ( $MSG == "" ){

	$mail_subject = $m_title."-".$lang_text["mail_subject_contact"][$this_lang];
	$mailTitle = $lang_text["mail_subject_contact"][$this_lang];

	//郵件內容
	$BODY = "<html><head>";
	$BODY .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
	$BODY .= "</head><style type=\"text/css\">";
	$BODY .= ".font1 { font-size: 15px; color: #333333; font-weight: bold;}";
	$BODY .= "</style><body bgcolor=\"#FFFFFF\">";
	$BODY .= "<p>&nbsp;&nbsp;</p><div >";
	$BODY .= "<center>";
	$BODY .= "<table width=\"70%\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\" style=\"border-collapse: collapse\" bordercolor=\"#C6C6C6\" align=\"center\">";
	$BODY .= "<tr>";
	$BODY .= "<td colspan=\"2\" bgcolor=\"#3F5768\" align=\"center\">";
	$BODY .= "<font color=\"#ffffff\" font size=\"5\"  font-weight: bold ><b>".$mailTitle."</b></font></td>";
	$BODY .= "</tr>";

	/////需要工程師調整↓↓↓
	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\" width=\"30%\">活動名稱</td>";
	$BODY .= "<td>活動資訊範例，移民 A 計畫：美國菁選移民途徑 4U｜實體說明會開放報名中</td>";
	$BODY .= "</tr>";
	//
	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\" width=\"30%\">活動詳情</td>";
	$BODY .= "<td>日期 | 2026年10月17日（六）~ 10月18日（日）10:00–17:00<br>地點 | 台北國際會議中心 (臺北市信義區信義路五段1號)<br>形式 | 開放式展會，人潮眾多，敬請把握機會</td>";
	$BODY .= "</tr>";
	/////需要工程師調整↑↑↑

	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\" width=\"30%\">".$lang_text["field_name"][$this_lang]."</td>";
	$BODY .= "<td>".$name."</td>";
	$BODY .= "</tr>";

	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\">".$lang_text["field_num"][$this_lang]."</td>";
	$BODY .= "<td>".$num."</td>";
	$BODY .= "</tr>";

	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\">".$lang_text["field_cellphone"][$this_lang]."</td>";
	$BODY .= "<td>".$cellphone."</td>";
	$BODY .= "</tr>";

	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\">".$lang_text["field_email"][$this_lang]."</td>";
	$BODY .= "<td>".$email."</td>";
	$BODY .= "</tr>";

	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\">".$lang_text["field_howevent"][$this_lang]."</td>";
	$BODY .= "<td>".$howevent."</td>";
	$BODY .= "</tr>";

	$BODY .= "<tr>";
	$BODY .= "<td class=\"font1\">".$lang_text["field_description"][$this_lang]."</td>";
	$BODY .= "<td>".$description."</td></tr>";
	$BODY .= "</tr>";

	$BODY .= "</table></center></div></body></html>";

	//寄給管理者
	$mg_from_title = SqlFilter($name,"tab");
	//$mg_from_mail  = SqlFilter($email,"tab");//來自-表單
	$mg_from_mail  = $m_from_mail;//來自-後台-SEO基本設定
	$mg_to_title   = $m_title;//來自-後台-SEO基本設定
	$mg_to_mail    = $m_to_mail;//來自-後台-SEO基本設定

	/*echo "mg_from_title=".$mg_from_title."<br>";
	echo "mg_from_mail=".$mg_from_mail."<br>";
	echo "mg_to_title=".$mg_to_title."<br>";
	echo "mg_to_mail=".$mg_to_mail."<br>";
	echo "BODY=".$BODY."<br>";
	exit;*/

	SendMail($mg_to_title,$mg_to_mail,$mg_from_title,$mg_from_mail,$mail_subject,$BODY);
	      //(收件者,收件信箱,寄件者,寄件信箱,主旨,內文)

	manage_alert_script(
		(string)$lang_text['mail_send_ok'][$this_lang],
		$web_url . $lang_text['folder_str'][$this_lang]
	);

} else {
	manage_alert_script(
		(string)$lang_text['warn_msg_error'][$this_lang] . "\n" . (string)$MSG,
		null,
		true
	);
}
?>
