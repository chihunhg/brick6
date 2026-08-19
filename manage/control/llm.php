<?php
declare(strict_types=1);
/**
 * 網站LLM設定 基本設定
 */

require_once '../_inc.php';
require_once '../_module.php';

$llmConfig = require __DIR__ . '/llm_config.php';
$csrfKey   = (string)($llmConfig['csrf'] ?? 'llm_form');

$subitem = 's1';
$Module_Name = $Module_Name ?? '網站LLM設定';

$returnUrl = (string)($WorkFile ?? 'llm.php');
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

    $MSG = crud_validate_llm_text_from_filter($filter_array ?? []);
    if ($MSG !== '') {
        crud_form_error_redirect($MSG, $returnUrl);
    }

    try {
        crud_save_llm_text_files($filter_array ?? []);
        manage_alert_script('修改成功!', $returnUrl);
    } catch (Throwable $e) {
        if (function_exists('sql_error')) {
            sql_error(
                '',
                $e->getMessage(),
                $_SERVER['PHP_SELF'] ?? 'llm.php',
                (string)($_SESSION['Login_ID'] ?? 'system'),
                $e->getFile(),
                $e->getLine()
            );
        }
        crud_form_error_redirect('儲存失敗，請稍後再試', $returnUrl);
    }
}

$llmTexts = crud_load_llm_text_files();
$llmit = (string)($llmTexts['llmit'] ?? '');
$llms  = (string)($llmTexts['llms'] ?? '');

?>
<?php require_once '../_layout_head.php'; ?>
<meta name="csrf-token" content="<?php echo e((string)$csrf_token); ?>">
<?php echo script_open(); ?>
function fieldCheck0(theForm) {
	if (typeof loading === 'function') {
		loading(1);
	}
	var array = [];
	var errors = [];
	var view = [];

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
                                <p class="text-muted small mb-3">內容儲存於前端根目錄 <code>llmit.txt</code>、<code>llms.txt</code>，儲存後可直接由網址存取。</p>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="llmit">
                                        llmit.txt
                                        <?php echo manage_render_field_help('description：核心優勢與服務簡介，可能出現在搜尋結果描述'); ?>
                                    </label>
                                    <div class="col--10">
                                        <textarea name="llmit" id="llmit" class="formInput" rows="10" placeholder="例：30年經驗、客製化服務、資安處理…"><?php echo e($llmit); ?></textarea>
                                    </div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel" for="llms">
                                        llms.txt
                                        <?php echo manage_render_field_help('description：核心優勢與服務簡介，可能出現在搜尋結果描述'); ?>
                                    </label>
                                    <div class="col--10">
                                        <textarea name="llms" id="llms" class="formInput" rows="10" placeholder="例：30年經驗、客製化服務、資安處理…"><?php echo e($llms); ?></textarea>
                                    </div>
                                </div>
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
                    <div class="notes__spacer"></div>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
