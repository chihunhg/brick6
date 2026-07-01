<?php echo script_src_tag($web_url . 'js/jquery-3.7.1.min.js'); ?>

<?php echo script_src_tag($web_url . 'js/popup.js?ver=' . filemtime(__DIR__ . '/js/popup.js')); ?>

<link href="<?php echo $web_url; ?>css/reset.css?ver=<?php echo filemtime(__DIR__ . '/css/reset.css') ?>" rel="stylesheet" charset="utf-8">
<link href="<?php echo $web_url; ?>css/bs-iconSite.css?ver=<?php echo filemtime(__DIR__ . '/css/bs-iconSite.css') ?>" rel="stylesheet">
<link href="<?php echo $web_url; ?>css/bs-site.css?ver=<?php echo filemtime(__DIR__ . '/css/bs-site.css') ?>" rel="stylesheet">

<!--swiper輪播-->
<link rel="stylesheet" href="<?php echo $web_url; ?>js/swiper/swiper-bundle.min.css">
<?php echo script_src_tag($web_url . 'js/swiper/swiper-bundle.min.js'); ?>

<!-- splitting 文字動畫 -->
<link rel="stylesheet" href="<?php echo $web_url; ?>js/splitting/dist/splitting.css">
<link rel="stylesheet" href="<?php echo $web_url; ?>js/splitting/dist/splitting-cells.css">


<link href="<?php echo $web_url; ?>css/style.css?ver=<?php echo filemtime(__DIR__ . '/css/style.css') ?>" rel="stylesheet" charset="utf-8">
<link href="<?php echo $web_url; ?>css/fonts.css?ver=<?php echo filemtime(__DIR__ . '/css/fonts.css') ?>" rel="stylesheet" charset="utf-8">

<?php require("_in_ga_gtm1.php"); ?>
