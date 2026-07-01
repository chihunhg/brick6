<?php
// elfinder_connector.php — PHP 8.x compatible, CKEditor 4.x friendly
declare(strict_types=1);

// 避免 Warning/Notice 輸出破壞 JSON
if (ob_get_level() === 0) {
    ob_start();
}

/** 後台登入檢查（須已登入且 Manage=Yes） */
function elfinder_require_auth(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['Login_ID']) || (string)($_SESSION['Manage'] ?? '') !== 'Yes') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
        }
        echo json_encode(['error' => ['Unauthorized']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

elfinder_require_auth();

// ---- Polyfill: PHP 7 也可用 ----
if (!function_exists('_starts_with')) {
    function _starts_with($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle   = (string)$needle;
        if ($needle === '') return true;
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// ====== 基本設定 ======
const ROOT_VPATH = '../../Upload/images/';  // 可用 /絕對URL 或 相對於本檔案的路徑
const VOL_PREFIX = 'l1_';
const TRASH_VOL_PREFIX = 't1_';
const TRASH_DIR_NAME = '.trash';
const MAX_UPLOAD = 10 * 1024 * 1024;       // 10MB
const ALLOW_EXT  = ['jpg','jpeg','png','gif','webp','svg','bmp','txt','md'];
const RESERVED_WIN = [
  'con','prn','aux','nul','com1','com2','com3','com4','com5','com6','com7','com8','com9',
  'lpt1','lpt2','lpt3','lpt4','lpt5','lpt6','lpt7','lpt8','lpt9'
];

// ====== 入口 ======
@header('Content-Type: application/json; charset=utf-8');
$cmd = strtolower($_REQUEST['cmd'] ?? '');

try {
    switch ($cmd) {
        case 'init':
        case 'open':      handle_open($cmd === 'init'); break;
        case 'tmb':       json_out(['images' => new stdClass()]); break;
        case 'upload':    handle_upload(); break;
        case 'rm':        handle_rm(); break;
        case 'rename':    handle_rename(); break;
        case 'mkdir':     handle_mkdir(); break;
        case 'mkfile':    handle_mkfile(); break;
        case 'paste':     handle_paste(); break;
        case 'duplicate': handle_duplicate(); break;
        case 'size':      handle_size(); break;
        case 'empty':     handle_empty(); break;
        case 'info':      handle_info(); break;
        case 'tree':      handle_tree(); break;
        case 'parents':   handle_parents(); break;
        case 'ping':      json_out(['pong' => 1]); break;
        case 'ls':        handle_ls(); break;
        default:          error_out('Unknown command'); break;
    }
} catch (HttpException $e) {
    error_out($e->getMessage());
} catch (Throwable $e) {
    error_out('Server error: ' . $e->getMessage());
}

// ====== open / init ======
function handle_open(bool $isInit): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $trashPhysical = elfinder_trash_physical($rootPhysical);

    $target = $_REQUEST['target'] ?? '';
    $ctx = elfinder_context_from_hash($target !== '' ? (string)$target : hash_path(''));

    $cwdRel = $ctx['rel'];
    $cwdPhysical = elfinder_physical_path($ctx, $cwdRel);

    $files = [];
    $files[] = as_root_info($rootPhysical);
    elfinder_append_trash_root_file($files, $trashPhysical);

    $cwdInfo = $ctx['is_trash'] && $cwdRel === ''
        ? elfinder_trash_root_info($trashPhysical)
        : as_dir_info($cwdPhysical, $cwdRel, $rootPhysical, $ctx['prefix'], $ctx['vol_base']);
    if (!elfinder_files_has_hash($files, (string)$cwdInfo['hash'])) {
        $files[] = $cwdInfo;
    }

    foreach (safe_enum_dirs($cwdPhysical) as $d) {
        $name = basename($d);
        if (!$ctx['is_trash'] && $cwdRel === '' && strcasecmp($name, TRASH_DIR_NAME) === 0) {
            continue;
        }
        $rel = trim(str_replace('\\', '/', to_rel($d, $ctx['vol_base'])), '/');
        $files[] = as_dir_info($d, $rel, $rootPhysical, $ctx['prefix'], $ctx['vol_base']);
    }
    foreach (safe_enum_files($cwdPhysical) as $f) {
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOW_EXT, true)) {
            continue;
        }
        $rel = trim(str_replace('\\', '/', to_rel($f, $ctx['vol_base'])), '/');
        $files[] = as_file_info($f, $rel, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
    }

    $options = [
        'path'      => $cwdRel === '' ? '/' : '/' . $cwdRel . '/',
        'url'       => url_combine($baseUrl, $ctx['is_trash'] ? '' : $cwdRel) . ($cwdRel === '' ? '' : '/'),
        'tmbUrl'    => '',
        'separator' => '/',
        'disabled'  => [],
    ];
    if (!$ctx['is_trash']) {
        $options['trashHash'] = hash_path('', TRASH_VOL_PREFIX);
    }

    $result = [
        'api'        => '2.1',
        'cwd'        => $cwdInfo,
        'files'      => $files,
        'options'    => $options,
        'uplMaxSize' => ((int)(MAX_UPLOAD / (1024 * 1024))) . 'M',
    ];

    if ($isInit || isset($_REQUEST['tree'])) {
        $result['tree'] = elfinder_collect_tree_entries($files);
    }

    json_out($result);
}

// ====== upload（含資料夾） ======
function handle_upload(): void {
    [$rootPhysical, $baseUrl] = ensure_root();

    $target = $_REQUEST['target'] ?? '';
    $ctx = elfinder_context_from_hash((string)$target);
    if ($ctx['is_trash']) {
        error_out('errPerm');
        return;
    }
    $cwdRel = $ctx['rel'];
    $cwdPhysical = elfinder_physical_path($ctx, $cwdRel);
    if (!is_dir($cwdPhysical)) {
        @mkdir($cwdPhysical, 0775, true);
    }

    $added = [];
    $err   = [];
    $fileWarns = [];

    // 可能的相對路徑欄位（不同前端版本）
    $relPaths = null;
    foreach (['upload_path', 'upload_path_', 'webkitRelativePath'] as $key) {
        if (isset($_POST[$key])) { $relPaths = $_POST[$key]; break; }
        if (isset($_POST[$key.'[]'])) { $relPaths = $_POST[$key.'[]']; break; }
    }
    if ($relPaths !== null && !is_array($relPaths)) $relPaths = [$relPaths];

    // 僅接受 elFinder 協定欄位 upload
    if (!isset($_FILES['upload']) || !is_array($_FILES['upload'])) {
        error_out('No upload file');
        return;
    }
    $files = flatten_files_array(['upload' => $_FILES['upload']]);
    foreach ($files as $i => $file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) { $fileWarns[] = "[{$i}] Null or not uploaded"; continue; }
        $size = intval($file['size'] ?? 0);
        $orig = (string)($file['name'] ?? '');
        if ($size <= 0) { $fileWarns[] = "[{$i}] Empty file: $orig"; continue; }
        if ($size > MAX_UPLOAD) { $fileWarns[] = "[{$i}] Too large: $orig ($size > ".MAX_UPLOAD.")"; continue; }
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOW_EXT, true)) { $fileWarns[] = "[{$i}] Ext not allowed: $orig (ext: .$ext)"; continue; }

        $rawName  = basename($orig);
        $safeName = sanitize_filename($rawName, false);
        if ($safeName === null || $safeName === '') { $fileWarns[] = "[{$i}] Bad filename after sanitize: \"$rawName\""; continue; }

        // 解析相對路徑 → 自動建立子資料夾
        $subDir = '';
        if ($relPaths && isset($relPaths[$i]) && trim((string)$relPaths[$i]) !== '') {
            $parts = preg_split('#/#', str_replace('\\\\', '/', (string)$relPaths[$i])) ?: [];
            if (count($parts) > 1) {
                $dirParts = array_map(function(string $s): string {
                    $v = sanitize_filename($s, true) ?? '';
                    return $v === '' ? 'New Folder' : $v;
                }, array_slice($parts, 0, -1));
                $subDir = implode(DIRECTORY_SEPARATOR, $dirParts);
            }
        }

        $saveDir = $subDir === '' ? $cwdPhysical : combine_safe($cwdPhysical, $subDir);
        try {
            elfinder_ensure_under_main_root($rootPhysical, $saveDir);
        } catch (Throwable $exEnsure) {
            $fileWarns[] = "[{$i}] Path rejected by root guard: $saveDir ({$exEnsure->getMessage()})";
            continue;
        }
        if (!is_dir($saveDir) && !@mkdir($saveDir, 0775, true)) { $fileWarns[] = "[{$i}] CreateDirectory failed: $saveDir"; continue; }

        $destPath = ensure_unique($saveDir . DIRECTORY_SEPARATOR . $safeName, false);
        if (!@move_uploaded_file($file['tmp_name'], $destPath)) { $fileWarns[] = "[{$i}] move_uploaded_file failed: $orig -> $destPath"; continue; }

        // 成功加入回傳清單
        $rel = trim(str_replace('\\', '/', to_rel($destPath, $rootPhysical)), '/');
        $added[] = as_file_info($destPath, $rel, $baseUrl);
    }

    if (!empty($fileWarns)) {
        $err = array_merge($err, $fileWarns);
    }

    $cwdInfo = as_dir_info($cwdPhysical, $cwdRel, $rootPhysical, $ctx['prefix'], $ctx['vol_base']);
    $cwdInfo['ts'] = time();

    $res = [
        'added'   => $added,
        'changed' => [$cwdInfo],
        'cwd'     => $cwdInfo
    ];
    if (!empty($err)) $res['warning'] = $err;

    json_out($res);
}

