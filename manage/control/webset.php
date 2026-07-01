<?php
declare(strict_types=1);
/**
 * 網站 SEO 基本設定（主表 webset，依 intLang 分語系列）
 */

require_once '../_inc.php';
require_once '../_module.php';

$websetConfig = require __DIR__ . '/webset_config.php';
$csrfKey      = (string)($websetConfig['csrf'] ?? 'webset_form');

$subitem = 's1';
$Module_Name = $Module_Name ?? '網站SEO設定';

$returnUrl = (string)($WorkFile ?? 'webset.php');
[$__manNo, $__subNo] = crud_addin_resolve_man_sub_no();
if ($__manNo > 0 && strpos($returnUrl, 'manNo=') === false) {
    $returnUrl .= (strpos($returnUrl, '?') !== false ? '&' : '?') . 'manNo=' . $__manNo;
    if ($__subNo > 0) {
        $returnUrl .= '&subNo=' . $__subNo;
    }
}

$csrf_token = crud_csrf_ensure_page($csrfKey);
$langCount  = max(1, count((array)($array_lang ?? [])));

$formFlashErrors = function_exists('manage_pull_form_flash_errors')
    ? manage_pull_form_flash_errors()
    : [];

if (isset($filter_array['Submit']) && $filter_array['Submit'] === '送出') {
    crud_csrf_verify($csrfKey);

    $MSG = crud_validate_webset_from_filter($filter_array ?? []);
    if ($MSG !== '') {
        crud_form_error_redirect($MSG, $returnUrl);
    }

    try {
        crud_save_webset_from_filter($filter_array ?? []);
        manage_alert_script('修改成功!', $returnUrl);
    } catch (Throwable $e) {
        if (function_exists('sql_error')) {
            sql_error(
                '',
                $e->getMessage(),
                $_SERVER['PHP_SELF'] ?? 'webset.php',
                (string)($_SESSION['Login_ID'] ?? 'system'),
                $e->getFile(),
                $e->getLine()
            );
        }
        crud_form_error_redirect('儲存失敗，請稍後再試', $returnUrl);
    }
}

$ws = crud_load_webset_form_data();

$strName     = $ws['strName'];
$Description = $ws['Description'];
$Keywords    = $ws['Keywords'];
$Tel         = $ws['Tel'];
$Fax         = $ws['Fax'];
$Address     = $ws['Address'];
$Facebook    = $ws['Facebook'];
$Line        = $ws['Line'];
$IG          = $ws['IG'];
$Youtube     = $ws['Youtube'];
$Twitter     = $ws['Twitter'];
$FromMail    = $ws['FromMail'];
$ToMail      = $ws['ToMail'];
$gaCode      = $ws['gaCode'];
$gtmCode     = $ws['gtmCode'];

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => '網站管理'],
    ['label' => 'SEO基本設定'],
];
$layout_page_title = manage_breadcrumbs_page_title($breadcrumbs);
?>
<?php require_once '../_layout_head.php'; ?>
<meta name="csrf-token" content="<?php echo e((string)$csrf_token); ?>">
<?php echo script_open(); ?>
$(function() {
	if ($.fn && typeof $.fn.maxlength === 'function') {
		var totalLang = parseInt($('#Total_lang').val(), 10) || 0;
		for (var i = 1; i <= totalLang; i++) {
			var $desc = $('#Description' + i);
			if ($desc.length) {
				$desc.maxlength({ maxCharacters: 160, slider: true });
			}
		}
	}
});

