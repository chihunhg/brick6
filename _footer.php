
<footer class="footer">
	<div class="container">
		<div class="footer__box">
			<div class="footer__box__left">
				<figure class="ftLogo wow fadeInUp">
					<img src="<?php echo e_attr($web_url); ?>images/all/logo.svg" alt="<?php echo e_attr($pageTitle2); ?>">
				</figure>
				<div class="ftInfoList wow fadeInUp">
					<div class="ftInfoList__item" title="" >
						<span class="txt"><?php echo $lang_text['ft_approval'][$this_lang]; ?></span>
						<span class="txt"><?php echo $lang_text['ft_Reg'][$this_lang]; ?></span>
					</div>
					<a class="ftInfoList__item --clock" title="<?php echo $lang_text['ft_opening'][$this_lang]; ?>" >
						<span class="txt"><?php echo $lang_text['ft_opening'][$this_lang]; ?></span>	
						<span class="txt">週一至週五 09:00 - 18:00 </span>
					</a>
					<a class="ftInfoList__item --phone" href="tel:0225779281" title="<?php echo $lang_text['ft_tel'][$this_lang]; ?>" >
						<span class="txt"><?php echo $lang_text['ft_tel'][$this_lang]; ?></span>
						<span class="txt">02 2577 9281</span>
					</a>
					<a class="ftInfoList__item --send" href="#" title="<?php echo $lang_text['ft_mail'][$this_lang]; ?>" >
						<span class="txt"><?php echo $lang_text['ft_mail'][$this_lang]; ?></span>
						<span class="txt">liushuhua860127@outlook.com</span>
					</a>
				</div>
				<div class="ftSocialList wow fadeInUp">
					<a class="ftSocialList__item" href="<?php echo href_attr((string)($Web_Facebook ?? '')); ?>" title="Facebook" target="_blank" rel="noopener noreferrer"><img src="<?php echo e_attr($web_url); ?>images/all/facebook.svg?ver=<?php echo filemtime(__DIR__ . '/images/all/facebook.svg'); ?>" alt="Facebook"></a>
					<a class="ftSocialList__item" href="<?php echo href_attr((string)($Web_IG ?? '')); ?>" title="Instagram" target="_blank" rel="noopener noreferrer"><img src="<?php echo e_attr($web_url); ?>images/all/instagram.svg?ver=<?php echo filemtime(__DIR__ . '/images/all/instagram.svg'); ?>" alt="Instagram"></a>
					<a class="ftSocialList__item" href="<?php echo href_attr((string)($Web_linkedin ?? '')); ?>" title="Linkedin" target="_blank" rel="noopener noreferrer"><img src="<?php echo e_attr($web_url); ?>images/all/linkedin.svg?ver=<?php echo filemtime(__DIR__ . '/images/all/linkedin.svg'); ?>" alt="Linkedin"></a>
					<a class="ftSocialList__item" href="<?php echo href_attr((string)($Web_Youtube ?? '')); ?>" title="YouTube" target="_blank" rel="noopener noreferrer"><img src="<?php echo e_attr($web_url); ?>images/all/youtube.svg?ver=<?php echo filemtime(__DIR__ . '/images/all/youtube.svg'); ?>" alt="YouTube"></a>
				</div>
			</div>
			<div class="footer__box__right">
				<p class="ftTitle wow fadeInUp"><?php echo $lang_text['ft_address'][$this_lang]; ?></p>
				<div class="ftInfoList wow fadeInUp">
					<a class="ftInfoList__item --location" href="https://share.google/1XnZ9r9CEZ27MiCm2" title="地址" >台北市信義區信義路五段7號57樓之1</a>
				</div>
				<figure class="ftMap wow fadeInUp">
					<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3615.0027471432354!2d121.561963975926!3d25.033980838291296!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3442abb6da9c9e1f%3A0x1206bcf082fd10a6!2zVGFpcGVpIDEwMSwgTm8uIDfkv6Hnvqnot6_kupTmrrXkv6HnvqnljYDoh7rljJfluIIxMTA!5e0!3m2!1szh-TW!2stw!4v1772521850654!5m2!1szh-TW!2stw" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
				</figure>
			</div>
		</div>
	</div>


	<div class="ftMenu">
		<a class="ftMenu__item" href="<?php echo $lang_text['p1']['p1_page']; ?>" title="<?php echo $lang_text['p1'][$this_lang]['p1']; ?>"><span class="txt"><?php echo $lang_text['p1'][$this_lang]['p1']; ?></span></a>
		<a class="ftMenu__item" href="<?php echo $lang_text['p2']['p2_page']; ?>" title="<?php echo $lang_text['p2'][$this_lang]['p2']; ?>"><span class="txt"><?php echo $lang_text['p2'][$this_lang]['p2']; ?></span></a>
		<a class="ftMenu__item" href="<?php echo $lang_text['p3']['p3_page']; ?>" title="<?php echo $lang_text['p3'][$this_lang]['p3']; ?>"><span class="txt"><?php echo $lang_text['p3'][$this_lang]['p3']; ?></span></a>
		<a class="ftMenu__item" href="<?php echo $lang_text['p4']['p4_page']; ?>" title="<?php echo $lang_text['p4'][$this_lang]['p4']; ?>"><span class="txt"><?php echo $lang_text['p4'][$this_lang]['p4']; ?></span></a>
		<a class="ftMenu__item" href="<?php echo $lang_text['p5']['p5_page']; ?>" title="<?php echo $lang_text['p5'][$this_lang]['p5']; ?>"><span class="txt"><?php echo $lang_text['p5'][$this_lang]['p5']; ?></span></a>
	</div>


	<div class="copyright">
		<span class="txt">© 2026 Endeavor Global Advisory. All Rights Reserved. &nbsp; <a href="https://www.tsg.com.tw/" target="_blank" rel="noopener noreferrer" title="TSG 網頁設計">TSG 網頁設計</a></span>
	</div>

</footer>

<div class="sideBtn">
	<a href="<?php echo $lang_text['p5']['p5_page']; ?>" class="sideBtn__item">
		<span class="sideBtn__icon --contact"></span>
		<span class="sideBtn__txt">聯絡我們</span>
	</a>
	<div class="sideBtn__item goTop" id="goTop">
		<span class="goTop__arrow">↑</span>
		<span class="goTop__btn">Go Top</span>
	</div>
</div>