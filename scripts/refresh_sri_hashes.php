<?php
declare(strict_types=1);

/**
 * 更新 include/sri_manifest.php 中外連 script 的 SRI hash。
 * 用法：php scripts/refresh_sri_hashes.php
 */
// reCAPTCHA 勿列入：Google 不定期更新 api.js，SRI 會導致瀏覽器封鎖腳本
$urls = [
    // 例：'https://www.googletagmanager.com/gtm.js?id=GTM-XXXX',
];

$manifest = [];
if ($urls === []) {
    fwrite(STDERR, "No URLs configured in scripts/refresh_sri_hashes.php\n");
    exit(0);
}

foreach ($urls as $url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'brick6-sri-refresh/1.0',
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || $body === '') {
        fwrite(STDERR, "Failed to fetch: {$url}\n");
        exit(1);
    }
    $hash = base64_encode(hash('sha384', $body, true));
    $manifest[$url] = 'sha384-' . $hash;
    echo $url . ' => ' . $manifest[$url] . PHP_EOL;
}

$export = var_export($manifest, true);
$php = "<?php\n"
    . "declare(strict_types=1);\n\n"
    . "/**\n"
    . " * 外連 script 的 SRI integrity 對照（完整 URL，含 query）。\n"
    . " * 由 scripts/refresh_sri_hashes.php 產生。\n"
    . " */\n"
    . "return {$export};\n";

$target = dirname(__DIR__) . '/include/sri_manifest.php';
if (file_put_contents($target, $php) === false) {
    fwrite(STDERR, "Failed to write {$target}\n");
    exit(1);
}
echo "Written {$target}\n";
