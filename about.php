<?php

/**
 * 前台關於我們導覽（about.htm）
 *
 * 依 company 模組顯示分類導覽，導向 company.php 內頁。
 */
$pageName = "p1";
$subPageName = "";
require("_inc.php");
$Module_PKey = frontend_module_pkey('company');
$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;

// 產生麵包屑
$bread_name = $bread_name ?? [];
$break_link = $break_link ?? [];
array_push($bread_name, e_attr($lang_text['home'][$this_lang] ?? '首頁'));
array_push($break_link,$web_root);
array_push($bread_name, e_attr($Module_Name));
array_push($break_link, $Module_Link);

/** ---------------- SEO 麵包屑（JSON-LD 結構） ---------------- */
// 構建 JSON-LD 時，不要先 e()，改用 json_encode 輸出（避免 XSS/注入）
$Elements = [];
for ($i = 0; $i < count($bread_name); $i++) {
	$position = $i + 1;
	$itemUrl  = safe_href((string)($web_url . ($break_link[$i] ?? '')));
	// 反向取原值（未 e()），避免雙重轉義；若上面先 e() 了，改用原始值：
	$nameRaw = isset($bread_name[$i]) ? (string)$bread_name[$i] : '';
	// 若 $bread_name 內元素已 e()，這裡簡單還原（保守做法：移除 HTML 標籤；如你能提供原始未 e() 值更好）
	$nameRaw = strip_tags($nameRaw);
	$Elements[] = [
		'@type'    => 'ListItem',
		'position' => $position,
		'item'     => $itemUrl,
		'name'     => $nameRaw
	];
}
$ldjson = [
	"@context" => "http://schema.org",
	"@type"    => "BreadcrumbList",
	"itemListElement" => $Elements
];
?>

<!DOCTYPE html>
<html <?php echo $lang_text["lang"][$this_lang]; ?>>
<head>
<?php require("_in_code_head.php"); ?>
<?php require("_in_javascript.php"); ?>
</head>

<body <?php if ( !empty($bodytxt) ){ echo $bodytxt; } ?>>
<?php require("_header.php"); ?>
<?php require("_banner.php"); ?>

<main class="pgContent">
    <section class="blockHeight blockHeight--about">
        <div class="container">
            <div class="aboutBox">
                <div class="aboutBox__content">
                    <h2 class="mainTitle mainTitle--left">
                        <span class="mainTitle__en wow fadeInUp">Endeavor Global Advisory</span>
                        <span class="mainTitle__mj wow fadeInUp">承遠國際移民事業有限公司</span>
                    </h2>
                    <div class="breifTxt">
                        <div class="breifTxt__box wow fadeIn" data-wow-delay="0.25s">
                            <p class="txt">
                                專精於全球投資移民與身分規劃。「承遠」兩字，是我們對移民事業的承諾，也代表我們與客戶共築未來的長遠陪伴。<br><br>
                                投資移民不只是一項交易或流程，更是一個關乎家庭、資產、生活與未來的長期決定。因此，我們致力於協助客戶在複雜多變的國際環境中，找到最適合的移民方案。
                            </p>
                        </div>
                        <div class="breifTxt__box wow fadeIn" data-wow-delay="0.5s">
                            <div class="decoTt">我們的優勢</div>
                            <ul class="ulList --benefit">
                                <li>熟悉各國移民法規且實務經驗豐富，幫客戶理性分析與篩選</li>
                                <li>與律師、政府單位及投資公司密切聯繫，掌握第一手資訊，確保準確性與專業度</li>
                                <li>從初步諮詢、申請準備到後續追蹤，都由顧問親自參與及把關</li>
                                <li>從客戶的角度出發，重視安全性、可行性與長期穩定性</li>
                                <li>與海外不動產、法律及會計等專業顧問合作，提供全方位的身分與資產服務</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="aboutBox__media">
                    <figure class="cover --about1 wow fadeIn" data-wow-delay="0.5s">
                        <img src="<?php echo $web_url?>images/about/1.jpg?20260324" class="cover__pic img-fluid" alt="">
                    </figure>
                </div>
            </div>

        </div>
    </section>
     <section class="blockHeight blockHeight--aboutSlogan">
        <div class="container">
            <h2 class="ixAboutWrap ixAboutWrap--pg">
                <span class="ixAboutWrap__en wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixAbout_en"]; ?></span>
                <span class="ixAboutWrap__mj ixAboutWrap__mj--important wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixAbout_title1"]; ?></span>
                <span class="ixAboutWrap__mj wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixAbout_title2"]; ?></span>
            </h2>
        </div>
     </section>
</main>

<?php require("_footer.php"); ?>
<?php require("_in_code_bottom.php"); ?>
</body>
</html>
