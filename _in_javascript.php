<?php
/**
 * head 內 JS 與 CSS 共用引入（jQuery、站內腳本與樣式）
 */
echo script_src_tag($web_root . 'js/jquery-3.7.1.min.js'); ?>

<?php echo script_src_tag($web_root . 'js/popup.js?ver=' . filemtime(__DIR__ . '/js/popup.js')); ?>

<link href="<?php echo $web_root; ?>css/reset.css?ver=<?php echo filemtime(__DIR__ . '/css/reset.css') ?>" rel="stylesheet" charset="utf-8">
<link href="<?php echo $web_root; ?>css/bs-iconSite.css?ver=<?php echo filemtime(__DIR__ . '/css/bs-iconSite.css') ?>" rel="stylesheet">
<link href="<?php echo $web_root; ?>css/bs-site.css?ver=<?php echo filemtime(__DIR__ . '/css/bs-site.css') ?>" rel="stylesheet">

<!--swiper輪播-->
<link rel="stylesheet" href="<?php echo $web_root; ?>js/swiper/swiper-bundle.min.css">
<?php echo script_src_tag($web_root . 'js/swiper/swiper-bundle.min.js'); ?>

<!-- splitting 文字動畫 -->
<link rel="stylesheet" href="<?php echo $web_root; ?>js/splitting/dist/splitting.css">
<link rel="stylesheet" href="<?php echo $web_root; ?>js/splitting/dist/splitting-cells.css">

<link href="<?php echo $web_root; ?>css/style.css?ver=<?php echo filemtime(__DIR__ . '/css/style.css') ?>" rel="stylesheet" charset="utf-8">
<link href="<?php echo $web_root; ?>css/fonts.css?ver=<?php echo filemtime(__DIR__ . '/css/fonts.css') ?>" rel="stylesheet" charset="utf-8">

<?php require("_in_ga_gtm1.php"); ?>
