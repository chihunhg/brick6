<?php

declare(strict_types=1);

$pageName = '03';

$subPageName = '';

require('_inc.php');

$Module_PKey = frontend_module_pkey('album');

frontend_module_set_config(array_merge(

    require __DIR__ . '/manage/album/_config.php',

    [

        'view'                    => 'view_album',

        'class_link'              => 'album',

        'detail_link'             => 'album-detail',

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

$interview     = strip_tags((string)crud_row_val($detailRow, 'Interview'));

$Class1        = crud_row_int($detailRow, 'Class1_PKey');

$m_description = (string)crud_row_val($detailRow, 'Description');

$m_keywords    = (string)crud_row_val($detailRow, 'Keywords');

frontend_apply_detail_class1_breadcrumb($Class1, $class1ItemCount);

frontend_append_detail_breadcrumb($strName);

$galleryItems = frontend_fetch_album_gallery_items($PKey);

$ldjson = frontend_breadcrumb_ldjson();

$backHref = frontend_class1_list_href($Class1);

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

            <div class="newsDBox">

                <h2 class="albumDetail__title"><?php echo e($strName); ?></h2>

                <?php if ($interview !== '') { ?>

                <p class="albumDetail__intro"><?php echo e($interview); ?></p>

                <?php } ?>

                <?php if ($galleryItems === []) { ?>

                <p class="albumEmpty"><?php echo e($lang_text['no_data_str'][$this_lang] ?? '資料建置中'); ?></p>

                <?php } else { ?>

                <div class="albumGallery">

                    <?php foreach ($galleryItems as $index => $item) {

                        $alt = $item['caption'] !== '' ? $item['caption'] : $strName;

                    ?>

                    <button type="button" class="albumGallery__item" data-album-index="<?php echo (int)$index; ?>"

                        aria-label="<?php echo e_attr('查看大圖：' . $alt); ?>">

                        <img src="<?php echo e_attr($item['thumb']); ?>" alt="<?php echo e_attr($alt); ?>" loading="lazy">

                        <?php if ($item['caption'] !== '') { ?>

                        <span class="albumGallery__caption"><?php echo e($item['caption']); ?></span>

                        <?php } ?>

                    </button>

                    <?php } ?>

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

<?php if ($galleryItems !== []) { ?>

<div class="albumViewer" id="albumViewer" aria-hidden="true" role="dialog" aria-modal="true"

    aria-label="<?php echo e_attr($strName); ?>">

    <div class="albumViewer__backdrop" data-album-close></div>

    <button type="button" class="albumViewer__close" data-album-close aria-label="關閉">×</button>

    <div class="swiper albumViewer__swiper">

        <div class="swiper-wrapper">

            <?php foreach ($galleryItems as $item) {

                $alt = $item['caption'] !== '' ? $item['caption'] : $strName;

            ?>

            <div class="swiper-slide">

                <figure class="albumViewer__figure">

                    <img src="<?php echo e_attr($item['full']); ?>" alt="<?php echo e_attr($alt); ?>"

                        class="albumViewer__img" loading="lazy">

                    <?php if ($item['caption'] !== '') { ?>

                    <figcaption class="albumViewer__caption"><?php echo e($item['caption']); ?></figcaption>

                    <?php } ?>

                </figure>

            </div>

            <?php } ?>

        </div>

        <div class="swiper-button">

            <div class="swiper-button-prev" aria-label="上一張"><span class="txt">Prev</span></div>

            <div class="swiper-button-next" aria-label="下一張"><span class="txt">Next</span></div>

        </div>

        <div class="swiper-pagination"></div>

    </div>

</div>

<?php } ?>

<?php require('_footer.php'); ?>

<?php if ($galleryItems !== []) {

    echo script_src_tag($web_url . 'js/album-detail.js?ver=' . filemtime(__DIR__ . '/js/album-detail.js'));

} ?>

<?php require('_in_code_bottom.php'); ?>

</body>

</html>
