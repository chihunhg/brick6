<?php
declare(strict_types=1);
/**
 * 後台帳號新增／編輯表單（由 add.php、update.php 引入）
 *
 * 需由父頁提供：$csrf_token、$breadcrumbs（可選）、$layout_page_title（可選）
 */

control_detail_export_vars();

$isAdd   = (int)($Update_PKey ?? 0) <= 0;
$pwMatch = (int)($PW_Match ?? $CRED_MIN_COMPLEXITY ?? 2);
if ($pwMatch < 2) {
    $pwMatch = 2;
}

$layout_page_title = (string)($layout_page_title ?? ($isAdd ? '新增帳號' : '編輯帳號'));
?>
<?php require_once '../_layout_head.php'; ?>
<meta name="csrf-token" content="<?php echo e((string)($csrf_token ?? '')); ?>">
<?php echo script_open(); ?>
function fieldCheck0(theForm) {
  if (typeof loading === 'function') {
    loading(1);
  }
  const errors = [];
  const fields = [];
  const isNew = <?php echo $isAdd ? 'true' : 'false'; ?>;
  const strName = $.trim($('#strName').val());
  const strID   = $.trim($('#strID').val());
  const strPW   = $.trim($('#strPW').val());
  const minComplexity = <?php echo $pwMatch; ?>;

  if (strName === '') {
    errors.push('管理者名稱空白');
    fields.push('strName');
  }

  const regID = /^[a-zA-Z0-9]{2,20}$/;
  if (!regID.test(strID)) {
    errors.push('帳號格式錯誤（限2~20碼英文或數字）');
    fields.push('strID');
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
      const need = [];
      if (!/[a-z]/.test(strPW)) need.push('小寫英文字母');
      if (!/[A-Z]/.test(strPW)) need.push('大寫英文字母');
      if (!/[0-9]/.test(strPW)) need.push('數字');
      if (!/[~!@#$%^&*()\-_=+{};:<,.>?]/.test(strPW)) need.push('特殊符號');
      errors.push('密碼需符合複雜度規則（至少 ' + minComplexity + ' 種），缺少：' + need.join('、'));
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

  if ($('input[name="FunctionName[]"]:checked').length === 0) {
    errors.push('請勾選至少一項權限');
  }

  if (errors.length > 0) {
    return window.manageFormValidationFail(errors, {
      focusField: fields[0],
      form: theForm
    });
  }
  return window.manageFormValidationOk(theForm);
}

function genStrongPassword(minComplexity, minLen, maxLen) {
  minComplexity = Math.max(2, Math.min(4, +minComplexity || 3));
  minLen = Math.max(8, +minLen || 12);
  maxLen = Math.max(minLen, +maxLen || 20);
  const sets = {
    U: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    L: 'abcdefghijklmnopqrstuvwxyz',
    D: '0123456789',
    S: '~!@#$%^&*()-_=+{};:<,.>?'
  };
  const pick = function (k) {
    const pool = sets[k];
    const idx = crypto.getRandomValues(new Uint32Array(1))[0] % pool.length;
    return pool[idx];
  };
  const keys = Object.keys(sets);
  keys.sort(function () {
    return (crypto.getRandomValues(new Uint32Array(1))[0] % 2) ? 1 : -1;
  });
  const needKeys = keys.slice(0, minComplexity);
  const chars = [];
  needKeys.forEach(function (k) {
    chars.push(pick(k));
  });
  const targetLen = Math.min(maxLen, Math.max(minLen, 14));
  const all = Object.values(sets).join('');
  while (chars.length < targetLen) {
    const idx = crypto.getRandomValues(new Uint32Array(1))[0] % all.length;
    chars.push(all[idx]);
  }
  for (let i = chars.length - 1; i > 0; i--) {
    const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
    const t = chars[i];
    chars[i] = chars[j];
    chars[j] = t;
  }
  return chars.join('');
}

function controlSelectAll() {
  $('input[name="FunctionName[]"]').prop('checked', true);
}

function controlSelectNone() {
  $('input[name="FunctionName[]"]').prop('checked', false);
}

document.addEventListener('DOMContentLoaded', function () {
  const btnGen = document.getElementById('btn-gen');
  if (btnGen) {
    btnGen.addEventListener('click', function () {
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
  }
  document.getElementById('btn-fn-all')?.addEventListener('click', controlSelectAll);
  document.getElementById('btn-fn-none')?.addEventListener('click', controlSelectNone);
});
<?php echo script_close(); ?>
</head>
<?php require_once '../_layout_body_open.php'; ?>
                    <?php
                    if (!empty($breadcrumbs) && is_array($breadcrumbs)) {
                        require_once '../_breadcrumbs.php';
                    }
                    ?>

                    <form action="addin.php" method="post" enctype="multipart/form-data" name="form1" id="form1" novalidate data-manage-validate="fieldCheck0">
                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"></ul>
                            </div>
                        </div>
                        <div class="table-container">
                            <table cellspacing="0" cellpadding="0" width="100%" border="0" class="detail">
                                <tr>
                                    <td>管理者名稱<span class="inputLabel__required">*</span></td>
                                    <td>
                                        <input type="text" name="strName" id="strName" class="formInput" value="<?php echo e($strName); ?>" size="20" maxlength="20">
                                    </td>
                                </tr>
                                <tr>
                                    <td>帳號<span class="inputLabel__required">*</span></td>
                                    <td>
                                        <input name="strID" type="text" id="strID" class="formInput" value="<?php echo e($strID); ?>" maxlength="20" placeholder="帳號長度2~20碼"<?php echo $isAdd ? '' : ' readonly'; ?>>
                                        <input name="oldID" type="hidden" id="oldID" value="<?php echo e($strID); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>密碼<?php echo $isAdd ? '<span class="inputLabel__required">*</span>' : ''; ?></td>
                                    <td>
                                        <div class="passwordInput">
                                            <input type="password" name="strPW" id="strPW" class="formInput" value=""
                                                autocomplete="new-password"
                                                placeholder="<?php echo $isAdd ? '建議12碼以上，含大小寫/數字/符號（系統規則：8~20碼）' : '留白表示不變更密碼'; ?>">
                                            <input name="oldPW" type="hidden" id="oldPW" value="">
                                            <i class="bi bi-eye-slash-fill password-icon eyeIcon"
                                                role="button"
                                                tabindex="0"
                                                data-manage-action="toggle-password"
                                                data-target-id="strPW"
                                                aria-label="顯示或隱藏密碼"></i>
                                        </div>
                                        <span id="strPW_txt" class="form-hint"></span>
                                        <button type="button" id="btn-gen" class="btnStyle btnStyle--outline btnStyle--sm" style="margin-top:6px;">產生強密碼</button>
                                        <ul class="set-tips">
                                            <li>密碼長度為 8~20 碼。</li>
                                            <li>提升密碼複雜度，需符合下列最少<?php echo $pwMatch; ?>種規則：</li>
                                            <li>英文大寫字元 (A 到 Z)</li>
                                            <li>英文小寫字元 (a 到 z)</li>
                                            <li>10 個基本數字 (0 到 9)</li>
                                            <li>非英文字母字元 (例如 ~ ! @ # $ % ^ & * ( ) - _ = + { } ; : &lt; , . &gt; ? )</li>
                                            <li>為安全起見，系統不會保存明文密碼；請妥善保管新設定的密碼。</li>
                                            <?php if (!$isAdd) { ?>
                                            <li>編輯時密碼留白表示不變更。</li>
                                            <?php } ?>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>權限範圍<span class="inputLabel__required">*</span></td>
                                    <td>
                                        <div class="menuSelect none">
                                            <button type="button" id="btn-fn-all" class="btnStyle btnStyle--outline btnStyle--sm">全選</button>
                                            <button type="button" id="btn-fn-none" class="btnStyle btnStyle--outline btnStyle--sm">取消全選</button>
                                            <ul>
                                                <?php
                                                $i = 0;
                                                $sql = "SELECT PKey, strName FROM module_p WHERE Upload = 'Yes' AND intType = 1 ORDER BY Home DESC, Sort";
                                                $moduleRows = crud_fetch_all($sql);
                                                foreach ($moduleRows as $mRow) {
                                                    $i++;
                                                    $pk = (int)($mRow['PKey'] ?? 0);
                                                    $nm = (string)($mRow['strName'] ?? '');
                                                    $checked = in_array($pk, (array)$M1, true);
                                                    ?>
                                                <li>
                                                    <input name="FunctionName[]" id="f-menu<?php echo $i; ?>" type="checkbox"
                                                        value="<?php echo $pk; ?>|<?php echo e($nm); ?>"<?php echo $checked ? ' checked' : ''; ?>>
                                                    <label for="f-menu<?php echo $i; ?>"><span></span><?php echo e($nm); ?></label>
                                                </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php if (!$isAdd) { ?>
                                <tr>
                                    <td>修改日期</td>
                                    <td><?php require_once '../_modify.php'; ?></td>
                                </tr>
                                <?php } ?>
                            </table>
                        </div>
                        <?php require_once '../_submit.php'; ?>
                    </form>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
