<?php
declare(strict_types=1);

/**
 * 批次補建 Pinecone 向量
 *
 * CLI 用法：
 *   php scripts/vector_reindex.php
 *   php scripts/vector_reindex.php --type=knowledge
 *   php scripts/vector_reindex.php --type=paper --dry-run
 *   php scripts/vector_reindex.php --type=news --offset=500 --limit=200
 *
 * HTTP 排程（Plesk「執行 URL」；需 .env VECTOR_REINDEX_TOKEN）：
 *   https://tsg5.com.tw/brick6/scripts/vector_reindex.php?token=YOUR_SECRET
 *   https://tsg5.com.tw/brick6/scripts/vector_reindex.php?token=YOUR_SECRET&type=knowledge&limit=200
 *
 * 部署時請同步上傳：
 *   scripts/vector_reindex.php
 *   include/vector_reindex_auth.php
 *   include/vector_reindex_runner.php
 *   include/vector_sync_helpers.php
 *   include/VectorSearchService.php
 *   config/vector_search_types.php
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/include/vector_reindex_auth.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
    @set_time_limit(0);
    ignore_user_abort(true);

    vector_reindex_load_env($projectRoot);

    $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
    if ($token === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = trim((string)$_SERVER['HTTP_AUTHORIZATION']);
        if (preg_match('/^Bearer\s+(\S+)/i', $auth, $m) === 1) {
            $token = trim($m[1]);
        }
    }

    if (vector_reindex_token() === '') {
        http_response_code(503);
        echo "[ERROR] VECTOR_REINDEX_TOKEN 未在 .env 設定\n";
        exit(1);
    }
    if (!vector_reindex_verify_token($token)) {
        http_response_code(403);
        echo "[ERROR] invalid token\n";
        echo "提示：token 含 + 或 = 時請 URL 編碼，或使用 POST token 參數\n";
        echo "例：https://.../vector_reindex.php?token=" . rawurlencode('YOUR_TOKEN') . "\n";
        exit(1);
    }

    $runnerPath = $projectRoot . '/include/vector_reindex_runner.php';
    if (!is_file($runnerPath)) {
        http_response_code(500);
        echo "[ERROR] 缺少 include/vector_reindex_runner.php，請同步部署\n";
        exit(1);
    }
    require_once $runnerPath;

    vector_reindex_bootstrap($projectRoot);

    $input = array_merge($_GET, $_POST);
    $options = vector_reindex_parse_http_options($input);
    $result = vector_reindex_execute($options);

    if (!$result['success']) {
        http_response_code(500);
    }

    exit(vector_reindex_render_result($result, false));
}

$runnerPath = $projectRoot . '/include/vector_reindex_runner.php';
if (!is_file($runnerPath)) {
    fwrite(STDERR, "[ERROR] 缺少 include/vector_reindex_runner.php\n");
    exit(1);
}
require_once $runnerPath;

vector_reindex_bootstrap($projectRoot);

$options = vector_reindex_parse_cli_args($argv);
$result = vector_reindex_execute($options);

exit(vector_reindex_render_result($result, true));
