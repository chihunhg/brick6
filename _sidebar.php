<?php
declare(strict_types=1);

/**
 * 前台 Class1 側欄選單（列表／內頁共用 partial）
 *
 * 需父頁提供 Module_PKey、Class1；frontend_sidebar_should_show 判斷是否顯示。
 */

$sidebarModulePKey = (int)($Module_PKey ?? 0);
$sidebarClass1 = (int)($Class1 ?? 0);

if ($sidebarModulePKey <= 0 || !frontend_sidebar_should_show($sidebarModulePKey)) {
    return;
}

$sidebarItems = frontend_nav_class1_items($sidebarModulePKey);
if ($sidebarItems === []) {
    return;
}

global $Array_MU_Name;

$sidebarModuleName = (string)($Array_MU_Name[$sidebarModulePKey] ?? '');
$sidebarAllLabel = (string)($listTitleAll ?? ($Module_Name ?? $sidebarModuleName));
if ($sidebarAllLabel === '') {
    $sidebarAllLabel = '全部';
}
$sidebarListHref = frontend_list_href();
$sidebarAllActive = frontend_sidebar_class1_active(0, $sidebarClass1);
?>
<aside class="sidebar sidebar--right" aria-label="<?php echo e_attr($sidebarModuleName !== '' ? $sidebarModuleName . ' 分類' : '分類選單'); ?>">
    <button type="button" class="sideToggle" title="開合選單" data-menu-toggle="sideNavWrap" data-menu-parent="sidebar"
        aria-expanded="false" aria-controls="class1SideNav">
        <span class="sideToggle__txt">分類</span>
    </button>
    <div class="sideNavWrap" id="class1SideNav">
        <?php if ($sidebarModuleName !== '') { ?>
        <p class="sidebar__title"><?php echo e($sidebarModuleName); ?></p>
        <?php } ?>
        <ul class="sideNav">
            <li class="sideNav__item<?php echo $sidebarAllActive ? ' active' : ''; ?>">
                <a href="<?php echo href_attr($sidebarListHref); ?>" class="sideNavLink"
                    title="<?php echo e_attr($sidebarAllLabel); ?>"><?php echo e($sidebarAllLabel); ?></a>
            </li>
            <?php foreach ($sidebarItems as $row) {
                $itemPKey = crud_row_int($row, 'PKey');
                $titleText = (string)crud_row_val($row, 'strName');
                $itemHref = frontend_class1_list_href($itemPKey);
                $isActive = frontend_sidebar_class1_active($itemPKey, $sidebarClass1);
            ?>
            <li class="sideNav__item<?php echo $isActive ? ' active' : ''; ?>">
                <a href="<?php echo href_attr($itemHref); ?>" class="sideNavLink"
                    title="<?php echo e_attr($titleText); ?>"><?php echo e($titleText); ?></a>
            </li>
            <?php } ?>
        </ul>
    </div>
</aside>