// ====== rm ======
function handle_rm(): void {
    [$rootPhysical] = ensure_root();
    $targets = get_param_values(['targets', 'targets[]']);

    $removed = [];
    foreach ($targets as $t) {
        $ctx = elfinder_context_from_hash((string)$t);
        if ($ctx['rel'] === '' && !$ctx['is_trash']) {
            continue;
        }
        $full = elfinder_physical_path($ctx, $ctx['rel']);
        if (is_dir($full)) {
            rrmdir($full);
            $removed[] = $t;
        } elseif (is_file($full)) {
            @unlink($full);
            $removed[] = $t;
        }
    }
    json_out(['removed' => $removed]);
}

// ====== rename ======
function handle_rename(): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $target = $_REQUEST['target'] ?? '';
    $name   = $_REQUEST['name'] ?? '';
    if (trim($target) === '' || trim($name) === '') {
        error_out('Bad request');
        return;
    }

    $ctx = elfinder_context_from_hash((string)$target);
    if ($ctx['rel'] === '' && !$ctx['is_trash']) {
        error_out('errPerm');
        return;
    }
    $fullOld = elfinder_physical_path($ctx, $ctx['rel']);

    $safeName = sanitize_filename($name, is_dir($fullOld));
    if ($safeName === null || $safeName === '') {
        error_out('Bad filename');
        return;
    }

    $dir = dirname($fullOld);
    $fullNew = $dir . DIRECTORY_SEPARATOR . $safeName;

    if (is_file($fullOld) && pathinfo($safeName, PATHINFO_EXTENSION) === '') {
        $fullNew = $dir . DIRECTORY_SEPARATOR . pathinfo($safeName, PATHINFO_FILENAME) . '.' . (pathinfo($fullOld, PATHINFO_EXTENSION) ?: '');
    }
    elfinder_ensure_under_main_root($rootPhysical, $fullNew);

    if (strcasecmp($fullOld, $fullNew) === 0) {
        $added = build_single_info($fullOld, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
        json_out(['added' => [$added], 'removed' => [$target]]);
    }

    if (is_dir($fullOld)) {
        $fullNew = ensure_unique($fullNew, true);
        @rename($fullOld, $fullNew);
    } elseif (is_file($fullOld)) {
        $ext = strtolower(pathinfo($fullNew, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOW_EXT, true)) {
            error_out('Ext not allowed');
            return;
        }
        $fullNew = ensure_unique($fullNew, false);
        @rename($fullOld, $fullNew);
    } else {
        error_out('Not found');
        return;
    }

    $added = build_single_info($fullNew, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
    json_out(['added' => [$added], 'removed' => [$target]]);
}

// ====== mkdir ======
function handle_mkdir(): void {
    [$rootPhysical, $baseUrl] = ensure_root();

    $target = $_REQUEST['target'] ?? '';
    $ctx = elfinder_context_from_hash((string)$target);
    $parentRel = $ctx['rel'];
    $parentPhysical = elfinder_physical_path($ctx, $parentRel);
    elfinder_ensure_under_main_root($rootPhysical, $parentPhysical);

    $dirs = $_REQUEST['dirs'] ?? null;
    if ($dirs !== null) {
        if (!is_array($dirs)) {
            $dirs = [$dirs];
        }
        $hashes = [];
        $added = [];
        foreach ($dirs as $dirPath) {
            $dirPath = str_replace('\\', '/', (string)$dirPath);
            $dirPath = trim($dirPath, '/');
            if ($dirPath === '') {
                $hashes['/'] = hash_path($parentRel, $ctx['prefix']);
                continue;
            }
            $segments = array_values(array_filter(explode('/', $dirPath), static fn($s) => $s !== ''));
            $currentRel = $parentRel;
            $currentPhysical = $parentPhysical;
            $pathKey = '';
            foreach ($segments as $seg) {
                $safeSeg = sanitize_filename($seg, true);
                if ($safeSeg === null || $safeSeg === '') {
                    continue;
                }
                $currentRel = $currentRel === '' ? $safeSeg : $currentRel . '/' . $safeSeg;
                $pathKey = '/' . $currentRel;
                $currentPhysical = $currentPhysical . DIRECTORY_SEPARATOR . $safeSeg;
                if (!is_dir($currentPhysical)) {
                    @mkdir($currentPhysical, 0775, true);
                }
                $hashes[$pathKey] = hash_path($currentRel, $ctx['prefix']);
                $added[] = build_single_info($currentPhysical, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
            }
            if ($pathKey !== '') {
                $hashes['/' . $dirPath] = $hashes[$pathKey] ?? hash_path($currentRel, $ctx['prefix']);
            }
        }
        json_out(['added' => $added, 'hashes' => $hashes]);
    }

    $name = $_REQUEST['name'] ?? '';
    $safeName = sanitize_filename($name, true);
    if ($safeName === null || $safeName === '') {
        $safeName = 'New Folder';
    }

    $newDir = ensure_unique($parentPhysical . DIRECTORY_SEPARATOR . $safeName, true);
    if (!@mkdir($newDir, 0775, true)) {
        error_out('Create directory failed');
        return;
    }

    $added = build_single_info($newDir, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
    json_out(['added' => [$added]]);
}

// ====== mkfile ======
function handle_mkfile(): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $target = $_REQUEST['target'] ?? '';
    $name   = $_REQUEST['name'] ?? '';

    $ctx = elfinder_context_from_hash((string)$target);
    if ($ctx['is_trash']) {
        error_out('errPerm');
        return;
    }
    $parentPhysical = elfinder_physical_path($ctx, $ctx['rel']);

    $safeName = sanitize_filename($name, false);
    if ($safeName === null || $safeName === '') {
        error_out('Bad filename');
        return;
    }

    $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOW_EXT, true)) {
        error_out('Ext not allowed');
        return;
    }

    $newFile = ensure_unique($parentPhysical . DIRECTORY_SEPARATOR . $safeName, false);
    $fh = @fopen($newFile, 'xb');
    if ($fh === false) {
        error_out('Create file failed');
        return;
    }
    fclose($fh);

    $added = build_single_info($newFile, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
    json_out(['added' => [$added]]);
}

// ====== paste (copy / move) ======
function handle_paste(): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $targets = get_param_values(['targets', 'targets[]']);
    $dstHash = $_REQUEST['dst'] ?? '';
    $cut     = ($_REQUEST['cut'] ?? '0') === '1';

    if (trim($dstHash) === '') {
        error_out('Bad dst');
        return;
    }

    $dstCtx = elfinder_context_from_hash((string)$dstHash);
    $dstRel = $dstCtx['rel'];
    $dstPhysical = elfinder_physical_path($dstCtx, $dstRel);
    elfinder_ensure_under_main_root($rootPhysical, $dstPhysical);
    if (!is_dir($dstPhysical)) {
        @mkdir($dstPhysical, 0775, true);
    }

    $added = [];
    $removed = [];

    foreach ($targets as $t) {
        $srcCtx = elfinder_context_from_hash((string)$t);
        $srcRel = $srcCtx['rel'];
        if ($srcRel === '' && !$srcCtx['is_trash']) {
            continue;
        }
        $srcPhysical = elfinder_physical_path($srcCtx, $srcRel);
        elfinder_ensure_under_main_root($rootPhysical, $srcPhysical);

        if (is_dir($srcPhysical)) {
            $newDir = ensure_unique($dstPhysical . DIRECTORY_SEPARATOR . basename($srcPhysical), true);
            if ($cut) {
                @rename($srcPhysical, $newDir);
                $removed[] = $t;
            } else {
                copydir_recursive($srcPhysical, $newDir);
            }
            $added[] = build_single_info($newDir, $rootPhysical, $baseUrl, $dstCtx['prefix'], $dstCtx['vol_base']);
        } elseif (is_file($srcPhysical)) {
            $ext = strtolower(pathinfo($srcPhysical, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOW_EXT, true)) {
                continue;
            }
            $newFile = ensure_unique($dstPhysical . DIRECTORY_SEPARATOR . basename($srcPhysical), false);
            if ($cut) {
                @rename($srcPhysical, $newFile);
                $removed[] = $t;
            } else {
                @copy($srcPhysical, $newFile);
            }
            $added[] = build_single_info($newFile, $rootPhysical, $baseUrl, $dstCtx['prefix'], $dstCtx['vol_base']);
        }
    }

    json_out(['added' => $added, 'removed' => $removed]);
}

