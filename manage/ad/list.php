<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';

$listCsrfKey = 'ad_list';
$table_name  = (string)($detailConfig['master'] ?? 'dbad');
$PKName      = 'PKey';
$FKName      = (string)($detailConfig['fk'] ?? 'AD_PKey');

$uploadBase = crud_upload_base();
$upload_foder = $upload_foder ?? $uploadBase;

$crud_cfg = crud_cfg($table_name, $FKName, ['upload_base' => $uploadBase]);
crud_process_list_actions($crud_cfg);

crud_csrf_guard_list($listCsrfKey);
$csrf_token = crud_csrf_ensure($listCsrfKey);

if (isset($ModuleNo)) {
    unset($_SESSION['PKey_' . $ModuleNo]);
}

[$PDO_Cond, $Cond_Array] = crud_module_where();
$PDO_Cond .= ' AND intLocal < :intLocalMax';
$Cond_Array['intLocalMax'] = 2;

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

$thumbByKey = [];
if ($listRows !== []) {
    $inPh = [];
    $inParams = [];
    foreach ($listRows as $idx => $row) {
        $id = (int)($row['PKey'] ?? 0);
        if ($id > 0) {
            $k = 'id' . $idx;
            $inPh[] = ':' . $k;
            $inParams[$k] = $id;
        }
    }
    if ($inPh !== []) {
        $imgRows = crud_fetch_all(
            'SELECT AD_PKey, Forder, Photo1 FROM dbad_img WHERE AD_PKey IN (' . implode(',', $inPh) . ')'
            . " AND Photo1 <> '' ORDER BY Sort ASC",
            $inParams
        );
        foreach ($imgRows as $imgRow) {
            $adPk = (int)($imgRow['AD_PKey'] ?? 0);
            if ($adPk <= 0 || isset($thumbByKey[$adPk])) {
                continue;
            }
            $forder = trim((string)($imgRow['Forder'] ?? ''), "/\\");
            $photo  = basename((string)($imgRow['Photo1'] ?? ''));
            if ($photo === '') {
                continue;
            }
            $diskPath = $uploadBase . ($forder !== '' ? $forder . '/' : '') . 'thumb_' . $photo;
            if (is_file($diskPath)) {
                $thumbByKey[$adPk] = '../../Upload/' . ($forder !== '' ? $forder . '/' : '') . 'thumb_' . $photo;
            }
        }
    }
}

$i = 0;
$maxQ = (int)($MaxQ ?? 0);
$showListAdd = ($maxQ <= 0 || $Total < $maxQ);

$list_show_expand_row = $list_show_expand_row ?? false;
manage_list_expand_enabled($list_show_expand_row);
$listGridClass = manage_list_grid_class('ad');
$detailConfig = require __DIR__ . '/_config.php';
require_once __DIR__ . '/../_list_lang_bootstrap.php';
$listGridClass = manage_list_grid_with_lang($listGridClass, (bool)($listShowLangColumn ?? false));
?>
<?php require_once '../_layout_head.php'; ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
	<?php require_once '../_breadcrumbs.php'; ?>

	<form action="" method="post" name="form1" id="form1" data-upload-url="_upload.php">
	<div id="view-list">
		<div class="card">
			<?php require_once '../_select.php'; ?>

			<div class="tableHeader " style="grid-template-columns:50px 50px 80px minmax(100px, 160px) 1fr minmax(68px, 96px) 100px 140px 120px;">
				<?php if (manage_list_expand_enabled()) { ?>
				<div class="textCenter">開合</div>
				<?php } ?>
				<div class="textCenter">拖曳</div>
				<div class="textCenter">選取</div>
				<div class="textCenter">順序</div>
				<div class="textCenter">縮圖</div>
				<div>標題</div>
				<?php manage_list_render_lang_header((bool)($listShowLangColumn ?? false)); ?>
				<div class="textCenter">上下架</div>
				<div class="textCenter">修改日期</div>
				<div class="textCenter">操作</div>
			</div>

			<div class="tableRow drag-list">
				<?php
				if ($listRows === []) {
					echo '<p class="listEmpty">暫無資料</p>';
				}
				foreach ($listRows as $row) {
					$i++;
					$rowPKey = (int)($row['PKey'] ?? 0);
					$rowSort = (int)($row['Sort'] ?? 0);
					$uploadYes = (($row['Upload'] ?? '') === 'Yes');
					$activeClass = $uploadYes ? '--active' : '--inactive';
					$thumbUrl = $thumbByKey[$rowPKey] ?? '';
				?>
				<div class="tableRow__item drag-item" draggable="true" data-id="<?php echo $rowPKey; ?>">
					<div class="tableRow__data" style="grid-template-columns:50px 50px 80px minmax(100px, 160px) 1fr minmax(68px, 96px) 100px 140px 120px;">
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
							<span class="drag-handle">☰</span>
						</div>
						<div class="flex flex--jtCenter">
							<label class="checkboxWrapper">
								<input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>"
									class="customCheckbox">
							</label>
						</div>
						<div class="flex flex--jtCenter">
							<input type="text" name="Sort<?php echo $i; ?>" id="Sort<?php echo $i; ?>"
								value="<?php echo (string)$rowSort; ?>" maxlength="4"
								class="tableRow__sortInput" size="4">
							<input name="PKey<?php echo $i; ?>" type="hidden" id="PKey<?php echo $i; ?>"
								value="<?php echo $rowPKey; ?>">
							<input name="O_Sort<?php echo $i; ?>" type="hidden" id="O_Sort<?php echo $i; ?>"
								value="<?php echo $rowSort; ?>">
						</div>
						<div class="textCenter">
							<?php if ($thumbUrl !== '') { ?>
							<img src="<?php echo e($thumbUrl); ?>?<?php echo time(); ?>" alt="" class="adListThumb">
							<?php } else { ?>
							<span class="adListThumb--empty">無圖</span>
							<?php } ?>
						</div>
						<div>
							<?php echo e((string)($row['strName'] ?? '')); ?>
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
							<strong>編號：</strong><?php echo $rowPKey; ?>
							&nbsp;|&nbsp;
							<strong>順序：</strong><?php echo $rowSort; ?>
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
				require_once '../_page.php';
			} ?>
		</div>
	</div>

	<div class="notes notes--lg">
		<div class="notes__header">
			<i class="bi bi-info-circle notes__icon"></i> 系統備註
		</div>
		<ul class="notes__list">
			<li>首頁形象最多可以上傳 1～<?php echo $maxQ > 0 ? (int)$maxQ : 5; ?> 筆資料。</li>
			<li>無資料或全部下架時，前台區塊不顯示。</li>
			<li>網站前台顯示順序，依照「順序」由小至大排序。</li>
		</ul>
	</div>
	<div class="notes__spacer"></div>
	</form>

	<!-- 拖曳排序的JS -->
	<script>
