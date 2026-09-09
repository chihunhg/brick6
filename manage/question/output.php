<?php
declare(strict_types=1);

/** Excel 二進位下載 */
$manage_binary_export = true;
ob_start();

require_once '../_inc.php';

$Question_PKey = 0;
if (is_numeric($_REQUEST['PKey'] ?? null)) {
    $Question_PKey = (int)$_REQUEST['PKey'];
}

$sql = 'Select * from question where PKey= :PKey ';
$rs = new recordset($sql, [$Question_PKey]);
$SQL_Error = $rs->getErrorMessage();
if (!empty($SQL_Error)) {
    $result = sql_error($sql, $SQL_Error, $_SERVER['PHP_SELF'], 'system');
    echo '<pre>';
    print_r($result);
    echo '</pre>';
    exit;
}

$Question_Name = '';
$Item_Key = [];
$Item_Name = [];

if (!$rs->eof) {
    $Question_PKey = (int)$rs->field('PKey');
    $Question_Name = (string)$rs->field('strName');

    $Item_Key[1] = $Question_PKey;
    $Item_Name[1] = '姓名';
    $Item_Key[2] = $Question_PKey;
    $Item_Name[2] = '行動電話';
    $Item_Key[3] = $Question_PKey;
    $Item_Name[3] = '電子信箱';
    $Item_Key[4] = $Question_PKey;
    $Item_Name[4] = '出生年月日';
    $i = 4;
    $sql = 'Select * From view_question_item where Question_PKey= :Question_PKey ';
    $rs1 = new recordset($sql, [$rs->field('PKey')]);
    $SQL_Error = $rs1->getErrorMessage();
    if (!empty($SQL_Error)) {
        $result = sql_error($sql, $SQL_Error, $_SERVER['PHP_SELF'], 'system');
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        exit;
    }
    while (!$rs1->eof) {
        $i++;
        $Item_Key[$i] = $rs1->field('PKey');
        $Item_Name[$i] = RemoveHTML($rs1->field('strName'));
        $rs1->movenext();
    }
    $i++;
    $Item_Key[$i] = 0;
    $Item_Name[$i] = '填寫日期';
}

$grid = [];
$grid[1][1] = '問卷主題';
$grid[1][2] = $Question_Name;
$grid[2][1] = '匯出時間';
$grid[2][2] = date('Y/m/d');
for ($i = 1; $i <= count($Item_Name); $i++) {
    $grid[3][$i + 1] = (string)($Item_Name[$i] ?? '');
}

$n = 3;
$Report_PKey = 0;
$sql = ' select * from view_question_report where Question_PKey= :Question_PKey';
$rs = new recordset($sql, [$Question_PKey]);
$SQL_Error = $rs->getErrorMessage();
if (!empty($SQL_Error)) {
    $result = sql_error($sql, $SQL_Error, $_SERVER['PHP_SELF'], 'system');
    echo '<pre>';
    print_r($result);
    echo '</pre>';
    exit;
}
while (!$rs->eof) {
    if ((int)$rs->field('Report_PKey') !== $Report_PKey) {
        $n++;
        $Report_PKey = (int)$rs->field('Report_PKey');
        for ($i = 1; $i < count($Item_Key); $i++) {
            $contents = '';
            switch ($i) {
                case 1:
                    $contents = (string)$rs->field('strName');
                    break;
                case 2:
                    $contents = (string)$rs->field('Mobile');
                    break;
                case 3:
                    $contents = (string)$rs->field('EMail');
                    break;
                case 4:
                    $contents = (string)$rs->field('Birthday');
                    break;
                default:
                    $sql = 'Select * from question_report_d Where Question_I_PKey= :Question_I_PKey and Report_PKey= :Report_PKey';
                    $rs1 = new recordset($sql, [$Item_Key[$i], $rs->field('Report_PKey')]);
                    $SQL_Error = $rs1->getErrorMessage();
                    if (!empty($SQL_Error)) {
                        $result = sql_error($sql, $SQL_Error, $_SERVER['PHP_SELF'], 'system');
                        echo '<pre>';
                        print_r($result);
                        echo '</pre>';
                        exit;
                    }
                    if (!$rs1->eof) {
                        $contents = (string)$rs1->field('Contents');
                    }
                    break;
            }
            $grid[$n][$i + 1] = $contents;
        }
        $grid[$n][$i + 1] = function_exists('date_en')
            ? (string)date_en($rs->field('dtDate'), 1)
            : (string)$rs->field('dtDate');
    }
    $rs->movenext();
}

$maxRow = $grid === [] ? 0 : max(array_keys($grid));
$maxCol = 0;
foreach ($grid as $rowCells) {
    if ($rowCells !== []) {
        $maxCol = max($maxCol, max(array_keys($rowCells)));
    }
}
$tableData = [];
for ($r = 1; $r <= $maxRow; $r++) {
    $line = [];
    for ($c = 1; $c <= $maxCol; $c++) {
        $line[] = (string)($grid[$r][$c] ?? '');
    }
    $tableData[] = $line;
}

xlsx_download($tableData, 'survey' . date('Ymd') . '.xlsx', '問卷');
