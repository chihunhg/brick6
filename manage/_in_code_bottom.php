<?php
$__js_ver = static function (string $file): string {
    $path = __DIR__ . '/js/' . ltrim($file, '/');
    return is_file($path) ? (string)filemtime($path) : '1';
};
?>
<?php echo script_src_tag('../js/lozad.min.js', ['defer' => true]); ?>
<?php echo script_src_tag('../js/script.js?ver=' . $__js_ver('script.js')); ?>
<?php echo script_src_tag('../js/file_preview.js?ver=' . $__js_ver('file_preview.js')); ?>
<?php echo script_src_tag('../js/filesize.js?ver=' . $__js_ver('filesize.js')); ?>

<link href="../js/wow/animate.css?ver=<?php echo filemtime('../js/wow/animate.css') ?>" rel="stylesheet">
<?php echo script_src_tag('../js/wow/wow.min.js'); ?>

<?php echo script_src_tag('../js/progressive-a11y.js'); ?>
<?php echo script_src_tag('../js/accessibility-manager.js'); ?>
<?php echo script_src_tag('../js/default.js?ver=' . $__js_ver('default.js')); ?>
<?php echo script_src_tag('../js/header.js?ver=' . $__js_ver('header.js')); ?>
<?php echo script_src_tag('../js/sidebar.js?ver=' . $__js_ver('sidebar.js')); ?>
<?php echo script_src_tag('../js/page-function.js?ver=' . $__js_ver('page-function.js')); ?>
<?php echo script_src_tag('../js/popup.js?ver=' . $__js_ver('popup.js')); ?>
<?php echo script_src_tag('../js/tooltip.js?ver=' . $__js_ver('tooltip.js')); ?>
<?php echo script_src_tag('../js/manage-csp.js?ver=' . $__js_ver('manage-csp.js')); ?>
<!-- 載入檢查欄位長度 -->
<?php echo script_src_tag('../js/jquery.maxlength.js?ver=' . $__js_ver('jquery.maxlength.js')); ?>
<!-- 動態載入下拉選單 -->
<?php echo script_src_tag('../js/ajax.js?ver=' . $__js_ver('ajax.js')); ?>
<!-- 載入html 編輯器 -->
<?php
$__ck_path = __DIR__ . '/ckeditor/ckeditor.js';
$__ck_ver  = is_file($__ck_path) ? (string)filemtime($__ck_path) : '1';
echo script_src_tag('../ckeditor/ckeditor.js?ver=' . $__ck_ver);
?>
<!-- 載入檔案Size檢查（jquery-browser；filesize 見上方 script_src_tag） -->
<?php echo script_src_tag('../js/jquery-browser.js?ver=' . $__js_ver('jquery-browser.js')); ?>
<!--禁用文字方塊Enter鍵送出表單-->
<?php echo script_open(); ?>
window.addEventListener('keydown', function (e) {
    if (e.keyIdentifier == 'U+000A' || e.keyIdentifier == 'Enter' || e.keyCode == 13) {
        if (e.target.nodeName == 'INPUT' && e.target.id != 'Keywords' && e.target.type == 'text') {
            e.preventDefault();
            return false;
        }
    }
}, true);
<?php echo script_close(); ?>

<!-- 載入文字方塊下拉選單 -->
<?php
$__ui_path = __DIR__ . '/js/jquery-ui-1.14.1/jquery-ui.js';
$__ui_ver  = is_file($__ui_path) ? (string)filemtime($__ui_path) : '1';
echo script_src_tag('../js/jquery-ui-1.14.1/jquery-ui.js?ver=' . $__ui_ver);
?>
<?php
$__layout_web_root = (string)($web_root ?? $GLOBALS['web_root'] ?? (defined('APP_WEB_ROOT') ? APP_WEB_ROOT : '/'));
?>
<link rel="stylesheet" href="<?php echo e($__layout_web_root); ?>manage/js/jquery-ui-1.14.1/jquery-ui.css">
