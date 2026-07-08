<?php
declare(strict_types=1);

/**
 * 前台最新消息列表（news.htm / news{N}.htm）
 *
 * 資料流程：_inc.php → 註冊模組設定 → frontend_list_where 查詢 → 分頁列表輸出。
 * 內頁請見 news-detail.php；後台表結構定義於 manage/news/_config.php。
 */

$pageName = 'p4';
$subPageName = 'p4_1';
require('_inc.php');

/**
 * 模組設定（前台列表／內頁共用）
 *
 * 用途：合併後台 _config.php（資料表 master/fk/lang 等）與前台專用選項，
 *       供 frontend_module_config() 及 frontend_* helper 組 SQL、產生連結。
 *
 * 使用方式：
 *   1. frontend_module_pkey_for_page('news.htm') — 依 PageLink 從後台選單反查 Module_PKey
 *   2. array_merge(require manage/…/_config.php, [前台覆寫]) — 後台與前台設定合一
 *   3. frontend_module_set_config() — 註冊後方可呼叫 frontend_list_where 等函式
 *
 * 前台覆寫欄位說明：
 *   view                   — 列表／內頁查詢用的 view 表（含語系、Upload 等欄位）
 *   class_link             — 分類列表友好 URL 前綴（例：news.htm、news3.htm 的 news）
 *   detail_link            — 內頁友好 URL 前綴（例：news-detail12.htm 的 news-detail）
 *   publish_window         — true：依 OpenDate～EndDate 刊登區間篩選；false：僅 Upload=Yes
 *   order_by               — 列表排序（白名單欄位，供 frontend_fetch_list 使用）
 *   page_size              — 每頁筆數（frontend_list_paginate / frontend_fetch_list）
 *   class1_filter_min_count — Class1 分類數 ≥ 此值才顯示分類篩選與側欄（預設 2）
 */
$Module_PKey = frontend_module_pkey_for_page('news.htm');
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
