<?php

/**
 * 前台投資人專區內頁（investor-detail.htm / investor-detail{N}.htm）
 *
 * 資料流程：註冊模組設定 → frontend_fetch_detail → 內文／檔案輸出。
 * 列表：investor.php；後台：manage/investor/_config.php。
 */

declare(strict_types=1);

$pageName = '01';

$subPageName = '';

require('_inc.php');

/**
 * 模組設定（前台列表／內頁共用）
 *
 * 用途：合併後台 _config.php（資料表 master/fk/lang/msg 等）與前台專用選項，
 *       供 frontend_module_config() 及 frontend_* helper 組 SQL、產生連結。
 *
 * 使用方式：
 *   1. frontend_module_pkey_for_page('investor.htm') — 依 PageLink 從後台選單反查 Module_PKey
 *   2. array_merge(require manage/…/_config.php, [前台覆寫]) — 後台與前台設定合一
 *   3. frontend_module_set_config() — 註冊後方可呼叫 frontend_fetch_detail 等函式
 *
 * 前台覆寫欄位說明：
 *   view                   — 內頁查詢用的 view 表（含語系、Upload、OpenDate 等欄位）
 *   class_link             — 分類列表友好 URL 前綴
 *   detail_link            — 內頁友好 URL 前綴
 *   publish_window         — true：依 OpenDate～EndDate 刊登區間篩選；false：僅 Upload=Yes
 *   class1_filter_min_count — Class1 分類數 ≥ 此值才在麵包屑顯示分類層級（預設 2）
 *
 * 內頁專用（設定後由下方程式使用）：
 *   frontend_request_pkey() — 由網址 PKey 參數取得主鍵
 *   frontend_fetch_detail() — 讀取單筆主檔（套用 publish_window / Upload 條件）
 */
$Module_PKey = frontend_module_pkey_for_page('investor.htm');

frontend_module_set_config(array_merge(

    require __DIR__ . '/manage/investor/_config.php',

    [

        'view'                    => 'view_investor',

        'class_link'              => 'investor',

        'detail_link'             => 'investor-detail',

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

$showType = crud_row_int($detailRow, 'show_type');

if ($showType > 0 && $showType !== 2) {

    frontend_not_found_exit(frontend_list_href());

}

$strName       = (string)crud_row_val($detailRow, 'strName');
$seoTitle      = frontend_lang_seo_title($detailRow);

$strDate       = (string)crud_row_val($detailRow, 'strDate');

$year          = (string)crud_row_val($detailRow, 'year');

$Movielink     = (string)crud_row_val($detailRow, 'Movielink');

$Class1        = crud_row_int($detailRow, 'Class1_PKey');

$m_description = (string)crud_row_val($detailRow, 'Description');

$m_keywords    = (string)crud_row_val($detailRow, 'Keywords');

frontend_apply_detail_class1_breadcrumb($Class1, $class1ItemCount);

frontend_append_detail_breadcrumb($strName);

$msgData  = frontend_fetch_msg_contents($PKey);

$Contents = $msgData['contents'];

$Show     = $msgData['show'];

$photoData = frontend_fetch_detail_photos($PKey);

$Photo     = $photoData['photo'];

$PhotoM    = $photoData['photoM'];

$layouts = frontend_fetch_content_layouts($PKey);

$ytSrc  = youtube_embed_src($Movielink);

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

                <div class="articleTop">

                    <h2 class="articleTt"><?php echo e_attr($strName); ?></h2>

                    <?php if ($year !== '' && $year !== '0') { ?>

                    <div class="dateTxt"><?php echo e_attr($year); ?></div>

                    <?php } elseif ($strDate !== '') { ?>

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

                            <img src="<?php echo e_attr((string)$Photo[$i]); ?>" class="img-fluid" loading="lazy"

                                alt="<?php echo e_attr($strName); ?>">

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

                    <iframe width="100%" height="100%" src="<?php echo e_attr($ytSrc); ?>" title="YouTube video player"

                        frameborder="0"

                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"

                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>

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

<?php require('_in_code_bottom.php'); ?>

</body>

</html>
