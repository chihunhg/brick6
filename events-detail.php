<?php

/**
 * 前台活動訊息內頁（events-detail.htm）
 *
 * 依 PKey 讀取單筆活動主檔與子表；含報名表單連結 mail-events.php。
 * 列表：events.php。
 */
$pageName = "p3";
$subPageName = "p3_1";
require("_inc.php");

$Module_PKey = frontend_module_pkey_for_link($lang_text['p3']['p3_page'] ?? 'events.htm');
if ($Module_PKey <= 0) {
    $Module_PKey = frontend_module_pkey_for_link('events.htm');
}
$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;

// 產生麵包屑
$bread_name = $bread_name ?? [];
$break_link = $break_link ?? [];
array_push($bread_name, e_attr($lang_text['home'][$this_lang] ?? '首頁'));
array_push($break_link,'');
array_push($bread_name, e_attr($Module_Name));
array_push($break_link, $Module_Link);

/* ============== 輸入參數：============== */
$PKey = isset($filter_array['PKey']) && $filter_array['PKey'] !== false && $filter_array['PKey'] !== null ? (int)$filter_array['PKey'] : 0;

/* ============== 查詢主要內容（參數化） ============== */
$PDO_Cond = ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey and intLang= :intLang AND PKey = :PKey';
$Cond_Array = [
    'Module_PKey' => $Module_PKey,
    'intLang' => $this_lang,
    'Upload'      => 'Yes',
    'PKey'        => $PKey,
];

$sql = 'SELECT * FROM view_paper ' . $PDO_Cond;
$rs  = new recordset($sql, $Cond_Array);

// 錯誤處理
if (($SQL_Error = $rs->getErrorMessage())) {
    $result = sql_error($sql . PHP_EOL . array_to_string($Cond_Array), $SQL_Error, basename(__FILE__), 'system');
    echo '<pre>', e(print_r($result, true)), '</pre>';
    exit;
}

