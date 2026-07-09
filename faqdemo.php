<?php
declare(strict_types=1);

/**
 * 前台 FAQ Demo 列表（faqdemo.htm）— Day 7 onboarding 練習模組
 *
 * 後台：manage/faqdemo/_config.php
 * SQL：sql/onboarding/day7_faqdemo_module.sql
 */

$pageName = '';
$subPageName = '';
require('_inc.php');

$Module_PKey = frontend_module_pkey_for_page('faqdemo.htm');
frontend_module_set_config(array_merge(
    require __DIR__ . '/manage/faqdemo/_config.php',
    [
        'view'                    => 'view_faqdemo',
        'class_link'              => 'faqdemo',
        'detail_link'             => 'faqdemo',
        'publish_window'          => false,
        'order_by'                => 'Sort ASC',
        'class1_filter_min_count' => 2,
    ]
));

$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;

frontend_init_breadcrumb($Module_Name, $Module_Link);

$class1ItemCount = frontend_class1_count($Module_PKey);
$Class1 = frontend_filter_class1($filter_array ?? []);

[$PDO_Cond, $Cond_Array] = frontend_list_where($Module_PKey);
frontend_apply_class1_filter($PDO_Cond, $Cond_Array, $Class1, $class1ItemCount);

$faqItems = frontend_fetch_faq_items($Module_PKey, null, $PDO_Cond, $Cond_Array);
unset($Cond_Array);
$ldjson = frontend_breadcrumb_ldjson();
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
    <section class="blockHeight blockHeight--faq blockHeight--faqdemo">
        <div class="container">
            <h2 class="mainTitle">
                <span class="mainTitle__mj wow fadeInUp"><?php echo e($Module_Name); ?></span>
            </h2>
            <?php if ($faqItems === []) { ?>
            <p class="faqEmpty"><?php echo e($lang_text['no_data_str'][$this_lang] ?? '資料建置中'); ?></p>
            <?php } else { ?>
            <div class="faqList" data-faq-accordion>
                <?php foreach ($faqItems as $item) { ?>
                <div class="faqItem">
                    <button type="button" class="faqItem__q" aria-expanded="false"
                        aria-controls="faqdemo-panel-<?php echo (int)$item['pkey']; ?>">
                        <span class="faqItem__mark faqItem__mark--q" aria-hidden="true">Q</span>
                        <span class="faqItem__title"><?php echo e($item['question']); ?></span>
                        <span class="faqItem__icon" aria-hidden="true"></span>
                    </button>
                    <div class="faqItem__a" id="faqdemo-panel-<?php echo (int)$item['pkey']; ?>" hidden>
                        <div class="faqItem__aInner">
                            <span class="faqItem__mark faqItem__mark--a" aria-hidden="true">A</span>
                            <div class="faqItem__body">
                                <?php if (!empty($item['note'])) { ?>
                                <p class="faqItem__note"><?php echo e($item['note']); ?></p>
                                <?php } ?>
                                <?php if (!empty($item['image'])) { ?>
                                <figure class="faqItem__pic">
                                    <img src="<?php echo e_attr((string)$item['image']); ?>" class="img-fluid" loading="lazy"
                                        alt="<?php echo e_attr($item['question']); ?>">
                                </figure>
                                <?php } ?>
                                <?php if ($item['answer'] !== '') { ?>
                                <div class="faqItem__text text">
                                    <?php echo frontend_render_html($item['answer']); ?>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </section>
</main>

<?php require('_footer.php'); ?>
<?php require('_in_code_bottom.php'); ?>
<?php echo script_src_tag($web_url . 'js/faqdemo-page.js?ver=' . filemtime(__DIR__ . '/js/faqdemo-page.js')); ?>
</body>
</html>
