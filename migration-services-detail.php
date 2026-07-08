<?php

/**
 * 前台遷移服務內頁（migration-services-detail.htm）
 *
 * 依 PKey 讀取 knowledge 單元內容；legacy 查 view_paper。
 * 列表：migration-services.php。
 */
$pageName = "p2";
$subPageName = "p2_1";
require("_inc.php");

$Module_PKey = frontend_module_pkey_for_page('migration-services.htm');
$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;

// 產生麵包屑
$bread_name = $bread_name ?? [];
$break_link = $break_link ?? [];
array_push($bread_name, e_attr($lang_text['home'][$this_lang] ?? '首頁'));
array_push($break_link,'');
array_push($bread_name, e_attr($Module_Name));
array_push($break_link, $Module_Link);

/* ============== 輸入參數：============== */
$PKey = isset($filter_array['PKey']) && $filter_array['PKey'] !== false && $filter_array['PKey'] !== null ? (int)$filter_array['PKey'] : 0;

/* ============== 查詢主要內容（參數化） ============== */
$PDO_Cond = ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey and intLang= :intLang AND PKey = :PKey';
$Cond_Array = [
    'Module_PKey' => $Module_PKey,
    'intLang' => $this_lang,
    'Upload'      => 'Yes',
    'PKey'        => $PKey,
];

$sql = 'SELECT * FROM view_paper ' . $PDO_Cond;
$rs  = new recordset($sql, $Cond_Array);

// 錯誤處理
if (($SQL_Error = $rs->getErrorMessage())) {
    $result = sql_error($sql . PHP_EOL . array_to_string($Cond_Array), $SQL_Error, basename(__FILE__), 'system');
    echo '<pre>', e(print_r($result, true)), '</pre>';
    exit;
}