const list = document.querySelector('.drag-list');
let dragSrcEl = null;

// 💡 核心功能：重新計算並更新畫面上所有輸入框的順序數字
function updateSortInputs() {
    const items = list.querySelectorAll('.drag-item');
    items.forEach((item, index) => {
        const newSortValue = index + 1; 
        const sortInput = item.querySelector('.tableRow__sortInput');
        if (sortInput) {
            sortInput.value = newSortValue;
        }
    });
}

// 1. 動態切換 draggable，防範 input 衝突、並確保能順利拖曳
list.addEventListener('mousedown', (e) => {
    const item = e.target.closest('.drag-item');
    if (!item) return;

    // 只有點擊「☰ 把手」時才允許該列 draggable="true"
    const isHandle = e.target.classList.contains('drag-handle') || e.target.closest('.drag-handle');
    if (isHandle) {
        item.setAttribute('draggable', 'true');
    } else {
        item.removeAttribute('draggable');
    }
});

// 2. 監聽拖曳開始
list.addEventListener('dragstart', (e) => {
    const target = e.target.closest('.drag-item');
    if (!target) return;

    dragSrcEl = target;

    // 💡 終極修正：使用 Promise.resolve().then()（微任務）替代 setTimeout
    // 這能保證在瀏覽器畫出「拖曳虛影圖」的下一幀，立刻 100% 加上 .dragging 樣式
    Promise.resolve().then(() => {
        target.classList.add('dragging');
    });

    // 部分瀏覽器（如 Firefox）必須在 dragstart 寫入 dataTransfer 才能啟動拖曳
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', ''); // 寫入空字串作為啟動鑰匙
    }
});

// 3. 監聽拖曳結束
list.addEventListener('dragend', (e) => {
    // 100% 安全清除畫面上所有的 dragging Class
    const draggingItems = list.querySelectorAll('.drag-item.dragging');
    draggingItems.forEach(item => {
        item.classList.remove('dragging');
        item.removeAttribute('draggable'); // 重設拖曳狀態
    });
    
    dragSrcEl = null;

    // 拖曳結束後，自動重整所有 Sort 輸入框的數字
    updateSortInputs();
});

// 4. 監聽拖曳移動
list.addEventListener('dragover', (e) => {
    e.preventDefault(); // 必須阻止預設行為才能觸發 drop 指針
    
    const draggingItem = list.querySelector('.drag-item.dragging');
    if (!draggingItem) return;

    const siblings = [...list.querySelectorAll('.drag-item:not(.dragging)')];

    const nextSibling = siblings.find(sibling => {
        const rect = sibling.getBoundingClientRect();
        return e.clientY <= rect.top + rect.height / 2;
    });

    if (nextSibling) {
        list.insertBefore(draggingItem, nextSibling);
    } else {
        list.appendChild(draggingItem);
    }
});
		
    </script>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