// ====== duplicate ======
function handle_duplicate(): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $targets = get_param_values(['targets', 'targets[]']);
    $added = [];

    foreach ($targets as $t) {
        $ctx = elfinder_context_from_hash((string)$t);
        if ($ctx['rel'] === '' && !$ctx['is_trash']) {
            continue;
        }
        $full = elfinder_physical_path($ctx, $ctx['rel']);

        if (is_dir($full)) {
            $dupDir = ensure_unique(dirname($full) . DIRECTORY_SEPARATOR . basename($full) . ' copy', true);
            copydir_recursive($full, $dupDir);
            $added[] = build_single_info($dupDir, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
        } elseif (is_file($full)) {
            $name = pathinfo($full, PATHINFO_FILENAME);
            $ext  = pathinfo($full, PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ALLOW_EXT, true)) {
                continue;
            }
            $dupFile = ensure_unique(dirname($full) . DIRECTORY_SEPARATOR . $name . ' copy' . ($ext !== '' ? ".$ext" : ''), false);
            @copy($full, $dupFile);
            $added[] = build_single_info($dupFile, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
        }
    }

    json_out(['added' => $added]);
}

// ====== ls（列出目錄） ======
function handle_ls(): void {
    [$rootPhysical] = ensure_root();

    $target = $_REQUEST['target'] ?? '';
    if ($target === '') {
        $arr = get_param_values(['targets', 'targets[]']);
        if (!empty($arr)) {
            $target = $arr[0];
        }
    }

    $ctx = elfinder_context_from_hash((string)$target);
    $dir = elfinder_physical_path($ctx, $ctx['rel']);

    if (!is_dir($dir)) {
        json_out(['list' => new stdClass()]);
    }

    $dict = [];
    foreach (safe_enum_dirs($dir) as $d) {
        $name = basename($d);
        $relChild = trim(str_replace('\\', '/', to_rel($d, $ctx['vol_base'])), '/');
        $dict[$name] = hash_path($relChild, $ctx['prefix']);
    }
    foreach (safe_enum_files($dir) as $f) {
        $name = basename($f);
        $relChild = trim(str_replace('\\', '/', to_rel($f, $ctx['vol_base'])), '/');
        $dict[$name] = hash_path($relChild, $ctx['prefix']);
    }

    json_out(['list' => $dict]);
}

