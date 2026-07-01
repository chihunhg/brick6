<?php
require_once("../_inc.php");

//使用Spreadsheet類
use PhpOffice\PhpSpreadsheet\Spreadsheet;
//xlsx格式類
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
//可以生成多種格式類
use PhpOffice\PhpSpreadsheet\IOFactory;

$Question_PKey = 0;
if(is_numeric($_REQUEST['PKey'])){
	$Question_PKey = $_REQUEST['PKey'];
}

$sql = 'Select * from question where PKey= :PKey ';
$rs = new recordset($sql,array($Question_PKey));
//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql,$SQL_Error,$_SERVER['PHP_SELF'],'system');
	echo '<pre>';
	print_r($result);
	echo '</pre>';
	exit;
}
if(! $rs->eof){
	$Question_PKey = $rs->field("PKey");
	$Question_Name = $rs->field("strName");
	$Contents = $rs->field("Contents");

	//問卷類別
	$Item_Key = array();
	$Item_Name = array();
	
	//問卷固定欄位
	$Item_Key[1] = $Question_PKey;
	$Item_Name[1] = '姓名';
	$Item_Key[2] = $Question_PKey;
	$Item_Name[2] = '行動電話';
	$Item_Key[3] = $Question_PKey;
	$Item_Name[3] = '電子信箱';
	$Item_Key[4] = $Question_PKey;
	$Item_Name[4] = '出生年月日';
	$i = 4;
	$sql  = 'Select * From view_question_item where Question_PKey= :Question_PKey ';
	$rs1 = new recordset($sql,array($rs->field('PKey')));
	//判斷有無錯誤訊息
	$SQL_Error = $rs1->getErrorMessage();
	if(!empty($SQL_Error)){
		//寫入資料庫存取錯誤記錄
		$result = sql_error($sql,$SQL_Error,$_SERVER['PHP_SELF'],'system');
		echo '<pre>';
		print_r($result);
		echo '</pre>';
		exit;
	}
	while(! $rs1->eof){
		$i++;
		$Item_Key[$i] = $rs1->field("PKey");
		$Item_Name[$i] = RemoveHTML($rs1->field("strName"));
	$rs1->movenext();
	}
	$i++;
	$Item_Key[$i] = 0;
	$Item_Name[$i] = '填寫日期';
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
//設置sheet的名字  兩種方法
$sheet->setTitle('phpspreadsheet——demo');
$spreadsheet->getActiveSheet()->setTitle('問卷');

//設置第一行小標題
$sheet->setCellValue('A1', '問卷主題');  //指定C1儲存格內容
$sheet->setCellValue('B1', $Question_Name);  //指定C1儲存格內容
$sheet->setCellValue('A2', '匯出時間');  //指定C1儲存格內容
$sheet->setCellValue('B2', date('Y/m/d'));  //指定D1儲存格內容
for($i=1;$i<=count($Item_Name);$i++){
	$chr = $i+65;
	if($chr < 91){
		$cel = chr($chr);
	}else{
		$cel = 'A'.chr($chr-26);
	}
	$sheet->setCellValue($cel.'3',$Item_Name[$i]);  //指定D1儲存格內容
}

$n = 3; 
$Report_PKey = 0;
$sql = ' select * from view_question_report where Question_PKey= :Question_PKey';
$rs = new recordset($sql,array($Question_PKey));

//判斷有無錯誤訊息
$SQL_Error = $rs->getErrorMessage();
if(!empty($SQL_Error)){
	//寫入資料庫存取錯誤記錄
	$result = sql_error($sql,$SQL_Error,$_SERVER['PHP_SELF'],'system');
	echo '<pre>';
	print_r($result);
	echo '</pre>';
	exit;
}
while(! $rs->eof){
	if($rs->field('Report_PKey')!=$Report_PKey){
		$n++;
		$Report_PKey = $rs->field('Report_PKey');
		$msg = '';
		for($i=1;$i< count($Item_Key);$i++){			
			$chr = $i+65;
			if($chr < 91){
				$cel = chr($chr);
			}else{
				$cel = 'A'.chr($chr-26);
			}
			$Contents = '';
			switch($i){
				case 1:
					$Contents = $rs->field('strName');
					break;
				case 2:
					$Contents = $rs->field('Mobile');
					break;
				case 3:
					$Contents = $rs->field('EMail');
					break;
				case 4:
					$Contents = $rs->field('Birthday');
					break;
				default:
					$sql = 'Select * from question_report_d Where Question_I_PKey= :Question_I_PKey and Report_PKey= :Report_PKey';
					$rs1 = new recordset($sql,array($Item_Key[$i],$rs->field('Report_PKey')));					
					//判斷有無錯誤訊息
					$SQL_Error = $rs1->getErrorMessage();
					if(!empty($SQL_Error)){
						//寫入資料庫存取錯誤記錄
						$result = sql_error($sql,$SQL_Error,$_SERVER['PHP_SELF'],'system');
						echo '<pre>';
						print_r($result);
						echo '</pre>';
						exit;
					}
					if(! $rs1->eof){
						$Contents = $rs1->field('Contents');				
					}
					break;
			}
			//echo $msg.=$Contents.';<br />';
			$sheet->setCellValue($cel.$n,$Contents,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);		
		}
		$chr = $i+65;
		if($chr < 91){
			$cel = chr($chr);
		}else{
			$cel = 'A'.chr($chr-26);
		}
		$sheet->setCellValue($cel.$n,date_en($rs->field('dtDate'),1),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	}
$rs->movenext();	
}
//exit;
$file_name = 'survey'.date("Ymd");
//第一種保存方式
/*$writer = new Xlsx($spreadsheet);
//保存的路徑可自行設置
$file_name = '../'.$file_name . ".xlsx";
$writer->save($file_name);*/
//第二種直接頁面上顯示下載
$file_name = $file_name . ".xlsx";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="'.$file_name.'"');
header('Cache-Control: max-age=0');
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
//注意createWriter($spreadsheet, 'Xls') 第二個參數首字母必須大寫
$writer->save('php://output');
exit;
?>
