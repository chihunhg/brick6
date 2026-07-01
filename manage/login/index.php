<?php
require_once("../_inc.php");

$Module_Name = '登入頁';

// ---- 安全初始化 ----
$action  = strtolower(request_scalar_string($filter_array['Action'] ?? ''));
$strID   = filter_request_scalar($filter_array, 'strID', 'str');
$strPW   = filter_request_scalar($filter_array, 'strPW', 'str');
$submit  = request_scalar_string($filter_array['Submit'] ?? '');
$chk     = 0;
$show    = '';
$error   = 0;

// ---- 登出 ----
if ($action === 'logout') {
    // 寫入網站管理記錄
    manage_history(3, $Module_Name, '', $WorkFile, ($_SESSION['Login_ID'] ?? ''), '使用者登出');
    session_destroy();
    $show = '你已執行登出動作';
    echo manage_inline_script(
        'alert(' . json_encode($show, JSON_UNESCAPED_UNICODE) . ');location.href=' . json_encode('login.php', JSON_UNESCAPED_SLASHES) . ';'
    );
    exit;
}

// 已登入者開啟登入頁 → 導向直播訂單（勿導回首頁 index）
if (isset($_SESSION['Manage'], $_SESSION['Login_ID'])
    && $_SESSION['Manage'] === 'Yes'
    && (string)$_SESSION['Login_ID'] !== '') {
    location_href($web_root . 'manage/login/login.php');
    exit;
}

// 依等級產生符合規則的初始密碼（僅建立帳號當下用一次）
//$initial_secret = generate_initial_secret($POLICY, 12, 20);
switch($Web_Secure){
	case 1://免費專業方案
		$initial_secret = 'brick4080';//預設值
		break;
	case 2://付費高階方案
		$initial_secret = 'Brick4080';//預設值
		break;
	case 3://公家普級方案
		$initial_secret = 'Aa@4080';//預設值
		break;
	default:
		$initial_secret = 'Brick4080';//預設值
		break;
}
// ---- 若無 Admin，建立預設管理者（不輸出敏感資訊）----
$sql = 'SELECT PKey FROM webcontrol WHERE strID = :strID';
$rs  = new recordset($sql, ['strID' => 'Admin']);
if ($err = $rs->getErrorMessage()) {
    $result = sql_error($sql.PHP_EOL.array_to_string(['Admin']), $err, $WorkFile, 'system');
    echo '<pre>'; print_r($result); echo '</pre>'; exit;
}
if ($rs->eof) {
    $sql_query  = new dbPDO();
    $table_name = 'webcontrol';
    $now        = date('Y-m-d H:i:s');
    $data_array = [
        'strName'    => '網站管理者',
        'intType'    => '1',
        'strID'      => 'Admin',
        'strPW'      => hash_password($initial_secret),
        'FunctionID' => '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15',
        'UserID'     => 'Admin',
        'dtUDate'    => $now,
        'dtDate'     => $now,
    ];
    $sql_query->insert($table_name, $data_array);
    $sql_query->close();
}
$rs->close();
// reCAPTCHA（從 .env 讀）
$site_key = $_ENV['RECAPTCHA_SITE_KEY'] ?? '';
// ---- 30 分鐘解鎖 ----
$pdo = new dbPDO();
$pdo->execute(
  "UPDATE webcontrol 
   SET isLock = 0, error = 0, dtUDate = NOW()
   WHERE TIMESTAMPDIFF(MINUTE, dtUDate, NOW()) > 30 AND isLock = 1"
);
$pdo->close();

