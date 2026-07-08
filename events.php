<?php

/**
 * 前台活動訊息列表（events.htm）
 *
 * 依後台 ad 模組 Class1 分類列出活動；legacy 直接查 view（尚未 frontend_module_set_config）。
 * 內頁：events-detail.php。
 */
$pageName = "p3";
$subPageName = "p3_1";
require("_inc.php");
$Module_PKey = frontend_module_pkey_for_link($lang_text['p3']['p3_page'] ?? 'events.htm');
if ($Module_PKey <= 0) {
    $Module_PKey = frontend_module_pkey_for_link('events.htm');
}
$Module_Name = $Array_MU_Name[$Module_PKey] ?? '';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? $page_link;

// 產生麵包屑
$bread_name = $bread_name ?? [];
$break_link = $break_link ?? [];
array_push($bread_name, e_attr($lang_text['home'][$this_lang] ?? '首頁'));
array_push($break_link,'');
array_push($bread_name, e_attr($Module_Name));
array_push($break_link, $Module_Link);

/** ---------------- 取出預設 Class1 ---------------- */
$Class1 = 0;
$sql = 'SELECT PKey FROM view_dbclass1 WHERE Upload = :Upload AND Module_PKey = :Module_PKey and intLang= :intLang  ORDER BY Sort, dtUDate DESC LIMIT 1';
$rs  = new recordset($sql, ['Upload' => 'Yes', 'Module_PKey' => $Module_PKey, 'intLang'=>$this_lang]);
if (($SQL_Error = $rs->getErrorMessage())) {
	$result = sql_error($sql . PHP_EOL . array_to_string(['Upload' => 'Yes', 'Module_PKey' => $Module_PKey, 'intLang'=>$this_lang]), $SQL_Error, basename(__FILE__), 'system');
	echo '<pre>', e(print_r($result, true)), '</pre>';
	exit;
}
if (!$rs->eof) {
    $Class1 = (int)$rs->field('PKey');
}
$rs->close();

// QueryString 指定 Class1（整數化）
if (isset($filter_array['Class1']) && ctype_digit((string)$filter_array['Class1'])) {
    $Class1 = (int)$filter_array['Class1'];
}

/** ---------------- 組查詢條件（參數化） ---------------- */
$PDO_Cond   = ' WHERE Upload = :Upload AND Module_PKey = :Module_PKey and intLang= :intLang ';
$Cond_Array = ['Upload' => 'Yes', 'Module_PKey' => $Module_PKey, 'intLang'=>$this_lang];

// 取得 Class1 名稱、加入條件
$Class1_Name = '';
if ($Class1 > 0) {
	$sql = 'SELECT PKey, strName FROM view_dbclass1 WHERE PKey = :PKey ';
    $rs = new recordset($sql, ['PKey' => $Class1]);
	if (($SQL_Error = $rs->getErrorMessage())) {
		$result = sql_error($sql . PHP_EOL . array_to_string(['PKey' => $Class1]), $SQL_Error, basename(__FILE__), 'system');
		echo '<pre>', e(print_r($result, true)), '</pre>';
		exit;
	}
    if (!$rs->eof) {
        $Class1_Name = (string)$rs->field('strName');
        $PDO_Cond   .= ' AND Class1_PKey = :Class1_PKey';
        $Cond_Array['Class1_PKey'] = (int)$rs->field('PKey');
		array_push($bread_name, e_attr($Class1_Name));
		array_push($break_link, 'events' . (int)$rs->field('PKey') . '.htm');
    }
    $rs->close();
}

/** ---------------- 總筆數 / 分頁 ---------------- */
// 與下方查詢一致，統一使用 view_paper
$rs    = new recordset('SELECT COUNT(PKey) AS Total FROM view_paper ' . $PDO_Cond, $Cond_Array);
$Total = (int)$rs->field('Total');
$rs->close();

$tPageSize  = 12;
$tPageTotal = max(1, (int)ceil($Total / $tPageSize));
$tPage      = (isset($filter_array['Page']) && ctype_digit((string)$filter_array['Page'])) ? (int)$filter_array['Page'] : 1;
if ($tPage < 1) $tPage = 1;
if ($tPage > $tPageTotal) $tPage = $tPageTotal;
$offset = ($tPage - 1) * $tPageSize;

