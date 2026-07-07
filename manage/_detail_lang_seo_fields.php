<?php
declare(strict_types=1);
/**
 * 語系 SEO 欄位（*_lang.Title / Description / Keywords）
 * 需由父層提供迴圈變數 $i
 */
$seoLangSlot = (int)($i ?? 0);
if ($seoLangSlot <= 0) {
    return;
}
$SeoTitle = is_array($SeoTitle ?? null) ? $SeoTitle : [];
$Description = is_array($Description ?? null) ? $Description : [];
$Keywords = is_array($Keywords ?? null) ? $Keywords : [];
$seoDescPlaceholder = (string)($seoDescPlaceholder ?? '請輸入160字元內的網站描述');

static $manageSeoTdkAiAssetsLoaded = false;
if (!$manageSeoTdkAiAssetsLoaded) {
    $manageSeoTdkAiAssetsLoaded = true;
    if (!function_exists('gemini_normalize_industry')) {
        require_once dirname(__DIR__) . '/include/gemini_editor_helpers.php';
    }
    $__seoTdkJs = __DIR__ . '/js/seo-tdk-ai.js';
    $__seoTdkJsVer = is_file($__seoTdkJs) ? (string)filemtime($__seoTdkJs) : '1';
    $__contentTdkJs = __DIR__ . '/js/content-tdk-ai.js';
    $__contentTdkJsVer = is_file($__contentTdkJs) ? (string)filemtime($__contentTdkJs) : '1';
    $__geminiSseJs = __DIR__ . '/js/gemini-sse-client.js';
    $__geminiSseJsVer = is_file($__geminiSseJs) ? (string)filemtime($__geminiSseJs) : '1';
    echo script_src_tag('../js/gemini-sse-client.js?ver=' . $__geminiSseJsVer);
    echo script_src_tag('../js/seo-tdk-ai.js?ver=' . $__seoTdkJsVer);
    echo script_src_tag('../js/content-tdk-ai.js?ver=' . $__contentTdkJsVer);
}

$tdkAiIndustry = gemini_normalize_industry((string)($tdkAiIndustry ?? $editorAiIndustry ?? 'general'));
$tdkAiIndustryOptions = gemini_industry_options();
$tdkAiFormatMode = gemini_normalize_format_mode((string)($tdkAiFormatMode ?? 'auto'));
$tdkAiFormatOptions = gemini_format_mode_options();
$combinedEditorTarget = 'Contents1_' . $seoLangSlot;
global $array_lang;
$seoLangLabel = trim((string)($array_lang[$seoLangSlot] ?? ''));

static $manageSeoTdkIndustrySelectRendered = false;
$showTdkIndustrySelect = !$manageSeoTdkIndustrySelectRendered;
if ($showTdkIndustrySelect) {
    $manageSeoTdkIndustrySelectRendered = true;
}
?>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel">SEO 工具</label>
                                        <div class="col--10 flex flex-wrap items-center gap--2">
                                            <?php if ($showTdkIndustrySelect) { ?>
                                            <label class="text-muted mb-0" style="font-size:13px;" for="seoTdkIndustry">產業別</label>
                                            <select id="seoTdkIndustry"
                                                class="formInput"
                                                style="width:auto; min-width:8rem;"
                                                data-seo-tdk-industry-select>
                                                <?php foreach ($tdkAiIndustryOptions as $industryValue => $industryLabel) { ?>
                                                <option value="<?php echo e($industryValue); ?>"
                                                    <?php echo $tdkAiIndustry === $industryValue ? 'selected' : ''; ?>>
                                                    <?php echo e($industryLabel); ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                            <label class="text-muted mb-0" style="font-size:13px;" for="contentTdkFormat">內文排版</label>
                                            <select id="contentTdkFormat"
                                                class="formInput"
                                                style="width:auto; min-width:8rem;"
                                                data-content-tdk-format-select>
                                                <?php foreach ($tdkAiFormatOptions as $formatValue => $formatLabel) { ?>
                                                <option value="<?php echo e($formatValue); ?>"
                                                    <?php echo $tdkAiFormatMode === $formatValue ? 'selected' : ''; ?>>
                                                    <?php echo e($formatLabel); ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                            <?php } ?>
                                            <button type="button"
                                                class="btnStyle btnStyle--outline btnStyle--sm"
                                                data-manage-action="content-tdk-generate"
                                                data-lang-slot="<?php echo $seoLangSlot; ?>"
                                                data-lang-label="<?php echo e($seoLangLabel); ?>"
                                                data-editor-target="<?php echo e($combinedEditorTarget); ?>">
                                                <i class="bi bi-lightning-charge" aria-hidden="true"></i> AI 同步產生 TDK 與內文
                                            </button>
                                            <button type="button"
                                                class="btnStyle btnStyle--outline btnStyle--sm"
                                                data-manage-action="seo-tdk-generate"
                                                data-lang-slot="<?php echo $seoLangSlot; ?>"
                                                data-lang-label="<?php echo e($seoLangLabel); ?>"
                                                data-seo-tdk-industry="<?php echo e($tdkAiIndustry); ?>">
                                                <i class="bi bi-stars" aria-hidden="true"></i> AI 產生 TDK
                                            </button>
                                            <span class="text-muted" style="font-size:13px;">同步產生會填入 SEO 欄位與「內容1」；建議先填寫標題</span>
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Title<?php echo $seoLangSlot; ?>">
                                            SEO標題<?php echo manage_render_field_help('結構化使用：顯示於 <title> 標籤'); ?>
                                        </label>
                                        <div class="col--10">
                                            <input name="Title<?php echo $seoLangSlot; ?>" type="text"
                                                id="Title<?php echo $seoLangSlot; ?>" class="formInput" maxlength="255"
                                                value="<?php echo e((string)($SeoTitle[$seoLangSlot] ?? '')); ?>">
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Description<?php echo $seoLangSlot; ?>">
                                            SEO內文<?php echo manage_render_field_help('顯示於 meta 的 description。搜尋時，瀏覽器會顯示的默認文字。一段時間後，則由搜索引擎自行判斷該頁面重要的內文予以顯示；屆時此欄位的權重會降低。'); ?>
                                        </label>
                                        <div class="col--10">
                                            <textarea name="Description<?php echo $seoLangSlot; ?>" id="Description<?php echo $seoLangSlot; ?>"
                                                class="formInput" style="height:100px"
                                                placeholder="<?php echo e($seoDescPlaceholder); ?>"><?php echo e((string)($Description[$seoLangSlot] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel">SEO關鍵字<?php echo manage_render_field_help('顯示於 meta 的 keywords。搜索引擎以公布降低此權重；改以實際內容之文案為主。'); ?></label>
                                        <div class="col--10 flex gap--2">
                                            <?php for ($n = 0; $n < 5; $n++) {
                                                $kwName = 'Keyword' . ($n + 1) . '_' . $seoLangSlot;
                                                ?>
                                            <input name="<?php echo e($kwName); ?>" type="text"
                                                id="<?php echo e($kwName); ?>" class="formInput"
                                                style="width:19%" maxlength="20"
                                                value="<?php echo e((string)($Keywords[$n][$seoLangSlot] ?? '')); ?>">
                                            <?php } ?>
                                        </div>
                                    </div>
