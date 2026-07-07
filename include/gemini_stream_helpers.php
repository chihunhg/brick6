<?php
declare(strict_types=1);

if (!function_exists('gemini_sse_flush')) {
/**
 * Gemini streamGenerateContent → Server-Sent Events (SSE)
 */

    function gemini_sse_flush(): void {
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }
}

if (!function_exists('gemini_sse_begin')) {
    /** 送出 SSE 標頭並清空 buffer */
    function gemini_sse_begin(int $httpCode = 200): void {
        if (function_exists('json_clear_output_buffers')) {
            json_clear_output_buffers();
        } else {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        if (headers_sent()) {
            return;
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('X-Content-Type-Options: nosniff');
        http_response_code($httpCode);

        echo ": gemini-stream\n\n";
        gemini_sse_flush();
    }
}

if (!function_exists('gemini_sse_send')) {
    /** @param array<string, mixed> $data */
    function gemini_sse_send(string $event, array $data): void {
        echo 'event: ' . $event . "\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n";
        gemini_sse_flush();
    }
}

if (!function_exists('gemini_sse_error_and_exit')) {
    /** 以 error 事件結束 SSE 並 exit */
    function gemini_sse_error_and_exit(string $message, int $httpCode = 500): never {
        gemini_sse_begin($httpCode);
        gemini_sse_send('error', ['success' => false, 'error' => $message]);
        exit;
    }
}

if (!function_exists('gemini_stream_extract_chunk_text')) {
    /** 從 stream chunk 擷取文字片段 */
    function gemini_stream_extract_chunk_text(\Gemini\Responses\GenerativeModel\GenerateContentResponse $response): string {
        if ($response->candidates === []) {
            return '';
        }

        $parts = [];
        try {
            $parts = $response->parts();
        } catch (ValueError) {
            $parts = $response->candidates[0]->content->parts ?? [];
        }

        $text = '';
        foreach ($parts as $part) {
            if ($part->text !== null && $part->text !== '') {
                $text .= $part->text;
            }
        }

        return $text;
    }
}

if (!function_exists('gemini_stream_parse_json_accumulated')) {
    /** @return array<string, mixed> */
    function gemini_stream_parse_json_accumulated(string $accumulated): array {
        $accumulated = gemini_sanitize_utf8_text(trim($accumulated));
        if ($accumulated === '') {
            throw new RuntimeException('Empty model response');
        }

        $data = json_decode($accumulated, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid model response');
        }

        return $data;
    }
}

if (!function_exists('gemini_stream_generate_content_sse')) {
    /**
     * 使用 streamGenerateContent 串流輸出 SSE，完成後以 done 事件回傳整理後 payload
     *
     * @param callable(array<string, mixed>): array<string, mixed> $finalizePayload
     */
    function gemini_stream_generate_content_sse(
        \Gemini\Resources\GenerativeModel $model,
        string $prompt,
        callable $finalizePayload,
    ): never {
        if (!function_exists('gemini_sanitize_utf8_text')) {
            require_once __DIR__ . '/gemini_editor_helpers.php';
        }

        $prompt = gemini_sanitize_utf8_text($prompt);
        gemini_sse_begin();
        gemini_sse_send('start', ['success' => true]);

        $accumulated = '';

        try {
            foreach ($model->streamGenerateContent($prompt) as $response) {
                if (!function_exists('gemini_response_safety_blocked_message')) {
                    require_once __DIR__ . '/gemini_client.php';
                }

                $blocked = gemini_response_safety_blocked_message($response);
                if ($blocked !== null) {
                    gemini_sse_send('error', ['success' => false, 'error' => $blocked]);
                    exit;
                }

                $delta = gemini_sanitize_utf8_text(gemini_stream_extract_chunk_text($response));
                if ($delta === '') {
                    continue;
                }

                $accumulated .= $delta;
                gemini_sse_send('delta', ['text' => $delta]);
            }

            $data = gemini_stream_parse_json_accumulated($accumulated);
            $payload = $finalizePayload($data);
            if (is_array($payload)) {
                $payload = gemini_sanitize_utf8_array($payload);
            }
            gemini_sse_send('done', $payload);
        } catch (Throwable $e) {
            if (!function_exists('gemini_api_error_message')) {
                require_once __DIR__ . '/gemini_client.php';
            }
            error_log('[gemini_stream] ' . $e->getMessage());
            gemini_sse_send('error', [
                'success' => false,
                'error' => gemini_api_error_message($e),
            ]);
        }

        exit;
    }
}
