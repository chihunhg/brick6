<?php
declare(strict_types=1);
/**
 * 重發既有帳號 MFA 綁定指引信
 */

require_once '../_inc.php';
require_once '../_module.php';

$detailConfig = require __DIR__ . '/_config.php';
$csrfKey      = (string)($detailConfig['csrf'] ?? 'manage_form');
$listFile     = (string)($detailConfig['list_file'] ?? 'list.php');
$listUrl      = manage_breadcrumbs_list_href($listFile);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    manage_alert_script('請由帳號編輯頁操作', $listUrl);
    exit;
}

crud_csrf_verify($csrfKey);

$pkey = (int)($filter_array['PKey'] ?? 0);
if ($pkey <= 0) {
    manage_alert_script('參數錯誤：PKey 無效', $listUrl);
    exit;
}

$returnUrl = 'update.php?PKey=' . $pkey;
if (!empty($filter_array['manNo'])) {
    $returnUrl .= '&manNo=' . (int)$filter_array['manNo'];
}
if (!empty($filter_array['subNo'])) {
    $returnUrl .= '&subNo=' . (int)$filter_array['subNo'];
}

$result = manage_mfa_resend_onboard_mail($pkey);
$msg = ($result['ok'] ?? false)
    ? (string)($result['message'] ?? '已重發 MFA 綁定指引信')
    : ('重發失敗：' . (string)($result['message'] ?? '未知錯誤'));

manage_alert_script($msg, $returnUrl);
exit;
