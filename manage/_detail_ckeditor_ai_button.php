<?php

declare(strict_types=1);

/**

 * CKEditor「AI 產生內容」按鈕

 *

 * 父層需提供：

 * - $editorAiFieldId：目標 textarea / CKEditor 的 id（例：Contents1_2）

 * - $editorAiIndustry：（可選）預設產業別

 * - $editorAiFormatMode：（可選）auto | prose | table | list

 * - $editorAiSourceUrl：（可選）預設參考網址；未設定時由使用者輸入

 * - $editorAiLabel：（可選）按鈕旁說明文字

 */

$editorAiFieldId = trim((string)($editorAiFieldId ?? ''));

if ($editorAiFieldId === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_\-]*$/', $editorAiFieldId)) {

    return;

}



if (!function_exists('gemini_normalize_industry')) {

    require_once dirname(__DIR__) . '/include/gemini_editor_helpers.php';

}



$editorAiIndustry = gemini_normalize_industry((string)($editorAiIndustry ?? 'general'));

$editorAiFormatMode = gemini_normalize_format_mode((string)($editorAiFormatMode ?? 'auto'));

$editorAiSourceUrl = trim((string)($editorAiSourceUrl ?? ''));

$editorAiLabel = trim((string)($editorAiLabel ?? '可選填參考網址；可選排版格式'));

$editorAiIndustrySelectId = 'editorAiIndustry_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $editorAiFieldId);

$editorAiFormatSelectId = 'editorAiFormat_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $editorAiFieldId);

$editorAiIndustryOptions = gemini_industry_options();

$editorAiFormatOptions = gemini_format_mode_options();



static $manageEditorAiAssetsLoaded = false;

if (!$manageEditorAiAssetsLoaded) {

    $manageEditorAiAssetsLoaded = true;

    $__editorAiJs = __DIR__ . '/js/editor-ai.js';

    $__editorAiJsVer = is_file($__editorAiJs) ? (string)filemtime($__editorAiJs) : '1';

    echo script_src_tag('../js/editor-ai.js?ver=' . $__editorAiJsVer);

}

?>

                                            <div class="mb-2 editor-ai-toolbar flex flex-wrap items-center gap--2">

                                                <label class="text-muted mb-0" style="font-size:13px;"

                                                    for="<?php echo e($editorAiIndustrySelectId); ?>">產業別</label>

                                                <select id="<?php echo e($editorAiIndustrySelectId); ?>"

                                                    class="formSelect"

                                                    style="width:auto; min-width:7.5rem;"

                                                    data-editor-ai-industry-select>

                                                    <?php foreach ($editorAiIndustryOptions as $industryValue => $industryLabel) { ?>

                                                    <option value="<?php echo e($industryValue); ?>"

                                                        <?php echo $editorAiIndustry === $industryValue ? 'selected' : ''; ?>>

                                                        <?php echo e($industryLabel); ?>

                                                    </option>

                                                    <?php } ?>

                                                </select>

                                                <label class="text-muted mb-0" style="font-size:13px;"

                                                    for="<?php echo e($editorAiFormatSelectId); ?>">排版</label>

                                                <select id="<?php echo e($editorAiFormatSelectId); ?>"

                                                    class="formSelect"

                                                    style="width:auto; min-width:7.5rem;"

                                                    data-editor-ai-format-select>

                                                    <?php foreach ($editorAiFormatOptions as $formatValue => $formatLabel) { ?>

                                                    <option value="<?php echo e($formatValue); ?>"

                                                        <?php echo $editorAiFormatMode === $formatValue ? 'selected' : ''; ?>>

                                                        <?php echo e($formatLabel); ?>

                                                    </option>

                                                    <?php } ?>

                                                </select>

                                                <button type="button"

                                                    class="btnStyle btnStyle--outline btnStyle--sm"

                                                    data-manage-action="editor-ai-generate"

                                                    data-editor-target="<?php echo e($editorAiFieldId); ?>"

                                                    <?php if ($editorAiSourceUrl !== '') { ?>

                                                    data-editor-source-url="<?php echo e($editorAiSourceUrl); ?>"

                                                    <?php } ?>>

                                                    <i class="bi bi-magic" aria-hidden="true"></i> AI 產生內容

                                                </button>

                                                <span class="text-muted" style="font-size:13px;"><?php echo e($editorAiLabel); ?></span>

                                            </div>

