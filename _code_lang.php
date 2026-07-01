<?php
//echo 'this_lang='.$this_lang.'<br>';
//------------------------------------------------------------------------
/* 備註
-語系使用變數1(單元)
-語系使用變數2(我不是機器人、資料建置中、警告、共用、功能)
*/
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

$lang_text["index"] = [
	'p0_page' => 'index.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
        'ixAbout_en' => 'Endeavor Global Advisory',
		'ixAbout_title1' => '承遠，<br class="--mb">代表一份長期投入、<br class="--mb">審慎判斷的承諾',
		'ixAbout_title2' => '期望成為您在全球身分與未來人生規劃中，值得信賴的專業顧問夥伴。',
		'ixAbout_btn' => '關於承遠',
		'ixProcess_en' => 'Process',
		'ixProcess_title' => '服務流程',
		'ixProcess1_1' => '初步諮詢',
		'ixProcess1_2' => '釐清核心目標，制定初步藍圖。',
		'ixProcess2_1' => '方案規劃',
		'ixProcess2_2' => '根據家庭、生活與財務現況，規劃最適合的方案。',
		'ixProcess3_1' => '申請準備',
		'ixProcess3_2' => '專業團隊處理申請文件，確保符合多變的審查要求。',
		'ixProcess4_1' => '身分取得',
		'ixProcess4_2' => '協助取得並維持第二身分。',
		'ixNews_btn' => '更多消息',
    ],
    2 => [
        'ixAbout_en' => 'Endeavor Global Advisory',
		'ixAbout_title1' => 'Endeavor represents a commitment to long-term devotion and thoughtful analysis.',
		'ixAbout_title2' => 'We aspire to be a trusted advisory partner in your global residency and citizenship and future planning.',
		'ixAbout_btn' => 'About Endeavor Global',
		'ixProcess_en' => 'Process',
		'ixProcess_title' => 'Service Process',

		'ixProcess1_1' => 'Initial Consultation',
		'ixProcess1_2' => 'Understanding your primary goals for global identity to establish a clear roadmap.',

		'ixProcess2_1' => 'Strategic Planning',
		'ixProcess2_2' => 'Designing the most suitable strategy based on your family circumstances, lifestyle preferences, and financial situation.',

		'ixProcess3_1' => 'Application Preparation',
		'ixProcess3_2' => 'Experienced team manages the application documents, ensuring compliance with evolving immigration requirements.',

		'ixProcess4_1' => 'Status Acquisition & Maintenance',
		'ixProcess4_2' => 'Assistance in the identity approval and ongoing support to help maintain your residency or citizenship status.',

		'ixNews_btn' => 'View More',
    ],
];

$lang_text['p1'] = [
	'p1_page' => 'about.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
        'p1' => '關於我們',//demo用可以改掉
		'p1_en' => 'About',
    ],
    2 => [
        'p1' => 'About Us',
		'p1_en' => 'About',
    ],
];

$lang_text['p2'] = [
	'p2_page' => 'migration-services.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
        'p2' => '各國移民',
		'p2_en' => 'Immigration Programs',
			'p2_1' => '美加移民',
			'p2_2' => '歐盟移民',
			'p2_3' => '快速護照',
			'p2_4' => '投資居留',
			'p2_5' => '大類可擴充',
    ],
    2 => [
        'p2' => 'Immigration Programs',
		'p2_en' => "Immigration Programs",
			'p2_1' => '',
			'p2_2' => '',
			'p2_3' => '',
			'p2_4' => '',
    ],
];

$lang_text['p3'] = [
	'p3_page' => 'events.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
		'p3' => "活動資訊",
		'p3_en' => "Events",
			'p3_1' => "說明會",
			'p3_2' => "移民展",
			'p3_3' => '投資居留',
    ],
    2 => [
		'p3' => "Events",
		'p3_en' => "Events",
			'p3_1' => '',
			'p3_2' => '',
			'p3_3' => '',
    ],
];

$lang_text['p4'] = [
	'p4_page' => 'news.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
		'p4' => "最新消息",
		'p4_en' => "News",
			'p4_1' => "所有資訊",
			'p4_2' => "政經情況",
			'p4_3' => "國際局勢",
			'p4_4' => "大類可擴充",

    ],
    2 => [
		'p4' => "News",
		'p4_en' => "News",
			'p4_1' => '',
			'p4_2' => '',
			'p4_3' => '',
			'p4_4' => '',
    ],
];

$lang_text['p5'] = [
	'p5_page' => 'contact.htm',//單元預設頁面(麵包屑、結構化使用)
    1 => [
		'p5' => "聯絡我們",
		'p5_en' => "Contact Us",
    ],
    2 => [
		'p5' => "Contact",
    ],
];
//------------------------------------------------------------------------
//語系使用變數2(我不是機器人、資料建置中、警告、共用、功能)
//====================================================================
//我不是機器人
$google_web_key = "6Lf6TY0qAAAAAAT8uqFYrgXkdDKuH1B7z6_UidFS";//金鑰(頁面用)
$google_chk_key = "6Lf6TY0qAAAAAPxJDW-l5RW71XDfloLHBGH44mPD";//密鑰(檢核用)
//====================================================================
//資料建置中（純文字，勿含 HTML）
$lang_text["no_data_str"] = array(
	1=>"資料建置中",
	2=>"Under Construction",
); 