if (!$rs->eof) {
    $Paper_PKey   = (int)$rs->field('PKey');
    $Description  = (string)$rs->field('Description');
    $Keywords     = (string)$rs->field('Keywords');
    $Sort         = (int)$rs->field('Sort');
    $Class1       = (int)$rs->field('Class1_PKey');
    $strName      = (string)$rs->field('strName');
    $seoTitle     = frontend_lang_seo_title([
        'Title'   => (string)($rs->field('Title') ?? ''),
        'strName' => $strName,
    ]);
    $Interview    = (string)$rs->field('Interview');
    $Movielink = (string)$rs->field('Movielink');
    $JoinForm      = (string)$rs->field('JoinForm');
    $strDate      = (string)$rs->field('strDate');
    $Location    = (string)$rs->field('Location');
    $Format    = (string)$rs->field('Format');
    $dtUDate      = (string)$rs->field('dtUDate');

	// 取得 Class1 名稱、加入條件
	$Class1_Name = '';
	if ($Class1 > 0) {
		$sql = 'SELECT PKey, strName FROM view_dbclass1 WHERE PKey = :PKey ';
		$rs1 = new recordset($sql, ['PKey' => $Class1]);
		if (($SQL_Error = $rs1->getErrorMessage())) {
			$result = sql_error($sql . PHP_EOL . array_to_string(['PKey' => $Class1]), $SQL_Error, basename(__FILE__), 'system');
			echo '<pre>', e(print_r($result, true)), '</pre>';
			exit;
		}
		if (!$rs1->eof) {
			$Class1_Name = (string)$rs1->field('strName');
			array_push($bread_name, e_attr($Class1_Name));
			array_push($break_link, 'events' . (int)$rs->field('PKey') . '.htm');
		}
		$rs->close();
	}
	$Backlink	  = 'events'.(int)$rs->field('Class1_PKey').'.htm';

	array_push($bread_name,e($strName));
	array_push($break_link,$page_link);

    // 內容區塊
    $Contents = [];
    $Show     = [];
	$sql = 'SELECT * FROM paper_msg WHERE Paper_PKey = :Paper_PKey and intLang = :intLang ORDER BY Sort';
    $rs1 = new recordset($sql , ['Paper_PKey' => $Paper_PKey,'intLang' => $this_lang]);

	// 錯誤處理
	if (($SQL_Error = $rs->getErrorMessage())) {
		$result = sql_error($sql . PHP_EOL . array_to_string(['Paper_PKey' => $Paper_PKey,'intLang' => $this_lang]), $SQL_Error, basename(__FILE__), 'system');
		echo '<pre>', e(print_r($result, true)), '</pre>';
		exit;
	}
    while (!$rs1->eof) {
        $i = (int)$rs1->field('Sort');
        // 原有 rwd_table() 邏輯保留；後續輸出再做安全淨化
        $Contents[$i] = rwd_table((string)$rs1->field('Contents'), 1);
        $Show[$i]     = (int)$rs1->field('isShow');
        $rs1->movenext();
    }
    $rs1->close();

    // 圖片（相對路徑 + webp 優先）
    $upload_folder = isset($upload_folder) ? rtrim((string)$upload_folder, "/\\") . '/' : 'Upload/';

    $Photo = [];
	$PhotoM = [];
	$sql = 'SELECT * FROM paper_img WHERE Paper_PKey = :Paper_PKey AND Photo1 <> :Photo1 and Sort > 1 ORDER BY Sort';
    $rs1 = new recordset($sql, ['Paper_PKey' => $Paper_PKey, 'Photo1' => '']);
	// 錯誤處理
	if (($SQL_Error = $rs1->getErrorMessage())) {
		$result = sql_error($sql . PHP_EOL . array_to_string($Cond_Array), $SQL_Error, basename(__FILE__), 'system');
		echo '<pre>', e(print_r($result, true)), '</pre>';
		exit;
	}
    while (!$rs1->eof) {
        $folder = preg_replace('/[^A-Za-z0-9_\/-]/', '', (string)$rs1->field('Forder'));
        $photo  = basename((string)$rs1->field('Photo1'));

        $relPath  = $upload_folder . $folder . '/' . $photo;
        $webpPath = preg_replace('/\.[^.]+$/i', '.webp', $relPath);

        $useRel = null;
        $ext    = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
        if ($ext !== 'gif' && is_file($webpPath)) {
            $useRel = $webpPath;
        } elseif (is_file($relPath)) {
            $useRel = $relPath;
        }

        if ($useRel !== null) {
            $i = (int)$rs1->field('Sort')-1;
            // 直接存成對外 URL，之後輸出就不用再接前綴
            $Photo[$i] = (string)($web_root . ltrim($useRel, '/'));
			$PhotoM[$i] = e_attr((string)$rs1->field('PhotoM'));
        }

        $rs1->movenext();
    }
    $rs1->close();

    $links = [];
	if (function_exists('chkTable') && chkTable('paper_link')) {
		$sql = 'SELECT * FROM paper_link WHERE Paper_PKey = :Paper_PKey and intLang = :intLang ORDER BY Sort';
		$rs1 = new recordset($sql , ['Paper_PKey' => $Paper_PKey, 'intLang' => $this_lang]);

		if (($SQL_Error = $rs1->getErrorMessage())) {
			$result = sql_error($sql . PHP_EOL . array_to_string(['Paper_PKey' => $Paper_PKey, 'intLang' => $this_lang]), $SQL_Error, basename(__FILE__), 'system');
			echo '<pre>', e(print_r($result, true)), '</pre>';
			exit;
		}

		while (!$rs1->eof) {
			$links[] = [
				'title' => e_attr((string)$rs1->field('strName')),
				'url'   => e_attr((string)$rs1->field('strLink'))
			];
			$rs1->movenext();
		}
		$rs1->close();
	}

} else {
    // 找不到資料：alert 後導回列表頁
    $listUrl = safe_href('events.htm');        // 產出安全 href（相對/站內）
    $listUrlAttr = e_attr($listUrl);           // HTML 屬性轉義
    $msg = $lang_text["warn_data_not_found"][$this_lang];    // 可依語系替換

    // 直接輸出最小 HTML，含 noscript 後援
    echo '<!DOCTYPE html><html lang="zh-Hant"><head>';
    echo '<meta charset="utf-8">';
    echo '<meta http-equiv="refresh" content="0;url=' . $listUrlAttr . '">'; // 無 JS 後援
    echo '<title>' . e($msg) . '</title>';
    echo '</head><body>';

    // 以 CSP nonce 輸出腳本（sec.php 提供 script_open()/script_close()）
    echo script_open();
    echo 'alert(' . js_str($msg) . ');';
    echo 'location.replace(' . js_str($listUrl) . ');';
    echo script_close();

    // noscript 提示
    echo '<noscript><p>' . e($msg) . '：<a href="' . $listUrlAttr . '">請點此返回</a></p></noscript>';

    echo '</body></html>';
    exit;
}

