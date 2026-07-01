<?php
declare(strict_types=1);

member_detail_export_vars();

$isAdd = (int)($Update_PKey ?? 0) <= 0;
$pwMatch = (int)($PW_Match ?? $GLOBALS['PW_Match'] ?? 2);
if ($pwMatch < 2) {
    $pwMatch = 2;
}
$layout_page_title = (string)($layout_page_title ?? ($isAdd ? '新增會員' : '編輯會員'));
?>
<?php require_once '../_layout_head.php'; ?>
<?php echo script_src_tag('../js/area.js'); ?>
<?php echo script_open(); ?>
function fieldCheck0(theForm) {
  if (typeof loading === 'function') {
    loading(1);
  }
  const errors = [];
  const fields = [];
  const isNew = <?php echo $isAdd ? 'true' : 'false'; ?>;
  const email = $.trim($('#EMail').val());
  const strPW = $.trim($('#strPW').val());
  const minComplexity = <?php echo $pwMatch; ?>;

  if (email === '') {
    errors.push('會員帳號（Email）空白');
    fields.push('EMail');
  } else if (typeof isEmail === 'function' && !isEmail(email)) {
    errors.push('會員帳號（Email）格式錯誤');
    fields.push('EMail');
  } else {
    const emailMsg = $.trim($('#EMail_txt').text());
    if (emailMsg !== '') {
      errors.push(emailMsg.indexOf('重複') >= 0 ? '會員帳號（Email）已被使用' : emailMsg);
      fields.push('EMail');
    }
  }

  if ($.trim($('#strName').val()) === '') {
    errors.push('姓名空白');
    fields.push('strName');
  }
  const mobile = $.trim($('#Mobile').val());
  if (mobile === '') {
    errors.push('手機號碼空白');
    fields.push('Mobile');
  } else if (typeof chkMobile === 'function' && !chkMobile(mobile)) {
    errors.push('手機號碼格式錯誤（請輸入 09 開頭共 10 碼）');
    fields.push('Mobile');
  }

  const checkPasswordRules = function () {
    if (strPW.length < 8 || strPW.length > 20) {
      errors.push('密碼長度需為8~20碼');
      fields.push('strPW');
      return;
    }
    let complexity = 0;
    if (/[a-z]/.test(strPW)) complexity++;
    if (/[A-Z]/.test(strPW)) complexity++;
    if (/[0-9]/.test(strPW)) complexity++;
    if (/[~!@#$%^&*()\-_=+{};:<,.>?]/.test(strPW)) complexity++;
    if (complexity < minComplexity) {
      errors.push('密碼需符合複雜度規則（至少 ' + minComplexity + ' 種）');
      fields.push('strPW');
    }
  };

  if (isNew) {
    if (strPW === '') {
      errors.push('密碼不可空白');
      fields.push('strPW');
    } else {
      checkPasswordRules();
    }
  } else if (strPW !== '') {
    checkPasswordRules();
  }

  const county = $('#strCounty').val() || '';
  const city = $('#strCity').val() || '';
  if (county === '' || county === '請選擇') {
    errors.push('請選擇縣市');
    fields.push('strCounty');
  }
  if (city === '' || city === '請選擇') {
    errors.push('請選擇鄉鎮市區');
    fields.push('strCity');
  }
  if ($.trim($('#Address').val()) === '') {
    errors.push('請輸入詳細地址');
    fields.push('Address');
  }

  if (errors.length > 0) {
    return window.manageFormValidationFail(errors, { focusField: fields[0], form: theForm });
  }
  return window.manageFormValidationOk(theForm);
}

function genStrongPassword(minComplexity, minLen, maxLen) {
  minComplexity = Math.max(2, Math.min(4, +minComplexity || 3));
  minLen = Math.max(8, +minLen || 12);
  maxLen = Math.max(minLen, +maxLen || 20);
  const sets = { U: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', L: 'abcdefghijklmnopqrstuvwxyz', D: '0123456789', S: '~!@#$%^&*()-_=+{};:<,.>?' };
  const pick = function (k) {
    const pool = sets[k];
    return pool[crypto.getRandomValues(new Uint32Array(1))[0] % pool.length];
  };
  const keys = Object.keys(sets).sort(function () {
    return (crypto.getRandomValues(new Uint32Array(1))[0] % 2) ? 1 : -1;
  });
  const needKeys = keys.slice(0, minComplexity);
  const chars = needKeys.map(pick);
  const targetLen = Math.min(maxLen, Math.max(minLen, 14));
  const all = Object.values(sets).join('');
  while (chars.length < targetLen) {
    chars.push(all[crypto.getRandomValues(new Uint32Array(1))[0] % all.length]);
  }
  for (let i = chars.length - 1; i > 0; i--) {
    const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
    const t = chars[i]; chars[i] = chars[j]; chars[j] = t;
  }
  return chars.join('');
}

function checkMemberEmail() {
  const email = $.trim($('#EMail').val());
  const $txt = $('#EMail_txt');
  if (email === '') {
    $txt.text('');
    return;
  }
  $.post('_chkid.php', {
    EMail: email,
    excludePKey: <?php echo (int)($Update_PKey ?? 0); ?>
  }).done(function (res) {
    $txt.text(typeof res === 'string' ? res : '');
  });
}

document.addEventListener('DOMContentLoaded', function () {
  const county = document.getElementById('strCounty');
  const city = document.getElementById('strCity');
  const post = document.getElementById('PostCode');
  if (county && city && post && typeof initCounty2 === 'function') {
    initCounty2(county, county.getAttribute('data-default-county') || '');
    initZone2(county, city, post, city.getAttribute('data-default-city') || '');
    county.addEventListener('change', function () { changeZone(county, city, post); });
    city.addEventListener('change', function () { showZipCode(county, city, post); });
  }
  document.getElementById('btn-gen')?.addEventListener('click', function () {
    try {
      const pw = genStrongPassword(<?php echo $pwMatch; ?>, 12, 20);
      const input = document.getElementById('strPW');
      if (input) {
        input.value = pw;
        $('#strPW_txt').text('已產生強密碼，請妥善保存（送出後不可再查看）。');
      }
    } catch (e) {
      alert('瀏覽器不支援安全亂數，請手動輸入密碼。');
    }
  });
  $('#EMail').on('blur', checkMemberEmail);
});
<?php echo script_close(); ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php
                    if (!empty($breadcrumbs) && is_array($breadcrumbs)) {
                        require_once '../_breadcrumbs.php';
                    }
                    ?>

          <form action="addin.php" method="post" name="form1" id="form1" novalidate data-manage-validate="fieldCheck0">
            <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
              <div class="errorArea__header">錯誤訊息</div>
              <div class="errorArea__body"><ul id="formErrorList"></ul></div>
            </div>
            <div class="table-container">
              <table cellspacing="0" cellpadding="0" width="100%" border="0" class="detail">
                <tr>
                  <td>會員帳號（Email）<span class="inputLabel__required">*</span></td>
                  <td>
                    <input id="EMail" type="email" name="EMail" class="formInput" maxlength="100"
                      value="<?php echo e((string)($EMail ?? '')); ?>" placeholder="請輸入 Email" autocomplete="username">
                    <input name="oldMail" type="hidden" id="oldMail" value="<?php echo e((string)($EMail ?? '')); ?>">
                    <span id="EMail_txt" class="input__errorTxt" role="alert"></span>
                  </td>
                </tr>
                <tr>
                  <td>密碼<?php echo $isAdd ? '<span class="inputLabel__required">*</span>' : ''; ?></td>
                  <td>
                    <div class="passwordInput">
                      <input type="password" name="strPW" id="strPW" class="formInput" value=""
                        autocomplete="new-password"
                        placeholder="<?php echo $isAdd ? '8~20碼，含大小寫/數字/符號' : '留白表示不變更密碼'; ?>">
                      <i class="bi bi-eye-slash-fill password-icon eyeIcon" role="button" tabindex="0"
                        data-manage-action="toggle-password" data-target-id="strPW" aria-label="顯示或隱藏密碼"></i>
                    </div>
                    <span id="strPW_txt" class="form-hint"></span>
                    <button type="button" id="btn-gen" class="btnStyle btnStyle--outline btnStyle--sm" style="margin-top:6px;">產生強密碼</button>
                    <ul class="set-tips">
                      <li>密碼長度 8~20 碼，至少符合 <?php echo $pwMatch; ?> 種複雜度（大寫、小寫、數字、符號）。</li>
                      <?php if (!$isAdd) { ?><li>編輯時留白表示不變更密碼。</li><?php } ?>
                    </ul>
                  </td>
                </tr>
                <tr>
                  <td>姓名<span class="inputLabel__required">*</span></td>
                  <td>
                    <input type="text" name="strName" id="strName" class="formInput" maxlength="20"
                      value="<?php echo e((string)($strName ?? '')); ?>">
                  </td>
                </tr>
                <tr>
                  <td>手機號碼<span class="inputLabel__required">*</span></td>
                  <td>
                    <input type="text" name="Mobile" id="Mobile" class="formInput" maxlength="20"
                      value="<?php echo e((string)($Mobile ?? '')); ?>">
                  </td>
                </tr>
                <tr>
                  <td>電話</td>
                  <td>
                    <input type="text" name="Tel" id="Tel" class="formInput" maxlength="20"
                      value="<?php echo e((string)($Tel ?? '')); ?>">
                  </td>
                </tr>
                <tr>
                  <td>性別</td>
                  <td>
                    <select name="Sex" id="Sex" class="formSelect">
                      <option value="">請選擇</option>
                      <option value="男"<?php echo (($Sex ?? '') === '男') ? ' selected' : ''; ?>>男</option>
                      <option value="女"<?php echo (($Sex ?? '') === '女') ? ' selected' : ''; ?>>女</option>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td>生日</td>
                  <td>
                    <select name="Birth_Y" id="Birth_Y" class="formSelect m-select">
                      <option value="">年</option>
                      <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 80; $y--) { ?>
                      <option value="<?php echo $y; ?>"<?php echo (string)($Birth_Y ?? '') === (string)$y ? ' selected' : ''; ?>><?php echo $y; ?></option>
                      <?php } ?>
                    </select>
                    <select name="Birth_M" id="Birth_M" class="formSelect m-select">
                      <option value="">月</option>
                      <?php for ($m = 1; $m <= 12; $m++) {
                          $mv = function_exists('Addzero') ? Addzero($m) : sprintf('%02d', $m);
                      ?>
                      <option value="<?php echo $mv; ?>"<?php echo (string)($Birth_M ?? '') === (string)$mv || (string)($Birth_M ?? '') === (string)$m ? ' selected' : ''; ?>><?php echo $mv; ?></option>
                      <?php } ?>
                    </select>
                    <select name="Birth_D" id="Birth_D" class="formSelect m-select">
                      <option value="">日</option>
                      <?php for ($d = 1; $d <= 31; $d++) {
                          $dv = function_exists('Addzero') ? Addzero($d) : sprintf('%02d', $d);
                      ?>
                      <option value="<?php echo $dv; ?>"<?php echo (string)($Birth_D ?? '') === (string)$dv || (string)($Birth_D ?? '') === (string)$d ? ' selected' : ''; ?>><?php echo $dv; ?></option>
                      <?php } ?>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td>地址<span class="inputLabel__required">*</span></td>
                  <td>
                    <div class="address">
                      <select name="strCounty" id="strCounty" class="formSelect m-select"
                        data-default-county="<?php echo e((string)($strCounty ?? '')); ?>"></select>
                      <select name="strCity" id="strCity" class="formSelect m-select"
                        data-default-city="<?php echo e((string)($strCity ?? '')); ?>"></select>
                      <input name="PostCode" type="text" id="PostCode" class="formInput" maxlength="5"
                        value="<?php echo e((string)($PostCode ?? '')); ?>" placeholder="郵遞區號" readonly>
                      <input type="text" name="Address" id="Address" class="formInput" maxlength="100"
                        value="<?php echo e((string)($Address ?? '')); ?>" placeholder="詳細地址">
                    </div>
                  </td>
                </tr>
                <?php if (!$isAdd) { ?>
                <tr>
                  <td>加入日期</td>
                  <td><?php echo e((string)($dtDate ?? '')); ?></td>
                </tr>
                <tr>
                  <td>修改日期</td>
                  <td><?php require_once '../_modify.php'; ?></td>
                </tr>
                <?php } ?>
              </table>
            </div>

            <?php
            echo hiddenNumeric('intLang', (int)($intLang ?? 1)) . PHP_EOL;
            require_once '../_submit.php';
            ?>
          </form>
<?php
require_once '../_layout_body_close.php';
require_once '../_in_code_bottom.php';
?>
</body>
</html>
