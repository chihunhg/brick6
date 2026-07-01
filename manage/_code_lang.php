<?php
//***此檔案，後端管理系統也有用到，所以請不要亂放Code***
//------------------------------------------------------------------------
//echo 'this_lang='.$this_lang.'<br>';
//------------------------------------------------------------------------
/* 備註
-語系使用變數1(單元)
-語系使用變數2(我不是機器人、資料建置中、警告、共用、功能)
*/
//------------------------------------------------------------------------
//以下這段，為「純美工版本使用」（***若此專案有寫程式，記得寫完程式後，這一整段，要刪掉***）
$pageTitle = "公司名稱";
$pageTitle2 = "公司完整名稱";

$Web_Name = $pageTitle2;
$Web_Tel = $telephone;
$Web_Address = $address;

//meta
$m_keywords = "";
$m_description = "";

//通知信
$m_title = $pageTitle2;//寄件者
$m_from_mail = "noreply@tsg.com.tw";//寄件者
$m_to_mail = "";//收件者
//------------------------------------------------------------------------
//語系使用變數1(單元) 1=>語系1    2=>語系2

$lang_text["lang"] = [//語系
	1 => "lang=\"zh-Hant-TW\"",
	2 => "lang=\"en\"",
];
$lang = $lang_text["lang"][$this_lang];

$lang_text["home"] = [
	1 => "首頁",
	2 => "Home",
];

$lang_text["home2"] = [//結構化使用(勿刪)
	1 => "首頁",
	2 => "Home",
];

$lang_text['p1'] = [
	'p1_page' => '&&demo.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
        'p1' => '天矽範本',//demo用可以改掉
			'p1_1' => '第1層',
			'p1_2' => '第1層-2',
				'p1_2_1' => '第2層',
				'p1_2_2' => '第2層-2',
				'p1_2_3' => '第2層-3',
					'p1_2_3_1' => '第3層',
					'p1_2_3_2' => '第3層-2',
					'p1_2_3_3' => '第3層-3',
			'p1_3' => '第1層-3',
			'p1_4' => '第1層-4',
    ],
    2 => [
        'p1' => 'About HSM',
			'p1_1' => "HSM Philosophy",
			'p1_2' => "Factory tour",
			'p1_3' => "Core Values",
			'p1_4' => "Our Milestones",
    ],
];

$lang_text['p2'] = [
	'p2_page' => 'about.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
        'p2' => '關於我們',
			'p2_1' => 'HSM 哲學',
			'p2_2' => '工廠參觀',
			'p2_3' => '核心價值',
			'p2_4' => '我們的里程碑',
    ],
    2 => [
        'p2' => 'Products',
			'p2_en' => "Products",
			'p2_title1' => "Product Information",
			'p2_title2' => "Download",
			'p2_download_msg' => "※ The above PDF Specifications are for reference only.",
    ],
];

$lang_text['p3'] = [
	'p3_page' => 'application.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
		'p3' => "產品應用",
			'p3_en' => "Application",
			'p3_title1' => "相關產品",
    ],
    2 => [
		'p3' => "Application",
			'p3_en' => "Application",
			'p3_title1' => "Related Products",
    ],
];

$lang_text['p4'] = [
	'p4_page' => 'contact.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
		'p4' => "投資人專區",
		'p4_1' => "財務訊息",//demo
			'p4_1_1' => "重大訊息",
			'p4_1_2' => "資安公告",
			'p4_1_3' => "最新財務",
			'p4_1_4' => "歷史財務",
			'p4_1_5' => "財務報告",
		'p4_2' => "股東服務",//demo
			'p4_2_1' => "法人說明會",
			'p4_2_2' => "股價資訊",
			'p4_2_3' => "股東會資料",
		'p4_3' => "公司治理",//demo
			'p4_3_1' => "公司治理",
			'p4_3_2' => "董事會",
			'p4_3_3' => "功能性委員會",
			'p4_3_4' => "推動永續發展",
			'p4_3_5' => "履行誠信經營",
			'p4_3_6' => "公司規章辦法",
		'p4_4' => "第一階可擴充",//demo
    ],
    2 => [
		'p4' => "Contact",
    ],
];

$lang_text['p5'] = [
	'p5_page' => 'contact.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
		'p5' => "聯絡我們",
    ],
    2 => [
		'p5' => "Contact",
    ],
];
//------------------------------------------------------------------------
//語系使用變數2(我不是機器人、資料建置中、警告、共用、功能)
//====================================================================
//我不是機器人
$google_web_key = "6LcGickiAAAAANNBlcK71rm4wQLjmzUHYvSMKE0i";//金鑰(頁面用)
$google_chk_key = "6LcGickiAAAAAKfB2YuuO1zsVhvo_8YDKJf5zR3a";//密鑰(檢核用)
//====================================================================
//資料建置中
$lang_text["no_data_str"] = array(
	1=>"<div style=\"text-align:center;color:red;padding:50px 0 0 0;;width:100%;\">資料建置中</div>",
	2=>"<div style=\"text-align:center;color:red;padding:50px 0 0 0;;width:100%;\">Under Construction</div>",
); 

