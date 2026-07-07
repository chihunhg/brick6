<?php
declare(strict_types=1);

if (!function_exists('json_clear_output_buffers')) {
    /** 清空所有 output buffer（JSON 輸出前） */
    function json_clear_output_buffers(): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}

if (!function_exists('json_out')) {
    /**
     * 輸出 JSON 並結束請求（ajax 端點共用）。
     *
     * @param array<string,mixed> $payload
     */
    function json_out(array $payload, int $code = 200): void {
        json_clear_output_buffers();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            http_response_code($code);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
