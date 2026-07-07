<?php
declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Gemini / OpenAI Embedding + Pinecone 向量搜尋服務
 *
 * 環境變數：見 vector_embedding_helpers.php、PINECONE_NAMESPACE（可選）
 * 類型對照：config/vector_search_types.php
 *
 * 使用方式：
 *   require_once __DIR__ . '/VectorSearchService.php';
 *   $service = VectorSearchService::fromEnv();
 *   $matches = $service->search('表格操作', 10);
 *   $rows    = $service->fetchResultsFromDb($matches);
 *   $service->upsertVector('knowledge', 123, '文件內容');
 *   $service->deleteVector('knowledge', 123);
 *
 * 向量 ID：{type}_{pkey}（例 knowledge_123）
 */
final class VectorSearchException extends RuntimeException
{
}

final class VectorSearchService
{
    /** @var array<string, array{table: string, pk: string, columns: list<string>}> */
    private array $typeMap;

    /**
     * 載入 Composer autoload 並確認 GuzzleHttp\Client 可用
     *
     * @throws VectorSearchException 缺少 vendor/autoload 或 Guzzle
     */
    private static function ensureComposerAutoload(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }

        if (!class_exists(Client::class)) {
            throw new VectorSearchException(
                '缺少 GuzzleHttp\\Client，請在專案根目錄執行 composer install（正式機需部署 vendor/ 目錄）'
            );
        }

