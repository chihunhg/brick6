<?php
declare(strict_types=1);

$pageName = 'p1';
$subPageName = '';
require('_inc.php');

$Module_PKey = frontend_module_pkey('company');
frontend_module_set_config(array_merge(
    require __DIR__ . '/manage/company/_config.php',
    [
        'view'                    => 'view_company',
        'class_link'              => 'about',
        'detail_link'             => 'company',
        'publish_window'          => false,
        'class1_filter_min_count' => 2,
    ]
));

$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;

frontend_init_breadcrumb($Module_Name, $Module_Link);

$PKey = frontend_request_pkey($filter_array ?? []);
$detailRow = frontend_fetch_detail($Module_PKey, $PKey);

if ($detailRow === null) {
    frontend_not_found_exit(frontend_list_href());
}

$strName       = (string)crud_row_val($detailRow, 'strName');
$seoTitle      = frontend_lang_seo_title($detailRow);
$strDate       = (string)crud_row_val($detailRow, 'strDate');
$Movielink     = (string)crud_row_val($detailRow, 'Movielink');
$m_description = (string)crud_row_val($detailRow, 'Description');
$m_keywords    = (string)crud_row_val($detailRow, 'Keywords');

frontend_append_detail_breadcrumb($strName);

$msgData  = frontend_fetch_msg_contents($PKey);
$Contents = $msgData['contents'];
$Show     = $msgData['show'];

$photoData = frontend_fetch_detail_photos($PKey);
$Photo     = $photoData['photo'];
$PhotoM    = $photoData['photoM'];

$layouts = frontend_fetch_content_layouts($PKey);

$links  = frontend_fetch_detail_links($PKey);
$ytSrc  = youtube_embed_src($Movielink);
$ldjson = frontend_breadcrumb_ldjson();
$backHref = frontend_list_href();
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
            <div class="newsDBox">
                <div class="articleTop">
                    <h2 class="articleTt"><?php echo e_attr($strName); ?></h2>
                    <?php if ($strDate !== '') { ?>
                    <div class="dateTxt"><?php echo e_attr(date_en($strDate, 1)); ?></div>
                    <?php } ?>
                </div>
                <div class="articleMain">
                    <?php
                    for ($i = 1; $i < 7; $i++) {
                        if (empty($Contents[$i]) && empty($Photo[$i])) {
                            continue;
                        }
                        $css = frontend_content_layout_css($i, $layouts);
                    ?>
                    <article class="<?php echo e_attr($css); ?>">
                        <?php if (!empty($Photo[$i])) { ?>
                        <figure>
                            <img src="<?php echo e_attr((string)$Photo[$i]); ?>" class="img-fluid" loading="lazy" alt="<?php echo e_attr($strName); ?>">
                        </figure>
                        <?php } ?>
                        <?php if (!empty($Contents[$i])) { ?>
                        <div class="text">
                            <?php echo frontend_render_html((string)$Contents[$i]); ?>
                        </div>
                        <?php } ?>
                    </article>
                    <?php } ?>
                </div>
                <?php if ($ytSrc) { ?>
                <div class="vdBox">
                    <iframe width="100%" height="100%" src="<?php echo e_attr($ytSrc); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
                <?php } ?>
                <?php if (!empty($links)) { ?>
                <div class="linkBox">
                    <?php foreach ($links as $linkItem) { ?>
                    <a href="<?php echo e_attr($linkItem['url']); ?>" target="_blank" rel="noopener noreferrer" class="linkBox__item">
                        <span class="txt"><?php echo e($linkItem['title']); ?></span>
                    </a>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
            <div class="btnWrap btnWrap--center">
                <a href="<?php echo href_attr($backHref); ?>" class="btnStyle">
                    <span class="txt">回上一頁</span>
                </a>
            </div>
        </div>
    </section>
</main>

<?php require('_footer.php'); ?>
<?php require('_in_code_bottom.php'); ?>
</body>
</html>
