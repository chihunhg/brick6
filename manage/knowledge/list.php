<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$listCsrfKey = 'knowledge_list';
$table_name  = 'knowledge';
$PKName      = 'PKey';
$FKName      = 'Knowledge_PKey';

$uploadBase = crud_upload_base();
$upload_foder = $upload_foder ?? $uploadBase;

$crud_cfg = crud_cfg($table_name, $FKName, ['upload_base' => $uploadBase]);
crud_process_list_actions($crud_cfg, static function (array $ids) use ($table_name, $PKName): void {
    if ($ids === []) {
        return;
    }
    $ph = [];
    $params = [];
    foreach ($ids as $i => $id) {
        $k = 'id' . $i;
        $ph[] = ':' . $k;
        $params[$k] = $id;
    }
    $sqlChk = 'SELECT ' . $PKName . ' FROM ' . $table_name
        . ' WHERE ' . $PKName . ' IN (' . implode(',', $ph) . ') AND intSource=1';
    $locked = crud_fetch_all($sqlChk, $params);
    if ($locked !== []) {
        manage_alert_script('資料來源為「天矽」的項目不可刪除', null, true);
    }
});

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

$Layer  = (int)($Layer ?? 1);
$Class1 = (int)($Class1 ?? 0);
$Class2 = (int)($Class2 ?? 0);
$Class3 = (int)($Class3 ?? 0);
$Class4 = (int)($Class4 ?? 0);

[$PDO_Cond, $Cond_Array] = crud_module_where();
crud_list_apply_class_filters(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    $Layer,
    $Class1,
    $Class2,
    $Class3,
    $Class4
);

$kwPlaceholder = '請輸入標題搜尋';
$Keywords = crud_list_apply_keyword_search(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    'strName',
    $kwPlaceholder,
    [
        'table' => $table_name,
        'pk' => $PKName,
        'msg_table' => 'knowledge_msg',
        'msg_fk' => $FKName,
    ],
);
if ($Keywords === '') {
    $Keywords = $kwPlaceholder;
}

$Total = crud_fetch_scalar(
    "SELECT COUNT({$PKName}) AS Total FROM {$table_name} {$PDO_Cond}",
    $Cond_Array,
    'Total'
);
$tPageSize = crud_list_page_size($filter_array ?? [], 15);
['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = crud_paginate(
    $Total,
    $tPageSize,
    $filter_array['Page'] ?? null
);

$sql = "SELECT * FROM {$table_name} {$PDO_Cond} ORDER BY Sort ASC, dtUDate DESC LIMIT "
    . (int)$tPageSize . ' OFFSET ' . (int)$offset;
$listRows = crud_fetch_all($sql, $Cond_Array);

$rowPKeys = array_map(static fn(array $r): int => (int)($r['PKey'] ?? 0), $listRows);
$class1Names = [];
$contentByKey = [];
$fileByKey = [];

if ($rowPKeys !== []) {
    $inPh = [];
    $inParams = [];
    foreach ($rowPKeys as $idx => $id) {
        $k = 'pk' . $idx;
        $inPh[] = ':' . $k;
        $inParams[$k] = $id;
    }
    $inClause = implode(',', $inPh);

    if ($Layer > 1 && function_exists('chkTable') && chkTable('dbclass1')) {
        $class1Ids = array_unique(array_filter(array_map(
            static fn(array $r): int => (int)($r['Class1_PKey'] ?? 0),
            $listRows
        )));
        if ($class1Ids !== []) {
            $cPh = [];
            $cParams = [];
            foreach ($class1Ids as $idx => $cid) {
                $ck = 'c' . $idx;
                $cPh[] = ':' . $ck;
                $cParams[$ck] = $cid;
            }
            $cRows = crud_fetch_all(
                'SELECT PKey, strName FROM dbclass1 WHERE PKey IN (' . implode(',', $cPh) . ')',
                $cParams
            );
            foreach ($cRows as $cRow) {
                $class1Names[(int)$cRow['PKey']] = (string)($cRow['strName'] ?? '');
            }
        }
    }

    $msgRows = crud_fetch_all(
        "SELECT Knowledge_PKey, Contents FROM knowledge_msg WHERE Knowledge_PKey IN ({$inClause}) ORDER BY Sort ASC",
        $inParams
    );
    foreach ($msgRows as $msgRow) {
        $kid = (int)($msgRow['Knowledge_PKey'] ?? 0);
        if ($kid > 0 && !isset($contentByKey[$kid])) {
            $contentByKey[$kid] = (string)($msgRow['Contents'] ?? '');
        }
    }

    $imgRows = crud_fetch_all(
        "SELECT Knowledge_PKey, Forder, Photo1 FROM knowledge_img WHERE Knowledge_PKey IN ({$inClause}) AND Photo1 <> '' ORDER BY Sort ASC",
        $inParams
    );
    foreach ($imgRows as $imgRow) {
        $kid = (int)($imgRow['Knowledge_PKey'] ?? 0);
        if ($kid > 0 && !isset($fileByKey[$kid])) {
            $path = $uploadBase . ($imgRow['Forder'] ?? '') . '/' . ($imgRow['Photo1'] ?? '');
            if (is_file($path)) {
                $fileByKey[$kid] = $path;
            }
        }
    }
}

