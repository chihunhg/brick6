<?php
declare(strict_types=1);
/**
 * 麵包屑（$breadcrumbs 陣列）
 * 每項：['label' => '文字', 'href' => '可選連結']
 * 未設定時使用 manage_breadcrumbs_default()
 */

if (!isset($breadcrumbs) || !is_array($breadcrumbs) || $breadcrumbs === []) {
    $breadcrumbs = manage_breadcrumbs_default();
}

$GLOBALS['manage_page_header_in_breadcrumbs'] = true;

$__breadcrumb_title = (string)($layout_page_title ?? '');
if ($__breadcrumb_title === '') {
    $__breadcrumb_title = manage_breadcrumbs_page_title($breadcrumbs);
}

$__home_href = '../index.php';
?>
<header class="pageHeader">
	<section>
		<div class="breadcrumb">
			<a class="breadcrumb__home" href="<?php echo e($__home_href); ?>" aria-label="首頁">
				<i class="bi bi-house" aria-hidden="true"></i>
			</a>
			<?php
			$__crumb_count = count($breadcrumbs);
			foreach ($breadcrumbs as $__i => $__crumb) {
			    if (!is_array($__crumb)) {
			        continue;
			    }
			    $__label = trim((string)($__crumb['label'] ?? ''));
			    if ($__label === '') {
			        continue;
			    }
			    $__href = trim((string)($__crumb['href'] ?? ''));
			    $__is_last = ($__i === $__crumb_count - 1);
			    if ($__href !== '' && !$__is_last) {
			        echo '<a class="breadcrumb__item breadcrumb__link" href="' . e($__href) . '">' . e($__label) . '</a>';
			    } else {
			        echo '<div class="breadcrumb__item">' . e($__label) . '</div>';
			    }
			}
			?>
		</div>
		<?php if ($__breadcrumb_title !== '') { ?>
		<h2 class="pageTitle"><?php echo e($__breadcrumb_title); ?></h2>
		<?php } ?>
	</section>
</header>
