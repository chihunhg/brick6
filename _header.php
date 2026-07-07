<?php
require('_in_gtm2.php');
?>

<header class="navbar <?php if ($pageName != 'index') { echo 'navbar--pg'; } ?>" aria-label="主要導覽列">
	<div class="container">
		<a class="navbarBrand" href="<?php echo e_attr($web_root); ?>" title="<?php echo e_attr($pageTitle2); ?>">
			<h1 class="logo">
				<figure>
					<img src="<?php echo e_attr($web_root); ?>images/all/logo.png" alt="<?php echo e_attr($pageTitle2); ?>">
				</figure>
			</h1>
		</a>
		<button type="button" class="navbarToggle" title="開合選單" data-menu-toggle="navbarNav" data-menu-parent="navbar"
			aria-expanded="false" aria-controls="navbarNav" aria-label="開合主選單">
			<span></span><span></span><span></span>
		</button>
		<nav class="navbarNav" role="menubar" aria-label="主要導覽列" id="navbarNav">
			<?php
			foreach (frontend_nav_module_items() as $navItem) {
				$modulePKey  = $navItem['pkey'];
				$moduleName  = $navItem['name'];
				$moduleLink  = $navItem['link'];
				$navListHref = frontend_nav_href($moduleLink);
				$isActive    = frontend_nav_is_active($modulePKey);
				$subItems    = frontend_nav_sub_items($modulePKey);
				$subCount    = count($subItems);
				$itemClass   = 'navbarNav__item' . ($isActive ? ' active' : '');
				if ($subCount > 1) {
					$itemClass .= ' dropdown';
				}
			?>
			<div class="<?php echo e_attr($itemClass); ?>" role="none">
				<?php if ($subCount > 1) { ?>
				<div class="navLink" data-toggle="dropdown" data-toggle-type="hover"
					title="<?php echo e_attr($moduleName); ?>" role="menuitem" aria-haspopup="true"
					aria-expanded="false"><?php echo e($moduleName); ?></div>
				<ul class="navSub dropdownMenu" role="menu" aria-label="<?php echo e_attr($moduleName); ?>">
					<?php foreach ($subItems as $row) {
						$itemPKey  = crud_row_int($row, 'PKey');
						$titleText = (string)crud_row_val($row, 'strName');
						$subHref   = frontend_nav_sub_href($modulePKey, $moduleLink, $itemPKey);
					?>
					<li class="navSub__item" role="none">
						<a class="navSubLink" href="<?php echo href_attr($subHref); ?>"
							title="<?php echo e_attr($titleText); ?>" role="menuitem"><?php echo e($titleText); ?></a>
					</li>
					<?php } ?>
				</ul>
				<?php } else { ?>
				<a class="navLink" href="<?php echo href_attr($navListHref); ?>"
					title="<?php echo e_attr($moduleName); ?>" role="menuitem"><?php echo e($moduleName); ?></a>
				<?php } ?>
			</div>
			<?php } ?>
		</nav>
		<div class="langBox">
			<?php
			if ($this_lang == '1'){?>
			<a href="<?php echo $web_url?>en/" class="langBox__item">EN</a>
			<?php } else
			if ($this_lang == '2'){?>
			<a href="<?php echo $web_url?>" class="langBox__item">中</a>
			<?php }?>
		</div>
	</div>
</header>
