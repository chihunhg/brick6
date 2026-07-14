<?php
declare(strict_types=1);
/**
 * 自 view_faq 複製建立 view_history，並確保 SELECT 含 intYear。
 * 會略過本站 history 主檔不存在的欄位（例如 Home）。
 *
 * 用法：php scripts/install_view_history.php
 */

require dirname(__DIR__) . '/include/host.php';
require dirname(__DIR__) . '/include/Conn.php';

$pdo = sql_conn();
if (!$pdo) {
    fwrite(STDERR, "無法連線資料庫，請確認 .env 設定。\n");
    exit(1);
}

$historyCols = [];
foreach ($pdo->query('SHOW COLUMNS FROM `history`') as $col) {
    $historyCols[strtolower((string)($col['Field'] ?? ''))] = true;
}
if ($historyCols === []) {
    fwrite(STDERR, "找不到 history 資料表，請先執行 sql/history.sql。\n");
    exit(1);
}

$row = $pdo->query('SHOW CREATE VIEW view_faq')->fetch(PDO::FETCH_ASSOC);
$ddl = (string)($row['Create View'] ?? '');
if ($ddl === '') {
    fwrite(STDERR, "找不到 view_faq，請先確認 FAQ 模組已安裝。\n");
    exit(1);
}

$ddl = str_replace('view_faq', 'view_history', $ddl);
$ddl = preg_replace('/\bfaq_lang\b/', 'history_lang', $ddl) ?? $ddl;
$ddl = preg_replace('/\bfaq_msg\b/', 'history_msg', $ddl) ?? $ddl;
$ddl = preg_replace('/\bfaq_img\b/', 'history_img', $ddl) ?? $ddl;
$ddl = preg_replace('/\bfaq\b/', 'history', $ddl) ?? $ddl;
$ddl = str_replace('FAQ_PKey', 'History_PKey', $ddl);

// 若 view_faq SELECT 含 history 不存在的主檔欄位，拿掉（常見：Home）
$optionalMaster = ['Home', 'Keywords', 'Description', 'OpenDate', 'EndDate', 'NoOpenDate', 'NoEndDate', 'strDate'];
foreach ($optionalMaster as $col) {
    if (isset($historyCols[strtolower($col)])) {
        continue;
    }
    $ddl = preg_replace('/,\s*(?:`?history`?|m)\.`?' . preg_quote($col, '/') . '`?/i', '', $ddl) ?? $ddl;
    $ddl = preg_replace('/(?:`?history`?|m)\.`?' . preg_quote($col, '/') . '`?\s*,/i', '', $ddl) ?? $ddl;
}

if (!isset($historyCols['intyear'])) {
    fwrite(STDERR, "history 缺少 intYear，請先執行 sql/history.sql 的 ALTER。\n");
    exit(1);
}

if (!preg_match('/\bintYear\b/i', $ddl)) {
    $ddl = preg_replace(
        '/((?:`?history`?|m)\.`?Sort`?)/i',
        '$1, m.intYear',
        $ddl,
        1
    ) ?? $ddl;
    if (!preg_match('/\bintYear\b/i', $ddl)) {
        fwrite(STDERR, "無法自動插入 intYear，請手動調整 VIEW。\n");
        exit(1);
    }
}

$pdo->exec('DROP VIEW IF EXISTS view_history');
$pdo->exec($ddl);

echo "view_history 已建立。\n";
