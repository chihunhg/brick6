<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

/** ① 模組資料表與刪除鎖定：改 _config.php */
$detailConfig = require __DIR__ . '/_config.php';

$listCsrfKey = 'class2_list';
$table_name  = (string)($detailConfig['master'] ?? 'dbclass2');
$PKName      = 'PKey';
$FKName      = (string)($detailConfig['fk'] ?? 'Class2_PKey');
$childFkChecks = crud_normalize_delete_lock_tables(
    is_array($detailConfig['delete_lock_tables'] ?? null)
        ? $detailConfig['delete_lock_tables']
        : []
);

$uploadBase = crud_upload_base();
$crud_cfg   = crud_cfg($table_name, $FKName, ['upload_base' => $uploadBase]);

crud_process_list_actions($crud_cfg, static function (array $ids) use ($childFkChecks): void {
    $locked = crud_ids_referenced_in_tables($ids, $childFkChecks);
    if ($locked !== []) {
        manage_alert_script('類別底下仍有資料，無法刪除', null, true);
    }
});

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

$Class1 = (int)($Class1 ?? 0);
$Class2 = (int)($Class2 ?? 0);
$Class3 = (int)($Class3 ?? 0);
$Class4 = (int)($Class4 ?? 0);
$searchLayer = 2;

[$PDO_Cond, $Cond_Array] = crud_module_where('t2');
crud_list_apply_class_filters(
    $PDO_Cond,
    $Cond_Array,
    $filter_array ?? [],
    $searchLayer,
    $Class1,
    $Class2,
    $Class3,
    $Class4,
    't2'
);

$kwPlaceholder = '搜尋類別名稱';
$Keywords = crud_list_apply_keyword_search($PDO_Cond, $Cond_Array, $filter_array ?? [], 't2.strName', $kwPlaceholder);
if ($Keywords === '') {
    $Keywords = $kwPlaceholder;
}

$joinClass1 = (function_exists('chkTable') && chkTable('dbclass1'))
    ? ' LEFT JOIN dbclass1 c1 ON c1.PKey = t2.Class1_PKey'
    : '';
$selectClass1 = $joinClass1 !== '' ? ', c1.strName AS class1_name' : ", '' AS class1_name";

$Total = crud_fetch_scalar(
    "SELECT COUNT(t2.{$PKName}) AS Total FROM {$table_name} t2{$joinClass1} {$PDO_Cond}",
    $Cond_Array,
    'Total'
);
$tPageSize = crud_list_page_size($filter_array ?? [], 15);
['tPage' => $tPage, 'tPageTotal' => $tPageTotal, 'offset' => $offset] = crud_paginate(
    $Total,
    $tPageSize,
    $filter_array['Page'] ?? null
);

$sql = 'SELECT t2.*' . $selectClass1
    . " FROM {$table_name} t2{$joinClass1} {$PDO_Cond}"
    . ' ORDER BY t2.Sort ASC, t2.dtUDate DESC LIMIT '
    . (int)$tPageSize . ' OFFSET ' . (int)$offset;
$listRows = crud_fetch_all($sql, $Cond_Array);

$rowIds = array_map(static fn(array $r): int => (int)$r['PKey'], $listRows);
$lockedIds = crud_ids_referenced_in_tables($rowIds, $childFkChecks);

$i = 0;

/** 列表開合欄：true 顯示，預設 false */
$list_show_expand_row = $list_show_expand_row ?? false;
manage_list_expand_enabled($list_show_expand_row);
$listGridClass = manage_list_grid_class('class2');
$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/../_list_lang_bootstrap.php';
$listGridClass = manage_list_grid_with_lang($listGridClass, (bool)($listShowLangColumn ?? false));

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
					<?php require_once '../_search.php'; ?>
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
					<a href="<?php echo e($clearUrl); ?>" class="btnStyle btnStyle--outline">
						<i class="bi bi-arrow-counterclockwise"></i> 清除
					</a>
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
				<div class="textCenter">順序</div>
				<div><?php echo e((string)($Class_Name[1] ?? '上層類別')); ?></div>
				<div>標題</div>
				<?php manage_list_render_lang_header((bool)($listShowLangColumn ?? false)); ?>
				<div class="textCenter">上下架</div>
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
					$rowPKey = (int)$row['PKey'];
					$rowSort = (int)($row['Sort'] ?? 0);
					$uploadYes = (($row['Upload'] ?? '') === 'Yes');
					$activeClass = $uploadYes ? '--active' : '--inactive';
					$isLocked = isset($lockedIds[$rowPKey]);
					$chkDisabled = $isLocked ? ' disabled="disabled"' : '';
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
						<div class="flex flex--jtCenter">
							<input type="text" name="Sort<?php echo $i; ?>" id="Sort<?php echo $i; ?>"
								value="<?php echo $rowSort; ?>" maxlength="4"
								class="tableRow__sortInput" size="4">
							<input name="PKey<?php echo $i; ?>" type="hidden" id="PKey<?php echo $i; ?>"
								value="<?php echo $rowPKey; ?>">
							<input name="O_Sort<?php echo $i; ?>" type="hidden" id="O_Sort<?php echo $i; ?>"
								value="<?php echo $rowSort; ?>">
						</div>
						<div><?php echo e((string)($row['class1_name'] ?? '')); ?></div>
						<div>
							<?php echo e((string)($row['strName'] ?? '')); ?>
							<?php if ($isLocked) { ?>
								<span class="badge notes__badge" title="子表仍有資料">不可刪</span>
							<?php } ?>
						</div>
						<?php manage_list_render_lang_cell($rowPKey, (bool)($listShowLangColumn ?? false), is_array($listLangMap ?? null) ? $listLangMap : []); ?>
						<div class="textCenter">
							<button type="button"
								class="toggleSwitch <?php echo e($activeClass); ?>"
								data-manage-action="toggle-upload"
								data-pkey="<?php echo $rowPKey; ?>"
								data-upload="<?php echo $uploadYes ? 'Yes' : 'No'; ?>"
								data-upload-url="_upload.php"
								aria-pressed="<?php echo $uploadYes ? 'true' : 'false'; ?>"
								aria-label="<?php echo $uploadYes ? '下架' : '上架'; ?>">
								<span class="toggleKnob"></span>
							</button>
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
							<strong>類別編號：</strong><?php echo $rowPKey; ?>
							&nbsp;|&nbsp;
							<strong>順序：</strong><?php echo $rowSort; ?>
							&nbsp;|&nbsp;
							<strong><?php echo e((string)($Class_Name[1] ?? '上層類別')); ?>：</strong><?php echo e((string)($row['class1_name'] ?? '')); ?>
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
			<li>網站前台顯示順序，依照「順序」由小至大排序；順序相同，依照「修改日期」由新至舊排序。</li>
			<li>類別底下仍有子分類或文章等資料時，該類別無法刪除。</li>
			<li>類別項目下架，不等於「<span class="badge notes__badge">文章</span>」項目下架。需至「<span class="badge notes__badge">文章管理</span>」中執行下架。</li>
		</ul>
	</div>
	<div class="notes__spacer"></div>
	</form>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