// ---- 登入提交 ----
if (!empty($submit) && $submit === '送出') {
	// ---- CSRF 驗證（一次性）----
	$posted  = request_scalar_string($filter_array['csrf_token'] ?? '');
	$session = (string)($_SESSION['csrf'][$__csrf_key] ?? '');
	if ($posted === '' || $session === '' || !hash_equals($session, $posted)) {
		http_response_code(403);
		echo manage_inline_script("alert('Invalid CSRF token');history.back();");
		exit;
	}
	// 單次使用後作廢，避免重放
	unset($_SESSION['csrf'][$__csrf_key]);	
	
	// reCAPTCHA（從 .env 讀）
    $secret_key = $_ENV['RECAPTCHA_SECRET'] ?? '';
    if ($secret_key) {
        // 【START: WAF 繞過調整】
        // 1. 從前端接收 Base64 包一層的 token（POST 須還原「+」）
        $resToken = recaptcha_resolve_response_token_from_request($filter_array);

        if ($resToken === '') {
            $show = '【我不是機器人】驗證失敗，請重試';
            echo manage_inline_script(
                'alert(' . json_encode($show, JSON_UNESCAPED_UNICODE) . ');location.href=' . json_encode($WorkFile, JSON_UNESCAPED_SLASHES) . ';'
            );
            exit;
        }

        $remoteIP = $_SERVER['REMOTE_ADDR'] ?? '';
        $verify    = recaptcha_siteverify($secret_key, $resToken, $remoteIP);
        if (!$verify['success'] && $remoteIP !== '') {
            $verify = recaptcha_siteverify($secret_key, $resToken, '');
        }
        if (!$verify['success']) {
            $show = '【我不是機器人】請點選';
            echo manage_inline_script(
                'alert(' . json_encode($show, JSON_UNESCAPED_UNICODE) . ');location.href=' . json_encode($WorkFile, JSON_UNESCAPED_SLASHES) . ';'
            );
            exit;
        }
        // 【END: WAF 繞過調整】
    }

    // 基本檢查
    if ($strID === '' || $strPW === '') {
        $show = '請輸入帳號與密碼';
        echo manage_inline_script(
            'alert(' . json_encode($show, JSON_UNESCAPED_UNICODE) . ');location.href=' . json_encode($WorkFile, JSON_UNESCAPED_SLASHES) . ';'
        ); exit;
    }

    $sql = 'SELECT * FROM webcontrol WHERE strID = :strID';
    $rs  = new recordset($sql, ['strID' => $strID]);

    if (!$rs->eof) {
        $PKey     = (int)$rs->field('PKey');
        $hashedPW = (string)$rs->field('strPW');
        $isLock   = (int)$rs->field('isLock');
        $error    = (int)$rs->field('error'); // 現有錯誤次數
        $show     = '';
        // 鎖定中
        if ($isLock === 1 && !empty($Web_Secure) && $Web_Secure > 1) {
            $chk  = 1;
            $show = '帳號已鎖定，30分鐘後重試';
        }
        // ✅ 改：用 secure_verify_and_migrate（支援 pepper + 舊格式遷移）
        elseif (!secure_verify_and_migrate($strPW, $hashedPW, $PKey)) {
            $chk   = 1;
            $error = $error + 1; // 累加
            $show  = '帳號或密碼不符';
        }
        // 驗證成功
        else {
            // if (session_status() !== PHP_SESSION_ACTIVE) {
				// session_start();
			// }
			// session_regenerate_id(true);
            $_SESSION['Manage']     = 'Yes';
            $_SESSION['UserName']   = $rs->field('strName');
            $_SESSION['Login_ID']   = $rs->field('strID');
            $_SESSION['FunctionID'] = $rs->field('FunctionID');

            // 清除錯誤與解除鎖定
            $pdo = new dbPDO();
            $pdo->update('webcontrol', [
                'isLock'  => 0,
                'error'   => 0,
                'dtUDate' => date('Y-m-d H:i:s'),
            ], 'PKey', $PKey);
            $pdo->close();

            $show = '登入成功';
            manage_history(3, $Module_Name, '登入成功', $WorkFile, $strID, $show);

            // 正常登入導頁（根相對路徑，子目錄部署亦可正確）
            location_href($web_root . 'manage/login/login.php');
            exit;
        }
    } else {
        // 帳號不存在 → 不揭露是哪邊錯（防止帳號探測）
        $chk   = 1;
        $show  = '帳號或密碼不符';
        $error = 0; // 帳號不存在，不累加
    }

    // 記錄，但避免把敏感資訊寫進 log
    $SQL_U = 'select * from webcontrol Where strID = :strID';
    manage_history(3, $Module_Name, $SQL_U, $WorkFile, $strID, $show);

    // 失敗 → 處理錯誤次數/鎖定（只針對存在的帳號）
    if ($chk === 1 && !empty($Web_Secure) && $Web_Secure > 1) {
        $sql2 = 'SELECT PKey, isLock, error FROM webcontrol WHERE strID = :strID';
        $rs2  = new recordset($sql2, ['strID' => $strID]);
        if (!$rs2->eof) {
            $PKey2   = (int)$rs2->field('PKey');
            $isLock2 = (int)$rs2->field('isLock');

            $pdo = new dbPDO();
            if ($error > 5 || $isLock2 === 1) {
                $upd = [
                    'isLock'  => 1,
                    'dtUDate' => date('Y-m-d H:i:s'),
                ];
                $show = '帳號已鎖定，30分鐘後重試';
            } else {
                $upd = ['error' => $error];
                $show = '登入錯誤，剩餘'.(5 - $error).'次';
            }
            $pdo->update('webcontrol', $upd, 'PKey', $PKey2);
            $pdo->close();

            manage_history(3, $Module_Name, '更新登入錯誤/鎖定', $WorkFile, $strID, $show);
        }
        $rs2->close();
    }

    echo manage_inline_script(
        'alert(' . json_encode($show, JSON_UNESCAPED_UNICODE) . ');location.href=' . json_encode($WorkFile, JSON_UNESCAPED_SLASHES) . ';'
    );
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($WebName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>｜後端管理系統</title>
<link rel="icon" href="<?= e_attr(site_favicon_href()) ?>" type="image/x-icon">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php require_once("../_in_javascript.php"); ?>

<?php echo script_open(); ?>
/* Base64 處理函數 */
// 由於 g-recaptcha-response 可能是 Base64-URL，我們使用標準 Base64 來進行二次編碼
function b64EncodeUnicode(str) {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/gi,
        function(match, p1) {
            return String.fromCharCode(parseInt(p1, 16));
        }));
}

