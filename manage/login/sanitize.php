<?php
$path = __DIR__ . '/index.php';
$code = file_get_contents($path);

// 把常見問題字元轉成一般空白或移除
$code = str_replace(
    ["\xEF\xBB\xBF", "\xE3\x80\x80", "\xC2\xA0", "\xE2\x80\x8B", "\xE2\x80\x8C", "\xE2\x80\x8D", "\xE2\x81\xA0"],
    ["",              " ",           " ",         "",           "",           "",           ""],
    $code
);

file_put_contents($path, $code);
echo "Sanitized.\n";
