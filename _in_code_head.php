<?php

/**
 * <head> 內 SEO／meta／CSS 共用片段
 */
// -------------------- 頁面標題處理 --------------------
switch ($pageName) {
    case 'p1':  $pageTitle = $p1.' ∣ '.$pageTitle2; break;
    case 'p2':  $pageTitle = $p2.' ∣ '.$pageTitle2; break;
    case 'p3':  $pageTitle = $p3.' ∣ '.$pageTitle2; break;
    case 'p4':  $pageTitle = $p4.' ∣ '.$pageTitle2; break;
    case 'p5':  $pageTitle = $p5.' ∣ '.$pageTitle2; break;
    case 'p6':  $pageTitle = $p6.' ∣ '.$pageTitle2; break;
    case 'p7':  $pageTitle = $p7.' ∣ '.$pageTitle2; break;
    case 'p8':  $pageTitle = $p8.' ∣ '.$pageTitle2; break;
    case 'p9':  $pageTitle = $p9.' ∣ '.$pageTitle2; break;
    case 'p10': $pageTitle = $p10.' ∣ '.$pageTitle2; break;
    case 'p11': $pageTitle = $p11.' ∣ '.$pageTitle2; break;
    case 'p12': $pageTitle = $p12.' ∣ '.$pageTitle2; break;
}

if (!empty($Description)) { $m_description = $Description; }
if (!empty($Keywords)) { $m_keywords = $Keywords; }

$fb_description = $m_description ?? '';
$pageTitle      = $Web_Name; // 預設覆蓋
$fb_img         = $web_url . 'images/default/default_fb.jpg';

// -------------------- 依單元頁覆蓋（module_lang TDK） --------------------
if ($pageName !== 'index') {
    $modulePKey = (int)($Module_PKey ?? 0);
    if ($modulePKey <= 0 && !empty($page_link) && function_exists('frontend_module_pkey_for_link')) {
        $modulePKey = frontend_module_pkey_for_link((string)$page_link);
    }

    $moduleTdk = function_exists('frontend_apply_module_lang_tdk')
        ? frontend_apply_module_lang_tdk($modulePKey > 0 ? $modulePKey : null)
        : frontend_module_lang_tdk($modulePKey > 0 ? $modulePKey : null);

    if ($moduleTdk['description'] !== '') {
        $fb_description = $moduleTdk['description'];
    }
    if ($moduleTdk['title'] !== '') {
        $pageTitle = $moduleTdk['title'] . '∣' . $Web_Name;
    }

    $detailSeoTitle = '';
    if (isset($seoTitle)) {
        $detailSeoTitle = trim((string)$seoTitle);
    }
    if ($detailSeoTitle === '' && (!empty($strName) || isset($Title))) {
        $detailSeoTitle = crud_lang_seo_title([
            'Title'   => $Title ?? '',
            'strName' => $strName ?? '',
        ]);
    }
    if ($detailSeoTitle !== '') {
        $pageTitle = $detailSeoTitle . '∣' . $Web_Name;
    }
    // OG 圖：列表圖 → 內容圖 → default_fb.jpg
    $fb_img = frontend_head_image_url();
}

$m_description = frontend_meta_description_with_summary_fallback(
    isset($m_description) ? (string)$m_description : '',
    isset($aiSummary) ? (string)$aiSummary : ''
);
if ($m_description !== '') {
    $fb_description = $m_description;
}

?>

<title><?php echo e_attr($pageTitle); ?></title>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" >
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="<?php if (!empty($m_description)) echo e_attr($m_description); ?>" />
<meta name="keywords" content="<?php if (!empty($m_keywords)) echo e_attr($m_keywords); ?>" />
<meta name="robots" content="all" />
<link rel="canonical" href="<?php if (!empty($canonical)) echo safe_url($canonical); ?>" />

<meta itemprop="name" content="<?php echo e_attr($pageTitle); ?>">
<meta itemprop="image" content="<?php echo safe_url($fb_img); ?>">

<meta property="og:site_name" content="<?php echo e_attr($pageTitle); ?>" />
<meta property="og:url" content="<?php echo safe_url($web_url.$page_link); ?>" />
<meta property="og:type" content="website" />
<meta property="og:title" content="<?php echo e_attr($pageTitle); ?>" />
<meta property="og:description" content="<?php if (!empty($m_description)) echo e_attr($m_description); ?>" />
<meta property="og:image" content="<?php echo safe_url($fb_img); ?>" />

<link rel="shortcut icon" href="<?php echo safe_url($web_root.'favicon.ico'); ?>?<?php echo time(); ?>" type="image/x-icon" />

<?php
// -------------------- JSON-LD 區塊 --------------------
if (!empty($ldjson)) {
    echo json_ld_script_tag($ldjson);
}

if ($pageName === 'index') {
    $org_ld = [
        "@context" => "https://schema.org",
        "@type"    => "Organization",
        "url"      => safe_href($web_url),
        "logo"     => safe_href($web_url.'images/logo.jpg')
    ];
    echo json_ld_script_tag($org_ld);
}

$website_ld = frontend_website_ldjson();
if ($website_ld !== null) {
    echo json_ld_script_tag($website_ld);
}

$professional_service_ld = frontend_professional_service_ldjson();
if ($professional_service_ld !== null) {
    echo json_ld_script_tag($professional_service_ld);
}

$article_ld = frontend_article_ldjson($fb_img);
if ($article_ld !== null) {
    echo json_ld_script_tag($article_ld);
}

$product_ld = frontend_product_ldjson($fb_img);
if ($product_ld !== null) {
    echo json_ld_script_tag($product_ld);
}

$faqpage_ld = frontend_faqpage_ldjson();
if ($faqpage_ld !== null) {
    echo json_ld_script_tag($faqpage_ld);
}

if (!empty($Web_Address)) {
    $store_ld = [
        "@context" => "https://schema.org",
        "@type"    => "Store",
        "name"     => $Web_Name,
        "url"      => safe_href($web_url),
        "telephone"=> $Web_Tel,
        "address"  => [
            "@type"           => "PostalAddress",
            "streetAddress"   => $Web_Address,
            "addressLocality" => "",
            "addressRegion"   => "",
            "postalCode"      => "",
            "addressCountry"  => "TW"
        ]
    ];
    echo json_ld_script_tag($store_ld);
}
?>