function clearRawRecaptchaFields() {
    // 清掉 widget 生成的 textarea 與任何 input.hidden.g-recaptcha-response
    // jQuery safe 選取
    try {
        var $areas = $('textarea[name="g-recaptcha-response"], input.g-recaptcha-response, textarea.g-recaptcha-response');
        if ($areas.length) {
            $areas.each(function() {
                $(this).val('');
                // 若想完全移除欄位可以使用 $(this).remove();
            });
        }
        // 有些情況 widget 會插入在 form 之外，保險起見也查整個 document
        $('textarea[name="g-recaptcha-response"]').val('');
    } catch (e) {
        // silent
        console.warn('clearRawRecaptchaFields() error', e);
    }
}

function sbForm(theForm) 
{
	//theForm.submit();
}
  
function fieldCheck0(theForm) {
  loading(1);
  var errors = [];
  var fields = [];

  if ($('#strID').val() === '') {
    errors.push('帳號空白');
    fields.push('strID');
  }

  if ($('#strPW').val() === '') {
    errors.push('密碼空白');
    fields.push('strPW');
  }

  if (errors.length) {
    return window.manageFormValidationFail(errors, {
      focusField: fields[0],
      form: theForm
    });
  }

    var loginRecaptchaOn = <?php echo ($site_key !== '' ? 'true' : 'false'); ?>;
    if (loginRecaptchaOn) {
    // reCAPTCHA 驗證：先取原 token（widget 填在 textarea.g-recaptcha-response）
    var rawToken = (typeof grecaptcha !== 'undefined' && grecaptcha && typeof grecaptcha.getResponse === 'function') ? grecaptcha.getResponse() : '';

    if (!rawToken || rawToken.length === 0) {
        return window.manageFormValidationFail(
          ["<?php echo $lang_text["chk_google_code"][$this_lang]; ?>"],
          { form: theForm }
        );
    }

    // 1) Base64 包一層（WAF 可略過）；另送 recaptcha_response_raw 備援，避免編碼／POST 截斷導致解碼為空
    try {
        var encodedToken = b64EncodeUnicode(rawToken);
    } catch (e) {
        console.warn('encode recaptcha token failed', e);
        encodedToken = '';
    }

    if (!encodedToken) {
        return window.manageFormValidationFail(['我不是機器人未勾選'], { form: theForm });
    }

    // 2) 隱藏欄：編碼 + 明文備援
    $('#encoded_recaptcha_token').val(encodedToken);
    $('#recaptcha_response_raw').val(rawToken);

    // 3) **清空** widget 自動產生的 g-recaptcha-response 欄位（避免 WAF 偵測）
    clearRawRecaptchaFields();

    // 勿在送出前呼叫 grecaptcha.reset()：會使本次 response token 在 Google 端失效，siteverify 永遠失敗。
    }

    // 4) 由表單 onsubmit 接續原生 POST（勿再 trigger submit，否則會重複觸發驗證）
    return true;
}

