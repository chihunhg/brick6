<?php
declare(strict_types=1);

require_once '../_inc.php';
require_once __DIR__ . '/_form_data.php';

$orderPKey = safe_int($filter_array['PKey'] ?? 0);
if ($orderPKey <= 0) {
    header('Content-Type: text/html; charset=utf-8');
    echo '參數錯誤';
    exit;
}

$row = crud_fetch_one(
    'SELECT PKey, AllPayLogisticsID, CVSPaymentNo, CVSValidationNo FROM order_p WHERE PKey = :pk LIMIT 1',
    ['pk' => $orderPKey]
);
if ($row === null) {
    header('Content-Type: text/html; charset=utf-8');
    echo '查無訂單';
    exit;
}

$merchantId = trim((string)($_ENV['ECPAY_LOGISTICS_MERCHANT_ID'] ?? getenv('ECPAY_LOGISTICS_MERCHANT_ID') ?: ''));
$hashKey = trim((string)($_ENV['ECPAY_LOGISTICS_HASH_KEY'] ?? getenv('ECPAY_LOGISTICS_HASH_KEY') ?: ''));
$hashIv = trim((string)($_ENV['ECPAY_LOGISTICS_HASH_IV'] ?? getenv('ECPAY_LOGISTICS_HASH_IV') ?: ''));
$gatewayUrl = trim((string)($_ENV['ECPAY_LOGISTICS_PRINT_URL'] ?? getenv('ECPAY_LOGISTICS_PRINT_URL') ?: ''));
if ($gatewayUrl === '') {
    $gatewayUrl = 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo';
}

if ($merchantId === '' || $hashKey === '' || $hashIv === '') {
    header('Content-Type: text/html; charset=utf-8');
    echo '超商列印尚未設定，請於 .env 設定 ECPAY_LOGISTICS_* 環境變數';
    exit;
}

$formArray = [
    'MerchantID'          => $merchantId,
    'AllPayLogisticsID'   => (string)($row['AllPayLogisticsID'] ?? ''),
    'CVSPaymentNo'        => (string)($row['CVSPaymentNo'] ?? ''),
    'CVSValidationNo'     => (string)($row['CVSValidationNo'] ?? ''),
    'PlatformID'          => '',
];

ksort($formArray);
$formArray['CheckMacValue'] = strtoupper(_getMacValue($hashKey, $hashIv, $formArray));

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="UTF-8">
    <title>超商列印</title>
</head>
<body onload="document.getElementById('keyinorder').submit();">
<form method="post" action="<?php echo htmlspecialchars($gatewayUrl, ENT_QUOTES, 'UTF-8'); ?>" id="keyinorder" name="keyinorder">
<?php foreach ($formArray as $key => $val) { ?>
    <input type="hidden" name="<?php echo htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'); ?>"
        value="<?php echo htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
</form>
</body>
</html>