$lang_text["no_data_str2"] = array(
	1=>"<tr><td colspan=\"100\" align=\"center\" style=\"padding: 50px 0;text-align:center;color:red;font-weight:100;\">資料建置中</td></tr>",
	2=>"<tr><td colspan=\"100\" align=\"center\" style=\"padding: 50px 0;text-align:center;color:red;font-weight:100;\">Under Construction</td></tr>",
);
//====================================================================
//警告
$lang_text["warn_data_not_found"] = array(//alert
	1=>"查無資料",
	2=>"Not Found",
);

$lang_text["warn_msg_error"] = array(//alert
	1=>"發生錯誤，請填寫下列欄位\\n",
	2=>"Error, Please fill the required question\\n",
);

$lang_text["warn_err"] = array(//alert
	1=>"錯誤",
	2=>"Error",
);

$lang_text["warn_submitting"] = array(
	1=>"表單正在提交中，請稍等...",
	2=>"Form submission in progress, please wait a moment...",
);
//====================================================================
//共用
$lang_text["sub_menu"] = array(//下拉
	1=>"次選單",
	2=>"Sub menu",
);

$lang_text["btn_more"] = array(
	1=>"詳細介紹",
	2=>"Read More",
);

$lang_text["btn_inquiry"] = array(
	1=>"加入詢價",
	2=>"Inquiry",
);

$lang_text["btn_back"] = array(
	1=>"回上一頁",
	2=>"Back To",
);

$lang_text["btn_search"] = array(
	1=>"搜尋",
	2=>"Search",
);

$lang_text["warn_input_keyword"] = array(//alert
	1=>"請輸入關鍵字",
	2=>"Please enter keyword",
);

$lang_text["pr_search"] = array(//placeholder
	1=>"請輸入關鍵字",
	2=>"Please enter keyword",
);

$lang_text["btn_submit"] = array(
	1=>"送出",
	2=>"Submit",
);
//====================================================================
//聯絡我們
$lang_text["field_name"] = array(//欄位
	1=>"姓名",
	2=>"Name",
);

$lang_text["chk_name"] = array(//alert
	1=>"請輸入【姓名】",
	2=>"You must enter a value in【Name】",
);

$lang_text["field_email"] = array(//欄位
	1=>"E-mail",
	2=>"E-mail",
);

$lang_text["chk_email"] = array(//alert
	1=>"請輸入【E-mail】",
	2=>"You must enter a value in【E-mail】",
);

$lang_text["chk_email_rule"] = array(//alert
	1=>"請輸入正確的【E-mail】",
	2=>"Please enter the correct value【E-mail】",
);

$lang_text["chk_email_err"] = array(//alert
	1=>"【E-mail】格式錯誤",
	2=>"【E-mail】Error",
);

$lang_text["field_company"] = array(//欄位
	1=>"公司名稱",
	2=>"Company",
);

$lang_text["chk_company"] = array(//alert
	1=>"請輸入【公司名稱】",
	2=>"You must enter a value in【Company】",
);

$lang_text["field_department"] = array(//欄位
	1=>"部門",
	2=>"Department",
);

$lang_text["field_tel"] = array(//欄位
	1=>"電話",
	2=>"Tel",
);

$lang_text["chk_tel"] = array(//alert
	1=>"請輸入【電話】",
	2=>"You must enter a value in【Tel】",
);

$lang_text["field_fax"] = array(//欄位
	1=>"傳真",
	2=>"Fax",
);

$lang_text["field_address"] = array(//欄位
	1=>"地址",
	2=>"Address",
);

$lang_text["field_country"] = array(//欄位
	1=>"國家",
	2=>"Country",
);

$lang_text["field_description"] = array(//欄位
	1=>"洽詢內容",
	2=>"Message",
);

$lang_text["chk_description"] = array(//alert
	1=>"請輸入【洽詢內容】",
	2=>"You must enter a value in【Message】",
);

$lang_text["field_google_code"] = array(//欄位
	1=>"驗證碼",
	2=>"Recaptcha",
);

$lang_text["chk_google_code"] = array(//alert
	1=>"【我不是機器人】請點選",
	2=>"【I am not a robot】Please click",
);

$lang_text["mail_subject_contact"] = array(//主旨
	1=>"聯絡我們",
	2=>"Contact",
);

$lang_text["mail_send_ok"] = array(//alert
	1=>"謝謝您的來信，我們將儘速為您服務",
	2=>"Thank you for the inquiry, we will contact you asap.",
);
//------------------------------------------------------------------------
//***此檔案，後端管理系統也有用到，所以請不要亂放Code***
?>