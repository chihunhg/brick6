<?php

/**
 * 前台首頁（index.htm）
 *
 * 首頁區塊：Banner、關於簡介、服務、最新消息等；由 _inc.php 載入共用環境。
 */
$pageName = "index";
$subPageName = "";

require("_inc.php");
?>

<!DOCTYPE html>
<html <?php echo $lang_text["lang"][$this_lang]; ?>>

<head>
	<?php require("_in_code_head.php"); ?>
	<?php require("_in_javascript.php"); ?>
</head>

<body <?php if (!empty($bodytxt)) { echo $bodytxt; } ?>>
	<?php require("_header.php"); ?>
	<?php require("_banner.php"); ?>

	<main>
		<!-- 關於承遠簡介 -->
		<section class="blockHeight blockHeight--ixAbout">
			<div class="container">
				<h2 class="ixAboutWrap">
					<span class="ixAboutWrap__en wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixAbout_en"]; ?></span>
					<span class="ixAboutWrap__mj ixAboutWrap__mj--important wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixAbout_title1"]; ?></span>
					<span class="ixAboutWrap__mj wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixAbout_title2"]; ?></span>
				</h2>
				<a href="about.htm" class="btnStyle wow fadeInUp"><span class="txt"><?php echo $lang_text["index"][$this_lang]["ixAbout_btn"]; ?></span></a>
			</div>
		</section>

		<!-- 各國移民服務 輪播 -->
		<section class="blockHeight blockHeight--ixSer">
			<div class="container">
				<h2 class="mainTitle">
					<span class="mainTitle__en wow fadeInUp"><?php echo $lang_text["p2"][$this_lang]["p2_en"]; ?></span>
					<span class="mainTitle__mj wow fadeInUp"><?php echo $lang_text["p2"][$this_lang]["p2"]; ?></span>
				</h2>
				<div class="swiper ixSer imgCardList wow fadeInUp">
					<div class="swiper-wrapper">
						<?php
						$ixSerModulePKey = frontend_migration_services_module_pkey();
						$sql = 'select * from view_dbclass1 WHERE Upload= :Upload and Module_PKey= :Module_PKey and intLang= :intLang ORDER BY Sort';
						$rs  = new recordset($sql,['Upload' => 'Yes', 'Module_PKey' => $ixSerModulePKey,'intLang'=>$this_lang]);
						if (($SQL_Error = $rs->getErrorMessage())) {
							$result = sql_error($sql . PHP_EOL . array_to_string(['Upload' => 'Yes', 'Module_PKey' => $ixSerModulePKey,'intLang'=>$this_lang,'Home'=>'Yes']), $SQL_Error,$WorkFile, 'system');
							echo '<pre>', e(print_r($result, true)), '</pre>';
							exit;
						}
						while(!$rs->eof){
							$titleText = (string)$rs->field('strName');
							$link_url = $web_root.'migration-services'.(int)$rs->field('PKey').'.htm';

							// 上傳相對路徑（例如 Upload/）
							$upload_folder = isset($upload_folder) ? rtrim((string)$upload_folder, "/\\") . '/' : 'Upload/';

							// 預設圖
							$displayImgUrl = safe_href((string)($web_root . 'images/default/default_fb.jpg'));

							// 取首圖（webp 優先）
							$sqlImg = "SELECT Forder, Photo1 FROM dbclass1_img WHERE Class1_PKey = :Class1_PKey AND Photo1 <> '' AND Sort = 1 ORDER BY Sort LIMIT 1";
							$rs1    = new recordset($sqlImg, ['Class1_PKey' => (int)$rs->field('PKey')]);

							if (($SQL_Error = $rs1->getErrorMessage())) {
								$result = sql_error($sqlImg . PHP_EOL . array_to_string(['Class1_PKey' => (int)$rs->field('PKey')]), $SQL_Error, basename(__FILE__), 'system');
								echo '<pre>', e(print_r($result, true)), '</pre>';
								exit;
							}
							if (!$rs1->eof) {
								$folder = preg_replace('/[^A-Za-z0-9_\/-]/', '', (string)$rs1->field('Forder'));
								$photo  = basename((string)$rs1->field('Photo1'));

								$relPath  = $upload_folder . $folder . '/' . $photo;            // 例：Upload/news/xxx.jpg
								$webpPath = preg_replace('/\.[^.]+$/i', '.webp', $relPath);    // 例：Upload/news/xxx.webp

								// 檢查實體檔案是否存在（如需，請依你的環境改用絕對路徑判斷）
								$useRel = is_file($webpPath) ? $webpPath : (is_file($relPath) ? $relPath : null);
								if ($useRel !== null) {
									$displayImgUrl = safe_href((string)($web_root . ltrim($useRel, '/')));
								}
							}
							$rs1->close();
						?>
						<div class="swiper-slide">
							<a href="<?php echo e_attr($link_url)?>" class="imgCardList__item" title="<?php echo e_attr($titleText)?>" >
								<figure class="imgCard__pic">
									<img src="<?php echo e_attr($displayImgUrl); ?>" alt="<?php echo e_attr($titleText)?>" class="img-fluid coverPic" loading="lazy">
								</figure>
								<div class="imgCard__info">
									<h4 class="title"><?php echo e_attr($titleText)?></h4>
								</div>
							</a>
						</div>
						<?php
						$rs->movenext();
						}
						$rs->close();
						?>
					</div>
					<div class="swiper-button">
						<div class="swiper-button-prev" aria-label="上一張"><span class="txt">Prev</span></div>
						<div class="swiper-pagination"></div>
						<div class="swiper-button-next" aria-label="下一張"><span class="txt">Next</span></div>
					</div>
				</div>
			</div>
		</section>

		<!-- 熱門地區 -->
		<section class="blockHeight blockHeight--ixHot">
			<div class="">
				<h2 class="mainTitle">
					<span class="mainTitle__en wow fadeInUp">Hot Areas</span>
					<span class="mainTitle__mj wow fadeInUp">熱門地區</span>
				</h2>
				<div class="mapWrap">
					<div class="mapList">
						<?php $location = [
							["title" => "加拿大地區", "class" => "--ca"],
							["title" => "美國地區",   "class" => "--usa"],
							["title" => "歐洲地區",   "class" => "--eu"],
							["title" => "中東地區",   "class" => "--me"],
							["title" => "東南亞地區", "class" => "--sea"]
						]; ?>
						<?php for ($i = 0; $i < count($location); $i++) { ?>
							<div class="mapList__item <?php echo $location[$i]["class"]; ?>">
								<div class="icon wow fadeInDown" data-wow-delay="<?php echo $i * 0.1; ?>s"></div>
								<div class="info wow fadeInRight" data-wow-delay="<?php echo $i * 0.1; ?>s"><?php echo $location[$i]["title"]; ?></div>
							</div>
						<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- 服務流程 -->
		<section class="blockHeight blockHeight--ixProcess">
			<div class="ixProcess__inner">
				<div class="container">
					<h2 class="mainTitle">
						<span class="mainTitle__en wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixProcess_en"]; ?></span>
						<span class="mainTitle__mj wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixProcess_title"]; ?></span>
					</h2>
					<div class="ixProcessList">
						<?php for ($i = 0; $i < 4; $i++) { ?>
							<div class="ixProcessList__item wow fadeInLeft" data-wow-delay="<?php echo $i * 0.1; ?>s">
								<div class="ixProcessList__item__num">
									<span class="txt">0<?php echo $i + 1; ?></span>
								</div>
								<figure class="ixProcessList__item__pic">
									<img src="<?php echo $web_url; ?>images/index/process<?php echo $i + 1; ?>.svg" alt="<?php echo $lang_text["index"][$this_lang]["ixProcess".($i + 1)]; ?>" class="img-fluid">
								</figure>
								<div class="ixProcessList__item__content">
									<div class="ixProcessList__item__title wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixProcess".($i + 1)."_1"]; ?></div>
									<div class="ixProcessList__item__txt wow fadeInUp"><?php echo $lang_text["index"][$this_lang]["ixProcess".($i + 1)."_2"]; ?></div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</section>

		<!-- 最新消息 -->
		<section class="blockHeight blockHeight--ixNews">
			<div class="container">
				<h2 class="mainTitle">
					<span class="mainTitle__en wow fadeInUp"><?php echo $lang_text["p4"][$this_lang]["p4_en"]; ?></span>
					<span class="mainTitle__mj wow fadeInUp"><?php echo $lang_text["p4"][$this_lang]["p4"]; ?></span>
				</h2>

				<div class="swiper ixNews imgCardList wow fadeInUp">
					<div class="swiper-wrapper">
						<?php
						$newsListCfg = array_merge(
							require __DIR__ . '/manage/news/_config.php',
							[
								'view'           => 'view_news',
								'class_link'     => 'news',
								'detail_link'    => 'news-detail',
								'publish_window' => true,
							]
						);
						$PDO_Cond = ' Where Module_PKey= :Module_PKey and intLang= :intLang and OpenDate<= :OpenDate and EndDate>= :EndDate and Home= :Home';
						$Cond_Array['Module_PKey'] = frontend_module_pkey_for_page('news.htm');
						$Cond_Array['intLang'] = $this_lang;
						$Cond_Array['OpenDate'] = date('Y-m-d H:i');
						$Cond_Array['EndDate'] = date('Y-m-d').' 23:59:59';
						$Cond_Array['Home'] = 'Yes';
						$sql = 'select * from view_news '.$PDO_Cond.' ORDER BY OpenDate desc';
						$rs  = new recordset($sql,$Cond_Array);
						if (($SQL_Error = $rs->getErrorMessage())) {
							$result = sql_error($sql . PHP_EOL . array_to_string($Cond_Array), $SQL_Error,$WorkFile, 'system');
							echo '<pre>', e(print_r($result, true)), '</pre>';
							exit;
						}
						while(!$rs->eof){
							$titleText = (string)$rs->field('strName');
							$interviewText = (string)$rs->field('Interview');
							$newsRow = [
								'PKey'      => (int)$rs->field('PKey'),
								'show_type' => (int)$rs->field('show_type'),
								'strURL'    => (string)$rs->field('strURL'),
								'strLink'   => (string)$rs->field('strLink'),
							];
							$linkInfo = frontend_show_type_list_link($newsRow, $newsListCfg);
							$link_href = $linkInfo['href'] ?? safe_href((string)($web_root . 'news-detail' . (int)$rs->field('PKey') . '.htm'));
							$link_target = (string)($linkInfo['target'] ?? '_self');
							$link_rel = $linkInfo['rel'] ?? null;

							// 上傳相對路徑（例如 Upload/）
							$upload_folder = isset($upload_folder) ? rtrim((string)$upload_folder, "/\\") . '/' : 'Upload/';

							// 預設圖
							$displayImgUrl = safe_href((string)($web_root . 'images/default/default_fb.jpg'));

							// 取首圖（webp 優先）
							$sqlImg = "SELECT Forder, Photo1 FROM news_img WHERE News_PKey = :News_PKey AND Photo1 <> '' AND Sort = 1 ORDER BY Sort LIMIT 1";
							$rs1    = new recordset($sqlImg, ['News_PKey' => (int)$rs->field('PKey')]);

							if (($SQL_Error = $rs1->getErrorMessage())) {
								$result = sql_error($sqlImg . PHP_EOL . array_to_string(['News_PKey' => (int)$rs->field('PKey')]), $SQL_Error, basename(__FILE__), 'system');
								echo '<pre>', e(print_r($result, true)), '</pre>';
								exit;
							}
							if (!$rs1->eof) {
								$folder = preg_replace('/[^A-Za-z0-9_\/-]/', '', (string)$rs1->field('Forder'));
								$photo  = basename((string)$rs1->field('Photo1'));

								$relPath  = $upload_folder . $folder . '/' . $photo;            // 例：Upload/news/xxx.jpg
								$webpPath = preg_replace('/\.[^.]+$/i', '.webp', $relPath);    // 例：Upload/news/xxx.webp

								// 檢查實體檔案是否存在（如需，請依你的環境改用絕對路徑判斷）
								$useRel = is_file($webpPath) ? $webpPath : (is_file($relPath) ? $relPath : null);
								if ($useRel !== null) {
									$displayImgUrl = safe_href((string)($web_root . ltrim($useRel, '/')));
								}
							}
							$rs1->close();
						?>
						<div class="swiper-slide">
							<a href="<?php echo e_attr((string)$link_href); ?>" class="imgCardList__item"
								<?php if ($link_target !== '_self') { ?> target="<?php echo e_attr($link_target); ?>"<?php } ?>
								<?php if (!empty($link_rel)) { ?> rel="<?php echo e_attr((string)$link_rel); ?>"<?php } ?>>
								<figure class="imgCard__pic">
									<img src="<?php echo e_attr($displayImgUrl); ?>" alt="<?php echo e_attr($titleText); ?>" class="img-fluid coverPic" loading="lazy">
								</figure>
								<div class="imgCard__info">
									<h4 class="title"><?php echo e_attr($titleText); ?></h4>
									<div class="txt"><?php echo e_attr($interviewText); ?></div>
								</div>
							</a>
						</div>
						<?php
						$rs->movenext();
						}
						$rs->close();
						unset($PDO_Cond);
						?>
					</div>
					<div class="swiper-button">
						<div class="swiper-button-prev" aria-label="上一張"><span class="txt">Prev</span></div>
						<div class="swiper-pagination"></div>
						<div class="swiper-button-next" aria-label="下一張"><span class="txt">Next</span></div>
					</div>
				</div>

				<a href="news.htm" class="btnStyle wow fadeInUp"><span class="txt"><?php echo $lang_text["index"][$this_lang]["ixNews_btn"]; ?></span></a>

			</div>
		</section>

	</main>
	<?php require("_footer.php"); ?>
	<?php require("_in_code_bottom.php"); ?>

	<?php echo script_src_tag($web_url . 'js/index-page.js?ver=' . filemtime('js/index-page.js')); ?>

</body>

</html>
