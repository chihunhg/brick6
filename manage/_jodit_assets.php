<?php

declare(strict_types=1);

/**
 * 後台 Jodit 資源。$prefix 相對目前頁面網址，例如 manage/news/ 用 ../jodit/。
 * 同一請求只輸出一次。
 */
if (!function_exists('manage_echo_jodit_assets')) {
    function manage_echo_jodit_assets(string $prefix): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $dir = __DIR__ . '/jodit/';
        $mtime = static function (string $name) use ($dir): string {
            $path = $dir . $name;
            return is_file($path) ? (string)filemtime($path) : '1';
        };
        $fixed = '4.18.12';
        $prefix = rtrim($prefix, '/') . '/';
        $cssVer = $mtime('content.css') . '-' . $mtime('styles.js');

        echo '<link rel="stylesheet" href="' . e_attr($prefix . 'jodit.min.css?ver=' . $fixed) . '">' . "\n";
        echo '<link rel="stylesheet" href="' . e_attr($prefix . 'content.css?ver=' . $mtime('content.css')) . '">' . "\n";
        echo script_src_tag($prefix . 'jodit.min.js?ver=' . $fixed) . "\n";
        echo script_src_tag($prefix . 'styles.js?ver=' . $mtime('styles.js')) . "\n";
        echo script_src_tag($prefix . 'config.js?ver=' . $mtime('config.js'), [
            'data-css-ver' => $cssVer,
        ]) . "\n";
    }
}
