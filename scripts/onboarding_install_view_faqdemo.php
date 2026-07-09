<?php
declare(strict_types=1);
/**
 * Day 7：自 view_faq 複製建立 view_faqdemo（dev 用）
 *
 * 用法：php scripts/onboarding_install_view_faqdemo.php
 */

require dirname(__DIR__) . '/include/host.php';
require dirname(__DIR__) . '/include/Conn.php';

$pdo = sql_conn();
if (!$pdo) {
    fwrite(STDERR, "無法連線資料庫，請確認 .env 設定。\n");
    exit(1);
}

$row = $pdo->query('SHOW CREATE VIEW view_faq')->fetch(PDO::FETCH_ASSOC);
$ddl = (string)($row['Create View'] ?? '');
if ($ddl === '') {
    fwrite(STDERR, "找不到 view_faq，請先確認 FAQ 模組已安裝。\n");
    exit(1);
}

$ddl = str_replace('view_faq', 'view_faqdemo', $ddl);
$ddl = preg_replace('/\bfaq_lang\b/', 'faqdemo_lang', $ddl);
$ddl = preg_replace('/\bfaq_msg\b/', 'faqdemo_msg', $ddl);
$ddl = preg_replace('/\bfaq_img\b/', 'faqdemo_img', $ddl);
$ddl = preg_replace('/\bfaq\b/', 'faqdemo', $ddl);
$ddl = str_replace('FAQ_PKey', 'FAQDemo_PKey', $ddl);

$pdo->exec('DROP VIEW IF EXISTS view_faqdemo');
$pdo->exec($ddl);

echo "view_faqdemo 已建立。\n";
