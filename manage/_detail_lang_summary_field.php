<?php
declare(strict_types=1);
/**
 * 語系 AI 搜尋核心摘要欄位（*_lang.Summary）
 * 需由父層提供迴圈變數 $i
 */
$summaryLangSlot = (int)($i ?? 0);
if ($summaryLangSlot <= 0) {
    return;
}
$Summary = is_array($Summary ?? null) ? $Summary : [];

static $manageSummaryAiAssetsLoaded = false;
if (!$manageSummaryAiAssetsLoaded) {
    $manageSummaryAiAssetsLoaded = true;
    $__geminiSseJs = __DIR__ . '/js/gemini-sse-client.js';
    $__geminiSseJsVer = is_file($__geminiSseJs) ? (string)filemtime($__geminiSseJs) : '1';
    $__summaryAiJs = __DIR__ . '/js/summary-ai.js';
    $__summaryAiJsVer = is_file($__summaryAiJs) ? (string)filemtime($__summaryAiJs) : '1';
    echo script_src_tag('../js/gemini-sse-client.js?ver=' . $__geminiSseJsVer);
    echo script_src_tag('../js/summary-ai.js?ver=' . $__summaryAiJsVer);
}

global $array_lang;
$summaryLangLabel = trim((string)($array_lang[$summaryLangSlot] ?? ''));
?>
                                    <div class="formGrid">
                                        <label class="col--2 inputLabel editView__formLabel" for="Summary<?php echo $summaryLangSlot; ?>">
                                            AI搜尋核心摘要
                                        </label>
                                        <div class="col--10">
                                            <div class="flex flex-wrap items-center gap--2 mb-2">
                                                <button type="button"
                                                    class="btnStyle btnStyle--outline btnStyle--sm"
                                                    data-manage-action="summary-ai-generate"
                                                    data-lang-slot="<?php echo $summaryLangSlot; ?>"
                                                    data-lang-label="<?php echo e($summaryLangLabel); ?>">
                                                    <i class="bi bi-stars" aria-hidden="true"></i> AI 產生搜尋核心摘要
                                                </button>
                                                <span class="text-muted" style="font-size:13px;">依標題與內容自動產生，最多 400 字</span>
                                            </div>
                                            <textarea name="Summary<?php echo $summaryLangSlot; ?>" id="Summary<?php echo $summaryLangSlot; ?>"
                                                class="formInput" style="height:100px" maxlength="400"
                                                placeholder="請輸入或點選 AI 產生搜尋核心摘要"><?php echo e((string)($Summary[$summaryLangSlot] ?? '')); ?></textarea>
                                        </div>
                                    </div>
