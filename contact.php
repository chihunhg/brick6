<?php

/**
 * 前台聯絡我們（contact.htm）
 *
 * 表單 POST 至 mail.htm；含 CSRF、reCAPTCHA 與前端欄位驗證。
 * 寄信處理：mail.php。
 */
$pageName    = "p5";
$subPageName = "";
require("_inc.php");

$Module_PKey = frontend_module_pkey_for_page('contact.htm');
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

$recaptcha_site_key = recaptcha_site_key();
?>

<!DOCTYPE html>
<html <?php echo $lang_text["lang"][$this_lang]; ?>>
<head>
<meta charset="utf-8">
<?php require("_in_code_head.php"); ?>
<?php require("_in_javascript.php"); ?>
<?php echo recaptcha_script_tag(); ?>
<?php echo script_open(); ?>
$(function(){
	$("#btn_submit").click(function(){
		loading(1);
		var errors = [];
		var fields = [];
		var formEl = document.getElementById('form1');

		if ($('#name').val() === '') {
			errors.push(<?php echo js_str($lang_text['chk_name'][$this_lang] ?? ''); ?>);
			fields.push('name');
		}

		if ($('#tel').val() === '') {
			errors.push(<?php echo js_str($lang_text['chk_tel'][$this_lang] ?? ''); ?>);
			fields.push('tel');
		}

		if ($('#email').val() === '') {
			errors.push(<?php echo js_str($lang_text['chk_email'][$this_lang] ?? ''); ?>);
			fields.push('email');
		} else if (typeof isEmail === 'function' && !isEmail($('#email').val())) {
			errors.push(<?php echo js_str($lang_text['chk_email_rule'][$this_lang] ?? ''); ?>);
			fields.push('email');
		}

		if ($('input[name="knowCountry[]"]:checked').length === 0) {
			errors.push(<?php echo js_str($lang_text['chk_knowCountry'][$this_lang] ?? ''); ?>);
			fields.push('knowCountry_wrap');
		}

		<?php if ($recaptcha_site_key !== '') { ?>
		if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.getResponse === 'function') {
			if (grecaptcha.getResponse().length === 0) {
				errors.push(<?php echo js_str($lang_text['chk_google_code'][$this_lang] ?? ''); ?>);
			}
		}
		<?php } ?>

		if (errors.length) {
			return window.manageFormValidationFail(errors, {
				focusField: fields[0] || '',
				errorFields: fields,
				form: formEl
			});
		}

		window.manageFormValidationOk(formEl);
		$("#Send").val("ok");
		$("#form1").submit();
		$("#Send").val("");
	});
});
<?php echo script_close(); ?>
</head>

