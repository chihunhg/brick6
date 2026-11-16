<?php

declare(strict_types=1);

/**
 * 後台 Quill 資源。$prefix 相對目前頁面網址，例如 manage/news/ 用 ../quill/。
 * $part：style 放 head、script 放 body 底部（須在 Jodit 之後，樣式清單才讀得到）。
 */
if (!function_exists('manage_echo_quill_assets')) {
    function manage_echo_quill_assets(string $prefix, string $part = 'all'): void
    {
        static $styleLoaded = false;
        static $scriptLoaded = false;

        $dir = __DIR__ . '/quill/';
        $mtime = static function (string $name) use ($dir): string {
            $path = $dir . $name;
            return is_file($path) ? (string)filemtime($path) : '1';
        };
        $fixed = '2.0.3';
        $prefix = rtrim($prefix, '/') . '/';
        $part = $part === 'style' || $part === 'script' ? $part : 'all';

        if (($part === 'all' || $part === 'style') && !$styleLoaded) {
            $styleLoaded = true;
            echo '<link rel="stylesheet" href="' . e_attr($prefix . 'quill.snow.css?ver=' . $fixed) . '">' . "\n";
            echo '<link rel="stylesheet" href="' . e_attr($prefix . 'content.css?ver=' . $mtime('content.css')) . '">' . "\n";
        }

        if (($part === 'all' || $part === 'script') && !$scriptLoaded) {
            $scriptLoaded = true;
            echo script_src_tag($prefix . 'quill.js?ver=' . $fixed) . "\n";
            echo script_src_tag($prefix . 'config.js?ver=' . $mtime('config.js')) . "\n";
        }
    }
}