/** ---------------- SEO 麵包屑（JSON-LD 結構） ---------------- */
// 構建 JSON-LD 時，不要先 e()，改用 json_encode 輸出（避免 XSS/注入）
$Elements = [];
for ($i = 0; $i < count($bread_name); $i++) {
	$position = $i + 1;
	$itemUrl  = safe_href((string)($web_url . ($break_link[$i] ?? '')));
	// 反向取原值（未 e()），避免雙重轉義；若上面先 e() 了，改用原始值：
	$nameRaw = isset($bread_name[$i]) ? (string)$bread_name[$i] : '';
	// 若 $bread_name 內元素已 e()，這裡簡單還原（保守做法：移除 HTML 標籤；如你能提供原始未 e() 值更好）
	$nameRaw = strip_tags($nameRaw);
	$Elements[] = [
		'@type'    => 'ListItem',
		'position' => $position,
		'item'     => $itemUrl,
		'name'     => $nameRaw
	];
}
$ldjson = [
	"@context" => "http://schema.org",
	"@type"    => "BreadcrumbList",
	"itemListElement" => $Elements
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
            <h2 class="mainTitle">
                <span class="mainTitle__en wow fadeInUp"><?php echo $lang_text[$pageName][$this_lang][$pageName.'_en']; ?></span>
                <span class="mainTitle__mj wow fadeInUp"><?php echo $lang_text[$pageName][$this_lang][$subPageName]; ?></span>
            </h2>
            <div class="imgCardList imgCardList--pg --events">
                <?php
				// 文章清單（參數化 + LIMIT 整數）
				$sql = 'SELECT * FROM view_paper ' . $PDO_Cond . ' ORDER BY Sort, dtUDate DESC LIMIT ' . (int)$offset . ',' . (int)$tPageSize;
				$rs  = new recordset($sql, $Cond_Array);

				if (($SQL_Error = $rs->getErrorMessage())) {
					$result = sql_error($sql . PHP_EOL . array_to_string($Cond_Array), $SQL_Error, basename(__FILE__), 'system');
					echo '<pre>', e(print_r($result, true)), '</pre>';
					exit;
				}

				if ($rs->eof) {
					echo '<p>', e($lang_text["no_data_str"][$this_lang]), '</p>';
				}

				// 上傳相對路徑（例如 Upload/）
				$upload_folder = isset($upload_folder) ? rtrim((string)$upload_folder, "/\\") . '/' : 'Upload/';

				while (!$rs->eof) {
					$i++;
					// 預設圖
					$displayImgUrl = safe_href((string)($web_root . 'images/default/default_fb.jpg'));

					// 取首圖（webp 優先）
					$sqlImg = "SELECT Forder, Photo1 FROM paper_img WHERE Paper_PKey = :Paper_PKey AND Photo1 <> '' AND Sort = 1 ORDER BY Sort LIMIT 1";
					$rs1    = new recordset($sqlImg, ['Paper_PKey' => (int)$rs->field('PKey')]);

					if (($SQL_Error = $rs1->getErrorMessage())) {
						$result = sql_error($sqlImg . PHP_EOL . array_to_string(['Paper_PKey' => (int)$rs->field('PKey')]), $SQL_Error, basename(__FILE__), 'system');
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

					// 文字欄位
					$title     = (string)$rs->field('strName');
					$strDate   = date_en((string)$rs->field('dtUDate'),2);
					$interview = (string)$rs->field('Interview');

					// 詳細頁連結（站內相對）
					$link_href = 'events-detail' . (int)$rs->field('PKey') . '.htm';
				?>
                <a href="<?php echo e_attr($link_href); ?>" class="imgCardList__item">
                    <figure class="imgCard__pic">
                        <img src="<?php echo e_attr($displayImgUrl); ?>" alt="<?php echo e_attr($title); ?>" class="img-fluid coverPic" loading="lazy">
                    </figure>
                    <div class="imgCard__info imgCard__info--mb">
                        <h4 class="title title--left"><?php echo e_attr($title); ?></h4>
                    </div>
                    <div class="imgCard__footer">
                        <div class="txt"><?php echo e_attr($interview); ?></div>
                        <span class="dateTxt"><?php echo e_attr($strDate); ?></span>
                    </div>
                </a>
                <?php
				$rs->movenext();
				}
				$rs->close();
				unset($Cond_Array);
				?>
            </div>
            <?php require ("_page_number.php")?>
        </div>
    </section>
</main>

<?php require("_footer.php"); ?>
<?php require("_in_code_bottom.php"); ?>
</body>
</html>