<body <?php echo $bodytxt; ?>>
    <?php require("_header.php"); ?>
    <?php require("_banner.php"); ?>

    <main class="pgContent">
        <section class="blockHeight blockHeight--contact">
            <div class="container">
				<h2 class="mainTitle">
					<span class="mainTitle__en wow fadeInUp"><?php echo $lang_text[$pageName][$this_lang][$pageName.'_en']; ?></span>
					<span class="mainTitle__mj wow fadeInUp"><?php echo $lang_text[$pageName][$this_lang][$pageName]; ?></span>
				</h2>
                <form name="form1" id="form1" method="post" action="<?php echo 'mail.htm'; ?>" class="formGroupWrap wow fadeIn" data-wow-delay="0.25s">
                    <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite" tabindex="-1">
                        <div class="errorArea__header"><?php echo e(($this_lang == 2) ? 'Error messages' : '錯誤訊息'); ?></div>
                        <div class="errorArea__body">
                            <ul id="formErrorList"></ul>
                        </div>
                    </div>
                    <div class="formGroup formGroup--bg">
                        <div class="formGroup__item formGroup__item--half">
                            <label for="name"><?php echo $lang_text["field_name"][$this_lang]; //姓名 ?><span class="red">*</span></label>
                            <div class="input-group">
								<input name="name" id="name" type="text" class="form-control" size="20">
								<div class="sexCheck">
									<label for="sex1" class="checkGroup">
										<input type="radio" name="sex" class="form-check-input" id="sex1" value="1" data-gtm-form-interact-field-id="1">
										<span class="txt">先生</span>
									</label>
										<label for="sex2" class="checkGroup">
										<input type="radio" name="sex" class="form-check-input" id="sex2" value="2" data-gtm-form-interact-field-id="0">
										<span class="txt">小姐</span>
									</label>
								</div>
							</div>
                            <small id="name_txt" class="errorTxt"></small>
                        </div>

						<div class="formGroup__item formGroup__item--half">
                            <label for="tel"><?php echo $lang_text["field_tel"][$this_lang]; //Tel ?><span class="red">*</span></label>
                            <input name="tel" id="tel" type="text" class="form-control" size="20">
                            <small id="tel_txt" class="errorTxt"></small>
                        </div>

                        <div class="formGroup__item formGroup__item--half">
                            <label for="email"><?php echo $lang_text["field_email"][$this_lang]; //E-mail ?><span class="red">*</span></label>
                            <input name="email" id="email" type="text" class="form-control" size="40">
                            <small id="email_txt" class="errorTxt"></small>
                        </div>

						<div class="formGroup__item formGroup__item--half">
                            <label for="snsID"><?php echo $lang_text["field_snsID"][$this_lang]; //通訊軟體ID ?></label>
                            <input name="snsID" id="snsID" type="text" class="form-control" size="20">
                        </div>

						<div class="formGroup__item">
                            <label for="snsID">
								<?php echo $lang_text["field_okTime"][$this_lang]; //方便聯絡的時段 ?>
								<span class="multTag"><?php echo $lang_text["chooseMult"][$this_lang]; //可複選 ?></span>
							</label>
							<div class="formMode">
								<?php
                                foreach ($lang_text["data_okTime"][$this_lang] as $num => $item){?>
								<div class="form-check">
                                    <input name="okTime[]" type="checkbox" class="form-check-input" id="okTime-<?php echo $num+1?>" value="<?php echo $item?>">
                                    <label for="okTime-<?php echo $num+1?>"><?php echo $item?></label>
                                </div>
								<?php }?>
							</div>
                        </div>

						<div class="formGroup__item">
                            <label for="snsID">
								<?php echo $lang_text["field_knowCountry"][$this_lang]; //想了解的國家 ?>
								<span class="red">*</span>
								<span class="multTag"><?php echo $lang_text["chooseMult"][$this_lang]; //可複選 ?></span>
							</label>

							<div class="formMode" id="knowCountry_wrap">
								<?php
                                foreach ($lang_text["data_knowCountry"][$this_lang] as $num => $item){?>
								<div class="form-check">
                                    <input name="knowCountry[]" type="checkbox" class="form-check-input" id="knowCountry-<?php echo $num+1?>" value="<?php echo $item?>">
                                    <label for="knowCountry-<?php echo $num+1?>"><?php echo $item?></label>
                                </div>
								<?php }?>
							</div>
							<small id="knowCountry_txt" class="errorTxt"></small>
                        </div>

						<div class="formGroup__item">
                            <label for="snsID">
								<?php echo $lang_text["field_mjNeed"][$this_lang]; //主要訴求 ?>
								<span class="multTag"><?php echo $lang_text["chooseMult"][$this_lang]; //可複選 ?></span>
							</label>

							<div class="formMode">
								<?php
                                foreach ($lang_text["data_mjNeed"][$this_lang] as $num => $item){?>
								<div class="form-check">
                                    <input name="mjNeed[]" type="checkbox" class="form-check-input" id="mjNeed-<?php echo $num+1?>" value="<?php echo $item?>">
                                    <label for="mjNeed-<?php echo $num+1?>"><?php echo $item?></label>
                                </div>
								<?php }?>
							</div>
                        </div>

                        <div class="formGroup__item">
                            <label for="description"><?php echo $lang_text["field_description"][$this_lang]; //洽詢內容 ?></label>
                            <textarea name="description" id="description" rows="5" class="form-control"></textarea>
                        </div>
                        <?php if ($recaptcha_site_key !== '') { ?>
                        <div class="formGroup__item formGroup__item--recaptcha">
                            <div class="g-recaptcha" data-sitekey="<?php echo e_attr($recaptcha_site_key); ?>"></div>
                            <small id="g-recaptcha_txt" class="errorTxt"></small>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="btnWrap btnWrap--center">
                        <button type="button" name="btn_submit" id="btn_submit" class="btnStyle">
							<span class="txt">
								<?php echo $lang_text["btn_submit"][$this_lang]; //送出 ?>
							</span>
                        </button>
                    </div>
                    <input type="hidden" name="Send" id="Send" value="">
                    <input type="hidden" name="this_lang" id="this_lang" value="<?php if(!empty($this_lang)&&is_numeric($this_lang)){echo sqlfilter($this_lang,'int');}?>" />
                    <input type="hidden" name="csrf_token" value="<?php echo e_attr($csrf_token); ?>" />
                </form>
            </div>
        </section>
    </main>

    <?php require("_footer.php"); ?>
    <?php require("_in_code_bottom.php"); ?>
</body>
</html>
