<?php
declare(strict_types=1);
/**
 * 列表語系欄：由 list.php / _list.php 在載入列資料後 require
 * 產出 $listShowLangColumn、$listLangMap
 */

if (!isset($listLangColumnInited)) {
    $listLangColumnInited = true;

    $detailConfig = $detailConfig ?? null;

    $listLangCtx = crud_list_lang_column_init(
        is_array($detailConfig) ? $detailConfig : [],
        isset($listRows) && is_array($listRows) ? $listRows : [],
        (string)($PKName ?? 'PKey')
    );
    $listShowLangColumn = (bool)($listLangCtx['show'] ?? false);
    $listLangMap        = is_array($listLangCtx['map'] ?? null) ? $listLangCtx['map'] : [];
}
