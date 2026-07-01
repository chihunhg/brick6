<?php if ($pageName == "index") { ?>
	<div class="swiper banner" role="region" aria-label="首頁 Banner 輪播">
		<div class="swiper-wrapper">
			<?php for ($i = 1; $i <= 3; $i++) { ?>
				<div class="swiper-slide banner__box">
					<picture>
						<source srcset="<?php echo $web_url; ?>images/banner/0<?php echo $i; ?>-mb.jpg" type="image/jpg" media="(max-width: 768px)">
						<img src="<?php echo $web_url; ?>images/banner/0<?php echo $i; ?>.jpg" alt="圖片說明<?php echo $i; ?>" class="img-fluid" loading="lazy">
					</picture>
					<h4 class="bannerTitle">圖片說明<?php echo $i; ?></h4>
				</div>
			<?php } ?>
		</div>
		<div class="swiper-button-next" aria-label="下一張"></div>
		<div class="swiper-button-prev" aria-label="上一張"></div>
		<div class="swiper-pagination"></div>
	</div>

<?php } else { ?>
	<div style="background:url(<?php echo $web_url; ?>images/banner/<?php echo $pageName; ?>.jpg) no-repeat;" class="pgBanner"></div>
<?php } ?>