// ====== elFinder 節點輸出 ======
function as_root_info(string $rootPhysical): array {
    return [
        'name'     => 'files',
        'hash'     => hash_path(''),
        'phash'    => null,
        'mime'     => 'directory',
        'ts'       => file_exists($rootPhysical) ? filemtime($rootPhysical) : time(),
        'size'     => 0,
        'dirs'     => 1,
        'read'     => 1,
        'write'    => 1,
        'locked'   => 0,
        'volumeid' => VOL_PREFIX,
        'isroot'   => true,
    ];
}

function as_dir_info(string $physical, string $relFromRoot, string $rootPhysical, string $volPrefix = VOL_PREFIX, ?string $volBase = null): array {
    $volBase = $volBase ?? $rootPhysical;
    $name = $relFromRoot === '' ? 'files' : basename($physical);
    $parentRel = $relFromRoot === '' ? '' : str_replace('\\', '/', dirname($relFromRoot));
    if ($parentRel === '.' || $parentRel === '/') {
        $parentRel = '';
    } else {
        $parentRel = trim($parentRel, '/');
    }
    return [
        'name'     => $name,
        'hash'     => hash_path($relFromRoot, $volPrefix),
        'phash'    => $relFromRoot === '' ? null : hash_path($parentRel, $volPrefix),
        'mime'     => 'directory',
        'ts'       => is_dir($physical) ? (filemtime($physical) ?: time()) : time(),
        'size'     => 0,
        'dirs'     => has_child_dir($physical) ? 1 : 0,
        'read'     => 1,
        'write'    => 1,
        'locked'   => 0,
        'volumeid' => $volPrefix,
    ];
}

