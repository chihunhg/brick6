<?php

if(!empty($Total) && $Total > 0){

?>

<!-- 最多顯示5頁 -->

  <ul class="pagination">

    <?php 	

	//--------先算是哪一區間

	$AreaNo = (int)(($tPage-1)/5);

	$sPage = 1+ (5 * $AreaNo);

	$ePage = 5 + (5 * $AreaNo);	

	?>

	<li class="arrow arrow--prev">

		<!-- 當沒有最底或最頂時，加上class="no"，如下： -->

		<a href="#" data-goto-page="1" data-goto-form="SearchF" class="no" title="第一頁"><i class="bi bi-chevron-double-left"></i></a>

		<?php 

		if ($tPage > 1){

		?>

		<a href="#" data-goto-page="<?php echo (int)$tPage - 1; ?>" data-goto-form="SearchF" class="no" title="上一頁"><i class="bi bi-chevron-left"></i></a>

		<?php 

		}

		?>

    </li>

    <!-- 最多秀5碼 -->

	<?php

	for ($i=$sPage;$i<=$ePage;$i++){

		if($i > $tPageTotal) break;

		$css_page = 'show';

		if ($tPage == $i){

			$css_page = 'show on';

		}

	?>

	<li class="<?php echo $css_page?>"><a href="#" data-goto-page="<?php echo (int)$i; ?>" data-goto-form="SearchF" title="<?php echo (int)$i; ?>"><?php echo (int)$i; ?></a></li>

	<?php } ?>

  

    <li class="arrow arrow--next">

		<?php 

		if ($tPage+1 <=$tPageTotal){

		?>

		<a href="#" data-goto-page="<?php echo (int)$tPage + 1; ?>" data-goto-form="SearchF" title="下一頁"><i class="bi bi-chevron-right"></i></a>

		<?php 

		}

		?>

		<a href="#" data-goto-page="<?php echo (int)$tPageTotal; ?>" data-goto-form="SearchF" title="最底頁"><i class="bi bi-chevron-double-right"></i></a>

    </li>

  </ul>

<form id="SearchF" name="SearchF" method="post" action="">

	<input name="Class1" type="hidden" id="Class1" value="<?php if(!empty($Class1)&&is_numeric($Class1)){echo $Class1;}?>" />

	<input name="Page" type="hidden" id="Page" value="<?php if(!empty($tPage)&&is_numeric($tPage)){echo $tPage;}?>" />

	<input type="hidden" name="csrf" value="<?php if(!empty($CSRF)){echo $CSRF;}?>" />

</form>

<?php 

}

?>

