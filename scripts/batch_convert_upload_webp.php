<?php
declare(strict_types=1);
/**
 * 批次將 Upload/ 既有 JPG/PNG 轉 WebP（修補歷史未轉檔的上傳圖）
 *
 * 用法（正式機 SSH）：
 *   php scripts/batch_convert_upload_webp.php
 *   php scripts/batch_convert_upload_webp.php --dry-run
 *   php scripts/batch_convert_upload_webp.php --limit=100
 */

require dirname(__DIR__) . '/include/host.php';
require dirname(__DIR__) . '/include/image.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$limit  = 0;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(0, (int)substr($arg, 8));
    }
}

$base = rtrim((string)($GLOBALS['PathForder'] ?? dirname(__DIR__)), "/\\") . '/Upload/';
if (!is_dir($base)) {
    fwrite(STDERR, "Upload 目錄不存在：{$base}\n");
    exit(1);
}

if (!function_exists('convert_uploaded_to_webp')) {
    fwrite(STDERR, "convert_uploaded_to_webp 不可用，請確認 GD/Imagick 支援 WebP。\n");
    exit(1);
}

$done = 0;
$skip = 0;
$fail = 0;

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
);

foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    $ext  = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        continue;
    }

    $webpPath = preg_replace('/\.[^.]+$/i', '.webp', $path);
    if ($webpPath !== null && is_file($webpPath) && is_really_webp($webpPath)) {
        $skip++;
        continue;
    }

    if ($limit > 0 && $done >= $limit) {
        break;
    }

    if ($dryRun) {
        echo "[dry-run] {$path}\n";
        $done++;
        continue;
    }

    $dir  = dirname($path) . DIRECTORY_SEPARATOR;
    $name = basename($path);
    $result = convert_uploaded_to_webp($name, $dir, 85);
    if ($result !== false && is_file((string)$result)) {
        echo "OK {$path}\n";
        $done++;
    } else {
        echo "FAIL {$path}\n";
        $fail++;
    }
}

echo "\n完成：轉換 {$done}、略過已有 WebP {$skip}、失敗 {$fail}"
    . ($dryRun ? '（dry-run）' : '') . "\n";