function fieldCheck0(theForm) {
	if (typeof loading === 'function') {
		loading(1);
	}
	var array = [];
	var errors = [];
	var view = [];
	var totalLang = parseInt($('#Total_lang').val(), 10) || 0;

	for (var j = 1; j <= totalLang; j++) {
		if ($.trim($('#strName' + j).val()) === '') {
			array.push('strName' + j);
			errors.push('網站名稱空白（語系 ' + j + '）');
			view.push(j);
		}
	}

	var fromMail = $.trim($('#FromMail').val());
	if (fromMail === '') {
		array.push('FromMail');
		errors.push('寄件信箱空白');
	} else if (typeof isEmail === 'function' && !isEmail(fromMail)) {
		array.push('FromMail');
		errors.push('寄件信箱格式錯誤');
	}

	var toMail = $.trim($('#ToMail').val());
	if (toMail !== '' && typeof isEmail === 'function') {
		var parts = toMail.split(';');
		for (var k = 0; k < parts.length; k++) {
			var em = $.trim(parts[k]);
			if (em !== '' && !isEmail(em)) {
				array.push('ToMail');
				errors.push('收件信箱格式錯誤：' + em);
				break;
			}
		}
	}

	if (errors.length) {
		return window.manageFormValidationFail(errors, {
			focusField: array[0],
			viewTab: view.length ? view[0] : undefined,
			form: theForm
		});
	}
	return window.manageFormValidationOk(theForm);
}
<?php echo script_close(); ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <section class="editView">
                        <form action="" method="post" name="form1" id="form1" novalidate data-manage-validate="fieldCheck0">

                        <div class="errorArea<?php echo $formFlashErrors === [] ? ' is-hidden' : ''; ?>" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"><?php
                                foreach ($formFlashErrors as $flashMsg) {
                                    echo '<li>' . e((string)$flashMsg) . '</li>';
                                }
                                ?></ul>
                            </div>
                        </div>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">基本設定</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="FromMail">
                                        寄件信箱 <?php echo manage_render_field_help('通知信的寄件信箱'); ?><span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="FromMail" type="email" id="FromMail" class="formInput" value="<?php echo e($FromMail); ?>" maxlength="100" autocomplete="email">
                                        <ul class="fieldHint">
                                            <li>只能設定 1 個信箱。</li>
                                            <li>若收件信箱為 Gmail，可能因 Google 政策收不到信（<a href="https://www.tsg.com.tw/blog-search-detail2-280-0-form-gmail.htm" target="_blank" rel="noopener noreferrer">詳細說明</a>）；建議寄件信箱可設為已驗證網域的 noreply 地址。</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="ToMail">
                                        收件信箱
                                        <?php echo manage_render_field_help('可設定前台的表單由哪些信箱收到。多組信箱請用「;」分隔，並顯示於聯絡我們單元頁面'); ?>
                                    </label>
                                    <div class="col--10">
                                        <input name="ToMail" type="text" id="ToMail" class="formInput" value="<?php echo e($ToMail); ?>" maxlength="500" autocomplete="email">
                                        <p class="fieldHint">通知信的收件信箱，多組信箱請用「;」分隔。</p>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="gaCode">GA-Code</label>
                                    <div class="col--10">
                                        <input type="text" name="gaCode" id="gaCode" class="formInput" value="<?php echo e($gaCode); ?>" maxlength="20" placeholder="貼上追蹤程式碼 ID（如 G-123456789）">
                                        <p class="fieldHint">
                                            &lt;script async src="https://www.googletagmanager.com/gtag/js?id=<span class="red">G-123456789</span>"&gt;&lt;/script&gt;
                                        </p>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="gtmCode">GTM-Code</label>
                                    <div class="col--10">
                                        <input type="text" name="gtmCode" id="gtmCode" class="formInput" value="<?php echo e($gtmCode); ?>" maxlength="20" placeholder="GTM 容器 ID">
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__tabs tabsGp">
                            <ul class="tabsGp__tabs">
                                <?php for ($i = 1; $i <= $langCount; $i++) { ?>
                                <li id="tabNav_<?php echo $i; ?>"
                                    class="tabsGp__link --color<?php echo $i; ?>"
                                    data-tab-target="tabCon_<?php echo $i; ?>">
                                    <?php echo e((string)($array_lang[$i] ?? '')); ?>
                                </li>
                                <?php } ?>
                            </ul>
                            <div class="tabsGp__body">
                                <?php for ($i = 1; $i <= $langCount; $i++) { ?>
                                <div id="tabCon_<?php echo $i; ?>" class="tabContent --color<?php echo $i; ?>">
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="strName<?php echo $i; ?>">
                                            網站名稱 <span class="inputLabel__required">*</span>
                                            <?php echo manage_render_field_help('結構化使用：顯示於 <title> 標籤'); ?>
                                        </label>
                                        <div class="col--10">
                                            <input type="text" name="strName<?php echo $i; ?>" id="strName<?php echo $i; ?>" class="formInput" value="<?php echo e($strName[$i] ?? ''); ?>" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Description<?php echo $i; ?>">
                                            網站描述
                                            <?php echo manage_render_field_help('顯示於 meta 的 description。搜尋時，瀏覽器會顯示的默認文字。一段時間後，則由搜索引擎自行判斷該頁面重要的內文予以顯示；屆時此欄位的權重會降低。'); ?>
                                        </label>
                                        <div class="col--10">
                                            <textarea name="Description<?php echo $i; ?>" id="Description<?php echo $i; ?>"
                                                class="formInput" rows="4" placeholder="請輸入160字元內的網站描述" maxlength="500"><?php echo e((string)($Description[$i] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel">
                                            網站關鍵字
                                            <?php echo manage_render_field_help('顯示於 meta 的 keywords。搜索引擎以公布降低此權重；改以實際內容之文案為主。'); ?>
                                        </label>
                                        <div class="col--10">
                                            <div class="websetKeywords">
                                                <?php for ($n = 1; $n <= 5; $n++) { ?>
                                                <input name="Keyword<?php echo $i . '_' . $n; ?>" type="text" id="Keyword<?php echo $i . '_' . $n; ?>" class="formInput" value="<?php echo e($Keywords[$i][$n] ?? ''); ?>" maxlength="50" placeholder="關鍵字 <?php echo $n; ?>">
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Tel<?php echo $i; ?>">聯絡電話<?php echo manage_render_field_help('結構化使用，並顯示於前台頁尾、聯絡我們'); ?></label>
                                        <div class="col--10">
                                            <input type="text" name="Tel<?php echo $i; ?>" id="Tel<?php echo $i; ?>" class="formInput" value="<?php echo e($Tel[$i] ?? ''); ?>" maxlength="50">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Fax<?php echo $i; ?>">聯絡傳真<?php echo manage_render_field_help('結構化使用，並顯示於前台頁尾、聯絡我們'); ?></label>
                                        <div class="col--10">
                                            <input type="text" name="Fax<?php echo $i; ?>" id="Fax<?php echo $i; ?>" class="formInput" value="<?php echo e($Fax[$i] ?? ''); ?>" maxlength="50">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Address<?php echo $i; ?>">聯絡地址
                                        <?php echo manage_render_field_help('結構化使用，並顯示於前台頁尾、聯絡我們'); ?></label>
                                        <div class="col--10">
                                            <input type="text" name="Address<?php echo $i; ?>" id="Address<?php echo $i; ?>" class="formInput" value="<?php echo e($Address[$i] ?? ''); ?>" maxlength="255">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Facebook<?php echo $i; ?>">Facebook連結
                                        <?php echo manage_render_field_help('顯示於前台頁尾'); ?></label>
                                        <div class="col--10">
                                            <input name="Facebook<?php echo $i; ?>" type="url" id="Facebook<?php echo $i; ?>" class="formInput" value="<?php echo e($Facebook[$i] ?? ''); ?>" maxlength="255" placeholder="https://">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="IG<?php echo $i; ?>">Instagram連結
                                        <?php echo manage_render_field_help('顯示於前台頁尾'); ?></label>
                                        <div class="col--10">
                                            <input name="IG<?php echo $i; ?>" type="url" id="IG<?php echo $i; ?>" class="formInput" value="<?php echo e($IG[$i] ?? ''); ?>" maxlength="255" placeholder="https://">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Youtube<?php echo $i; ?>">Youtube連結
                                        <?php echo manage_render_field_help('顯示於前台頁尾'); ?></label>
                                        <div class="col--10">
                                            <input name="Youtube<?php echo $i; ?>" type="url" id="Youtube<?php echo $i; ?>" class="formInput" value="<?php echo e($Youtube[$i] ?? ''); ?>" maxlength="255" placeholder="https://">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Line<?php echo $i; ?>">Line連結
                                        <?php echo manage_render_field_help('顯示於前台頁尾'); ?></label>
                                        <div class="col--10">
                                            <input name="Line<?php echo $i; ?>" type="url" id="Line<?php echo $i; ?>" class="formInput" value="<?php echo e($Line[$i] ?? ''); ?>" maxlength="255" placeholder="https://">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Twitter<?php echo $i; ?>">Twitter連結
                                        <?php echo manage_render_field_help('顯示於前台頁尾'); ?></label>
                                        <div class="col--10">
                                            <input name="Twitter<?php echo $i; ?>" type="url" id="Twitter<?php echo $i; ?>" class="formInput" value="<?php echo e($Twitter[$i] ?? ''); ?>" maxlength="255" placeholder="https://">
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </article>

                        <div class="editView__footer">
                            <button type="submit" name="Submit" id="Submit" value="送出" class="btnStyle">
                                <i class="bi bi-save"></i> 送出
                            </button>
                            <?php
                            echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;
                            echo hiddenNumeric('Total_lang', $langCount) . PHP_EOL;
                            echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
                            echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
                            ?>
                        </div>
                        </form>
                    </section>

                    <div class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>各語系對應 <span class="badge notes__badge">webset</span> 表一筆資料（<code>intLang</code>）。</li>
                            <li>寄件／收件信箱、GA／GTM 代碼儲存於各語系列（讀取時取第一筆非空值）。</li>
                            <li>網站名稱為必填；描述建議 160 字元以內。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
