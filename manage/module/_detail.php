<?php
declare(strict_types=1);

$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$langCount = !empty($array_lang) && is_array($array_lang) ? count($array_lang) : 0;
$layerNames = $layerNames ?? [];
$langStrName = $langStrName ?? [];
$Description = $Description ?? [];
$Keywords = $Keywords ?? [];
$isShow = $isShow ?? [];

if (!isset($layout_page_title) || $layout_page_title === '') {
    $layout_page_title = '單元設定';
}
?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once '../_in_code_head.php'; ?>
    <?php require_once '../_in_javascript.php'; ?>
<?php echo script_open(); ?>
(function () {
  'use strict';

  const toInt = (v) => Number.parseInt(String(v || '').trim(), 10) || 0;
  let latestReqId = 0;
  let submitLock = false;

  const panelIds = ['program', 'layer'];

  function hidePanels(ids) {
    panelIds.forEach((id) => {
      const show = ids.indexOf(id) >= 0;
      document.querySelectorAll('[data-module-panel="' + id + '"]').forEach((el) => {
        el.classList.toggle('is-hidden', !show);
      });
    });
  }

  function rebuildLayerOptions(maxLayer, keepValue) {
    const sel = document.getElementById('intLayer');
    if (!sel) return;
    const oldLayerEl = document.getElementById('oldLayer');
    const cur = keepValue !== undefined
      ? String(keepValue)
      : (sel.value || (oldLayerEl ? oldLayerEl.value : ''));
    sel.innerHTML = '<option value="">請選擇</option>';
    for (let i = 2; i <= maxLayer; i++) {
      const opt = document.createElement('option');
      opt.value = String(i);
      opt.textContent = String(i);
      sel.appendChild(opt);
    }
    if (cur && toInt(cur) >= 2 && toInt(cur) <= maxLayer) {
      sel.value = cur;
    }
  }

  function showModule(num) {
    hidePanels([]);
    if (Number(num) === 1) {
      const useEl = document.querySelector('input[name="intUse"]:checked');
      const maxL = useEl ? toInt(useEl.getAttribute('data-max-layer')) : 0;
      showUse(useEl ? useEl.value : '', maxL);
    }
  }

  function showUse(num, maxLayer) {
    const chk = document.getElementById('chkUse');
    if (chk) chk.value = num || '';
    const panels = ['program'];
    if (Number(maxLayer) > 0) {
      panels.push('layer');
      rebuildLayerOptions(maxLayer);
      showLayer();
    } else {
      const sel = document.getElementById('intLayer');
      if (sel) {
        sel.innerHTML = '<option value="">請選擇</option>';
        sel.value = '';
      }
      applySublistHtml('<ul class="moduleLayerList__items"></ul>');
    }
    hidePanels(panels);
  }

  function debounce(fn, wait) {
    let t = 0;
    return function () {
      clearTimeout(t);
      const ctx = this;
      const args = arguments;
      t = setTimeout(() => fn.apply(ctx, args), wait);
    };
  }

  function resolveLayerSourcePKey() {
    const modulePk = toInt(document.getElementById('Module_PKey')?.value);
    if (modulePk > 0) {
      return modulePk;
    }
    const pkey = toInt(document.getElementById('PKey')?.value);
    if (pkey > 0) {
      return pkey;
    }
    return toInt(document.getElementById('Copy_PKey')?.value);
  }

  function applySublistHtml(html) {
    const box = document.getElementById('subList');
    if (!box) return;
    box.innerHTML = html;
    const first = document.querySelector('#subList input[id^="subName"]');
    if (first && typeof first.focus === 'function') {
      first.focus();
    }
  }

  const doShowLayer = debounce(function () {
    const layer = document.getElementById('intLayer')?.value || '';
    const box = document.getElementById('subList');
    if (!layer || toInt(layer) <= 1) {
      if (box) {
        box.innerHTML = '<ul class="moduleLayerList__items"></ul>';
      }
      return;
    }

    const pkey = resolveLayerSourcePKey();
    const reqId = ++latestReqId;
    const token = document.querySelector('input[name="csrf_token"]')?.value || '';
    const body = new URLSearchParams({
      PKey: String(Math.max(0, pkey)),
      Layer: layer,
      csrf_token: token
    });

    fetch('_sublist.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString(),
      credentials: 'same-origin'
    })
      .then(function (res) {
        if (!res.ok) {
          throw new Error('HTTP ' + res.status);
        }
        return res.text();
      })
      .then(function (html) {
        if (reqId !== latestReqId) return;
        applySublistHtml(html);
      })
      .catch(function (err) {
        console.error('載入階層子單元失敗:', err);
      });
  }, 120);

  function showLayer() {
    doShowLayer();
  }

  window.showModule = showModule;
  window.showUse = showUse;
  window.showLayer = showLayer;

  window.login = function (theForm) {
    if (submitLock) return;
    if (fieldCheck0(theForm)) {
      submitLock = true;
      theForm.submit();
    }
  };

  window.fieldCheck0 = function (theForm) {
    if (typeof loading === 'function') loading(1);
    const errors = [];
    const fields = [];

    const sortVal = (document.getElementById('Sort')?.value || '').trim();
    if (sortVal === '' || !/^\d+$/.test(sortVal)) {
      errors.push('單元順序不是數字');
      fields.push('Sort');
    }

    const masterNameEl = document.getElementById('strName');
    if (masterNameEl && (masterNameEl.value || '').trim() === '') {
      errors.push('單元名稱為空白');
      fields.push('strName');
    }

    const totalLang = toInt(document.getElementById('Total_lang')?.value) || 0;
    let hasUnitName = !!(masterNameEl && (masterNameEl.value || '').trim());
    for (let li = 1; li <= totalLang; li++) {
      const el = document.getElementById('strName' + li);
      if (!el) continue;
      if ((el.value || '').trim() !== '') {
        hasUnitName = true;
      }
    }
    if (totalLang > 0 && !hasUnitName) {
      errors.push('單元名稱為空白（請至少填寫一個語系）');
      fields.push('strName1');
    }

    if (document.getElementById('intPage2')?.checked) {
      const link = (document.getElementById('PageLink')?.value || '').trim();
      const ok = /^([a-zA-Z0-9_\-/.]+|\w+\.(html?|php))$/.test(link);
      if (!link || !ok) {
        errors.push('前台連結網址格式不正確');
        fields.push('PageLink');
      }
    }

    const typeVal = document.querySelector('input[name="intType"]:checked')?.value;
    if (typeVal === '1' && !document.querySelector('input[name="intUse"]:checked')) {
      errors.push('功能模組未選擇');
      fields.push('intUse1');
    }

    const layer = toInt(document.getElementById('intLayer')?.value);
    for (let i = 1; i <= layer; i++) {
      const el = document.getElementById('subName' + i);
      if (el && (el.value || '').trim() === '') {
        errors.push('子單元名稱' + i + '為空白');
        fields.push('subName' + i);
      }
    }

    if (errors.length) {
      return window.manageFormValidationFail(errors, {
        focusField: fields[0],
        form: theForm || document.getElementById('form1')
      });
    }
    return window.manageFormValidationOk(theForm || document.getElementById('form1'));
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $.fn && typeof $.fn.maxlength === 'function' && document.getElementById('Interview')) {
      $('#Interview').maxlength({ maxCharacters: 400, slider: true });
    }

    const intType = toInt(<?php echo (int)$intType; ?>);
    showModule(intType);

    document.addEventListener('change', function (ev) {
      if (ev.target && ev.target.id === 'intLayer') {
        const layer = toInt(ev.target.value);
        if (layer <= 1) {
          applySublistHtml('<ul class="moduleLayerList__items"></ul>');
          return;
        }
        showLayer();
      }
    });

    document.querySelectorAll('input[name="intType"]').forEach((radio) => {
      radio.addEventListener('change', function () {
        showModule(toInt(this.value));
      });
    });

    document.querySelectorAll('input[name="intUse"]').forEach((radio) => {
      radio.addEventListener('change', function () {
        showUse(this.value, toInt(this.getAttribute('data-max-layer')));
      });
    });

    const layerSelect = document.getElementById('intLayer');
    if (toInt(layerSelect?.value) > 1) {
      showLayer();
    }
  });
})();
<?php echo script_close(); ?>
</head>

