<?php 
require_once("../_inc.php");
//參數解碼
if (is_numeric($filter_array['PKey'])){
	$PKey = SqlFilter($filter_array['PKey'],'int');
}
$SQL_U = " update module_p Set Upload = '".SqlFilter($filter_array['Upload'],'str')."' Where PKey=".SqlFilter($PKey,'int');
execute_sql($SQL_U);
?>