<?php echo script_close(); ?>
<?php
$__recaptcha_nonce = e_attr((string)($cspNonce ?? csp_nonce()));
if ($site_key !== '') {
    echo '<script src="https://www.google.com/recaptcha/api.js?hl=zh-TW" async defer nonce="' . $__recaptcha_nonce . '"></script>' . "\n";
}
?>
</head>

<body class="loginPage">
	<?php require_once("../_header.php"); ?>
	<div class="wrap login">
		<form action="" method="post" name="form1" id="form1" data-manage-validate="fieldCheck0">
		<div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
			<div class="errorArea__header">錯誤訊息</div>
			<div class="errorArea__body">
				<ul id="formErrorList"></ul>
			</div>
		</div>
		<div class="box">
			<h1><?= e($WebName) ?><span>後端管理系統</span></h1>
			<div class="item">
				<label for="strID">帳號</label>
				<input type="text" autofocus name="strID" id="strID" autocomplete="off" class="formInput">
				<span id="strID_txt" class="red"></span>
			</div>
			<div class="item">
				<label for="strPW">密碼</label>
				<div class="passwordInput">
					<input type="password" name="strPW" id="strPW" autocomplete="new-password" class="formInput">
					<i class="bi bi-eye-slash-fill password-icon eyeIcon" role="button" tabindex="0" data-manage-action="toggle-password" data-target-id="strPW" aria-label="顯示或隱藏密碼"></i>
				</div>
				<span id="strPW_txt" class="red"></span>
			</div>
			<div class="item">
				<label>驗証碼</label>
				<div class="passwordInput">
					<?php if ($site_key !== ''): ?>
					<div class="g-recaptcha" data-sitekey="<?php echo e_attr($site_key); ?>"></div>
					<?php else: ?>
					<p class="loginRecaptchaHint" role="status">未設定 RECAPTCHA_SITE_KEY，登入略過機器人驗證。</p>
					<?php endif; ?>
					<span id="g-recaptcha_txt" class="red"></span>
					<input type="hidden" name="encoded_recaptcha_token" id="encoded_recaptcha_token" value="">
					<input type="hidden" name="recaptcha_response_raw" id="recaptcha_response_raw" value="">
				</div>
			</div>
			<button type="submit" name="Submit" id="Submit" value="送出" class="full">登入</button>
			<input type="hidden" name="csrf_token" value="<?php echo e($csrf_token) ?>">
		</div>
		</form>
		<p class="copyright">Copyright © <?php echo date('Y').' '.$WebName?> All rights.&nbsp;<a href="https://www.tsg.com.tw/" target="_blank" rel="noopener noreferrer" title="天矽科技 │ 客製化網頁設計">Designed by TSG</a></p>
	</div>
	<?php require_once("../_in_code_bottom.php"); ?>
</body>

</html>