$lang_text["no_data_str2"] = array(
	1=>"資料建置中",
	2=>"Under Construction",
);
//====================================================================
//footer
$lang_text["ft_approval"] = array(//alert
	1=>"中移廣字 第 xxxxxx 號",
	2=>" Advertisement No. xxxxxx, National Immigration Agency.",
);

$lang_text["ft_Reg"] = array(//alert
	1=>"註冊登記證第 xxxxx 號",
	2=>"Registration Certificate No. xxxxx.",
);

$lang_text["ft_opening"] = array(//alert
	1=>"服務時間",
	2=>"Business Hours",
);

$lang_text["ft_tel"] = array(
	1=>"聯絡電話",
	2=>"Phone",
);

$lang_text["ft_mail"] = array(
	1=>"客服信箱",
	2=>"Email",
);

$lang_text["ft_address"] = array(
	1=>"辦事據點",
	2=>"Office Address",
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
//報名表單
$lang_text["field_num"] = array(//欄位
	1=>"報名人數",
	2=>"Number",
);
$lang_text["chk_num"] = array(//欄位
	1=>"請輸入【報名人數】",
	2=>"You must enter a value in 【Number】",
);
$lang_text["field_cellphone"] = array(//欄位
	1=>"手機",
	2=>"Cellphone",
);
$lang_text["chk_cellphone"] = array(//欄位
	1=>"請輸入【手機】",
	2=>"You must enter a value in 【Cellphone】",
);
$lang_text["field_howevent"] = array(//欄位
	1=>"如何得知本活動？",
	2=>"How did you find out about this event?",
);
$lang_text["chk_howevent"] = array(//欄位
	1=>"請選擇【如何得知本活動】",
	2=>"You must enter a value in 【】",
);
$lang_text["data_howevent"] = array(//欄位
	1=>[
		"官網",
		"FB",
		"官方Line @",
		"承遠專欄",
		"Google搜尋",
		"顧問介紹",
		"朋友介紹",
	],
	2=>[
		"Official Website",
		"Facebook",
		"Official Line @",
		"Endeavor Global Columns",
		"Google Search",
		"Consultant Introduction",
		"Friend's Recommendation"
	],
);
//====================================================================
$lang_text["field_snsID"] = array(//欄位
	1=>"通訊軟體ID",
	2=>"ID of Messaging App",
);
$lang_text["data_sexM"] = array(//欄位
	1=>"先生",
	2=>"Mr.",
);
$lang_text["data_sexF"] = array(//欄位
	1=>"小姐",
	2=>"Ms.",
);

$lang_text["field_okTime"] = array(//欄位
	1=>"方便聯絡的時段",
	2=>"Preferred Contact Time",
);
$lang_text["data_okTime"] = array(//欄位
	1=>[
		'平日',
		'假日',
		'上午',
		'下午',
		'晚上',
	],
	2=>[
		'Weekdays',
		'Weekend',
		'Morning',
		'Afternoon',
		'Evening',
	],
);

$lang_text["field_knowCountry"] = array(//欄位
	1=>"想了解的國家",
	2=>"Countries of Interest",
);
$lang_text["chk_knowCountry"] = array(//欄位
	1=>"請勾選【想了解的國家】",
	2=>"You must enter a value in【Countries of Interest】",
);
$lang_text["data_knowCountry"] = array(//欄位
	1=>[
		'美國',
		'葡萄牙',
		'土耳其',
		'馬來西亞',
		'杜拜',
		'保加利亞',
		'希臘',
		'馬爾他',
		'義大利',
		'匈牙利',
		'加勒比海',
		'巴拿馬',
		'加拿大',
		'萬那杜',
		'尚無想法，待諮詢後決定'
	],
	2=>[
		'United States',
		'Portugal',
		'Turkey',
		'Malaysia',
		'Dubai',
		'Bulgaria',
		'Greece',
		'Malta',
		'Italy',
		'Hungary',
		'Caribbean Countries',
		'Panama',
		'Canada',
		'Vanuatu',
		'Not sure yet — seeking professional advice',
	],
);

$lang_text["field_mjNeed"] = array(//欄位
	1=>"主要訴求",
	2=>"Primary Objective",
);
$lang_text["data_mjNeed"] = array(//欄位
	1=>[
		'快速身分',
		'大國移民',
		'避險',
		'不想長住海外',
		'希望取得居留權',
		'希望取得護照'
	],
	2=>[
		'Fast-track Identity',
		'Leading Immigration Destinations',
		'Risk Diversification',
		'No Intention of Relocating Abroad Long-term',
		'Seeking Residency',
		'Seeking Passport/Citizenship',	
	],
);
$lang_text["chooseMult"] = array(//欄位
	1=>"可複選",
	2=>"Multiple Choices",
);
//====================================================================
//聯絡我們
$lang_text["field_name"] = array(//欄位
	1=>"姓名",
	2=>"Full Name",
);

$lang_text["chk_name"] = array(//alert
	1=>"請輸入【姓名】",
	2=>"You must enter a value in【Full Name】",
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
	1=>"備註",
	2=>"Remarks",
);

$lang_text["chk_description"] = array(//alert
	1=>"請輸入【備註】",
	2=>"You must enter a value in【Remark】",
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