function as_file_info(string $physical, string $relFromRoot, string $baseUrl, string $volPrefix = VOL_PREFIX, ?string $volBase = null): array {
    $name = basename($physical);
    $dirRel = str_replace('\\', '/', dirname($relFromRoot));
    if ($dirRel === '.' || $dirRel === '/') {
        $dirRel = '';
    } else {
        $dirRel = trim($dirRel, '/');
    }

    $ext = strtolower(pathinfo($physical, PATHINFO_EXTENSION));
    $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp'], true);
    $url = url_combine($baseUrl, $volPrefix === TRASH_VOL_PREFIX ? '' : $relFromRoot);

    $info = [
        'name'     => $name,
        'hash'     => hash_path($relFromRoot, $volPrefix),
        'phash'    => hash_path($dirRel, $volPrefix),
        'mime'     => get_mime_by_ext($ext),
        'ts'       => is_file($physical) ? (filemtime($physical) ?: time()) : time(),
        'size'     => is_file($physical) ? (filesize($physical) ?: 0) : 0,
        'read'     => 1,
        'write'    => 1,
        'locked'   => 0,
        'url'      => $url,
        'volumeid' => $volPrefix,
    ];

    if ($isImg && $volPrefix !== TRASH_VOL_PREFIX) {
        $info['tmb'] = $url;
    }

    return $info;
}

function build_single_info(string $physical, string $rootPhysical, string $baseUrl, string $volPrefix = VOL_PREFIX, ?string $volBase = null): array {
    $volBase = $volBase ?? $rootPhysical;
    $rel = trim(str_replace('\\', '/', to_rel($physical, $volBase)), '/');
    return is_dir($physical)
        ? as_dir_info($physical, $rel, $rootPhysical, $volPrefix, $volBase)
        : as_file_info($physical, $rel, $baseUrl, $volPrefix, $volBase);
}

// ====== 小工具 ======
function ensure_root(): array {
    $rootV = (string)ROOT_VPATH;

    // 實體儲存路徑
    $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? __FILE__;
    $scriptDir  = is_string($scriptFile) ? dirname($scriptFile) : __DIR__;

    if (_starts_with($rootV, '/')) {
        $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $rootPhysical = $docRoot !== '' ? $docRoot . '/' . ltrim($rootV, '/') : normalize_path(__DIR__ . '/' . ltrim($rootV, '/'));
    } else {
        $rootPhysical = normalize_path($scriptDir . '/' . $rootV);
    }
    if (!is_dir($rootPhysical)) @mkdir($rootPhysical, 0775, true);

    // 公開 URL
    $baseUrl = get_base_url();
    return [$rootPhysical, $baseUrl];
}

function safe_enum_dirs(string $p): array {
    try { $it = @scandir($p); if ($it === false) return []; $out = [];
        foreach ($it as $name) { if ($name === '.' || $name === '..') continue; $full = $p . DIRECTORY_SEPARATOR . $name; if (is_dir($full)) $out[] = $full; }
        return $out;
    } catch (Throwable) { return []; }
}

function safe_enum_files(string $p): array {
    try { $it = @scandir($p); if ($it === false) return []; $out = [];
        foreach ($it as $name) { if ($name === '.' || $name === '..') continue; $full = $p . DIRECTORY_SEPARATOR . $name; if (is_file($full)) $out[] = $full; }
        return $out;
    } catch (Throwable) { return []; }
}

function to_rel(string $full, string $rootPhysical): string {
    $root  = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPhysical), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $fullN = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $full), DIRECTORY_SEPARATOR);
    if (!_starts_with(strtolower($fullN) . DIRECTORY_SEPARATOR, strtolower($root))) return '';
    return ltrim(substr($fullN, strlen($root)), DIRECTORY_SEPARATOR);
}

function combine_safe(string $root, string $rel): string {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $real = realpath($path);
    return $real !== false ? $real : normalize_path($path);
}

