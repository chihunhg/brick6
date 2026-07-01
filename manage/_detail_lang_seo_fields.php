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
    $__seoTdkJs = __DIR__ . '/js/seo-tdk-ai.js';
    $__seoTdkJsVer = is_file($__seoTdkJs) ? (string)filemtime($__seoTdkJs) : '1';
    echo script_src_tag('../js/seo-tdk-ai.js?ver=' . $__seoTdkJsVer);
}
?>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel">SEO 工具</label>
                                        <div class="col--10">
                                            <button type="button"
                                                class="btnStyle btnStyle--outline btnStyle--sm"
                                                data-manage-action="seo-tdk-generate"
                                                data-lang-slot="<?php echo $seoLangSlot; ?>">
                                                <i class="bi bi-stars" aria-hidden="true"></i> AI 產生 TDK
                                            </button>
                                            <span class="text-muted ms-2" style="font-size:13px;">建議先填寫標題與下方內容區，再依同語系資料自動產生 SEO 欄位</span>
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