        $loaded = true;
    }

    /**
     * 建立向量搜尋服務實例
     *
     * @param array<string, array{table: string, pk: string, columns: list<string>}>|null $typeMap
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly Client $http,
        private readonly string $pineconeApiKey,
        private readonly string $pineconeHost,
        private readonly string $pineconeNamespace = '',
        ?array $typeMap = null,
    ) {
        $this->typeMap = $typeMap ?? self::loadTypeMap();
    }

    /**
     * 從環境變數建立服務（需已載入 .env / host.php）
     */
    public static function fromEnv(?PDO $pdo = null): self
    {
        self::ensureComposerAutoload();
        require_once __DIR__ . '/vector_embedding_helpers.php';

        $provider = vector_embedding_provider();
        if (!vector_embedding_api_ready($provider)) {
            if ($provider === 'gemini') {
                throw new VectorSearchException('GEMINI_API_KEY 未設定（VECTOR_EMBEDDING_PROVIDER=gemini）');
            }
            throw new VectorSearchException('OPENAI_API_KEY 未設定（VECTOR_EMBEDDING_PROVIDER=openai）');
        }

        $pineconeKey = vector_pinecone_api_key();
        $pineconeHost = vector_pinecone_host();
        if ($pineconeKey === '') {
            throw new VectorSearchException(
                'PINECONE_API_KEY 未設定或仍為占位符（pcsk_...）；請在 .env 只保留一組有效金鑰'
            );
        }
        if ($pineconeHost === '') {
            throw new VectorSearchException('PINECONE_HOST 未設定（Pinecone Index Host）');
        }

        if ($pdo === null) {
            if (!function_exists('sql_conn')) {
                throw new VectorSearchException('PDO 未提供且 sql_conn() 不可用，請先載入 include/Conn.php');
            }
            $pdo = sql_conn();
        }
        if (!$pdo instanceof PDO) {
            throw new VectorSearchException('資料庫連線失敗，請確認 .env 的 DB_* 設定');
        }

        $namespace = self::envString('PINECONE_NAMESPACE');
        $timeout = max(10, (int)self::envString('VECTOR_SEARCH_HTTP_TIMEOUT', '30'));

        return new self(
            pdo: $pdo,
            http: self::createHttpClient($timeout),
            pineconeApiKey: $pineconeKey,
            pineconeHost: $pineconeHost,
            pineconeNamespace: $namespace,
        );
    }

    /**
     * 將文字轉換為向量（預設 Gemini；可由 VECTOR_EMBEDDING_PROVIDER 切換）
     *
     * @return list<float>
     */
    public function generateEmbedding(string $text, bool $isQuery = false): array
    {
        return vector_embedding_generate($text, $isQuery, $this->http);
    }

    /**
     * 呼叫 Pinecone 查詢相似向量
     *
     * @return list<array{id: string, score: float, metadata: array<string, mixed>}>
     */
    public function search(string $query, int $topK = 10): array
    {
        $topK = max(1, min($topK, 100));
        $vector = $this->generateEmbedding($query, true);

        $body = [
            'vector'          => $vector,
            'topK'            => $topK,
            'includeMetadata' => true,
        ];
        if ($this->pineconeNamespace !== '') {
            $body['namespace'] = $this->pineconeNamespace;
        }

        $url = self::pineconeQueryUrl($this->pineconeHost);

        try {
            $response = $this->http->post($url, [
                'headers' => [
                    'Api-Key'        => $this->pineconeApiKey,
                    'Content-Type'   => 'application/json',
                    'X-Pinecone-API-Version' => '2025-01',
                ],
                'json' => $body,
            ]);
        } catch (GuzzleException $e) {
            throw new VectorSearchException('Pinecone 查詢失敗：' . $e->getMessage(), 0, $e);
        }

        $payload = self::decodeJson((string)$response->getBody(), 'Pinecone Query');
        $rawMatches = $payload['matches'] ?? [];
        if (!is_array($rawMatches)) {
            return [];
        }

        $matches = [];
        foreach ($rawMatches as $match) {
            if (!is_array($match)) {
                continue;
            }
            $id = trim((string)($match['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $metadata = is_array($match['metadata'] ?? null) ? $match['metadata'] : [];
            $matches[] = [
                'id'       => $id,
                'score'    => (float)($match['score'] ?? 0.0),
                'metadata' => $metadata,
            ];
        }

        return $matches;
    }

    /**
     * 依 Pinecone matches 的 metadata.type 分批從 MySQL 撈取資料
     *
     * @param list<array{id?: string, score?: float, metadata?: array<string, mixed>}> $matches
     * @return list<array{
     *     vector_id: string,
     *     score: float,
     *     type: string,
     *     record: array<string, mixed>|null
     * }>
     */
    public function fetchResultsFromDb(array $matches): array
    {
        if ($matches === []) {
            return [];
        }

        /** @var array<string, list<array{vector_id: string, score: float, pkey: int}>> $grouped */
        $grouped = [];

        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }

            $vectorId = trim((string)($match['id'] ?? ''));
            $score = (float)($match['score'] ?? 0.0);
            $metadata = is_array($match['metadata'] ?? null) ? $match['metadata'] : [];

            $type = strtolower(trim((string)($metadata['type'] ?? '')));
            if ($type === '') {
                $type = self::inferTypeFromVectorId($vectorId);
            }
            if ($type === '' || !isset($this->typeMap[$type])) {
                continue;
            }

            $pkey = self::resolvePkey($metadata, $vectorId, $type);
            if ($pkey <= 0) {
                continue;
            }

            $grouped[$type][] = [
                'vector_id' => $vectorId,
                'score'     => $score,
                'pkey'      => $pkey,
            ];
        }

        /** @var array<string, array<int, array<string, mixed>>> $recordsByType */
        $recordsByType = [];
        foreach ($grouped as $type => $items) {
            $pkeys = array_values(array_unique(array_map(
                static fn (array $item): int => (int)$item['pkey'],
                $items
            )));
            $recordsByType[$type] = $this->fetchTypeRecords($type, $pkeys);
        }

        $results = [];
        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }

            $vectorId = trim((string)($match['id'] ?? ''));
            $score = (float)($match['score'] ?? 0.0);
            $metadata = is_array($match['metadata'] ?? null) ? $match['metadata'] : [];

            $type = strtolower(trim((string)($metadata['type'] ?? '')));
            if ($type === '') {
                $type = self::inferTypeFromVectorId($vectorId);
            }

            $record = null;
            if ($type !== '' && isset($this->typeMap[$type])) {
                $pkey = self::resolvePkey($metadata, $vectorId, $type);
                $record = $recordsByType[$type][$pkey] ?? null;
            }

            $results[] = [
                'vector_id' => $vectorId,
                'score'     => $score,
                'type'      => $type,
                'record'    => $record,
            ];
        }

        return $results;
    }

    /**
     * 僅回傳 Pinecone 向量 ID 列表（便利方法）
     *
     * @return list<string>
     */
    public function searchIds(string $query, int $topK = 10): array
    {
        return array_values(array_filter(array_map(
            static fn (array $match): string => (string)($match['id'] ?? ''),
            $this->search($query, $topK)
        )));
    }

    /**
     * 寫入或更新 Pinecone 向量
     *
     * @param array<string, scalar|null> $extraMetadata
     */
    public function upsertVector(string $type, int $pkey, string $text, array $extraMetadata = []): void
    {
        $type = strtolower(trim($type));
        if ($type === '' || $pkey <= 0) {
            throw new VectorSearchException('upsertVector 參數無效');
        }

        $text = trim($text);
        if ($text === '') {
            $this->deleteVector($type, $pkey);

            return;
        }

        $vector = $this->generateEmbedding($text, false);
        $vectorId = self::buildVectorId($type, $pkey);
        $metadata = array_merge(
            ['type' => $type, 'pkey' => $pkey],
            $extraMetadata
        );
        $metadata = self::sanitizeMetadata($metadata);

        $body = [
            'vectors' => [[
                'id'       => $vectorId,
                'values'   => $vector,
                'metadata' => $metadata,
            ]],
        ];
        if ($this->pineconeNamespace !== '') {
            $body['namespace'] = $this->pineconeNamespace;
        }

        $this->postToPinecone(self::pineconeVectorsUrl($this->pineconeHost, 'upsert'), $body, 'Pinecone Upsert');
    }

    /** 自 Pinecone 刪除向量 */
    public function deleteVector(string $type, int $pkey): void
    {
        $type = strtolower(trim($type));
        if ($type === '' || $pkey <= 0) {
            return;
        }

        $body = ['ids' => [self::buildVectorId($type, $pkey)]];
        if ($this->pineconeNamespace !== '') {
            $body['namespace'] = $this->pineconeNamespace;
        }

        $this->postToPinecone(self::pineconeVectorsUrl($this->pineconeHost, 'delete'), $body, 'Pinecone Delete');
    }

    /** 組合 Pinecone 向量 ID：{type}_{pkey} */
    public static function buildVectorId(string $type, int $pkey): string
    {
        $type = strtolower(preg_replace('/[^a-z0-9_]/', '', $type) ?? $type);

        return $type . '_' . $pkey;
    }

    /**
     * POST 至 Pinecone（Api-Key header；401/400 維度錯誤附提示）
     *
     * @param array<string, mixed> $body
     * @throws VectorSearchException
     */
    private function postToPinecone(string $url, array $body, string $context): void
    {
        try {
            $this->http->post($url, [
                'headers' => [
                    'Api-Key'                  => $this->pineconeApiKey,
                    'Content-Type'             => 'application/json',
                    'X-Pinecone-API-Version'   => '2025-01',
                ],
                'json' => $body,
            ]);
        } catch (GuzzleException $e) {
            $message = $context . ' 失敗：' . $e->getMessage();
            if (str_contains($message, '401')) {
                $message .= '（請確認 PINECONE_API_KEY 與 Index 同一 Project，且 .env 勿留 pcsk_... 占位符）';
            }
            if (str_contains($message, '400') && str_contains($message, 'dimension')) {
                $message .= '（請將 VECTOR_EMBEDDING_DIMENSIONS 設為與 Pinecone Index 相同，brick6-prod 為 1024）';
            }
            throw new VectorSearchException($message, 0, $e);
        }
    }

    /**
     * 清理 metadata（僅保留 scalar；字串截斷 500 字）
     *
     * @param array<string, mixed> $metadata
     * @return array<string, scalar|null>
     */
    private static function sanitizeMetadata(array $metadata): array
    {
        $clean = [];
        foreach ($metadata as $key => $value) {
            $key = trim((string)$key);
            if ($key === '' || !is_scalar($value)) {
                continue;
            }
            if (is_string($value)) {
                $value = mb_substr($value, 0, 500);
            }
            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * 組合 /vectors/{upsert|delete} URL
     *
     * @param string $action  upsert 或 delete
     */
    private static function pineconeVectorsUrl(string $host, string $action): string
    {
        $base = self::pineconeQueryUrl($host);
        $base = preg_replace('#/query$#', '', $base) ?? $base;

        return rtrim($base, '/') . '/vectors/' . $action;
    }

    /**
     * 載入 config/vector_search_types.php 類型對照
     *
     * @return array<string, array{table: string, pk: string, columns: list<string>}>
     */
    private static function loadTypeMap(): array
    {
        $path = dirname(__DIR__) . '/config/vector_search_types.php';
        if (!is_file($path)) {
            return [];
        }

        $loaded = require $path;

        return is_array($loaded) ? $loaded : [];
    }

    /**
     * 依 type 與主鍵列表批次查詢 MySQL
     *
     * @param list<int> $pkeys
     * @return array<int, array<string, mixed>>  主鍵 => 列資料
     * @throws VectorSearchException
     */
    private function fetchTypeRecords(string $type, array $pkeys): array
    {
        if ($pkeys === [] || !isset($this->typeMap[$type])) {
            return [];
        }

        $config = $this->typeMap[$type];
        $table = self::safeIdentifier((string)$config['table'], 'table');
        $pk = self::safeIdentifier((string)$config['pk'], 'pk');
        $columns = self::sanitizeColumns($config['columns'] ?? ['PKey', 'strName']);

        $placeholders = [];
        $params = [];
        foreach ($pkeys as $idx => $id) {
            $key = 'id' . $idx;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $columnSql = implode(', ', array_map(
            static fn (string $col): string => '`' . self::safeIdentifier($col, 'column') . '`',
            $columns
        ));

        $sql = "SELECT {$columnSql} FROM `{$table}` WHERE `{$pk}` IN (" . implode(', ', $placeholders) . ')';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[VectorSearchService] DB 查詢失敗 (' . $type . ')：' . $e->getMessage());
            throw new VectorSearchException('資料庫查詢失敗：' . $e->getMessage(), 0, $e);
        }

        $indexed = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowPk = (int)($row[$pk] ?? 0);
            if ($rowPk > 0) {
                $indexed[$rowPk] = $row;
            }
        }

        return $indexed;
    }

    /**
     * 從 metadata 或 vector ID 解析主鍵
     *
     * @param array<string, mixed> $metadata
     */
    private static function resolvePkey(array $metadata, string $vectorId, string $type): int
    {
        foreach (['pkey', 'PKey', 'id', 'pk'] as $key) {
            if (isset($metadata[$key]) && is_numeric($metadata[$key])) {
                $value = (int)$metadata[$key];
                if ($value > 0) {
                    return $value;
                }
            }
        }

        if (preg_match('/^' . preg_quote($type, '/') . '_(\d+)$/i', $vectorId, $m) === 1) {
            return (int)$m[1];
        }

        if (preg_match('/(\d+)$/', $vectorId, $m) === 1) {
            return (int)$m[1];
        }

        return 0;
    }

    /**
     * 自 vector ID（knowledge_123）推斷 type
     *
     * @return string  推斷失敗回傳空字串
     */
    private static function inferTypeFromVectorId(string $vectorId): string
    {
        if (preg_match('/^([a-z][a-z0-9_]*)_\d+$/i', $vectorId, $m) === 1) {
            return strtolower($m[1]);
        }

        return '';
    }

    /**
     * 驗證 SQL 識別字（防注入）
     *
     * @throws VectorSearchException
     */
    private static function safeIdentifier(string $name, string $label): string
    {
        $name = trim($name);
        if (preg_match('/^[a-zA-Z0-9_]+$/', $name) !== 1) {
            throw new VectorSearchException("無效的 {$label} 名稱：{$name}");
        }

        return $name;
    }

    /**
     * 過濾並回傳安全欄位名列表
     *
     * @param list<string>|mixed $columns
     * @return list<string>
     */
    private static function sanitizeColumns(mixed $columns): array
    {
        if (!is_array($columns) || $columns === []) {
            return ['PKey', 'strName'];
        }

        $safe = [];
        foreach ($columns as $col) {
            $col = trim((string)$col);
            if ($col !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $col) === 1) {
                $safe[] = $col;
            }
        }

        return $safe !== [] ? $safe : ['PKey', 'strName'];
    }

    /**
     * 組合 Pinecone query URL（host 可含或不含 https://）
     *
     * @throws VectorSearchException host 為空
     */
    private static function pineconeQueryUrl(string $host): string
    {
        $host = trim($host);
        if ($host === '') {
            throw new VectorSearchException('Pinecone Host 不可為空');
        }

        if (str_starts_with($host, 'http://') || str_starts_with($host, 'https://')) {
            return rtrim($host, '/') . '/query';
        }

        return 'https://' . rtrim($host, '/') . '/query';
    }

    /**
     * 解析 JSON 回應
     *
     * @return array<string, mixed>
     * @throws VectorSearchException
     */
    private static function decodeJson(string $json, string $context): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new VectorSearchException($context . ' JSON 解析失敗：' . $e->getMessage(), 0, $e);
        }

        return is_array($data) ? $data : [];
    }

    /**
     * 建立 Guzzle Client（SSL 見 gemini_resolve_ssl_verify）
     */
    private static function createHttpClient(int $timeout): Client
    {
        if (!function_exists('gemini_resolve_ssl_verify')) {
            require_once __DIR__ . '/gemini_client.php';
        }

        return new Client([
            'timeout' => $timeout,
            'verify'  => gemini_resolve_ssl_verify(),
        ]);
    }

    /** 讀取環境變數字串（$_ENV 優先，trim 後回傳） */
    private static function envString(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return trim(is_string($value) ? $value : $default);
    }
}
