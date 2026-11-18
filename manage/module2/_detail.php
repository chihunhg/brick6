<?php
declare(strict_types=1);

$isAdd = stripos((string)($WorkFile ?? ''), 'add') !== false;
$strName = (string)($strName ?? '');
$SeoTitle = $SeoTitle ?? [];
$Description = $Description ?? [];
$Keywords = $Keywords ?? [];
$Question = $Question ?? [];
$Answer = $Answer ?? [];
$qaSlotMax = function_exists('module_qa_slot_max') ? module_qa_slot_max() : 10;

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

  let submitLock = false;

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

    if (errors.length) {
      return window.manageFormValidationFail(errors, {
        focusField: fields[0],
        form: theForm || document.getElementById('form1')
      });
    }
    return window.manageFormValidationOk(theForm || document.getElementById('form1'));
  };
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
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="strName">
                                        單元名稱
                                    </label>
                                    <div class="col--10 inputGroup">
                                        <input name="strName" type="text" id="strName" class="formInput"
                                            value="<?php echo e($strName); ?>" maxlength="20" readonly>
                                        <span class="inputGroup__hint">單元名稱請至「單元設定」修改，本頁不可變更。</span>
                                    </div>
                                </div>
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
                                <h4 class="editView__sectionTitle">SEO / GEO 與 FAQ</h4>
                                <?php
                                $i = 1;
                                $seoFieldsEnableAi = false;
                                require dirname(__DIR__) . '/_detail_lang_seo_fields.php';
                                require dirname(__DIR__) . '/_detail_module_qa_fields.php';
                                ?>
                            </div>
                        </article>

                        <input name="Module_PKey" type="hidden" id="Module_PKey" value="<?php echo (int)($Module_PKey ?? $Update_PKey ?? 0); ?>">
                        <input type="hidden" id="Copy_PKey" name="Copy_PKey" value="<?php echo (int)($copySourcePKey ?? 0); ?>">
                        <input name="PKey" type="hidden" id="PKey" value="<?php echo (int)($Update_PKey ?? $Module_PKey ?? 0); ?>">
                        <input type="hidden" name="intType" id="intType" value="2">
                        <input type="hidden" name="csrf_token" value="<?php echo e((string)($csrf_token ?? '')); ?>">

                        <?php require_once '../_submit.php'; ?>
                        </form>
                    </section>

                    <section class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>本頁僅為既有美工頁單元補上 SEO/GEO（TDK）與 FAQ，不必選擇功能模組。</li>
                            <li>單元名稱唯讀，請至「單元設定」修改。</li>
                            <li>單元下架後，網站前台不顯示。</li>
                            <li>SEO/GEO 與 FAQ 為單語系欄位，送出時會同步寫入 module_lang、module_qa 各語系列。</li>
                            <li>SEO/GEO 標題、內文、關鍵字供前台 meta 使用；FAQ 最多 <?php echo (int)$qaSlotMax; ?> 組問答。</li>
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
