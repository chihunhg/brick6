<?php
declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Gemini\Enums\TaskType;

/**
 * Pinecone 向量搜尋 — Embedding 提供者（Gemini / OpenAI）
 *
 * 環境變數（.env）：
 *   VECTOR_EMBEDDING_PROVIDER   gemini|openai（預設：有 GEMINI_API_KEY 則 gemini）
 *   VECTOR_EMBEDDING_MODEL      Gemini 預設 gemini-embedding-001
 *   VECTOR_EMBEDDING_DIMENSIONS 須與 Pinecone Index 維度一致（例：1024）
 *   GEMINI_API_KEY / OPENAI_API_KEY
 *   PINECONE_API_KEY / PINECONE_HOST
 *
 * 使用方式：
 *   require_once __DIR__ . '/vector_embedding_helpers.php';
 *   require_once __DIR__ . '/VectorSearchService.php';
 *   $queryVec = vector_embedding_generate('表格操作', true);   // 查詢用 RETRIEVAL_QUERY
 *   $docVec   = vector_embedding_generate('產品說明…', false); // 寫入用 RETRIEVAL_DOCUMENT
 */

if (!function_exists('vector_env_secret')) {
    /**
     * 讀取 .env 敏感字串並去除引號
     *
     * @param string $key 環境變數名稱（如 PINECONE_API_KEY）
     */
    function vector_env_secret(string $key): string
    {
        if (function_exists('host_env_string')) {
            $value = trim(host_env_string($key));
        } else {
            $value = trim((string)($_ENV[$key] ?? getenv($key) ?: ''));
        }

        if ($value === '') {
            return '';
        }

        if (($value[0] === '"' && str_ends_with($value, '"'))
            || ($value[0] === "'" && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        return trim($value);
    }
}

if (!function_exists('vector_pinecone_api_key')) {
    /**
     * 取得有效的 Pinecone API Key（略過 pcsk_... 占位符）
     */
    function vector_pinecone_api_key(): string
    {
        $key = vector_env_secret('PINECONE_API_KEY');
        if ($key === '' || $key === 'pcsk_...' || preg_match('/\.\.\.$/', $key) === 1) {
            return '';
        }

        return $key;
    }
}

if (!function_exists('vector_pinecone_host')) {
    /**
     * 取得 Pinecone Index Host（不含 https://）
     */
    function vector_pinecone_host(): string
    {
        $host = vector_env_secret('PINECONE_HOST');
        if ($host === '' || str_contains($host, 'xxxx') || str_contains($host, 'example')) {
            return '';
        }

        return $host;
    }
}

if (!function_exists('vector_embedding_provider')) {
    /**
     * 目前 Embedding 提供者：gemini 或 openai
     *
     * 由 VECTOR_EMBEDDING_PROVIDER 指定；未指定時有 GEMINI_API_KEY 則 gemini
     */
    function vector_embedding_provider(): string
    {
        $raw = strtolower(trim((string)($_ENV['VECTOR_EMBEDDING_PROVIDER'] ?? getenv('VECTOR_EMBEDDING_PROVIDER') ?: '')));
        if (in_array($raw, ['gemini', 'openai'], true)) {
            return $raw;
        }

        if (vector_embedding_gemini_api_key() !== '') {
            return 'gemini';
        }

        return 'openai';
    }
}

if (!function_exists('vector_embedding_gemini_api_key')) {
    /**
     * 解析 Gemini API Key（GEMINI_API_KEY 或 GOOGLE_API_KEY）
     */
    function vector_embedding_gemini_api_key(): string
    {
        if (function_exists('manage_semantic_resolve_api_key')) {
            $key = trim(manage_semantic_resolve_api_key());
            if ($key !== '') {
                return $key;
            }
        }

        foreach (['GEMINI_API_KEY', 'GOOGLE_API_KEY'] as $envKey) {
            $key = trim((string)($_ENV[$envKey] ?? getenv($envKey) ?: ''));
            if ($key !== '') {
                return $key;
            }
        }

        return '';
    }
}

if (!function_exists('vector_embedding_openai_api_key')) {
    /** 讀取 OPENAI_API_KEY */
    function vector_embedding_openai_api_key(): string
    {
        return trim((string)($_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?: ''));
    }
}

if (!function_exists('vector_embedding_model')) {
    /**
     * Embedding 模型名稱
     *
     * @param string|null $provider gemini|openai；null 時自動判斷
     */
    function vector_embedding_model(?string $provider = null): string
    {
        $provider = $provider ?? vector_embedding_provider();
        if ($provider === 'gemini') {
            $raw = trim((string)($_ENV['VECTOR_EMBEDDING_MODEL'] ?? getenv('VECTOR_EMBEDDING_MODEL') ?: ''));
            if ($raw !== '') {
                return $raw;
            }
            if (function_exists('manage_semantic_embedding_model')) {
                return manage_semantic_embedding_model();
            }

            return 'gemini-embedding-001';
        }

        $raw = trim((string)($_ENV['OPENAI_EMBEDDING_MODEL'] ?? getenv('OPENAI_EMBEDDING_MODEL') ?: ''));

        return $raw !== '' ? $raw : 'text-embedding-3-small';
    }
}

if (!function_exists('vector_embedding_dimensions')) {
    /**
     * 輸出向量維度（須與 Pinecone Index 一致）
     *
     * 環境變數 VECTOR_EMBEDDING_DIMENSIONS，預設 1024
     */
    function vector_embedding_dimensions(): int
    {
        $raw = (int)($_ENV['VECTOR_EMBEDDING_DIMENSIONS'] ?? getenv('VECTOR_EMBEDDING_DIMENSIONS') ?: 1024);

        return max(128, min($raw, 3072));
    }
}

if (!function_exists('vector_embedding_l2_normalize')) {
    /**
     * L2 正規化向量（gemini-embedding-001 縮維時必須，Pinecone cosine 用）
     *
     * @param list<float> $values
     * @return list<float>
     */
    function vector_embedding_l2_normalize(array $values): array
    {
        $sum = 0.0;
        foreach ($values as $v) {
            $sum += $v * $v;
        }
        if ($sum <= 0.0) {
            return $values;
        }

        $norm = sqrt($sum);

        return array_map(static fn (float $v): float => $v / $norm, $values);
    }
}

if (!function_exists('vector_embedding_api_ready')) {
    /**
     * 目前 provider 的 API Key 是否已設定
     *
     * @param string|null $provider null 時用 vector_embedding_provider()
     */
    function vector_embedding_api_ready(?string $provider = null): bool
    {
        $provider = $provider ?? vector_embedding_provider();

        return $provider === 'gemini'
            ? vector_embedding_gemini_api_key() !== ''
            : vector_embedding_openai_api_key() !== '';
    }
}

if (!function_exists('vector_embedding_generate')) {
    /**
     * 產生 Embedding 向量（依 VECTOR_EMBEDDING_PROVIDER 呼叫 Gemini 或 OpenAI）
     *
     * @param bool $isQuery true=搜尋查詢（RETRIEVAL_QUERY），false=文件寫入（RETRIEVAL_DOCUMENT）
     * @return list<float>
     * @throws VectorSearchException
     */
    function vector_embedding_generate(string $text, bool $isQuery = false, ?Client $http = null): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new VectorSearchException('Embedding 文字不可為空');
        }

        $text = mb_substr($text, 0, 8000);
        $provider = vector_embedding_provider();

        if ($provider === 'gemini') {
            return vector_embedding_generate_gemini($text, $isQuery);
        }

        if (!$http instanceof Client) {
            throw new VectorSearchException('OpenAI Embedding 需要 HTTP Client');
        }

        return vector_embedding_generate_openai($text, $http);
    }
}