<body<?php if (!empty($bodytxt)) {
    echo ' ' . $bodytxt;
} ?>>
    <div class="appRoot">
        <?php require_once '../_header.php'; ?>
        <div class="appBody">
            <?php require_once '../_sidebar.php'; ?>

            <main class="mainContent">
                <div class="container">
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <section class="editView">
                        <form action="addin.php" method="post" enctype="multipart/form-data"
                            name="form1" id="form1" data-manage-validate="fieldCheck0">

                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"></ul>
                            </div>
                        </div>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">基本設定</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Sort">
                                        單元順序 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="Sort" id="Sort" type="number" inputmode="numeric"
                                            min="0" step="1" class="formInput editView__sortInput"
                                            value="<?php echo (int)($Sort ?? 0); ?>" maxlength="4">
                                        <span id="Sort_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                                <?php if ($langCount <= 0) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="strName">
                                        單元名稱 <span class="inputLabel__required">*</span>
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="strName" type="text" id="strName" class="formInput"
                                            value="<?php echo e((string)($strName ?? '')); ?>" maxlength="20">
                                        <span id="strName_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="Upload">上下架</label>
                                    <div class="col--10">
                                        <select name="Upload" id="Upload" class="formSelect">
                                            <option value="Yes"<?php echo ($Upload ?? '') === 'Yes' ? ' selected' : ''; ?>>上架</option>
                                            <option value="No"<?php echo ($Upload ?? '') === 'No' ? ' selected' : ''; ?>>下架</option>
                                        </select>
                                    </div>
                                </div>
                                <?php if (!$isAdd) { ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">修改紀錄</label>
                                    <div class="col--10">
                                        <span class="dateSpan"><?php require_once '../_modify.php'; ?></span>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </article>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">單元型態與前台</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">單元型態 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10 inputGroup">
                                        <label class="formCheck">
                                            <input name="intType" type="radio" value="1"<?php echo ((int)$intType === 1) ? ' checked' : ''; ?>>
                                            功能頁面
                                        </label>
                                        <label class="formCheck">
                                            <input name="intType" type="radio" value="2"<?php echo ((int)$intType === 2) ? ' checked' : ''; ?>>
                                            美工頁面
                                        </label>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">前台單元 <span class="inputLabel__required">*</span></label>
                                    <div class="col--10 inputGroup">
                                        <label class="formCheck">
                                            <input type="radio" name="intPage" id="intPage1" value="1"<?php echo ((int)$intPage !== 2) ? ' checked' : ''; ?>>
                                            無前台頁面
                                        </label>
                                        <label class="formCheck">
                                            <input type="radio" name="intPage" id="intPage2" value="2"<?php echo ((int)$intPage === 2) ? ' checked' : ''; ?>>
                                            有前台頁面
                                        </label>
                                        <span class="inputGroup__hint">連結檔案名稱</span>
                                        <input name="PageLink" type="text" id="PageLink" class="formInput"
                                            value="<?php echo e((string)($PageLink ?? '')); ?>" placeholder="例：about.htm">
                                        <span id="PageLink_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body is-hidden" data-module-panel="program" id="modulePanelProgram">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">功能模組</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">模組選擇</label>
                                    <div class="col--10 inputGroup">
                                        <ul class="moduleProgramList">
                                        <?php
                                        $progIdx = 0;
                                        $rs = new recordset('SELECT PKey, strName, MaxLayer FROM program ORDER BY Sort');
                                        while (!$rs->eof) {
                                            $progIdx++;
                                            $pid = (int)$rs->field('PKey');
                                            $checked = ($pid === (int)$intUse) ? ' checked' : '';
                                            ?>
                                            <li class="moduleProgramList__item">
                                                <label class="formCheck">
                                                    <input type="radio" name="intUse" id="intUse<?php echo $progIdx; ?>"
                                                        value="<?php echo $pid; ?>"<?php echo $checked; ?>
                                                        data-max-layer="<?php echo (int)$rs->field('MaxLayer'); ?>">
                                                    <?php echo e((string)$rs->field('strName')); ?>
                                                </label>
                                            </li>
                                            <?php
                                            $rs->movenext();
                                        }
                                        $rs->close();
                                        ?>
                                        </ul>
                                        <input type="hidden" name="chkUse" id="chkUse" value="<?php echo (int)$intUse; ?>">
                                        <span id="chkUse_txt" class="input__errorTxt"></span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body is-hidden" data-module-panel="layer" id="modulePanelLayer">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">單元階層</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="intLayer">階層數</label>
                                    <div class="col--10 inputGroup">
                                        <select name="intLayer" id="intLayer" class="formSelect">
                                            <option value="">請選擇</option>
                                            <?php for ($lv = 2; $lv <= (int)$MaxLayer; $lv++) { ?>
                                            <option value="<?php echo $lv; ?>"<?php echo ((int)$intLayer === $lv) ? ' selected' : ''; ?>><?php echo $lv; ?></option>
                                            <?php } ?>
                                        </select>
                                        <input name="oldLayer" type="hidden" id="oldLayer" value="<?php echo (int)$intLayer; ?>">
                                        <div id="subList" class="moduleLayerList">
                                            <ul class="moduleLayerList__items">
                                            <?php
                                            if ((int)$intLayer > 1) {
                                                for ($sort = 1; $sort <= (int)$intLayer; $sort++) {
                                                    $sname = (string)($layerNames[$sort] ?? '');
                                                    ?>
                                                <li class="moduleLayerList__item">
                                                    <label class="inputLabel" for="subName<?php echo $sort; ?>">第 <?php echo $sort; ?> 階名稱</label>
                                                    <input type="text" name="subName<?php echo $sort; ?>"
                                                        id="subName<?php echo $sort; ?>" class="formInput"
                                                        value="<?php echo e($sname); ?>" maxlength="20">
                                                    <span id="subName<?php echo $sort; ?>_txt" class="input__errorTxt"></span>
                                                </li>
                                                    <?php
                                                }
                                            }
                                            ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <?php if ($langCount > 0) { ?>
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
                                            單元名稱<?php echo $i === 1 ? ' <span class="inputLabel__required">*</span>' : ''; ?>
                                        </label>
                                        <div class="col--10 inputGroup">
                                            <input name="strName<?php echo $i; ?>" type="text" id="strName<?php echo $i; ?>"
                                                class="formInput" maxlength="20"
                                                value="<?php echo e((string)($langStrName[$i] ?? '')); ?>"
                                                placeholder="<?php echo $i === 1 ? '列表顯示名稱，至少填寫一個語系' : ''; ?>">
                                            <span id="strName<?php echo $i; ?>_txt" class="input__errorTxt"></span>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </article>
                        <?php } ?>

                        <input name="Module_PKey" type="hidden" id="Module_PKey" value="<?php echo (int)($Module_PKey ?? $Update_PKey ?? 0); ?>">
                        <input type="hidden" id="Copy_PKey" name="Copy_PKey" value="<?php echo (int)($copySourcePKey ?? 0); ?>">
                        <input name="PKey" type="hidden" id="PKey" value="<?php echo (int)($Update_PKey ?? $Module_PKey ?? 0); ?>">
                        <input type="hidden" name="intColum" id="intColum" value="<?php echo (int)($isColum ?? 0); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e((string)($csrf_token ?? '')); ?>">
                        <?php if ($langCount > 0) {
                            echo hiddenNumeric('Total_lang', $langCount) . PHP_EOL;
                        } ?>

                        <?php require_once '../_submit.php'; ?>
                        </form>
                    </section>

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>單元下架後，網站前台不顯示。</li>
                            <li>「功能頁面」須選擇功能模組；階層名稱依所選模組最高階層顯示（欄位名稱 subName）。</li>
                            <li>語系分頁的單元名稱寫入 module_lang；列表主檔名稱取第一個有值的語系。</li>
                            <li>儲存時會同步寫入 module_d（階層子表）與 module_lang（語系名稱）。</li>
                        </ul>
                    </section>
                    <div class="notes__spacer"></div>
                </div>
                <?php require_once '../_footer.php'; ?>
            </main>
        </div>
    </div>

    <?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