if (!$rs->eof) {
    $Paper_PKey   = (int)$rs->field('PKey');
    $Description  = (string)$rs->field('Description');
    $Keywords     = (string)$rs->field('Keywords');
    $Sort         = (int)$rs->field('Sort');
    $Class1       = (int)$rs->field('Class1_PKey');
    $strName      = (string)$rs->field('strName');
    $seoTitle     = frontend_lang_seo_title([
        'Title'   => (string)($rs->field('Title') ?? ''),
        'strName' => $strName,
    ]);
    $Interview    = (string)$rs->field('Interview');
    $Movielink = (string)$rs->field('Movielink');
    $JoinForm      = (string)$rs->field('JoinForm');
    $strDate      = (string)$rs->field('strDate');
    $Location    = (string)$rs->field('Location');
    $Format    = (string)$rs->field('Format');
    $dtUDate      = (string)$rs->field('dtUDate');

	// 取得 Class1 名稱、加入條件
	$Class1_Name = '';
	if ($Class1 > 0) {
		$sql = 'SELECT PKey, strName FROM view_dbclass1 WHERE PKey = :PKey ';
		$rs1 = new recordset($sql, ['PKey' => $Class1]);
		if (($SQL_Error = $rs1->getErrorMessage())) {
			$result = sql_error($sql . PHP_EOL . array_to_string(['PKey' => $Class1]), $SQL_Error, basename(__FILE__), 'system');
			echo '<pre>', e(print_r($result, true)), '</pre>';
			exit;
		}
		if (!$rs1->eof) {
			$Class1_Name = (string)$rs1->field('strName');
			array_push($bread_name, e_attr($Class1_Name));
			array_push($break_link, 'migration-services' . (int)$rs->field('PKey') . '.htm');
		}
		$rs1->close();
	}
	$Backlink	  = 'migration-services'.(int)$rs->field('Class1_PKey').'.htm';

	array_push($bread_name,e($strName));
	array_push($break_link,$page_link);

    // 內容區塊
    $Contents = [];
    $Show     = [];
	$sql = 'SELECT * FROM paper_msg WHERE Paper_PKey = :Paper_PKey and intLang = :intLang ORDER BY Sort';
    $rs1 = new recordset($sql , ['Paper_PKey' => $Paper_PKey,'intLang' => $this_lang]);
	// 錯誤處理
	if (($SQL_Error = $rs1->getErrorMessage())) {
		$result = sql_error($sql . PHP_EOL . array_to_string(['Paper_PKey' => $Paper_PKey,'intLang' => $this_lang]), $SQL_Error, basename(__FILE__), 'system');
		echo '<pre>', e(print_r($result, true)), '</pre>';
		exit;
	}
    while (!$rs1->eof) {
        $i = (int)$rs1->field('Sort');
        $Contents[$i] = rwd_table((string)$rs1->field('Contents'), 1);
        $Show[$i]     = (int)$rs1->field('isShow');
        $rs1->movenext();
    }
    $rs1->close();
    // 圖片（相對路徑 + webp 優先）
    $upload_folder = isset($upload_folder) ? rtrim((string)$upload_folder, "/\\") . '/' : 'Upload/';

    $Photo = [];
	$PhotoM = [];
	$sql = 'SELECT * FROM paper_img WHERE Paper_PKey = :Paper_PKey AND Photo1 <> :Photo1 and Sort > 1 ORDER BY Sort';
    $rs1 = new recordset($sql, ['Paper_PKey' => $Paper_PKey, 'Photo1' => '']);
	// 錯誤處理
	if (($SQL_Error = $rs1->getErrorMessage())) {
		$result = sql_error($sql . PHP_EOL . array_to_string($Cond_Array), $SQL_Error, basename(__FILE__), 'system');
		echo '<pre>', e(print_r($result, true)), '</pre>';
		exit;
	}
    while (!$rs1->eof) {
        $folder = preg_replace('/[^A-Za-z0-9_\/-]/', '', (string)$rs1->field('Forder'));
        $photo  = basename((string)$rs1->field('Photo1'));

        $relPath  = $upload_folder . $folder . '/' . $photo;
        $webpPath = preg_replace('/\.[^.]+$/i', '.webp', $relPath);

        $useRel = null;
        $ext    = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
        if ($ext !== 'gif' && is_file($webpPath)) {
            $useRel = $webpPath;
        } elseif (is_file($relPath)) {
            $useRel = $relPath;
        }

        if ($useRel !== null) {
            $i = (int)$rs1->field('Sort')-1;
            // 直接存成對外 URL，之後輸出就不用再接前綴
            $Photo[$i] = (string)($web_root . ltrim($useRel, '/'));
			$PhotoM[$i] = e_attr((string)$rs1->field('PhotoM'));
        }

        $rs1->movenext();
    }
    $rs1->close();

    $links = [];
	if (function_exists('chkTable') && chkTable('paper_link')) {
		$sql = 'SELECT * FROM paper_link WHERE Paper_PKey = :Paper_PKey and intLang = :intLang ORDER BY Sort';
		$rs1 = new recordset($sql , ['Paper_PKey' => $Paper_PKey, 'intLang' => $this_lang]);

		if (($SQL_Error = $rs1->getErrorMessage())) {
			$result = sql_error($sql . PHP_EOL . array_to_string(['Paper_PKey' => $Paper_PKey, 'intLang' => $this_lang]), $SQL_Error, basename(__FILE__), 'system');
			echo '<pre>', e(print_r($result, true)), '</pre>';
			exit;
		}

		while (!$rs1->eof) {
			$links[] = [
				'title' => e_attr((string)$rs1->field('strName')),
				'url'   => e_attr((string)$rs1->field('strLink'))
			];
			$rs1->movenext();
		}
		$rs1->close();
	}

} else {
    // 找不到資料：alert 後導回列表頁
    $listUrl = safe_href('migration-services.htm');        // 產出安全 href（相對/站內）
    $listUrlAttr = e_attr($listUrl);           // HTML 屬性轉義
    $msg = $lang_text["warn_data_not_found"][$this_lang];    // 可依語系替換

    // 直接輸出最小 HTML，含 noscript 後援
    echo '<!DOCTYPE html><html lang="zh-Hant"><head>';
    echo '<meta charset="utf-8">';
    echo '<meta http-equiv="refresh" content="0;url=' . $listUrlAttr . '">'; // 無 JS 後援
    echo '<title>' . e($msg) . '</title>';
    echo '</head><body>';

    // 以 CSP nonce 輸出腳本（sec.php 提供 script_open()/script_close()）
    echo script_open();
    echo 'alert(' . js_str($msg) . ');';
    echo 'location.replace(' . js_str($listUrl) . ');';
    echo script_close();

    // noscript 提示
    echo '<noscript><p>' . e($msg) . '：<a href="' . $listUrlAttr . '">請點此返回</a></p></noscript>';

    echo '</body></html>';
    exit;
}

