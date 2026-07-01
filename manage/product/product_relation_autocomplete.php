<?php

declare(strict_types=1);



require_once '../_inc.php';

require_once '../_module.php';



$detailConfig = require __DIR__ . '/_config.php';

manage_detail_set_config($detailConfig, true);



header('Content-Type: application/json; charset=utf-8');



if (!function_exists('product_relation_json_resolve')) {

    function product_relation_json_resolve(int $pkey, string $name, string $error = ''): void

    {

        echo json_encode(

            ['pkey' => $pkey, 'name' => $name, 'error' => $error],

            JSON_UNESCAPED_UNICODE

        );

        exit;

    }

}



if (!function_exists('product_relation_has_lang_table')) {

    function product_relation_has_lang_table(): bool

    {

        return function_exists('chkTable') && chkTable('product_lang');

    }

}



if (!function_exists('product_relation_autocomplete_context')) {

    /**

     * @return array{cond:string, params:array<string,mixed>, displaySql:string}

     */

    function product_relation_autocomplete_context(int $modulePKey, int $excludePKey, bool $applyExclude): array

    {

        $params = ['Module_PKey' => $modulePKey];

        $cond = 'p.Module_PKey = :Module_PKey';

        if ($applyExclude && $excludePKey > 0) {

            $cond .= ' AND p.PKey <> :excludePKey';

            $params['excludePKey'] = $excludePKey;

        }

        if (product_relation_has_lang_table()) {

            $displaySql = "TRIM(COALESCE(

                (SELECT pl.strName FROM product_lang pl WHERE pl.Product_PKey = p.PKey AND pl.intLang = 1 LIMIT 1),

                p.strName

            ))";

        } else {

            $displaySql = 'TRIM(p.strName)';

        }

        return ['cond' => $cond, 'params' => $params, 'displaySql' => $displaySql];

    }

}



if (!function_exists('product_relation_autocomplete_keyword_search')) {

    /**

     * @return array{clause:string, params:array<string,string>}

     */

    function product_relation_autocomplete_keyword_search(string $term): array

    {

        $params = ['KeywordP' => $term];

        $clause = 'LOCATE(:KeywordP, p.strName) > 0';

        if (product_relation_has_lang_table()) {

            $clause .= ' OR EXISTS ('

                . ' SELECT 1 FROM product_lang plk WHERE plk.Product_PKey = p.PKey'

                . ' AND LOCATE(:KeywordL, plk.strName) > 0)';

            $params['KeywordL'] = $term;

        }

        return ['clause' => $clause, 'params' => $params];

    }

}



if (!function_exists('product_relation_autocomplete_name_match_search')) {

    /**

     * @return array{clause:string, params:array<string,string>}

     */

    function product_relation_autocomplete_name_match_search(string $strName): array

    {

        $params = ['strNameP' => $strName];

        $clause = 'p.strName = :strNameP';

        if (product_relation_has_lang_table()) {

            $clause .= ' OR EXISTS ('

                . ' SELECT 1 FROM product_lang plm WHERE plm.Product_PKey = p.PKey'

                . ' AND plm.strName = :strNameL)';

            $params['strNameL'] = $strName;

        }

        return ['clause' => $clause, 'params' => $params];

    }

}



$rtype = safe_int($filter_array['RType'] ?? $_GET['RType'] ?? $_POST['RType'] ?? 0);

$term = trim((string)($filter_array['term'] ?? $_GET['term'] ?? $_POST['term'] ?? ''));

$strName = trim((string)($filter_array['strName'] ?? $_GET['strName'] ?? $_POST['strName'] ?? ''));

$modulePKey = safe_int($filter_array['manNo'] ?? $_GET['manNo'] ?? $_POST['manNo'] ?? $GLOBALS['Module_PKey'] ?? 0);

$excludePKey = safe_int($filter_array['excludePKey'] ?? $_GET['excludePKey'] ?? $_POST['excludePKey'] ?? 0);



if ($modulePKey <= 0) {

    if ($rtype === 4) {

        product_relation_json_resolve(0, '', '【品名】無資料');

    }

    echo json_encode([], JSON_UNESCAPED_UNICODE);

    exit;

}



if ($rtype === 2) {

    if ($term === '') {

        echo json_encode([], JSON_UNESCAPED_UNICODE);

        exit;

    }

    $ctx = product_relation_autocomplete_context($modulePKey, $excludePKey, false);

    $kw = product_relation_autocomplete_keyword_search($term);

    $params = array_merge($ctx['params'], $kw['params']);

    $sql = 'SELECT p.PKey, ' . $ctx['displaySql'] . ' AS strName FROM product p WHERE ' . $ctx['cond']

        . ' AND (' . $kw['clause'] . ')'

        . ' ORDER BY strName LIMIT 30';

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

        product_relation_json_resolve(0, '', '【品名】無資料');

    }

    $ctx = product_relation_autocomplete_context($modulePKey, $excludePKey, false);

    $nm = product_relation_autocomplete_name_match_search($strName);

    $params = array_merge($ctx['params'], $nm['params']);

    $sql = 'SELECT p.PKey, ' . $ctx['displaySql'] . ' AS strName FROM product p WHERE ' . $ctx['cond']

        . ' AND (' . $nm['clause'] . ') LIMIT 1';

    $row = crud_fetch_one($sql, $params);

    if ($row === null) {

        product_relation_json_resolve(0, '', '【品名】無資料');

    }

    $disp = trim((string)($row['strName'] ?? $strName));

    product_relation_json_resolve((int)($row['PKey'] ?? 0), $disp);

}



echo json_encode([], JSON_UNESCAPED_UNICODE);