$ytSrc = youtube_embed_src($Movielink);

/** ---------------- SEO 麵包屑（JSON-LD 結構） ---------------- */
// $page_link, $web_root 應來自 _inc.php 且為經過驗證的 URL
$canonical = $web_root.$page_link;
$Elements = array();
for($i=0;$i<count($bread_name);$i++){
	$n = $i+1;
	$array = [
		'@type'=> 'ListItem',
		'position'=> strval($n),
		// safe_url 應確保輸出的 URL 是安全的
		'item' => safe_url($web_root.$break_link[$i]),
		// e() 確保麵包屑名稱的內容被逸出
		'name' => e($bread_name[$i])
	];
	$Elements[$n] = $array;
}

$ldjson = [
	// URL 內容在 JSON 中是安全的
	"@context"=> "http://schema.org",
	"@type"=> "BreadcrumbList",
	// $Elements 中的 'name' 已經過 e() 處理，這裡輸出 JSON 是安全的
	"itemListElement"=> $Elements
];
?>

<!DOCTYPE html>
<html <?php echo $lang_text["lang"][$this_lang]; ?>>
<head>
<?php require("_in_code_head.php"); ?>
<?php require("_in_javascript.php"); ?>
<?php echo recaptcha_script_tag(); ?>
<?php echo script_open(); ?>
$(function(){
	$("#btn_submit").click(function(){
		loading(1);
		var array = new Array();
		var flag = true;

		if ($('#name').val() == ""){
			$("#name").addClass('errorLine')
			$("#name_txt").text(<?php echo js_str($lang_text['chk_name'][$this_lang] ?? ''); ?>);
			array.push("name");
			flag = false;
		}else{
			$("#name").removeClass("errorLine");
			$("#name_txt").text("");
		}

		if ($('#num').val() == ""){
			$("#num").addClass('errorLine')
			$("#num_txt").text(<?php echo js_str($lang_text['chk_num'][$this_lang] ?? ''); ?>);
			array.push("num");
			flag = false;
		}else{
			$("#num").removeClass("errorLine");
			$("#num_txt").text("");
		}

        if ($('#cellphone').val() == ""){
			$("#cellphone").addClass('errorLine')
			$("#cellphone_txt").text(<?php echo js_str($lang_text['chk_cellphone'][$this_lang] ?? ''); ?>);
			array.push("cellphone");
			flag = false;
		}else{
			$("#cellphone").removeClass("errorLine");
			$("#cellphone_txt").text("");
		}

		if ($('#email').val() == "") {
			$("#email").addClass('errorLine')
			$("#email_txt").text(<?php echo js_str($lang_text['chk_email'][$this_lang] ?? ''); ?>);
			array.push("email");
			flag = false;
		}else{
			if (! isEmail($('#email').val())) {
				$("#email").addClass('errorLine')
				$('#email_txt').text(<?php echo js_str($lang_text['chk_email_rule'][$this_lang] ?? ''); ?>);
				array.push("email");
				flag = false;
			}else{
				$("#email").removeClass("errorLine");
				$("#email_txt").text("");
			}
		}

        if ($('input[name="howevent"]:checked').length === 0) {
            $('#howevent').addClass('errorLine');
            $("#howevent_txt").text(<?php echo js_str($lang_text['chk_howevent'][$this_lang] ?? ''); ?>);
            array.push("howevent");
            flag = false;
        } else {
            $('#howevent').removeClass('errorLine');
            $("#howevent_txt").text("");
        }

		if ($('#description').val() == ""){
			// $("#description").addClass('errorLine')
			// $("#description_txt").text(<?php echo js_str($lang_text['chk_description'][$this_lang] ?? ''); ?>);
			// array.push("description");
			// flag = false;
		}else{
			$("#description").removeClass("errorLine");
			$("#description_txt").text("");
		}

		var response = grecaptcha.getResponse();
		if (response.length == 0) {
			$("#g-recaptcha_txt").text(<?php echo js_str($lang_text['chk_google_code'][$this_lang] ?? ''); ?>);
			flag = false;
		} else {
			$("#g-recaptcha_txt").text("");
		}

		if ( flag == false ){
			loading(0);
			var field = array[0];
			//alert('發生錯誤，請填寫下列欄位');
			$('#' + field).focus();

		} else {
			$("#Send").val("ok");
			$("#form1").submit();
			$("#Send").val("");
		}

	});
});
<?php echo script_close(); ?>
</head>

