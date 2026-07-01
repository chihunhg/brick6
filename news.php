<?php
declare(strict_types=1);

$pageName = 'p4';
$subPageName = 'p4_1';
require('_inc.php');

$Module_PKey = frontend_module_pkey('news');
frontend_module_set_config(array_merge(
    require __DIR__ . '/manage/news/_config.php',
    [
        'view'                   => 'view_news',
        'class_link'             => 'news',
        'detail_link'            => 'news-detail',
        'publish_window'         => true,
        'order_by'               => 'OpenDate DESC',
        'page_size'              => 12,
        'class1_filter_min_count' => 2,
    ]
));

$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;

frontend_init_breadcrumb($Module_Name, $Module_Link);

$class1ItemCount = frontend_class1_count($Module_PKey);
$Class1 = frontend_filter_class1($filter_array ?? []);

[$PDO_Cond, $Cond_Array] = frontend_list_where($Module_PKey);
$Class1_Name = frontend_apply_class1_filter($PDO_Cond, $Cond_Array, $Class1, $class1ItemCount);

$Total = frontend_list_total($PDO_Cond, $Cond_Array);
['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = frontend_list_paginate(
    $Total,
    $filter_array['Page'] ?? null
);
$ldjson = frontend_breadcrumb_ldjson();
$listRows = frontend_fetch_list($PDO_Cond, $Cond_Array, $offset, (int)frontend_module_config()['page_size']);
unset($Cond_Array);

$listTitleAll = (string)($lang_text[$pageName][$this_lang][$subPageName] ?? '所有資訊');
$class1_name = frontend_class1_display_name($Class1, $listTitleAll);
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
            <h2 class="mainTitle">
                <span class="mainTitle__en wow fadeInUp"><?php echo $lang_text[$pageName][$this_lang][$pageName . '_en']; ?></span>
                <span class="mainTitle__mj wow fadeInUp"><?php echo e($class1_name); ?></span>
            </h2>
            <?php require('_pg_sidebar_open.php'); ?>
            <div class="imgCardList imgCardList--pg --news">
                <?php
                foreach ($listRows as $row) {
                    $pkey = crud_row_int($row, 'PKey');
                    $displayImgUrl = frontend_cover_image_url($pkey);
                    $title     = (string)crud_row_val($row, 'strName');
                    $interview = strip_tags((string)crud_row_val($row, 'Interview'));
                    $linkInfo  = frontend_show_type_list_link($row);
                    $link_href = $linkInfo['href'] ?? frontend_detail_href($pkey);
                    $link_target = (string)($linkInfo['target'] ?? '_self');
                    $link_rel = $linkInfo['rel'] ?? null;
                ?>
                <a href="<?php echo e_attr((string)$link_href); ?>" class="imgCardList__item"
                    <?php if ($link_target !== '_self') { ?> target="<?php echo e_attr($link_target); ?>"<?php } ?>
                    <?php if (!empty($link_rel)) { ?> rel="<?php echo e_attr((string)$link_rel); ?>"<?php } ?>>
                    <figure class="imgCard__pic">
                        <img src="<?php echo e_attr($displayImgUrl); ?>" alt="<?php echo e_attr($title); ?>" class="img-fluid coverPic" loading="lazy">
                    </figure>
                    <div class="imgCard__info imgCard__info--mb">
                        <h4 class="title title--left"><?php echo e_attr($title); ?></h4>
                        <div class="txt"><?php echo e_attr($interview); ?></div>
                    </div>
                </a>
                <?php } ?>
            </div>
            <?php require('_page_number.php'); ?>
            <?php require('_pg_sidebar_close.php'); ?>
        </div>
    </section>
</main>

<?php require('_footer.php'); ?>
<?php require('_in_code_bottom.php'); ?>
</body>
</html>