function normalize_path(string $p): string {
    $parts = [];
    foreach (explode(DIRECTORY_SEPARATOR, str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $p)) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($parts); continue; }
        $parts[] = $seg;
    }
    $prefix = _starts_with($p, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
    return $prefix . implode(DIRECTORY_SEPARATOR, $parts);
}

function ensure_under_root(string $root, string $path): void {
    if (!is_under_root($root, $path)) throw new HttpException(403, 'Forbidden');
}

function is_under_root(string $root, string $path): bool {
    $r = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $p = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return _starts_with(strtolower($p), strtolower($r));
}

function ensure_unique(string $fullPath, bool $isDir): string {
    if ($isDir) {
        if (!is_dir($fullPath)) return $fullPath;
        $base = basename($fullPath); $parent = dirname($fullPath); $i = 1;
        do { $try = $parent . DIRECTORY_SEPARATOR . sprintf('%s (%d)', $base, $i++); } while (is_dir($try));
        return $try;
    } else {
        if (!file_exists($fullPath)) return $fullPath;
        $dir = dirname($fullPath);
        $name = pathinfo($fullPath, PATHINFO_FILENAME);
        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
        $i = 1; do { $try = $dir . DIRECTORY_SEPARATOR . sprintf('%s (%d)%s', $name, $i++, $ext !== '' ? ".$ext" : ''); } while (file_exists($try));
        return $try;
    }
}

function get_mime_by_ext(string $ext): string {
    switch ($ext) {
        case 'jpg':
        case 'jpeg': return 'image/jpeg';
        case 'png':  return 'image/png';
        case 'gif':  return 'image/gif';
        case 'webp': return 'image/webp';
        case 'svg':  return 'image/svg+xml';
        case 'bmp':  return 'image/bmp';
        case 'txt':  return 'text/plain';
        case 'md':   return 'text/markdown';
        default:     return 'application/octet-stream';
    }
}

function sanitize_filename(string $input, bool $isDir): ?string {
    $name = trim(basename($input));
    if ($name === '') return null;
    $name = str_replace(['/', '\\'], '_', $name);
    $invalid = ["\0","\r","\n","\t",'"','<','>','|',':','*','?'];
    $name = str_replace($invalid, '_', $name);
    $name = rtrim($name, " .");
    if ($name === '.' || $name === '..' || $name === '') return null;
    foreach (RESERVED_WIN as $r) {
        if (strcasecmp($name, $r) === 0) { $name = '_' . $name; break; }
    }
    if (mb_strlen($name) > 255) $name = mb_substr($name, 0, 255);
    return $name;
}

function hash_path(?string $rel, string $prefix = VOL_PREFIX): string {
    $rel = trim(str_replace('\\', '/', (string)$rel), '/');
    $b64 = rtrim(strtr(base64_encode($rel), '+/', '-_'), '=');
    return $prefix . $b64;
}

function unhash_path(?string $hash): string {
    if ($hash === null || $hash === '') {
        return '';
    }
    $body = (string)$hash;
    if (_starts_with($body, TRASH_VOL_PREFIX)) {
        $body = substr($body, strlen(TRASH_VOL_PREFIX));
    } elseif (_starts_with($body, VOL_PREFIX)) {
        $body = substr($body, strlen(VOL_PREFIX));
    }
    $pad = strlen($body) % 4;
    if ($pad) {
        $body .= str_repeat('=', 4 - $pad);
    }
    $raw = base64_decode(strtr($body, '-_', '+/'), true);
    return $raw === false ? '' : $raw;
}

function hash_volume(?string $hash): string {
    if ($hash !== null && _starts_with((string)$hash, TRASH_VOL_PREFIX)) {
        return TRASH_VOL_PREFIX;
    }
    return VOL_PREFIX;
}

function elfinder_trash_physical(string $rootPhysical): string {
    $path = $rootPhysical . DIRECTORY_SEPARATOR . TRASH_DIR_NAME;
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
    return $path;
}

/** @return array{main_root:string,trash_root:string,vol_base:string,rel:string,prefix:string,is_trash:bool,base_url:string} */
function elfinder_context_from_hash(?string $hash): array {
    [$rootPhysical, $baseUrl] = ensure_root();
    $trashPhysical = elfinder_trash_physical($rootPhysical);
    $prefix = hash_volume($hash);
    $rel = trim(str_replace('\\', '/', unhash_path($hash)), '/');
    $isTrash = ($prefix === TRASH_VOL_PREFIX);
    return [
        'main_root'  => $rootPhysical,
        'trash_root' => $trashPhysical,
        'vol_base'   => $isTrash ? $trashPhysical : $rootPhysical,
        'rel'        => $rel,
        'prefix'     => $prefix,
        'is_trash'   => $isTrash,
        'base_url'   => $baseUrl,
    ];
}

function elfinder_physical_path(array $ctx, string $rel): string {
    $path = combine_safe($ctx['vol_base'], $rel);
    elfinder_ensure_under_main_root($ctx['main_root'], $path);
    return $path;
}

function elfinder_ensure_under_main_root(string $mainRoot, string $path): void {
    $trashRoot = elfinder_trash_physical($mainRoot);
    if (is_under_root($mainRoot, $path) || is_under_root($trashRoot, $path)) {
        return;
    }
    throw new HttpException(403, 'Forbidden');
}

