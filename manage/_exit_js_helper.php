<?php
declare(strict_types=1);

if (!function_exists('exit_js')) {
    /**
     * 後台 addin：alert 後 history.back() 並結束。
     *
     * @param mixed $msg 傳入 json_encode 之值
     */
    function exit_js(mixed $msg): void {
        $js = 'alert(' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ');history.back();';
        if (function_exists('manage_inline_script')) {
            echo manage_inline_script($js);
        } else {
            echo '<script>alert(' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ');history.back();</script>';
        }
        exit;
    }
}
