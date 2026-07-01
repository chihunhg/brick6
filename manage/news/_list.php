<?php
declare(strict_types=1);
// _list.php — news 列表（欄位對應舊版 list：順序、分類、標題、刊登/下架日期、首頁、預覽、複製、編輯）

if (!isset($listRows) || !is_array($listRows)) {
    $listRows = [];
}

if (!isset($detailConfig) && is_file(__DIR__ . '/_config.php')) {
    $detailConfig = require __DIR__ . '/_config.php';
}
if (!function_exists('news_show_type_label') && is_file(__DIR__ . '/_form_data.php')) {
    require_once __DIR__ . '/_form_data.php';
}
require_once __DIR__ . '/../_list_lang_bootstrap.php';

$Layer = (int)($Layer ?? 1);
$PKName = (string)($PKName ?? 'PKey');
$listRowOffset = (int)($offset ?? 0);
$showHomeColumn = manage_module_show_detail_field('home');
$hasShowTypeCol = (bool)($detailConfig['list_show_type'] ?? true);

$listShowLangColumn = (bool)($listShowLangColumn ?? false);
$listLangMap = is_array($listLangMap ?? null) ? $listLangMap : [];

$gridClass = manage_list_grid_class('news-l' . max(1, min(5, $Layer)));
if ($showHomeColumn) {
    $gridClass .= ' tableGrid--has-home';
}
if ($hasShowTypeCol) {
    $gridClass .= ' tableGrid--has-show-type';
}
$gridClass = manage_list_grid_with_lang($gridClass, $listShowLangColumn);

$classNameCache = [];
$getClassName = static function (int $level, int $pkey) use (&$classNameCache): string {
    if ($pkey <= 0) {
        return '';
    }
    $key = $level . '#' . $pkey;
    if (isset($classNameCache[$key])) {
        return $classNameCache[$key];
    }
    $table = crud_class_table_name($level);
    if ($table === null) {
        return $classNameCache[$key] = '';
    }
    $sql = "SELECT strName FROM {$table} WHERE PKey = :PKey";
    $rows = crud_fetch_all($sql, ['PKey' => $pkey]);
    $name = isset($rows[0]['strName']) ? (string)$rows[0]['strName'] : '';
    return $classNameCache[$key] = $name;
};

$formatListEndDate = static function (array $row): string {
    if ((string)($row['NoEndDate'] ?? '0') === '0') {
        return '無';
    }
    return Date_EN($row['EndDate'] ?? '', 1);
};

$webRoot = rtrim((string)($web_root ?? ''), '/');
?>
<div class="tableHeader <?php echo e($gridClass); ?>">
    <?php if (manage_list_expand_enabled()) { ?>
    <div class="textCenter">開合</div>
    <?php } ?>
    <div class="textCenter">選取</div>
    <div class="textCenter">順序</div>
    <?php for ($lv = 1; $lv <= 4; $lv++) {
        if ($Layer > $lv) { ?>
    <div><?php echo e($Class_Name[$lv] ?? ('分類' . $lv)); ?></div>
    <?php }
    } ?>
    <div>標題</div>
    <?php if ($hasShowTypeCol) { ?>
    <div class="textCenter">顯示方式</div>
    <?php } ?>
    <?php manage_list_render_lang_header($listShowLangColumn); ?>
    <div class="textCenter">刊登日期</div>
    <div class="textCenter">下架日期</div>
    <?php if ($showHomeColumn) { ?>
    <div class="textCenter">首頁呈現</div>
    <?php } ?>
    <div class="textCenter listPreviewCol">預覽<?php echo $remark_view3 ?? ''; ?></div>
    <div class="textCenter">操作</div>
</div>

<div class="tableRow">
<?php
if ($listRows === []) {
    echo '<p class="listEmpty">暫無資料</p>';
}
foreach ($listRows as $row) {
    $i++;
    $rowPKey = (int)($row[$PKName] ?? $row['PKey'] ?? 0);
    $rowName = (string)($row['strName'] ?? '');
    $homeYes = (($row['Home'] ?? '') === 'Yes');
    $rowShowType = (int)($row['show_type'] ?? 2);
    $rowNum = AddZero($listRowOffset + $i);

    $c1 = (int)($row['Class1_PKey'] ?? 0);
    $previewUrl = $webRoot !== ''
        ? $webRoot . '/news_detail' . $rowPKey . '_' . $c1 . '.htm'
        : 'news_detail' . $rowPKey . '_' . $c1 . '.htm';
    ?>
    <div class="tableRow__item" data-id="<?php echo $rowPKey; ?>">
        <div class="tableRow__data <?php echo e($gridClass); ?>">
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
                    <input type="checkbox" name="nid[]" value="<?php echo $rowPKey; ?>" class="customCheckbox">
                </label>
            </div>
            <div class="textCenter"><?php echo e($rowNum); ?></div>
            <?php for ($lv = 1; $lv <= 4; $lv++) {
                if ($Layer > $lv) {
                    $cVal = (int)($row['Class' . $lv . '_PKey'] ?? 0);
                    ?>
            <div><?php echo e($getClassName($lv, $cVal)); ?></div>
            <?php }
            } ?>
            <div class="listTitle"><?php echo e($rowName); ?></div>
            <?php if ($hasShowTypeCol) { ?>
            <div class="textCenter"><?php echo news_show_type_label($rowShowType); ?></div>
            <?php } ?>
            <?php manage_list_render_lang_cell($rowPKey, $listShowLangColumn, $listLangMap); ?>
            <div class="textCenter">
                <span class="dateSpan"><?php echo Date_EN($row['OpenDate'] ?? '', 1); ?></span>
            </div>
            <div class="textCenter">
                <span class="dateSpan"><?php echo e($formatListEndDate($row)); ?></span>
            </div>
            <?php if ($showHomeColumn) { ?>
            <div class="textCenter">
                <?php if ($homeYes) { ?>
                <span class="badge notes__badge" title="首頁區塊呈現">首頁</span>
                <?php } else { ?>
                —
                <?php } ?>
            </div>
            <?php } ?>
            <div class="textCenter">
                <a href="<?php echo e_attr($previewUrl); ?>" target="_blank" rel="noopener noreferrer"
                    class="btnStyle btnStyle--sm btnStyle--outline">預覽</a>
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
                    &nbsp;|&nbsp;<strong><?php echo e($Class_Name[1] ?? '分類'); ?>：</strong><?php echo e($getClassName(1, $c1)); ?>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
<?php } ?>
</div>