function elfinder_trash_root_info(string $trashPhysical): array {
    return [
        'name'     => TRASH_DIR_NAME,
        'hash'     => hash_path('', TRASH_VOL_PREFIX),
        'phash'    => null,
        'mime'     => 'directory',
        'ts'       => is_dir($trashPhysical) ? (filemtime($trashPhysical) ?: time()) : time(),
        'size'     => 0,
        'dirs'     => has_child_dir($trashPhysical) ? 1 : 0,
        'read'     => 1,
        'write'    => 1,
        'locked'   => 0,
        'volumeid' => TRASH_VOL_PREFIX,
        'isroot'   => true,
        'alias'    => 'Trash',
        'csscls'   => 'elfinder-navbar-root-trash',
    ];
}

/** 主磁碟區回應中附上回收桶根節點，供側欄與 trashHash 驗證 */
function elfinder_append_trash_root_file(array &$files, string $trashPhysical): void {
    $trashHash = hash_path('', TRASH_VOL_PREFIX);
    foreach ($files as $file) {
        if (($file['hash'] ?? '') === $trashHash) {
            return;
        }
    }
    $files[] = elfinder_trash_root_info($trashPhysical);
}

/** @param list<array<string,mixed>> $files */
function elfinder_files_has_hash(array $files, string $hash): bool {
    if ($hash === '') {
        return false;
    }
    foreach ($files as $file) {
        if (($file['hash'] ?? '') === $hash) {
            return true;
        }
    }
    return false;
}

/** @param list<array<string,mixed>> $files */
function elfinder_collect_tree_entries(array $files): array {
    $tree = [];
    $mainHash = hash_path('');
    $trashHash = hash_path('', TRASH_VOL_PREFIX);
    foreach ($files as $file) {
        if (($file['mime'] ?? '') !== 'directory') {
            continue;
        }
        $hash = (string)($file['hash'] ?? '');
        if ($hash === $mainHash || $hash === $trashHash) {
            $tree[] = $file;
        }
    }
    return $tree;
}

// ====== info ======
function handle_info(): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $targets = get_param_values(['targets', 'targets[]']);
    $result = [];

    foreach ($targets as $t) {
        $ctx = elfinder_context_from_hash((string)$t);
        if ($ctx['is_trash'] && $ctx['rel'] === '') {
            $result[] = elfinder_trash_root_info($ctx['trash_root']);
            continue;
        }
        if (!$ctx['is_trash'] && $ctx['rel'] === '') {
            $result[] = as_root_info($rootPhysical);
            continue;
        }
        $full = elfinder_physical_path($ctx, $ctx['rel']);
        if (!is_file($full) && !is_dir($full)) {
            continue;
        }
        $result[] = build_single_info($full, $rootPhysical, $baseUrl, $ctx['prefix'], $ctx['vol_base']);
    }

    json_out(['files' => $result]);
}

// ====== tree ======
function handle_tree(): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $trashPhysical = elfinder_trash_physical($rootPhysical);

    $target = $_REQUEST['target'] ?? '';
    $ctx = elfinder_context_from_hash((string)$target);
    $cwdPhysical = elfinder_physical_path($ctx, $ctx['rel']);

    $tree = [as_root_info($rootPhysical)];
    if ($ctx['is_trash']) {
        $tree[] = elfinder_trash_root_info($trashPhysical);
    }

    if ($ctx['rel'] !== '') {
        $tree[] = as_dir_info($cwdPhysical, $ctx['rel'], $rootPhysical, $ctx['prefix'], $ctx['vol_base']);
    } elseif (!$ctx['is_trash']) {
        $tree[] = as_dir_info($cwdPhysical, $ctx['rel'], $rootPhysical, $ctx['prefix'], $ctx['vol_base']);
    }

    foreach (safe_enum_dirs($cwdPhysical) as $d) {
        $name = basename($d);
        if (!$ctx['is_trash'] && $ctx['rel'] === '' && strcasecmp($name, TRASH_DIR_NAME) === 0) {
            continue;
        }
        $rel = trim(str_replace('\\', '/', to_rel($d, $ctx['vol_base'])), '/');
        $tree[] = as_dir_info($d, $rel, $rootPhysical, $ctx['prefix'], $ctx['vol_base']);
    }

    json_out(['tree' => $tree]);
}

// ====== parents ======
function handle_parents(): void {
    [$rootPhysical, $baseUrl] = ensure_root();
    $trashPhysical = elfinder_trash_physical($rootPhysical);

    $target = $_REQUEST['target'] ?? '';
    $ctx = elfinder_context_from_hash((string)$target);
    $tree = [as_root_info($rootPhysical)];
    if ($ctx['is_trash']) {
        $tree[] = elfinder_trash_root_info($trashPhysical);
    }

    if ($ctx['rel'] !== '') {
        $parts = explode('/', $ctx['rel']);
        $acc = '';
        foreach ($parts as $part) {
            $acc = $acc === '' ? $part : $acc . '/' . $part;
            $physical = elfinder_physical_path($ctx, $acc);
            if (is_dir($physical)) {
                $tree[] = as_dir_info($physical, $acc, $rootPhysical, $ctx['prefix'], $ctx['vol_base']);
            }
        }
    }

    json_out(['tree' => $tree]);
}