<body <?php if ( !empty($bodytxt) ){ echo $bodytxt; } ?>>
<?php require("_header.php"); ?>
<?php require("_banner.php"); ?>

<main class="pgContent">
    <section class="blockHeight blockHeight--news">
        <div class="container">
            <div class="newsDBox">
                <div class="articleTop">
                    <h2 class="articleTt"><?php echo e_attr($strName)?></h2>
                    <div class="dateTxt"><?php echo e_attr(date_en($dtUDate,1))?></div>
                </div>
                <div class="articleMain">
                    <?php
					if($JoinForm=='Yes'){
					?>
                    <div class="applyBtn">
                        <a href="#applyZone" class="applyBtn__item">馬上報名</a>
                    </div>
					<?php
					}
					?>
					<?php
					for($i=1;$i<7;$i++){
						if(! empty($Contents[$i]) || !empty($Photo[$i])){
							switch($i){
								case 2:
								case 4:
									$css = 'tx01 img-left';
									break;
								case 3:
								case 5:
									$css = 'tx01 img-right';
									break;
								default:
									$css = 'tx01';
									break;
							}
					?>
					<article class="<?php echo $css?>">
						<?php
						if(!empty($Photo[$i])){
						?>
						<figure>
							<img src="<?php echo e_attr((string)$Photo[$i]); ?>" class="img-fluid" loading="lazy" alt="<?php echo e_attr($strName)?>">
						</figure>
						<?php
						}
						if(!empty($Contents[$i])){
						?>
						<div class="text">
							<?php echo frontend_render_html((string)$Contents[$i]); ?>
						</div>
						<?php
						}
						?>
					</article>
					<?php
						}
					}
					?>
                </div>
				<?php if ($ytSrc): ?>
				<div class="vdBox">
					<iframe width="100%" height="100%" src="<?php echo e_attr($ytSrc); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
				</div>
				<?php endif;?>
				<?php if (!empty($links)): ?>
				<div class="linkBox">
						<?php foreach ($links as $item): ?>
							<a href="<?= e_attr($item['url']) ?>" target="_blank" rel="noopener noreferrer" class="linkBox__item">
								<span class="txt"><?= e($item['title']) ?></span>
							</a>
						<?php endforeach; ?>
				</div>
				<?php endif; ?>
				<?php
				if($JoinForm=='Yes'):
				?>
                <div class="applyWrap porDotZone">
                    <span class="porDotZone__dot" id="applyZone"></span>
                    <form name="form1" id="form1" method="post" action="<?php echo $web_root.'mail-events.htm'; ?>" class="formGroupWrap wow fadeIn" data-wow-delay="0.25s">
                        <div class="formGroup formGroup--bg">
                            <div class="formGroup__item --infor">
                                <h2 class="formTt">
                                    <span class="mainTitle__en wow fadeInUp animated" style="visibility: visible; animation-name: fadeInUp;">Register now</span>
                                    <span class="mainTitle__mj wow fadeInUp animated" style="visibility: visible; animation-name: fadeInUp;">立即報名</span>
                                </h2>
                                <div class="applyInforWrap">
                                    <!-- 文字欄位*3：日期+地點+形式 -->
                                    <div class="applyInfor">
                                        <span class="tt">日期</span>
                                        <span class="txt">2026年10月17日（六）~ 10月18日（日）10:00–17:00</span>
                                    </div>
                                    <div class="applyInfor">
                                        <span class="tt">地點</span>
                                        <span class="txt">台北國際會議中心 (臺北市信義區信義路五段1號)</span>
                                    </div>
                                    <div class="applyInfor">
                                        <span class="tt">形式</span>
                                        <span class="txt">開放式展會，人潮眾多，敬請把握機會</span>
                                    </div>
                                </div>
                            </div>
                            <div class="formGroup__item formGroup__item--half">
                                <label for="name"><?php echo $lang_text["field_name"][$this_lang]; //姓名 ?><span class="red">*</span></label>
                                <div class="input-group">
                                    <input name="name" id="name" type="text" class="form-control" size="20">
                                </div>
                                <small id="name_txt" class="errorTxt"></small>
                            </div>
                            <div class="formGroup__item formGroup__item--half">
                                <label for="num"><?php echo $lang_text["field_num"][$this_lang]; //報名人數 ?><span class="red">*</span></label>
                                <div class="input-group">
                                    <input name="num" id="num" type="text" class="form-control" size="20">
                                </div>
                                <small id="num_txt" class="errorTxt"></small>
                            </div>

                            <div class="formGroup__item formGroup__item--half">
                                <label for="cellphone"><?php echo $lang_text["field_cellphone"][$this_lang]; //cellphone ?><span class="red">*</span></label>
                                <input name="cellphone" id="cellphone" type="text" class="form-control" size="20">
                                <small id="cellphone_txt" class="errorTxt"></small>
                            </div>

                            <div class="formGroup__item formGroup__item--half">
                                <label for="email"><?php echo $lang_text["field_email"][$this_lang]; //E-mail ?><span class="red">*</span></label>
                                <input name="email" id="email" type="text" class="form-control" size="40">
                                <small id="email_txt" class="errorTxt"></small>
                            </div>

                            <div class="formGroup__item">
                                <label for="howevent">
                                    <?php echo $lang_text["field_howevent"][$this_lang]; //如何得知本活動？ ?>
                                    <span class="red">*</span>
                                </label>

                                <div class="formMode" id="howevent">
                                    <?php foreach ($lang_text["data_howevent"][$this_lang] as $num => $item){?>
                                        <div class="form-check">
                                            <input name="howevent" type="radio" class="form-check-input" id="howevent-<?php echo $num+1?>" value="<?php echo $item?>">
                                            <label for="howevent-<?php echo $num+1?>"><?php echo $item?></label>
                                        </div>
                                    <?php } ?>
                                </div>
                                <small id="howevent_txt" class="errorTxt"></small>
                            </div>

                            <div class="formGroup__item">
                                <label for="description"><?php echo $lang_text["field_description"][$this_lang]; //洽詢內容 ?></label>
                                <textarea name="description" id="description" rows="5" class="form-control"></textarea>
                            </div>
                            <div class="formGroup__item formGroup__item--recaptcha">
                                <!-- <label for=""><?php echo $lang_text["field_google_code"][$this_lang]; //驗證碼 ?><span class="red">*</span></label> -->
                                <div class="g-recaptcha" data-sitekey="<?php echo e_attr((string)($google_web_key ?? '')); ?>"></div>
                                <small id="g-recaptcha_txt" class="errorTxt"></small>
                            </div>

                        </div>
                        <div class="btnWrap btnWrap--center">
                            <button type="button" name="btn_submit" id="btn_submit" class="btnStyle">
                                <span class="txt">
                                    送出表單
                                </span>
                            </button>
                        </div>
                        <input type="hidden" name="Send" id="Send" value="">
                        <input type="hidden" name="this_lang" id="this_lang" value="<?php if(!empty($this_lang)&&is_numeric($this_lang)){echo sqlfilter($this_lang,'int');}?>" />
                        <input type="hidden" name="csrf_token" value="<?php echo e_attr($csrf_token); ?>" />
                    </form>
                </div>
				<?php endif; ?>
            </div>
            <div class="btnWrap btnWrap--right">
                <a href="events.htm" class="btnStyle">
                    <span class="txt">回上一頁</span>
                </a>
            </div>
        </div>
    </section>
</main>

<?php require("_footer.php"); ?>
<?php require("_in_code_bottom.php"); ?>
</body>
</html>