$ytSrc = youtube_embed_src($Movielink);

/** ---------------- SEO 麵包屑（JSON-LD 結構） ---------------- */
// $page_link, $web_root 應來自 _inc.php 且為經過驗證的 URL
$canonical = $web_root.$page_link;
$Elements = array();
for($i=0;$i<count($bread_name);$i++){
	$n = $i+1;
	$array = [
		'@type'=> 'ListItem',
		'position'=> strval($n),
		// safe_url 應確保輸出的 URL 是安全的
		'item' => safe_url($web_root.$break_link[$i]),
		// e() 確保麵包屑名稱的內容被逸出
		'name' => e($bread_name[$i])
	];
	$Elements[$n] = $array;
}

$ldjson = [
	// URL 內容在 JSON 中是安全的
	"@context"=> "http://schema.org",
	"@type"=> "BreadcrumbList",
	// $Elements 中的 'name' 已經過 e() 處理，這裡輸出 JSON 是安全的
	"itemListElement"=> $Elements
];
?>

<!DOCTYPE html>
<html <?php echo $lang_text["lang"][$this_lang]; ?>>
<head>
<?php require("_in_code_head.php"); ?>
<?php require("_in_javascript.php"); ?>
</head>

<body <?php if ( !empty($bodytxt) ){ echo $bodytxt; } ?>>
<?php require("_header.php"); ?>
<?php require("_banner.php"); ?>

<main class="pgContent">
    <section class="blockHeight blockHeight--news">
        <div class="container">
            <div class="newsDBox">
                <div class="articleTop">
                    <h2 class="articleTt"><?php echo e_attr($strName)?></h2>
                </div>
                 <div class="articleMain">
                    <?php
					for($i=1;$i<7;$i++){
						if(! empty($Contents[$i]) || !empty($Photo[$i])){
							switch($i){
								case 2:
								case 4:
									$css = 'tx01 img-left';
									break;
								case 3:
								case 5:
									$css = 'tx01 img-right';
									break;
								default:
									$css = 'tx01';
									break;
							}
					?>
					<article class="<?php echo $css?>">
						<?php
						if(!empty($Photo[$i])){
						?>
						<figure>
							<img src="<?php echo e_attr((string)$Photo[$i]); ?>" class="img-fluid" loading="lazy" alt="<?php echo e_attr($strName)?>">
						</figure>
						<?php
						}
						if(!empty($Contents[$i])){
						?>
						<div class="text">
							<?php echo frontend_render_html((string)$Contents[$i]); ?>
						</div>
						<?php
						}
						?>
					</article>
					<?php
						}
					}
					?>
                </div>
				<?php if ($ytSrc): ?>
				<div class="vdBox">
					<iframe width="100%" height="100%" src="<?php echo e_attr($ytSrc); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
				</div>
				<?php endif;?>
				<?php if (!empty($links)): ?>
				<div class="linkBox">
						<?php foreach ($links as $item): ?>
							<a href="<?= e_attr($item['url']) ?>" target="_blank" rel="noopener noreferrer" class="linkBox__item">
								<span class="txt"><?= e($item['title']) ?></span>
							</a>
						<?php endforeach; ?>
				</div>
				<?php endif; ?>
            </div>
            <div class="btnWrap btnWrap--center">
                <a href="migration-services.htm" class="btnStyle">
                    <span class="txt">回上一頁</span>
                </a>
            </div>
        </div>
    </section>
</main>

<?php require("_footer.php"); ?>
<?php require("_in_code_bottom.php"); ?>
</body>
</html>
