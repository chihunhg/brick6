<?php
declare(strict_types=1);
/**
 * 前台文章 AI 摘要區塊（需由內頁提供 $aiSummary）
 */

$aiSummary = trim((string)($aiSummary ?? ''));
if ($aiSummary === '') {
    return;
}
?>
<section class="articleSummary" aria-labelledby="article-summary-heading">
    <h3 id="article-summary-heading" class="articleSummary__label">AI 摘要</h3>
    <p class="articleSummary__text"><?php echo e($aiSummary); ?></p>
</section>
