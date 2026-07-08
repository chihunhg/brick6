<?php
/**
 * 前台 Banner 輪播（首頁 index 使用）
 *
 * 由 frontend_banner_rows() 讀取 ad 模組 Banner 資料。
 */
if ($pageName == "index") { ?>
	<section class="bannerWrap">
		<div class="swiper banner" role="region" aria-label="首頁 Banner 輪播">
			<div class="swiper-wrapper">
                <?php
                $bannerRows = frontend_banner_rows(1);
                foreach ($bannerRows as $row) {
                    $adPKey = crud_row_int($row, 'PKey');
                    $slideImgUrl = frontend_ad_slide_image_url($adPKey);
                    if ($slideImgUrl === null) {
                        continue;
                    }

                    $titleText   = safe_inline_html((string)crud_row_val($row, 'strName'), ['p', 'span', 'br']);
                    $subjectText = safe_inline_html((string)crud_row_val($row, 'Subject'), ['p', 'span', 'br']);
                    $altText     = $titleText !== '' ? $titleText : (string)($Web_Name ?? '');
				?>
				<div class="swiper-slide banner__box">
					<picture class="bnPic">
						<source srcset="<?php echo e_attr($slideImgUrl); ?>" type="image/jpg" media="(max-width: 768px)">
						<img src="<?php echo e_attr($slideImgUrl); ?>" alt="<?php echo e_attr($altText); ?>" class="img-fluid" loading="eager" fetchpriority="high" width="1920" height="900">
					</picture>
					<div class="bnTxt__box">
						<div class="container">
							<h4 class="bnTit" data-splitting><?php echo $titleText; ?></h4>
							<p class="bnTxt" data-splitting><?php echo $subjectText; ?></p>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
			<div class="swiper-button">
				<div class="swiper-button-prev" aria-label="上一張"><span class="txt">Prev</span></div>
				<div class="swiper-button-next" aria-label="下一張"><span class="txt">Next</span></div>
			</div>
			<div class="swiper-pagination"></div>
		</div>
	</section>

<?php } else {
	$bannerSlug = trim((string)($pageName ?? ''));
	$bannerFile = __DIR__ . '/images/banner/' . $bannerSlug . '.jpg';
	if ($bannerSlug === '' || !is_file($bannerFile)) {
		$bannerSlug = is_file(__DIR__ . '/images/banner/03.jpg') ? '03' : '01';
	}
	$bannerBgUrl = $web_root . 'images/banner/' . $bannerSlug . '.jpg';
?>
	<div class="pgBanner pgBanner--dynamic" data-bg-image="<?php echo e_attr($bannerBgUrl); ?>"></div>

	<!-- 麵包屑 -->
	<nav class="breadCrumbsWrap" aria-label="breadcrumb">
		<div class="container">
			<ol class="breadCrumbs">
				<?php
				for ($i = 0; $i < count($bread_name); $i++) {
					$title = $bread_name[$i];
					$url = $break_link[$i];
				?>
				<li class="breadCrumbs__item"><a href="<?php echo href_attr((string)$url); ?>"><?php echo e((string)$title); ?></a></li>
				<?php
				}
				?>
			</ol>
		</div>
	</nav>
<?php } ?>
