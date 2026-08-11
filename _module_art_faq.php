<?php
declare(strict_types=1);
/**
 * 美工頁 FAQ 區塊（module_qa，頁尾上方手風琴）
 */

if (!empty($skipModuleArtFaq)) {
    return;
}

$moduleArtFaqPKey = (int)($Module_PKey ?? 0);
if ($moduleArtFaqPKey <= 0 && !empty($page_link) && function_exists('frontend_module_pkey_for_link')) {
    $moduleArtFaqPKey = frontend_module_pkey_for_link((string)$page_link);
}

if (
    $moduleArtFaqPKey <= 0
    || !function_exists('frontend_module_is_art_page')
    || !frontend_module_is_art_page($moduleArtFaqPKey)
    || !function_exists('frontend_fetch_module_qa')
) {
    return;
}

$moduleArtFaqItems = frontend_fetch_module_qa($moduleArtFaqPKey, (int)($this_lang ?? 1));
if ($moduleArtFaqItems === []) {
    return;
}

$moduleArtFaqLang = $lang_text['module_art_faq'][$this_lang] ?? [];
$moduleArtFaqTitlePrefix = trim((string)($moduleArtFaqLang['title_prefix'] ?? ''));
$moduleArtFaqTitleAccent = trim((string)($moduleArtFaqLang['title_accent'] ?? ''));
$moduleArtFaqLead = trim((string)($moduleArtFaqLang['lead'] ?? ''));
?>

<section class="blockHeight blockHeight--moduleFaq" aria-labelledby="module-art-faq-heading">
    <div class="container">
        <div class="moduleFaqHead wow fadeInUp">
            <h2 id="module-art-faq-heading" class="moduleFaqHead__title">
                <?php if ($moduleArtFaqTitlePrefix !== '') { ?>
                <span class="moduleFaqHead__prefix"><?php echo e($moduleArtFaqTitlePrefix); ?></span>
                <?php } ?>
                <?php if ($moduleArtFaqTitleAccent !== '') { ?>
                <span class="moduleFaqHead__accent"><?php echo e($moduleArtFaqTitleAccent); ?></span>
                <?php } ?>
            </h2>
            <?php if ($moduleArtFaqLead !== '') { ?>
            <p class="moduleFaqHead__lead"><?php echo e($moduleArtFaqLead); ?></p>
            <?php } ?>
        </div>
        <div class="faqList faqList--moduleArt wow fadeIn" data-faq-accordion data-wow-delay="0.15s">
            <?php foreach ($moduleArtFaqItems as $idx => $item) {
                $panelId = 'module-art-faq-panel-' . $moduleArtFaqPKey . '-' . ($idx + 1);
                ?>
            <div class="faqItem">
                <button type="button" class="faqItem__q" aria-expanded="false" aria-controls="<?php echo e_attr($panelId); ?>">
                    <span class="faqItem__mark faqItem__mark--q" aria-hidden="true">Q</span>
                    <span class="faqItem__title"><?php echo e($item['question']); ?></span>
                    <span class="faqItem__icon faqItem__icon--chevron" aria-hidden="true"></span>
                </button>
                <div class="faqItem__a" id="<?php echo e_attr($panelId); ?>" hidden>
                    <div class="faqItem__aInner">
                        <span class="faqItem__mark faqItem__mark--a" aria-hidden="true">A</span>
                        <div class="faqItem__body">
                            <div class="faqItem__text text">
                                <?php echo nl2br(e($item['answer'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<?php echo script_src_tag($web_url . 'js/faq-page.js?ver=' . filemtime(__DIR__ . '/js/faq-page.js')); ?>
