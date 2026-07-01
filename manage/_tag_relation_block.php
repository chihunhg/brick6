<?php
declare(strict_types=1);
/**
 * 關聯標籤 UI 區塊（news / paper 等模組共用）
 * 需先載入 tag_relation_helpers.php
 */

$detailConfig = is_array($detailConfig ?? null) ? $detailConfig : [];
if ($detailConfig === [] && is_file(__DIR__ . '/news/_config.php')) {
    // fallback unused
}

if (!function_exists('tag_relation_resolve_module_pkey')) {
    require_once dirname(__DIR__) . '/include/tag_relation_helpers.php';
}

$tagRelations = is_array($tagRelations ?? null) ? $tagRelations : [];
$Tag_Total = (int)($Tag_Total ?? count($tagRelations));
$tagModulePKey = tag_relation_resolve_module_pkey($detailConfig);
$showTagRelation = (string)($detailConfig['tag_relation_parent_col'] ?? '') !== ''
    && function_exists('manage_module_show_detail_field')
    && manage_module_show_detail_field('tag');
if (!$showTagRelation) {
    return;
}
?>
<div class="formGrid">
    <label class="col--2 inputLabel editView__formLabel" for="tag_query">關聯標籤</label>
    <div class="col--10">
        <div class="link-pd">
            <div class="enter">
                <input type="text" id="tag_query" name="tag_query" class="formInput"
                    placeholder="請輸入標籤名稱，選取後完成新增。"
                    style="max-width:50%" autocomplete="off">
            </div>
            <div class="box">
                <ul id="tag_item_list">
                <?php
                $relIdx = 0;
                foreach ($tagRelations as $rel) {
                    $relIdx++;
                    $rowPKey = (int)($rel['rowPKey'] ?? 0);
                    $targetPKey = (int)($rel['targetPKey'] ?? 0);
                    $relName = (string)($rel['strName'] ?? '');
                ?>
                    <li id="tag_item_<?php echo $relIdx; ?>">
                        <button type="button" class="link-pd__tag"
                            data-manage-action="tag-relation-remove"
                            data-relation-index="<?php echo $relIdx; ?>"><?php echo e($relName); ?></button>
                        <input name="TagRowPKey<?php echo $relIdx; ?>" type="hidden"
                            id="TagRowPKey<?php echo $relIdx; ?>" value="<?php echo $rowPKey; ?>" />
                        <input name="Tag_Name<?php echo $relIdx; ?>"
                            id="Tag_Name<?php echo $relIdx; ?>" type="hidden"
                            value="<?php echo e($relName); ?>" />
                        <input name="Tag<?php echo $relIdx; ?>"
                            id="Tag<?php echo $relIdx; ?>" type="hidden"
                            value="<?php echo $targetPKey; ?>" />
                    </li>
                <?php } ?>
                </ul>
                <input name="Tag_Total" type="hidden" id="Tag_Total"
                    value="<?php echo $relIdx; ?>" />
            </div>
        </div>
        <p class="notes" style="margin-top:0.5rem">僅能關聯已上架的標籤；儲存表單後寫入資料庫。</p>
    </div>
</div>
