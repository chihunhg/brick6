<?php
declare(strict_types=1);

if (empty($Total) || (int)$Total <= 0) {
    return;
}

$tPage      = max(1, (int)($tPage ?? 1));
$tPageTotal = max(1, (int)($tPageTotal ?? 1));
$tPageSize  = max(1, (int)($tPageSize ?? 15));
$Total      = (int)$Total;

if ($tPage > $tPageTotal) {
    $tPage = $tPageTotal;
}

$offset = isset($offset) ? (int)$offset : (($tPage - 1) * $tPageSize);
$from   = min($Total, $offset + 1);
$to     = min($Total, $offset + $tPageSize);

$formId = 'form1';
$pageSizes = [10, 15, 25, 50];
if (!in_array($tPageSize, $pageSizes, true)) {
    $pageSizes[] = $tPageSize;
    sort($pageSizes);
}

$areaNo = (int)(($tPage - 1) / 10);
$sPage  = 1 + (10 * $areaNo);
$ePage  = min(10 + (10 * $areaNo), $tPageTotal);
?>
<div class="pagination">
    <div class="pagination__info">
        顯示第 <span class="pagination__infoNumber"><?php echo $from; ?></span>
        到 <span class="pagination__infoNumber"><?php echo $to; ?></span> 筆，
        共 <span class="pagination__infoNumber"><?php echo $Total; ?></span> 筆
        （<?php echo $tPage; ?> / <?php echo $tPageTotal; ?> 頁）
    </div>
    <div class="flex flex--itCenter pagination__controls">
        <label class="visually-hidden" for="PageSizeSelect">每頁筆數</label>
        <select id="PageSizeSelect" class="formSelect pagination__select"
            data-manage-action="page-size"
            data-form-id="<?php echo e($formId); ?>">
            <?php foreach ($pageSizes as $size) { ?>
            <option value="<?php echo $size; ?>"<?php echo ($tPageSize === $size) ? ' selected' : ''; ?>>
                <?php echo $size; ?> 筆/頁
            </option>
            <?php } ?>
        </select>
        <div class="flex flex--itCenter pagination__nav">
            <?php if ($tPage > 1) { ?>
            <button type="button" class="btnIcon" title="第一頁"
                data-manage-action="goto-page" data-page="1" data-form-id="<?php echo e($formId); ?>">
                <i class="bi bi-chevron-double-left pagination__icon"></i>
            </button>
            <button type="button" class="btnIcon" title="上一頁"
                data-manage-action="goto-page" data-page="<?php echo $tPage - 1; ?>" data-form-id="<?php echo e($formId); ?>">
                <i class="bi bi-chevron-left pagination__icon"></i>
            </button>
            <?php } else { ?>
            <button type="button" class="btnIcon" disabled title="第一頁">
                <i class="bi bi-chevron-double-left pagination__icon"></i>
            </button>
            <button type="button" class="btnIcon" disabled title="上一頁">
                <i class="bi bi-chevron-left pagination__icon"></i>
            </button>
            <?php } ?>

            <?php if ($tPage > 11) { ?>
            <button type="button" class="btnStyle btnStyle--outline pagination__pageBtn" title="上十頁"
                data-manage-action="goto-page" data-page="<?php echo max(1, $tPage - 10); ?>" data-form-id="<?php echo e($formId); ?>">
                …
            </button>
            <?php } ?>

            <?php for ($i = $sPage; $i <= $ePage; $i++) {
                if ($i > $tPageTotal) {
                    break;
                }
                $isCurrent = ($tPage === $i);
                $btnClass = 'btnStyle pagination__pageBtn' . ($isCurrent ? '' : ' btnStyle--outline');
                if ($isCurrent) {
                    $btnClass .= ' --active';
                }
            ?>
            <button type="button" class="<?php echo e(trim($btnClass)); ?>"
                <?php echo $isCurrent ? 'aria-current="page"' : ''; ?>
                data-manage-action="goto-page" data-page="<?php echo $i; ?>" data-form-id="<?php echo e($formId); ?>">
                <?php echo $i; ?>
            </button>
            <?php } ?>

            <?php if ($tPage + 10 <= $tPageTotal) { ?>
            <button type="button" class="btnStyle btnStyle--outline pagination__pageBtn" title="下十頁"
                data-manage-action="goto-page" data-page="<?php echo min($tPageTotal, $tPage + 10); ?>" data-form-id="<?php echo e($formId); ?>">
                …
            </button>
            <?php } ?>

            <?php if ($tPage < $tPageTotal) { ?>
            <button type="button" class="btnIcon" title="下一頁"
                data-manage-action="goto-page" data-page="<?php echo $tPage + 1; ?>" data-form-id="<?php echo e($formId); ?>">
                <i class="bi bi-chevron-right pagination__icon"></i>
            </button>
            <button type="button" class="btnIcon" title="最末頁"
                data-manage-action="goto-page" data-page="<?php echo $tPageTotal; ?>" data-form-id="<?php echo e($formId); ?>">
                <i class="bi bi-chevron-double-right pagination__icon"></i>
            </button>
            <?php } else { ?>
            <button type="button" class="btnIcon" disabled title="下一頁">
                <i class="bi bi-chevron-right pagination__icon"></i>
            </button>
            <button type="button" class="btnIcon" disabled title="最末頁">
                <i class="bi bi-chevron-double-right pagination__icon"></i>
            </button>
            <?php } ?>
        </div>
    </div>
    <?php if (!empty($SortCond)) { ?>
    <input name="SortCond" id="SortCond" type="hidden" value="<?php echo e((string)$SortCond); ?>">
    <?php } ?>
</div>
