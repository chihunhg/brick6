<?php
declare(strict_types=1);

/**
 * 前台常見問題列表（faq.htm / faq{N}.htm）
 *
 * 資料流程：註冊模組設定 → frontend_fetch_faq_items 手風琴列表（無分頁）。
 * 後台：manage/faq/_config.php。
 */

$pageName = '';
$subPageName = '';
require('_inc.php');

/**
 * 模組設定（前台列表／內頁共用）
 *
 * 用途：合併後台 _config.php（資料表 master/fk/lang 等）與前台專用選項，
 *       供 frontend_module_config() 及 frontend_* helper 組 SQL、產生連結。
 *
 * 使用方式：
 *   1. frontend_module_pkey_for_page('faq.htm') — 依 PageLink 從後台選單反查 Module_PKey
 *   2. array_merge(require manage/…/_config.php, [前台覆寫]) — 後台與前台設定合一
 *   3. frontend_module_set_config() — 註冊後方可呼叫 frontend_list_where 等函式
 *
 * 前台覆寫欄位說明：
 *   view                   — 列表查詢用的 view 表（含語系、Upload 等欄位）
 *   class_link             — 分類列表友好 URL 前綴（例：news.htm 的 news）
 *   detail_link            — 內頁友好 URL 前綴（例：news-detail12.htm 的 news-detail）
 *   publish_window         — true：依 OpenDate～EndDate 刊登區間篩選；false：僅 Upload=Yes
 *   order_by               — 列表排序（供 frontend_fetch_list 使用）
 *   page_size              — 每頁筆數（frontend_list_paginate / frontend_fetch_list）
 *   class1_filter_min_count — Class1 分類數 ≥ 此值才顯示分類篩選與側欄（預設 2）
 */
$Module_PKey = frontend_module_pkey_for_page('faq.htm');
frontend_module_set_config(array_merge(
    require __DIR__ . '/manage/faq/_config.php',
    [
        'view'           => 'view_faq',
        'class_link'     => 'faq',
        'detail_link'    => 'faq',
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
    <section class="blockHeight blockHeight--faq">
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
                        aria-controls="faq-panel-<?php echo (int)$item['pkey']; ?>">
                        <span class="faqItem__mark faqItem__mark--q" aria-hidden="true">Q</span>
                        <span class="faqItem__title"><?php echo e($item['question']); ?></span>
                        <span class="faqItem__icon" aria-hidden="true"></span>
                    </button>
                    <div class="faqItem__a" id="faq-panel-<?php echo (int)$item['pkey']; ?>" hidden>
                        <div class="faqItem__aInner">
                            <span class="faqItem__mark faqItem__mark--a" aria-hidden="true">A</span>
                            <div class="faqItem__body">
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
<?php echo script_src_tag($web_url . 'js/faq-page.js?ver=' . filemtime(__DIR__ . '/js/faq-page.js')); ?>
</body>
</html>
