<?php

declare(strict_types=1);



$pageName = '02';

$subPageName = '';

require('_inc.php');



$Module_PKey = frontend_module_pkey('product');

if ($Module_PKey <= 0) {

    $Module_PKey = frontend_module_pkey_for_link('product.htm');

}

frontend_module_set_config(array_merge(

    require __DIR__ . '/manage/product/_config.php',

    [

        'view'                    => 'view_product',

        'class_link'              => 'product',

        'detail_link'             => 'product-detail',

        'publish_window'          => false,

        'class1_filter_min_count' => 2,

    ]

));



$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';

$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;



frontend_init_breadcrumb($Module_Name, $Module_Link);



$class1ItemCount = frontend_class1_count($Module_PKey);

$PKey = frontend_request_pkey($filter_array ?? []);

$detailRow = frontend_fetch_detail($Module_PKey, $PKey);



if ($detailRow === null) {

    frontend_not_found_exit(frontend_list_href());

}



$strName       = (string)crud_row_val($detailRow, 'strName');
$seoTitle      = frontend_lang_seo_title($detailRow);

$strNo         = (string)crud_row_val($detailRow, 'strNo');

$interview     = strip_tags((string)crud_row_val($detailRow, 'Interview'));

$Class1        = crud_row_int($detailRow, 'Class1_PKey');

$m_description = (string)crud_row_val($detailRow, 'Description');

$m_keywords    = (string)crud_row_val($detailRow, 'Keywords');



frontend_apply_detail_class1_breadcrumb($Class1, $class1ItemCount);

frontend_append_detail_breadcrumb($strName);



$galleryImages = frontend_fetch_product_gallery_images($PKey);

$msgTabs       = frontend_fetch_product_msg_tabs($PKey);

$ldjson        = frontend_breadcrumb_ldjson();

$backHref      = frontend_class1_list_href($Class1);



$mainImage = $galleryImages[0] ?? null;

?>



<!DOCTYPE html>

<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>

<?php require('_in_code_head.php'); ?>

<?php require('_in_javascript.php'); ?>

</head>



<body <?php if (!empty($bodytxt)) { echo $bodytxt; } ?>>

<?php require('_header.php'); ?>

<?php require('_banner.php'); ?>



<main class="pgContent">

    <section class="blockHeight blockHeight--news">

        <div class="container">

            <?php require('_pg_sidebar_open.php'); ?>

            <div class="productDBox">

                <h2 class="productD__title"><?php echo e($strName); ?></h2>

                <?php if ($strNo !== '') { ?>

                <p class="productD__no"><?php echo e($strNo); ?></p>

                <?php } ?>

                <?php if ($interview !== '') { ?>

                <p class="productD__intro"><?php echo e($interview); ?></p>

                <?php } ?>



                <?php if ($mainImage !== null) { ?>

                <div class="productGallery" data-product-gallery>

                    <figure class="productGallery__main">

                        <img src="<?php echo e_attr($mainImage['url']); ?>"

                            alt="<?php echo e_attr($mainImage['alt'] !== '' ? $mainImage['alt'] : $strName); ?>"

                            class="productGallery__mainImg" loading="eager">

                    </figure>

                    <?php if (count($galleryImages) > 1) { ?>

                    <div class="productGallery__thumbs">

                        <?php foreach ($galleryImages as $index => $image) {

                            $alt = $image['alt'] !== '' ? $image['alt'] : $strName;

                        ?>

                        <button type="button"

                            class="productGallery__thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"

                            data-gallery-index="<?php echo (int)$index; ?>"

                            data-gallery-src="<?php echo e_attr($image['url']); ?>"

                            aria-label="<?php echo e_attr('檢視圖片 ' . ($index + 1)); ?>"

                            aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>">

                            <img src="<?php echo e_attr($image['thumb']); ?>" alt="<?php echo e_attr($alt); ?>" loading="lazy">

                        </button>

                        <?php } ?>

                    </div>

                    <?php } ?>

                </div>

                <?php } ?>



                <?php if ($msgTabs !== []) { ?>

                <div class="productBookmark" data-product-bookmark>

                    <ul class="productBookmark__nav" role="tablist">

                        <?php foreach ($msgTabs as $index => $tab) { ?>

                        <li class="productBookmark__navItem" role="presentation">

                            <button type="button" class="productBookmark__tab<?php echo $index === 0 ? ' is-active' : ''; ?>"

                                role="tab"

                                id="productTabBtn-<?php echo (int)$tab['slot']; ?>"

                                aria-controls="productTabPanel-<?php echo (int)$tab['slot']; ?>"

                                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"

                                data-bookmark-tab="<?php echo (int)$tab['slot']; ?>">

                                <?php echo e($tab['title']); ?>

                            </button>

                        </li>

                        <?php } ?>

                    </ul>

                    <div class="productBookmark__panels">

                        <?php foreach ($msgTabs as $index => $tab) { ?>

                        <div class="productBookmark__panel<?php echo $index === 0 ? ' is-active' : ''; ?>"

                            role="tabpanel"

                            id="productTabPanel-<?php echo (int)$tab['slot']; ?>"

                            aria-labelledby="productTabBtn-<?php echo (int)$tab['slot']; ?>"

                            data-bookmark-panel="<?php echo (int)$tab['slot']; ?>"

                            <?php if ($index !== 0) { ?>hidden<?php } ?>>

                            <div class="productBookmark__content text">

                                <?php echo frontend_render_html($tab['html']); ?>

                            </div>

                        </div>

                        <?php } ?>

                    </div>

                </div>

                <?php } ?>

            </div>



            <div class="btnWrap btnWrap--center">

                <a href="<?php echo href_attr($backHref); ?>" class="btnStyle">

                    <span class="txt"><?php echo e($lang_text['back_str'][$this_lang] ?? '回上一頁'); ?></span>

                </a>

            </div>

            <?php require('_pg_sidebar_close.php'); ?>

        </div>

    </section>

</main>



<?php require('_footer.php'); ?>

<?php echo script_src_tag($web_url . 'js/product-detail.js?ver=' . filemtime(__DIR__ . '/js/product-detail.js')); ?>

<?php require('_in_code_bottom.php'); ?>

</body>

</html>