if (!function_exists('vector_embedding_generate_gemini')) {
    /**
     * 呼叫 Gemini embedContent API
     *
     * @return list<float>
     * @throws VectorSearchException
     */
    function vector_embedding_generate_gemini(string $text, bool $isQuery): array
    {
        $apiKey = vector_embedding_gemini_api_key();
        if ($apiKey === '') {
            throw new VectorSearchException('GEMINI_API_KEY 未設定');
        }

        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new VectorSearchException('缺少 vendor/autoload.php，請執行 composer install');
        }
        require_once $autoload;

        if (!function_exists('gemini_create_client')) {
            require_once __DIR__ . '/gemini_client.php';
        }

        $model = vector_embedding_model('gemini');
        $dimensions = vector_embedding_dimensions();
        $taskType = $isQuery ? TaskType::RETRIEVAL_QUERY : TaskType::RETRIEVAL_DOCUMENT;
        $timeout = max(10, (int)trim((string)($_ENV['VECTOR_SEARCH_HTTP_TIMEOUT'] ?? getenv('VECTOR_SEARCH_HTTP_TIMEOUT') ?: '30')));

        try {
            $client = gemini_create_client($apiKey, $timeout);
            $response = $client->embeddingModel($model)->embedContent(
                $text,
                $taskType,
                null,
                $dimensions
            );
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (function_exists('gemini_api_error_message')) {
                $message = gemini_api_error_message($e);
            }
            throw new VectorSearchException('Gemini Embedding 請求失敗：' . $message, 0, $e);
        }

        $values = $response->embedding->values ?? null;
        if (!is_array($values) || $values === []) {
            throw new VectorSearchException('Gemini Embedding 回應格式錯誤');
        }

        $values = array_map(static fn (mixed $v): float => (float)$v, $values);
        if (count($values) !== $dimensions) {
            throw new VectorSearchException(
                'Gemini Embedding 維度不符：收到 ' . count($values) . '，預期 ' . $dimensions
                . '（請確認 VECTOR_EMBEDDING_DIMENSIONS 與 Pinecone Index 一致）'
            );
        }

        if ($dimensions !== 3072) {
            $values = vector_embedding_l2_normalize($values);
        }

        return $values;
    }
}

if (!function_exists('vector_embedding_generate_openai')) {
    /**
     * 呼叫 OpenAI /v1/embeddings API
     *
     * @return list<float>
     * @throws VectorSearchException
     */
    function vector_embedding_generate_openai(string $text, Client $http): array
    {
        $apiKey = vector_embedding_openai_api_key();
        if ($apiKey === '') {
            throw new VectorSearchException('OPENAI_API_KEY 未設定');
        }

        $model = vector_embedding_model('openai');

        try {
            $response = $http->post('https://api.openai.com/v1/embeddings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'input' => $text,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new VectorSearchException('OpenAI Embedding 請求失敗：' . $e->getMessage(), 0, $e);
        }

        try {
            $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new VectorSearchException('OpenAI Embedding JSON 解析失敗：' . $e->getMessage(), 0, $e);
        }

        $embedding = is_array($payload) ? ($payload['data'][0]['embedding'] ?? null) : null;
        if (!is_array($embedding) || $embedding === []) {
            throw new VectorSearchException('OpenAI Embedding 回應格式錯誤');
        }

        return array_map(static fn (mixed $v): float => (float)$v, $embedding);
    }
}
