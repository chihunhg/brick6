<?php
declare(strict_types=1);

require_once '../_inc.php';

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/include/tag_relation_helpers.php';

if (!function_exists('tag_relation_json_resolve')) {
    /** 輸出標籤關聯 JSON（pkey、name、error）並結束請求 */
    function tag_relation_json_resolve(int $pkey, string $name, string $error = ''): void {
        echo json_encode(
            ['pkey' => $pkey, 'name' => $name, 'error' => $error],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}

$rtype = safe_int($filter_array['RType'] ?? $_GET['RType'] ?? $_POST['RType'] ?? 0);
$term = trim((string)($filter_array['term'] ?? $_GET['term'] ?? $_POST['term'] ?? ''));
$strName = trim((string)($filter_array['strName'] ?? $_GET['strName'] ?? $_POST['strName'] ?? ''));
$modulePKey = safe_int(
    $filter_array['tagManNo']
        ?? $filter_array['manNo']
        ?? $_GET['tagManNo']
        ?? $_POST['tagManNo']
        ?? $_GET['manNo']
        ?? $_POST['manNo']
        ?? 0
);

if ($modulePKey <= 0) {
    $modulePKey = tag_relation_resolve_module_pkey();
}

if ($modulePKey <= 0) {
    if ($rtype === 4) {
        tag_relation_json_resolve(0, '', '【標籤】無資料');
    }
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$displaySql = tag_relation_display_sql();
$baseCond = 't.Module_PKey = :Module_PKey AND t.Upload = :Upload';
$baseParams = ['Module_PKey' => $modulePKey, 'Upload' => 'Yes'];

if ($rtype === 2) {
    if ($term === '') {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $params = array_merge($baseParams, ['KeywordP' => $term]);
    $clause = 'LOCATE(:KeywordP, t.strName) > 0';
    if (function_exists('chkTable') && chkTable('tag_lang')) {
        $clause .= ' OR EXISTS ('
            . ' SELECT 1 FROM tag_lang tlk WHERE tlk.Tag_PKey = t.PKey'
            . ' AND LOCATE(:KeywordL, tlk.strName) > 0)';
        $params['KeywordL'] = $term;
    }

    $sql = 'SELECT t.PKey, ' . $displaySql . ' AS strName FROM tag t WHERE '
        . $baseCond . ' AND (' . $clause . ') ORDER BY strName LIMIT 30';
    $rows = crud_fetch_all($sql, $params);

    $result = [];
    $seen = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['strName'] ?? ''));
        $pkey = (int)($r['PKey'] ?? 0);
        if ($name === '' || $pkey <= 0 || isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;
        $result[] = ['label' => $name, 'value' => $name, 'pkey' => $pkey];
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($rtype === 4) {
    if ($strName === '') {
        tag_relation_json_resolve(0, '', '【標籤】無資料');
    }

    $params = array_merge($baseParams, ['strNameP' => $strName]);
    $clause = 't.strName = :strNameP';
    if (function_exists('chkTable') && chkTable('tag_lang')) {
        $clause .= ' OR EXISTS ('
            . ' SELECT 1 FROM tag_lang tlm WHERE tlm.Tag_PKey = t.PKey'
            . ' AND tlm.strName = :strNameL)';
        $params['strNameL'] = $strName;
    }

    $sql = 'SELECT t.PKey, ' . $displaySql . ' AS strName FROM tag t WHERE '
        . $baseCond . ' AND (' . $clause . ') LIMIT 1';
    $row = crud_fetch_one($sql, $params);
    if ($row === null) {
        tag_relation_json_resolve(0, '', '【標籤】無資料');
    }
    $disp = trim((string)($row['strName'] ?? $strName));
    tag_relation_json_resolve((int)($row['PKey'] ?? 0), $disp);
}

echo json_encode([], JSON_UNESCAPED_UNICODE);