$i = 0;

/** 列表開合欄：true 顯示 */
$list_show_expand_row = $list_show_expand_row ?? true;
manage_list_expand_enabled($list_show_expand_row);
$listGridProfile = $Layer > 1 ? 'knowledge-cat' : 'knowledge';
$listGridClass = manage_list_grid_class($listGridProfile);

$clearUrl = ($WorkFile ?? 'list.php')
    . '?manNo=' . urlencode((string)($manNo ?? ''))
    . '&subNo=' . urlencode((string)($subNo ?? ''));
?>
<?php require_once '../_layout_head.php'; ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
	<?php require_once '../_breadcrumbs.php'; ?>

	<form action="" method="post" name="form1" id="form1" data-upload-url="_upload.php">
	<div id="view-list">
		<div class="card filterWrap">
			<div class="filterWrap__content">
				<div class="filterWrap__grid">
					<?php $searchAutoSubmit = true; require_once '../_search.php'; ?>
					<div class="inputGroup">
						<label class="inputLabel" for="Keywords">智慧語意搜尋</label>
						<div class="inputWrapper">
							<input type="text" name="Keywords" id="Keywords"
								value="<?php echo e($Keywords); ?>"
								placeholder="<?php echo e($kwPlaceholder); ?>"
								class="formInput"
								data-manage-action="list-search"
								data-form-id="form1"
								data-work-file="<?php echo e($WorkFile ?? ''); ?>"
								data-default-keywords="<?php echo e($kwPlaceholder); ?>">
						</div>
					</div>
				</div>
				<div class="filterWrap__actions">
					<?php if ($Layer > 1) { ?>
					<a href="<?php echo e($clearUrl); ?>" class="btnStyle btnStyle--outline">
						<i class="bi bi-arrow-counterclockwise"></i> 清除
					</a>
					<?php } ?>
					<button type="submit" class="btnStyle --isAnim" name="Submit" value="搜尋">
						<i class="bi bi-search"></i> 搜尋
					</button>
				</div>
			</div>
		</div>

		<div class="card">
			<?php require_once '../_select.php'; ?>

			<div class="tableHeader <?php echo e($listGridClass); ?>">
				<?php if (manage_list_expand_enabled()) { ?>
				<div class="textCenter">開合</div>
				<?php } ?>
				<div class="textCenter">選取</div>
				<?php if ($Layer > 1) { ?>
				<div><?php echo e($Class_Name[1] ?? '分類'); ?></div>
				<?php } ?>
				<div class="textCenter">順序</div>
				<div>標題／內容</div>
				<div class="textCenter">檔案</div>
				<div class="textCenter">修改日期</div>
				<div class="textCenter">操作</div>
			</div>

			<div class="tableRow">
				<?php
				if ($listRows === []) {
					echo '<p class="listEmpty">暫無資料</p>';
				}
				foreach ($listRows as $row) {
					$i++;
					$rowPKey = (int)($row['PKey'] ?? 0);
					$rowSort = (int)($row['Sort'] ?? 0);
					$rowName = (string)($row['strName'] ?? '');
					$isTiansi = ((int)($row['intSource'] ?? 0) === 1);
					$chkDisabled = $isTiansi ? ' disabled="disabled"' : '';

					$c1 = (int)($row['Class1_PKey'] ?? 0);
					$class1Label = $class1Names[$c1] ?? '';
					$contents = $contentByKey[$rowPKey] ?? '';
					$fileUrl = $fileByKey[$rowPKey] ?? '';
					if ($fileUrl === '' && (int)($row['intLink'] ?? 0) === 1) {
						$url = (string)($row['strLink'] ?? '');
						if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
							$fileUrl = $url;
						}
					}
				?>
				<div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
					<div class="tableRow__data <?php echo e($listGridClass); ?>">
						<?php if (manage_list_expand_enabled()) { ?>
						<div class="flex flex--jtCenter">
							<button type="button" data-manage-action="expand-row"
								data-row-id="<?php echo $rowPKey; ?>"
								class="tableRow__expandBtn" aria-label="展開詳細">
								<i class="bi bi-chevron-down"></i>
							</button>
						</div>
						<?php } ?>
						<div class="flex flex--jtCenter">
							<label class="checkboxWrapper">
								<input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>"
									class="customCheckbox"<?php echo $chkDisabled; ?>>
							</label>
						</div>
						<?php if ($Layer > 1) { ?>
						<div><?php echo e($class1Label); ?></div>
						<?php } ?>
						<div class="flex flex--jtCenter">
							<input type="text" name="Sort<?php echo $i; ?>" id="Sort<?php echo $i; ?>"
								value="<?php echo $rowSort; ?>" maxlength="4"
								class="tableRow__sortInput" size="4">
							<input name="PKey<?php echo $i; ?>" type="hidden" id="PKey<?php echo $i; ?>"
								value="<?php echo $rowPKey; ?>">
							<input name="O_Sort<?php echo $i; ?>" type="hidden" id="O_Sort<?php echo $i; ?>"
								value="<?php echo $rowSort; ?>">
						</div>
						<div>
							<div class="knowledge_tt">標題：<?php echo e($rowName); ?></div>
						</div>
						<div class="textCenter">
							<?php if ($fileUrl !== '') { ?>
							<a href="<?php echo e($fileUrl); ?>" target="_blank" rel="noopener noreferrer"
								class="btnStyle btnStyle--sm btnStyle--outline">檔案</a>
							<?php } ?>
						</div>
						<div class="textCenter">
							<span class="dateSpan"><?php echo Date_EN($row['dtUDate'] ?? '', 0); ?></span>
						</div>
						<div class="flex flex--jtCenter gap--2">
							<button type="button" class="btnIcon" title="複製"
								data-manage-action="manage-copy"
								data-page="add.php"
								data-pkey="<?php echo $rowPKey; ?>">
								<i class="bi bi-copy"></i>
							</button>
							<button type="button" class="btnStyle btnStyle--sm btnStyle--outline"
								data-manage-action="manage-update"
								data-page="update.php"
								data-pkey="<?php echo $rowPKey; ?>">
								<i class="bi bi-pencil-square"></i> 編輯
							</button>
						</div>
					</div>
					<?php if (manage_list_expand_enabled()) { ?>
					<div id="detail-<?php echo $rowPKey; ?>" class="tableRow__detail is-collapsed">
						<div class="tableRow__detail">
							<strong>編號：</strong><?php echo $rowPKey; ?>
							<?php if ($Layer > 1 && $c1 > 0) { ?>
							&nbsp;|&nbsp;<strong><?php echo e($Class_Name[1] ?? '分類'); ?>：</strong><?php echo e($class1Label); ?>
							<?php } ?>
							<?php if ($contents !== '') { ?>
							<div class="knowledge__detailContent cke_editable">
								<?php echo manage_render_ckeditor_html($contents); ?>
							</div>
							<?php } ?>
						</div>
					</div>
					<?php } ?>
				</div>
				<?php } ?>
			</div>

			<?php
			echo hiddenText('csrf_token', e($csrf_token)) . PHP_EOL;
			echo hiddenNumeric('manNo', $manNo ?? '') . PHP_EOL;
			echo hiddenNumeric('subNo', $subNo ?? '') . PHP_EOL;
			echo hiddenNumeric('Total', $i) . PHP_EOL;
			echo hiddenNumeric('PKey', $PKey ?? '') . PHP_EOL;
			echo hiddenNumeric('Page', $tPage) . PHP_EOL;
			echo hiddenNumeric('PageSize', $tPageSize) . PHP_EOL;
			?>

			<?php if (file_exists(__DIR__ . '/../_page.php')) {
				require_once __DIR__ . '/../_page.php';
			} ?>
		</div>
	</div>

	<div class="notes notes--lg">
		<div class="notes__header">
			<i class="bi bi-info-circle notes__icon"></i> 系統備註
		</div>
		<ul class="notes__list">
			<li>
				顯示順序依「<?php echo e($Class_Name[1] ?? '分類'); ?>順序」由小至大
				→「筆記順序」由小至大 →「標題」由小至大。
			</li>
			<li>複製功能僅能複製文字，圖片需重新上傳。</li>
			<li>資料來源為「天矽」之項目無法刪除及編輯。</li>
		</ul>
	</div>
	<div class="notes__spacer"></div>
	</form>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
