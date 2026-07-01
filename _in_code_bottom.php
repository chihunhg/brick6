<?php echo script_src_tag($web_root . 'js/lozad.min.js', ['defer' => true]); ?>
<?php echo script_src_tag($web_root . 'js/script.js?ver=' . filemtime(__DIR__ . '/js/script.js')); ?>

<link href="<?php echo $web_root; ?>js/wow/animate.css?ver=<?php echo filemtime(__DIR__ . '/js/wow/animate.css'); ?>" rel="stylesheet">
<?php echo script_src_tag($web_root . 'js/wow/wow.min.js'); ?>

<!-- <script src="<?php echo$web_root?>js/progressive-a11y.js"></script>漸進式無障礙系統 - 接案型專案範本 -->
<!-- <script src="<?php echo$web_root?>js/accessibility-manager.js"></script>可選：完整無障礙功能（只有無障礙專案才需要） -->
<?php echo script_src_tag($web_root . 'js/form-validation.js?ver=' . filemtime(__DIR__ . '/js/form-validation.js')); ?>
<?php echo script_src_tag($web_root . 'js/default.js?ver=' . filemtime(__DIR__ . '/js/default.js')); ?>
<?php echo script_src_tag($web_root . 'js/header.js?ver=' . filemtime(__DIR__ . '/js/header.js')); ?>
<?php echo script_src_tag($web_root . 'js/sidebar.js?ver=' . filemtime(__DIR__ . '/js/sidebar.js')); ?>
<?php echo script_src_tag($web_root . 'js/page-function.js?ver=' . filemtime(__DIR__ . '/js/page-function.js')); ?>

<?php
$visitPageLink = frontend_visit_normalize_page_link(
    (string)($GLOBALS['REQUEST_URI_PATH'] ?? $page_link ?? '')
);
$visitModulePKey = (int)($Module_PKey ?? 0);
$visitModulePKey = frontend_visit_resolve_module_pkey($visitModulePKey, $visitPageLink);
$visitLogEnabled = frontend_visit_log_enabled() && !frontend_visit_is_crawler();
if ($visitLogEnabled) {
    $visitLogResult = frontend_visit_log_insert($visitModulePKey, $visitPageLink);
    if (!$visitLogResult['success'] && empty($visitLogResult['skipped'])) {
        error_log('[frontend_visit_log] page insert failed: '
            . (string)($visitLogResult['error'] ?? 'unknown')
            . ' link=' . $visitPageLink);
    }
}
?>
<?php if ($visitLogEnabled) { ?>
<div id="frontend-visit-log" class="d-none" aria-hidden="true"
    data-module-pkey="<?php echo (int)$visitModulePKey; ?>"
    data-page-link="<?php echo e_attr($visitPageLink); ?>"
    data-log-url="<?php echo e_attr($web_url . 'frontend-visit-log.php'); ?>"></div>
<?php echo script_src_tag($web_root . 'js/frontend-visit-log.js?ver=' . filemtime(__DIR__ . '/js/frontend-visit-log.js')); ?>
<?php } ?>

<!-- Lenis -->
<?php echo script_src_tag($web_root . 'js/lenis.min.js?ver=' . filemtime(__DIR__ . '/js/lenis.min.js')); ?>

<!--splitting.js-->
<?php echo script_src_tag($web_root . 'js/splitting/dist/splitting.min.js?ver=' . filemtime(__DIR__ . '/js/splitting/dist/splitting.min.js')); ?>

<div class="load-wrapp" id="Submit_Close" style="display:none;">
    <div class="loading">
        <div class="spinner">
            <div class="bubble-1"></div>
            <div class="bubble-2"></div>
        </div>
        <span>表單送出中，請稍候</span>
    </div>
</div>
