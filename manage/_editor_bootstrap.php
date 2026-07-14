<?php
declare(strict_types=1);
/**
 * 後台富文本引擎設定（CKEditor 4 / Summernote PoC）
 *
 * .env:
 *   MANAGE_EDITOR=ckeditor|summernote   （全站預設，預設 ckeditor）
 *   MANAGE_EDITOR_POC=faq               （僅 FAQ 模組改用 summernote）
 */

if (!function_exists('manage_editor_env')) {
    function manage_editor_env(string $key, string $default = ''): string {
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? $default;
        }
        return trim((string)$v);
    }
}

if (!function_exists('manage_editor_is_faq_script')) {
    function manage_editor_is_faq_script(): bool {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        return (bool)preg_match('#/manage/faq(/|$)#i', $script . ' ' . $uri);
    }
}

if (!function_exists('manage_editor_engine')) {
    /** @return 'ckeditor'|'summernote' */
    function manage_editor_engine(): string {
        $engine = strtolower(manage_editor_env('MANAGE_EDITOR', 'ckeditor'));
        $poc = strtolower(manage_editor_env('MANAGE_EDITOR_POC', ''));
        if ($poc === 'faq' && manage_editor_is_faq_script()) {
            return 'summernote';
        }
        if ($engine === 'summernote') {
            return 'summernote';
        }
        return 'ckeditor';
    }
}

if (!function_exists('manage_editor_render_assets')) {
    /**
     * 輸出編輯器 JS／CSS／初始化設定（供 _in_code_bottom / _ckeditor.php）
     */
    function manage_editor_render_assets(): void {
        if (!empty($GLOBALS['manage_editor_assets_rendered'])) {
            return;
        }
        $GLOBALS['manage_editor_assets_rendered'] = true;

        $engine = manage_editor_engine();
        $elfinderUrl = '../elFinder/elfinder_cke.html';
        $meJs = __DIR__ . '/js/manage-editor.js';
        $meVer = is_file($meJs) ? (string)filemtime($meJs) : '1';

        if ($engine === 'summernote') {
            echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" crossorigin="anonymous">' . "\n";
        } else {
            $ckeJs = __DIR__ . '/ckeditor/ckeditor.js';
            $ckeVer = is_file($ckeJs) ? (string)filemtime($ckeJs) : '1';
            // _in_code_bottom 可能已載入 ckeditor.js；有則略過重複
            if (empty($GLOBALS['manage_ckeditor_js_loaded'])) {
                echo script_src_tag('../ckeditor/ckeditor.js?ver=' . $ckeVer);
                $GLOBALS['manage_ckeditor_js_loaded'] = true;
            }
        }

        $cfgJson = json_encode(
            [
                'engine'      => $engine,
                'elfinderUrl' => $elfinderUrl,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        echo manage_inline_script('window.MANAGE_EDITOR_CONFIG = ' . $cfgJson . ';');

        if ($engine === 'summernote') {
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js" crossorigin="anonymous"></script>' . "\n";
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/lang/summernote-zh-TW.min.js" crossorigin="anonymous"></script>' . "\n";
        }

        echo script_src_tag('../js/manage-editor.js?ver=' . $meVer);
    }
}
