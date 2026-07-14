<?php
declare(strict_types=1);
/**
 * 富文本編輯器載入入口（相容舊知識庫等 require _ckeditor.php 的頁面）
 * 實際引擎由 MANAGE_EDITOR / MANAGE_EDITOR_POC 決定。
 */
require_once __DIR__ . '/_editor_bootstrap.php';

if (manage_editor_engine() === 'ckeditor') {
    $ckeCfg = __DIR__ . '/ckeditor/config.js';
    $cfgVer = is_file($ckeCfg) ? (string)filemtime($ckeCfg) : '1';
    if (empty($GLOBALS['manage_ckeditor_js_loaded'])) {
        $ckeJs = __DIR__ . '/ckeditor/ckeditor.js';
        $ckeVer = is_file($ckeJs) ? (string)filemtime($ckeJs) : '1';
        echo script_src_tag('ckeditor/ckeditor.js?ver=' . $ckeVer);
        $GLOBALS['manage_ckeditor_js_loaded'] = true;
    }
    echo script_src_tag('ckeditor/config.js?ver=' . $cfgVer);
}

manage_editor_render_assets();
