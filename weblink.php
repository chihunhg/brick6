<?php

declare(strict_types=1);



$pageName = '';

$subPageName = '';

require('_inc.php');



$Module_PKey = frontend_module_pkey('weblink');

frontend_module_set_config(array_merge(

    require __DIR__ . '/manage/weblink/_config.php',

    [

        'view'           => 'view_web',

        'class_link'     => 'weblink',

        'detail_link'    => 'weblink',

        'publish_window' => false,

        'order_by'       => 'Sort ASC',

        'page_size'      => 12,

    ]

));



$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';

$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;



frontend_init_breadcrumb($Module_Name, $Module_Link);



[$PDO_Cond, $Cond_Array] = frontend_list_where($Module_PKey);



$Total = frontend_list_total($PDO_Cond, $Cond_Array);

['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = frontend_list_paginate(

    $Total,

    $filter_array['Page'] ?? null

);

$ldjson = frontend_breadcrumb_ldjson();

$listRows = frontend_fetch_list($PDO_Cond, $Cond_Array, $offset, (int)frontend_module_config()['page_size']);

unset($Cond_Array);



$listTitle = (string)($Module_Name !== '' ? $Module_Name : '相關網站');

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

                <span class="mainTitle__mj wow fadeInUp"><?php echo e($listTitle); ?></span>

            </h2>

            <div class="imgCardList imgCardList--pg --news">

                <?php

                foreach ($listRows as $row) {

                    $pkey = crud_row_int($row, 'PKey');

                    $title = (string)crud_row_val($row, 'strName');

                    $summary = strip_tags((string)crud_row_val($row, 'Interview'));

                    if ($summary === '') {

                        $summary = strip_tags((string)crud_row_val($row, 'Subject'));

                    }

                    $displayImgUrl = frontend_cover_image_url($pkey);

                    $rawLink = trim((string)crud_row_val($row, 'strLink'));

                    $linkHref = $rawLink !== '' ? frontend_external_link_href($rawLink) : null;

                    $linkTarget = frontend_link_target((string)crud_row_val($row, 'Target'));

                ?>

                <?php if ($linkHref !== null && $linkHref !== '#') { ?>

                <a href="<?php echo e_attr($linkHref); ?>" class="imgCardList__item"

                    target="<?php echo e_attr($linkTarget); ?>"

                    <?php if ($linkTarget === '_blank') { ?> rel="noopener noreferrer"<?php } ?>

                    title="<?php echo e_attr($title); ?>">

                <?php } else { ?>

                <div class="imgCardList__item">

                <?php } ?>

                    <figure class="imgCard__pic">

                        <img src="<?php echo e_attr($displayImgUrl); ?>" alt="<?php echo e_attr($title); ?>"

                            class="img-fluid coverPic" loading="lazy">

                    </figure>

                    <div class="imgCard__info imgCard__info--mb">

                        <h4 class="title title--left"><?php echo e_attr($title); ?></h4>

                        <div class="txt"><?php echo e_attr($summary); ?></div>

                    </div>

                <?php if ($linkHref !== null && $linkHref !== '#') { ?>

                </a>

                <?php } else { ?>

                </div>

                <?php } ?>

                <?php } ?>

            </div>

            <?php require('_page_number.php'); ?>

        </div>

    </section>

</main>



<?php require('_footer.php'); ?>

<?php require('_in_code_bottom.php'); ?>

</body>

</html>


