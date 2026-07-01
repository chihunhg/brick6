<?php

declare(strict_types=1);



$pageName = '01';

$subPageName = '';

require('_inc.php');



$Module_PKey = frontend_module_pkey('investor');

if ($Module_PKey <= 0) {

    $Module_PKey = frontend_module_pkey_for_link('investor.htm');

}

frontend_module_set_config(array_merge(

    require __DIR__ . '/manage/investor/_config.php',

    [

        'view'                    => 'view_investor',

        'class_link'              => 'investor',

        'detail_link'             => 'investor-detail',

        'publish_window'          => false,

        'order_by'                => 'Sort ASC',

        'page_size'               => 20,

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



$investorItems = frontend_investor_items_from_rows($listRows);



$listTitleAll = (string)($Module_Name !== '' ? $Module_Name : '投資人專區');

$class1_name = frontend_class1_display_name($Class1, $listTitleAll);

?>



<!DOCTYPE html>

<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>

<?php require('_in_code_head.php'); ?>

<?php require('_in_javascript.php'); ?>

<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" crossorigin="anonymous">

</head>



<body <?php if (!empty($bodytxt)) { echo $bodytxt; } ?>>

<?php require('_header.php'); ?>

<?php require('_banner.php'); ?>



<main class="pgContent">

    <section class="blockHeight blockHeight--download">

        <div class="container">

            <h2 class="mainTitle">

                <span class="mainTitle__mj wow fadeInUp"><?php echo e($class1_name); ?></span>

            </h2>

            <?php require('_pg_sidebar_open.php'); ?>

            <?php if ($investorItems === []) { ?>

            <p class="downloadEmpty"><?php echo e($lang_text['no_data_str'][$this_lang] ?? '資料建置中'); ?></p>

            <?php } else { ?>

            <ul class="downloadList">

                <?php foreach ($investorItems as $item) { ?>

                <li class="downloadList__item">

                    <?php if (!empty($item['href'])) { ?>

                    <a href="<?php echo href_attr((string)$item['href']); ?>" class="downloadList__title downloadList__title--link"

                        target="<?php echo e_attr($item['target']); ?>"

                        <?php if (!empty($item['rel'])) { ?> rel="<?php echo e_attr((string)$item['rel']); ?>"<?php } ?>

                        <?php if (!empty($item['download'])) { ?> download<?php } ?>>

                        <?php echo e($item['title']); ?>

                    </a>

                    <a href="<?php echo href_attr((string)$item['href']); ?>" class="downloadList__icon"

                        target="<?php echo e_attr($item['target']); ?>"

                        <?php if (!empty($item['rel'])) { ?> rel="<?php echo e_attr((string)$item['rel']); ?>"<?php } ?>

                        title="<?php echo e_attr($item['title']); ?>"

                        aria-label="<?php echo e_attr($item['title']); ?>"

                        <?php if (!empty($item['download'])) { ?> download<?php } ?>>

                        <i class="<?php echo e_attr($item['icon']); ?>" aria-hidden="true"></i>

                    </a>

                    <?php } else { ?>

                    <span class="downloadList__title"><?php echo e($item['title']); ?></span>

                    <span class="downloadList__icon downloadList__icon--disabled" aria-hidden="true">

                        <i class="<?php echo e_attr($item['icon']); ?>"></i>

                    </span>

                    <?php } ?>

                </li>

                <?php } ?>

            </ul>

            <?php } ?>

            <?php require('_page_number.php'); ?>

            <?php require('_pg_sidebar_close.php'); ?>

        </div>

    </section>

</main>



<?php require('_footer.php'); ?>

<?php require('_in_code_bottom.php'); ?>

</body>

</html>