function elfinder_count_tree(string $dir, int &$fileCnt, int &$dirCnt): void {
    if (!is_dir($dir)) {
        return;
    }
    $items = @scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            $dirCnt++;
            elfinder_count_tree($full, $fileCnt, $dirCnt);
        } elseif (is_file($full)) {
            $fileCnt++;
        }
    }
}

// ====== size ======
function handle_size(): void {
    [$rootPhysical] = ensure_root();
    $targets = get_param_values(['targets', 'targets[]']);
    $fileCnt = 0;
    $dirCnt = 0;
    $totalSize = 0;

    foreach ($targets as $t) {
        $ctx = elfinder_context_from_hash((string)$t);
        $full = elfinder_physical_path($ctx, $ctx['rel']);
        if (is_dir($full)) {
            $dirCnt++;
            elfinder_count_tree($full, $fileCnt, $dirCnt);
        } elseif (is_file($full)) {
            $fileCnt++;
            $totalSize += (int)(filesize($full) ?: 0);
        }
    }

    json_out([
        'fileCnt'  => (string)$fileCnt,
        'dirCnt'   => (string)$dirCnt,
        'size'     => $totalSize,
        'formated' => number_format($fileCnt + $dirCnt) . ' items',
    ]);
}

// ====== empty（清空回收桶） ======
function handle_empty(): void {
    [$rootPhysical] = ensure_root();
    $target = $_REQUEST['target'] ?? '';
    $ctx = elfinder_context_from_hash((string)$target);
    if (!$ctx['is_trash']) {
        error_out('errCmdParams');
        return;
    }
    $dir = elfinder_physical_path($ctx, $ctx['rel']);
    foreach (safe_enum_dirs($dir) as $d) {
        rrmdir($d);
    }
    foreach (safe_enum_files($dir) as $f) {
        @unlink($f);
    }
    json_out(['changed' => [elfinder_trash_root_info($ctx['trash_root'])]]);
}

function json_out($data): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function error_out(string $msg): void {
    json_out(['error' => [$msg]]);
}

function get_param_values(array $keys): array {
    foreach ($keys as $k) {
        if (isset($_REQUEST[$k])) {
            $v = $_REQUEST[$k];
            return is_array($v) ? $v : [$v];
        }
    }
    return [];
}

function has_child_dir(string $dir): bool {
    $h = @opendir($dir); if (!$h) return false;
    while (($e = readdir($h)) !== false) {
        if ($e === '.' || $e === '..') continue;
        if (is_dir($dir . DIRECTORY_SEPARATOR . $e)) { closedir($h); return true; }
    }
    closedir($h); return false;
}

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = scandir($dir); if (!$items) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) rrmdir($full); else @unlink($full);
    }
    @rmdir($dir);
}

function copydir_recursive(string $src, string $dst): void {
    if (!is_dir($src)) return;
    if (!is_dir($dst)) @mkdir($dst, 0775, true);
    $items = scandir($src); if (!$items) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $from = $src . DIRECTORY_SEPARATOR . $item;
        $to   = $dst . DIRECTORY_SEPARATOR . $item;
        if (is_dir($from)) copydir_recursive($from, $to);
        else @copy($from, $to);
    }
}

function get_base_url(): string {
    $rootV = (string)ROOT_VPATH;
    if (_starts_with($rootV, '/')) return rtrim($rootV, '/');
    $scriptUrlDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    $joined = $scriptUrlDir . '/' . $rootV;
    $parts = [];
    foreach (explode('/', str_replace('\\','/', $joined)) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($parts); continue; }
        $parts[] = $seg;
    }
    return '/' . implode('/', $parts);
}

function url_combine(string $baseUrl, string $relativePath): string {
    $baseUrl = rtrim($baseUrl, '/');
    $rel = trim(str_replace('\\','/', $relativePath), '/');
    if ($rel === '') return $baseUrl;
    $parts = array_values(array_filter(explode('/', $rel), fn($p) => $p !== ''));
    $encoded = array_map(fn($p) => rawurlencode($p), $parts);
    return $baseUrl . '/' . implode('/', $encoded);
}

function flatten_files_array(array $files): array {
    $out = [];
    $normalize = function($name, $type, $tmp_name, $error, $size) use (&$out, &$normalize) {
        if (is_array($name)) {
            foreach ($name as $i => $n) {
                $normalize($n, $type[$i] ?? null, $tmp_name[$i] ?? null, $error[$i] ?? null, $size[$i] ?? null);
            }
        } else {
            $out[] = [
                'name'     => (string)$name,
                'type'     => (string)($type ?? ''),
                'tmp_name' => (string)($tmp_name ?? ''),
                'error'    => (int)($error ?? 0),
                'size'     => (int)($size ?? 0)
            ];
        }
    };
    foreach ($files as $f) {
        $normalize($f['name'] ?? null, $f['type'] ?? null, $f['tmp_name'] ?? null, $f['error'] ?? null, $f['size'] ?? null);
    }
    return $out;
}

// ====== 例外類型 ======
class HttpException extends Exception {
    public $statusCode;
    public function __construct($statusCode, $message) {
        $this->statusCode = (int)$statusCode;
        parent::__construct($message, (int)$statusCode);
        if (function_exists('http_response_code')) http_response_code((int)$statusCode);
    }
}
