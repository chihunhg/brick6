<?php
/**
 * 共用刪除模組
 * 需求：已載入 _inc.php 讓 chkTable/recordset/manage_history/sql_error 等可用
 * 依你的 dbPDO 介面：delete($table, $key_column, $value)
 */

if (!function_exists('join_path')) {
    function join_path(...$parts) {
        $parts = array_map(function($p){ return trim((string)$p, "/\\"); }, $parts);
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        // 若第一個參數像 D:\ 或 / 根目錄，保留
        return $path;
    }
}

/** 刪單一檔案 + 對應 webp / 縮圖；回傳 ['deleted'=>[], 'miss'=>[]] */
if (!function_exists('delete_physical_files')) {
    function delete_physical_files(string $baseDir, string $folder, string $filename, array $opt = []): array {
        $res = ['deleted'=>[], 'miss'=>[]];

        $baseDir = rtrim($baseDir, "/\\");
        $folder  = trim($folder, "/\\");
        $photo   = (string)$filename;
        if ($photo === '') return $res;

        $full = $baseDir . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $photo;
        if (is_file($full)) { @unlink($full); $res['deleted'][] = $full; } else { $res['miss'][] = $full; }

        // webp
        if (!empty($opt['webp'])) {
            $ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
            if ($ext !== '') {
                $webp = preg_replace('/\.'.preg_quote($ext,'/').'$/i', '.webp', $photo);
                $webpPath = $baseDir . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $webp;
                if (is_file($webpPath)) { @unlink($webpPath); $res['deleted'][] = $webpPath; } else { $res['miss'][] = $webpPath; }
            }
        }
        // 縮圖
        if (!empty($opt['thumb_prefix'])) {
            $thumb = $opt['thumb_prefix'] . $photo;
            $thumbPath = $baseDir . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $thumb;
            if (is_file($thumbPath)) { @unlink($thumbPath); $res['deleted'][] = $thumbPath; } else { $res['miss'][] = $thumbPath; }
        }
        return $res;
    }
}

/**
 * 依主鍵 IDs 刪除主表 + 關聯子表 + 檔案
 *
 * @param array $ids 主鍵值（int）陣列
 * @param array $cfg 設定：
 *   - table      主表名（如 dbad）
 *   - pk         主鍵名（如 PKey）
 *   - fk         子表外鍵名（如 AD_PKey）
 *   - upload_base 檔案根目錄（絕對路徑）
 *   - module     array{Module_PKey, Module_Name, WorkFile, Login_ID} 供 manage_history 使用
 *   - img_table  (可選) 圖檔表名（預設 table.'_img'）
 *   - msg_table  (可選) 內文表名（預設 table.'_msg'）
 *   - lang_table (可選) 語系表名（預設 table.'_lang'）
 *   - relation_table (可選) 關聯表名（預設 table.'_relation'）
 *   - img_folder_col  (可選) 圖檔表中的資料夾欄位（預設 'Forder'）
 *   - img_file_cols   (可選) 圖檔檔名欄位清單（預設 ['Photo1']）
 *   - img_thumb_prefix(可選) 縮圖前綴（預設 'thumb_'；給空字串停用）
 *   - img_has_webp    (可選) 是否刪除對應 webp（預設 true）
 *
 * @return array 結果：['ok'=>bool, 'deleted'=>int, 'miss_files'=>[], 'errors'=>[]]
 */
if (!function_exists('delete_cascade_by_ids')) {
function delete_cascade_by_ids(array $ids, array $cfg): array {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $ids = array_filter($ids, fn($x) => $x > 0);

    $ret = ['ok'=>true, 'deleted'=>0, 'miss_files'=>[], 'errors'=>[]];
    if (empty($ids)) return $ret;

    if (isset($cfg['table_relation']) && !isset($cfg['relation_table'])) {
        $cfg['relation_table'] = $cfg['table_relation'];
    }

    $table       = $cfg['table'];
    $pk          = $cfg['pk'];
    $fk          = $cfg['fk'];
    $upload_base = rtrim((string)$cfg['upload_base'], "/\\");
    $module      = $cfg['module'] ?? ['Module_PKey'=>0,'Module_Name'=>'','WorkFile'=>'','Login_ID'=>''];

    $img_table   = $cfg['img_table']  ?? ($table . '_img');
    $msg_table   = $cfg['msg_table']  ?? ($table . '_msg');
    $lang_table  = $cfg['lang_table'] ?? ($table . '_lang');
    $relation_table  = $cfg['relation_table'] ?? ($table . '_relation');
    $link_table      = $cfg['link_table'] ?? ($table . '_link');

    $img_folder_col   = $cfg['img_folder_col']    ?? 'Forder';
    $img_file_cols    = $cfg['img_file_cols']     ?? ['Photo1'];
    $img_thumb_prefix = array_key_exists('img_thumb_prefix',$cfg) ? (string)$cfg['img_thumb_prefix'] : 'thumb_';
    $img_has_webp     = array_key_exists('img_has_webp',$cfg) ? (bool)$cfg['img_has_webp'] : true;

    foreach ($ids as $id) {
        try {
            // === 1) 刪子表：img ===
            if (function_exists('chkTable') && chkTable($img_table)) {
                $sql = "SELECT PKey, {$img_folder_col} AS _folder, " . implode(',', $img_file_cols) .
                       " FROM {$img_table} WHERE {$fk} = :{$fk}";
                $rs1 = new recordset($sql, array($id));
                $SQL_Error = $rs1->getErrorMessage();
                if (!empty($SQL_Error)) {
                    $ret['ok'] = false;
                    $ret['errors'][] = $SQL_Error;
                    sql_error($sql . PHP_EOL . "{$fk}={$id}", $SQL_Error, $module['WorkFile'], 'system');
                    continue;
                }

                while (!$rs1->eof) {
                    $folder = (string)$rs1->field('_folder');

                    // 刪檔（每個檔案欄位都處理）
                    foreach ($img_file_cols as $col) {
                        $file = (string)$rs1->field($col);
                        if ($file !== '') {
                            $r = delete_physical_files($upload_base, $folder, $file, [
                                'webp'         => $img_has_webp,
                                'thumb_prefix' => $img_thumb_prefix !== '' ? $img_thumb_prefix : null,
                            ]);
                            // 記 miss（僅記錄，不視為致命）
                            foreach ($r['miss'] as $miss) {
                                $ret['miss_files'][] = $miss;
                                if (function_exists('manage_history')) {
                                    manage_history(
                                        $module['Module_PKey'], $module['Module_Name'],
                                        "[delete_records_batch][files]\n[MISS] {$miss}",
                                        $module['WorkFile'], 'system', '程式警告'
                                    );
                                }
                            }
                        }
                    }

                    // 刪 img 行
                    $pdo = new dbPDO();
                    $pdo->delete($img_table, 'PKey', (int)$rs1->field('PKey'));
                    if (method_exists($pdo, 'getErrorMessage')) {
                        $e = $pdo->getErrorMessage();
                        if (!empty($e)) {
                            $ret['ok'] = false;
                            $ret['errors'][] = $e;
                            sql_error($pdo->getLastSql(), $e, $module['WorkFile'], 'system');
                            $pdo->close();
                            // 不中斷，繼續後面的刪除
                        }
                    }
                    if (function_exists('manage_history')) {
                        $SQL_U = $pdo->getLastSql() . "\nPKey=" . $rs1->field('PKey');
                        manage_history($module['Module_PKey'], $module['Module_Name'], $SQL_U, $module['WorkFile'], $module['Login_ID'], '刪除檔案');
                    }
                    $pdo->close();
                    $rs1->movenext();
                }
            }

            // === 2) 刪子表：msg ===
            if (function_exists('chkTable') && chkTable($msg_table)) {
                $pdo = new dbPDO();
                $pdo->delete($msg_table, $fk, (int)$id);
                if (function_exists('manage_history')) {
                    $SQL_U = $pdo->getLastSql() . "\n{$fk}={$id}";
                    manage_history($module['Module_PKey'], $module['Module_Name'], $SQL_U, $module['WorkFile'], $module['Login_ID'], '刪除檔案');
                }
                $pdo->close();
            }

            // === 3) 刪子表：lang ===
            if (function_exists('chkTable') && chkTable($lang_table)) {
                $pdo = new dbPDO();
                $pdo->delete($lang_table, $fk, (int)$id);
                if (function_exists('manage_history')) {
                    $SQL_U = $pdo->getLastSql() . "\n{$fk}={$id}";
                    manage_history($module['Module_PKey'], $module['Module_Name'], $SQL_U, $module['WorkFile'], $module['Login_ID'], '刪除檔案');
                }
                $pdo->close();
            }

            // === 4) 刪子表：relation ===
            if (function_exists('chkTable') && chkTable($relation_table)) {
                $pdo = new dbPDO();
                $pdo->delete($relation_table, $fk, (int)$id);
                if (function_exists('manage_history')) {
                    $SQL_U = $pdo->getLastSql() . "\n{$fk}={$id}";
                    manage_history($module['Module_PKey'], $module['Module_Name'], $SQL_U, $module['WorkFile'], $module['Login_ID'], '刪除檔案');
                }
                $pdo->close();
            }

            // === 5) 刪子表：link（如 webcontrol_link）===
            if (function_exists('chkTable') && chkTable($link_table)) {
                $pdo = new dbPDO();
                $pdo->delete($link_table, $fk, (int)$id);
                if (function_exists('manage_history')) {
                    $SQL_U = $pdo->getLastSql() . "\n{$fk}={$id}";
                    manage_history($module['Module_PKey'], $module['Module_Name'], $SQL_U, $module['WorkFile'], $module['Login_ID'], '刪除檔案');
                }
                $pdo->close();
            }

            // === 6) 刪主表 ===
            $pdo = new dbPDO();
            $pdo->delete($table, $pk, (int)$id);
            if (function_exists('manage_history')) {
                $SQL_U = $pdo->getLastSql() . "\n{$pk}={$id}";
                manage_history($module['Module_PKey'], $module['Module_Name'], $SQL_U, $module['WorkFile'], $module['Login_ID'], '刪除檔案');
            }
            $pdo->close();
            $ret['deleted']++;

        } catch (Throwable $ex) {
            $ret['ok'] = false;
            $ret['errors'][] = $ex->getMessage();
            if (function_exists('sql_error')) {
                sql_error("delete_cascade_by_ids({$table}) {$pk}={$id}", $ex->getMessage(), $module['WorkFile'], 'system');
            }
            // 繼續處理其他 ID
        }
    } // end foreach id

    return $ret;
}}


/* =========================================================
 *  基礎：安全檢查資料表是否存在
 * ======================================================= */
if (!function_exists('tableExists')) {
    function tableExists(string $table): bool {
        try {
            $pdo = sql_conn();
            if (!$pdo) return false;

            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
                if (!$db) return false;
                $sql = "SELECT 1
                          FROM information_schema.tables
                         WHERE table_schema = :db AND LOWER(table_name) = LOWER(:tb)
                         LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([':db' => $db, ':tb' => $table]);
                return (bool)$st->fetchColumn();
            } elseif ($driver === 'sqlite') {
                $st = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:tb");
                $st->execute([':tb' => $table]);
                return (bool)$st->fetchColumn();
            } else {
                $st = $pdo->prepare("
                    SELECT 1
                      FROM information_schema.tables
                     WHERE table_name = :tb
                     LIMIT 1
                ");
                $st->execute([':tb' => $table]);
                return (bool)$st->fetchColumn();
            }
        } catch (Throwable $e) {
            if (function_exists('sql_error')) {
                sql_error('tableExists('.$table.')', $e->getMessage(), $GLOBALS['WorkFile'] ?? '', (string)($GLOBALS['Login_ID'] ?? 'system'));
            }
            return false;
        }
    }
}

if (!function_exists('chkTableCompat')) {
    // 先嘗試專案既有的 chkTable()；失敗或拋例外時，回落到 tableExists()
    function chkTableCompat(string $table): bool {
        if (function_exists('chkTable')) {
            try { return (bool)chkTable($table); } catch (Throwable $e) { /* fallthrough */ }
        }
        return tableExists($table);
    }
}

/* =========================================================
 *  批次更新 Sort（含 dtUDate）
 * ======================================================= */
/**
 * 批次更新 Sort（含可選 dtUDate），逐筆寫入 manage_history；並輸出 DEBUG 到 error_log
 * @param string $tableName  資料表（如 'dbad'）
 * @param array  $items      例如：[['PKey'=>1,'Sort'=>3], ['PKey'=>2,'Sort'=>5]]
 * @param string $PKName     主鍵欄名（預設 'PKey'）
 * @return array{ok: array<int,int>, fail: array<int,string>}
 */
function UpdateSortBatch(string $tableName, array $items, string $PKName = 'PKey'): array {
    $res = ['ok' => [], 'fail' => []];

    if (!chkTableCompat($tableName)) {
        error_log("[UpdateSortBatch] table not found (compat): {$tableName}");
        return $res;
    }
    if (empty($items)) {
        error_log("[UpdateSortBatch] empty items");
        return $res;
    }

    $WorkFile    = $GLOBALS['WorkFile']    ?? '';
    $Login_ID    = $GLOBALS['Login_ID']    ?? '';
    $Module_PKey = $GLOBALS['Module_PKey'] ?? '';
    $Module_Name = $GLOBALS['Module_Name'] ?? '';

    try {
        $pdo = sql_conn();
        if ($pdo === null) {
            if (function_exists('sql_error')) sql_error('UpdateSortBatch', '資料庫連線失敗', $WorkFile, (string)$Login_ID);
            return $res;
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // 是否有 dtUDate 欄位
        $hasDtUDate = false;
        try {
            $ck = $pdo->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE 'dtUDate'");
            $ck->execute();
            $hasDtUDate = (bool)$ck->fetchColumn();
        } catch (Throwable $e) {
            error_log("[UpdateSortBatch] SHOW COLUMNS error: " . $e->getMessage());
        }

        $sql = $hasDtUDate
            ? "UPDATE `{$tableName}` SET `Sort` = :sort, `dtUDate` = :dtUDate WHERE `{$PKName}` = :pk"
            : "UPDATE `{$tableName}` SET `Sort` = :sort WHERE `{$PKName}` = :pk";
        $stmt = $pdo->prepare($sql);

        $pdo->beginTransaction();

        foreach ($items as $row) {
            $pk   = (int)($row[$PKName] ?? 0);
            $sort = (int)($row['Sort'] ?? 0);
            if ($pk <= 0) { $res['fail'][$pk] = '主鍵無效'; continue; }

            try {
                $params = [':sort' => $sort, ':pk' => $pk];
                if ($hasDtUDate) $params[':dtUDate'] = date('Y-m-d H:i:s');

                $stmt->execute($params);
                $affected = $stmt->rowCount(); // 值相同可為 0
                $res['ok'][$pk] = $affected;

                if (function_exists('manage_history')) {
                    $logParams = http_build_query(array_combine(
                        array_map(fn($k) => ltrim($k, ':'), array_keys($params)),
                        array_values($params)
                    ), '', '; ');
                    manage_history($Module_PKey, $Module_Name, $sql . "\n" . $logParams, $WorkFile, $Login_ID, '更新順序');
                }
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $res['fail'][$pk] = $msg;
                if (function_exists('sql_error')) {
                    $logParams = "Sort={$sort}; {$PKName}={$pk}" . ($hasDtUDate ? '; dtUDate=NOW()' : '');
                    sql_error($sql . "\n" . $logParams, $msg, $WorkFile, (string)$Login_ID);
                }
                error_log("[UpdateSortBatch][ERROR] pk={$pk} sort={$sort} msg=" . $msg);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        try { $pdo?->rollBack(); } catch (Throwable $e2) {}
        if (function_exists('sql_error')) sql_error('UpdateSortBatch(tx)', $e->getMessage(), $WorkFile, (string)$Login_ID);
        error_log("[UpdateSortBatch][FATAL] " . $e->getMessage());
    }

    error_log("[UpdateSortBatch] done table={$tableName} ok=" . count($res['ok']) . " fail=" . count($res['fail']));
    return $res;
}

/* =========================================================
 *  單筆刪除圖片 row（含實體檔）
 * ======================================================= */
function DeleteImageRow(string $tableName, ?int $pkey = null, string $pkName = 'PKey'): bool {
    if ($pkey === null && isset($GLOBALS['filter_array'][$pkName]) && is_numeric($GLOBALS['filter_array'][$pkName])) {
        $pkey = (int)$GLOBALS['filter_array'][$pkName];
    }
    if (!is_int($pkey)) return false;

    $WorkFile    = $GLOBALS['WorkFile']    ?? '';
    $Login_ID    = $GLOBALS['Login_ID']    ?? '';
    $Module_PKey = $GLOBALS['Module_PKey'] ?? '';
    $Module_Name = $GLOBALS['Module_Name'] ?? '';
    $upload_dir  = $GLOBALS['upload_foder'] ?? ($GLOBALS['upload_folder'] ?? '');

    if (!chkTableCompat($tableName)) return false;

    $sql = "SELECT {$pkName} AS PKey, Photo1, Forder FROM {$tableName} WHERE {$pkName} = :pk LIMIT 1";
    $rs  = new recordset($sql, [':pk' => $pkey]);
    $err = $rs->getErrorMessage();
    if (!empty($err)) {
        if (function_exists('sql_error')) sql_error($sql . PHP_EOL . "pk={$pkey}", $err, $WorkFile, (string)$Login_ID);
        return false;
    }
    if ($rs->eof) { $rs->close(); return false; }

    $photo  = (string)$rs->field('Photo1');
    $forder = (string)$rs->field('Forder');
    $rowPk  = (int)$rs->field('PKey');

    if ($photo !== '' && $upload_dir !== '') {
        $baseDir = rtrim($upload_dir, '/\\') . '/' . $forder;
        deleteImageFiles($baseDir, $photo);
    }
    $rs->close();

    $pdo = new dbPDO();
    $ok  = $pdo->delete($tableName, $pkName, $rowPk);
    $SQL_U = $pdo->getLastSql() . "\n{$pkName}={$rowPk}";
    $SQL_Error = $pdo->getErrorMessage();

    if (!$ok || !empty($SQL_Error)) {
        if (function_exists('sql_error')) sql_error($SQL_U, $SQL_Error ?: '刪除失敗', $WorkFile, (string)$Login_ID);
        $pdo->close();
        return false;
    }
    $pdo->close();

    if (function_exists('manage_history')) {
        manage_history($Module_PKey, $Module_Name, $SQL_U, $WorkFile, $Login_ID, '刪除檔案');
    }
    return true;
}

/**
 * 便捷：只更新 Upload 欄位（Yes/No）
 */
function update_upload_by_table(
    string $table,
    string $pkName,
    int $pkey,
    string $uploadValue,
    array $ctx = [],
    ?string $srcFile = null,
    ?int $srcLine = null
): array {
    $ret = [
        'ok'     => false,
        'error'  => '',
        'sql'    => '',
        'params' => []
    ];

    $WorkFile    = $ctx['WorkFile']    ?? ($GLOBALS['WorkFile'] ?? '');
    $Login_ID    = $ctx['Login_ID']    ?? ($GLOBALS['Login_ID'] ?? 'system');
    $Module_PKey = $ctx['Module_PKey'] ?? ($GLOBALS['Module_PKey'] ?? 0);
    $Module_Name = $ctx['Module_Name'] ?? ($GLOBALS['Module_Name'] ?? '');
    $Action      = $ctx['Action']      ?? '更新上下架';

    if ($pkey <= 0) {
        $ret['error'] = 'PKey 錯誤';
        return $ret;
    }

    if ($uploadValue !== 'Yes' && $uploadValue !== 'No') {
        $ret['error'] = 'Upload 值錯誤';
        return $ret;
    }

    if (!function_exists('chkTableCompat') || !chkTableCompat($table)) {
        $ret['error'] = '資料表不存在：' . $table;
        return $ret;
    }

    try {
        $pdo = sql_conn();
        if (!$pdo) {
            $ret['error'] = '資料庫連線失敗';
            return $ret;
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // 檢查是否有 dtUDate 欄位
        $hasDtUDate = false;
        try {
            $ck = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE 'dtUDate'");
            $ck->execute();
            $hasDtUDate = (bool)$ck->fetchColumn();
        } catch (Throwable $e) {
            $hasDtUDate = false;
        }

        if ($hasDtUDate) {
            $sql = "UPDATE `{$table}` 
                       SET `Upload` = :upload,
                           `dtUDate` = :dtUDate
                     WHERE `{$pkName}` = :pk
                     LIMIT 1";
            $params = [
                ':upload'  => $uploadValue,
                ':dtUDate' => date('Y-m-d H:i:s'),
                ':pk'      => $pkey
            ];
        } else {
            $sql = "UPDATE `{$table}` 
                       SET `Upload` = :upload
                     WHERE `{$pkName}` = :pk
                     LIMIT 1";
            $params = [
                ':upload' => $uploadValue,
                ':pk'     => $pkey
            ];
        }

        $ret['sql'] = $sql;
        $ret['params'] = $params;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $affected = $stmt->rowCount();

        if (function_exists('manage_history')) {
            $log = $sql . "\n" .
                   "Upload={$uploadValue}; {$pkName}={$pkey}" .
                   ($hasDtUDate ? '; dtUDate=' . date('Y-m-d H:i:s') : '');

            if ($srcFile !== null) {
                $log .= "\n[src] " . $srcFile . ($srcLine !== null ? ':' . $srcLine : '');
            }

            manage_history(
                $Module_PKey,
                $Module_Name,
                $log,
                $WorkFile,
                $Login_ID,
                $Action
            );
        }

        $ret['ok'] = true;
        return $ret;

    } catch (Throwable $e) {
        $ret['error'] = $e->getMessage();

        if (function_exists('sql_error')) {
            $log = ($ret['sql'] ?: 'update_upload_by_table') . "\n" .
                   "Upload={$uploadValue}; {$pkName}={$pkey}";
            if ($srcFile !== null) {
                $log .= "\n[src] " . $srcFile . ($srcLine !== null ? ':' . $srcLine : '');
            }

            sql_error($log, $e->getMessage(), $WorkFile, (string)$Login_ID);
        }

        return $ret;
    }
}

/**
 * 批次更新 Upload（Yes/No）
 *
 * @param int[] $pkeys
 * @return array{ok:bool,updated:int,failed:int[],error:string}
 */
function update_upload_batch_by_table(
    string $table,
    string $pkName,
    array $pkeys,
    string $uploadValue,
    array $ctx = []
): array {
    $ret = [
        'ok'      => true,
        'updated' => 0,
        'failed'  => [],
        'error'   => '',
    ];

    if ($uploadValue !== 'Yes' && $uploadValue !== 'No') {
        $ret['ok'] = false;
        $ret['error'] = 'Upload 值錯誤';
        return $ret;
    }

    $pkeys = array_values(array_unique(array_filter(
        array_map('intval', $pkeys),
        static fn(int $id): bool => $id > 0
    )));

    if ($pkeys === []) {
        $ret['ok'] = false;
        $ret['error'] = '請至少選擇一個項目';
        return $ret;
    }

    foreach ($pkeys as $id) {
        $one = update_upload_by_table($table, $pkName, $id, $uploadValue, $ctx);
        if (!empty($one['ok'])) {
            $ret['updated']++;
        } else {
            $ret['failed'][] = $id;
        }
    }

    if ($ret['failed'] !== []) {
        $ret['ok'] = false;
        $ret['error'] = '部分項目更新失敗（' . count($ret['failed']) . ' 筆）';
    }

    return $ret;
}

/* =========================================================
 *  回列表 & 小工具
 * ======================================================= */
require_once __DIR__ . '/crud_helpers.php';

function _safeRelativePath(string $path, string $default = 'list.php'): string {
    $path = trim($path);
    if ($path === '') return $default;
    if (preg_match('#^(?:[a-z]+:)?//#i', $path)) return $default; // 避免外部導轉
    return $path;
}

/**
 * 組合導回網址（Q_優先；ClassN vs ClassN_PKey 二擇一）
 * $opts:
 *   - sendText   (預設 '搜尋')
 *   - classCount (預設 4)
 *   - numeric    額外數字鍵 (outKey => inKey)
 *   - strings    額外字串鍵 (outKey => inKey)
 */
function buildBackUrl(string $base, array $filter = [], array $opts = []): string {
    $base = _safeRelativePath($base, 'list.php');

    $sendText   = $opts['sendText']   ?? '搜尋';
    $classCount = (int)($opts['classCount'] ?? 4);

    $numericMap = array_merge([
        'Page' => 'Page', 'PageSize' => 'PageSize',
        'manNo' => 'manNo', 'subNo' => 'subNo',
        'Album_PKey' => 'Album_PKey', 'Product_PKey' => 'Product_PKey',
        'Brand' => 'Brand', 'Serial' => 'Serial',
        'intLocal' => 'intLocal', 'intState' => 'intState',
        'intPay' => 'intPay', 'intType' => 'intType', 'intUse' => 'intUse',
    ], $opts['numeric'] ?? []);

    $stringMap = array_merge([
        'Keywords' => 'Keywords', 'OpenDate' => 'OpenDate',
        'EndDate'  => 'EndDate',  'Upload'   => 'Upload',
        'list'     => 'list',     'language' => 'language',
    ], $opts['strings'] ?? []);

    $params = ['Send' => $sendText];

    $addNumSmart = function(string $outKey, ?string $inKey = null) use (&$params, $filter) {
        $try = ($inKey === null || $inKey === '' || $inKey === $outKey) ? ['Q_'.$outKey, $outKey] : [$inKey];
        foreach ($try as $src) {
            if (isset($filter[$src]) && $filter[$src] !== '' && is_numeric($filter[$src])) {
                $val = function_exists('SqlFilter') ? SqlFilter($filter[$src], 'int') : (int)$filter[$src];
                $params[$outKey] = (string)$val; return;
            }
        }
    };
    $addStrSmart = function(string $outKey, ?string $inKey = null) use (&$params, $filter) {
        $try = ($inKey === null || $inKey === '' || $inKey === $outKey) ? ['Q_'.$outKey, $outKey] : [$inKey];
        foreach ($try as $src) {
            if (!empty($filter[$src])) {
                $val = function_exists('SqlFilter') ? SqlFilter((string)$filter[$src], 'str') : (string)$filter[$src];
                $params[$outKey] = $val; return;
            }
        }
    };

    foreach ($numericMap as $outKey => $inKey) { $addNumSmart((string)$outKey, (string)$inKey); }
    foreach ($stringMap as $outKey => $inKey) { $addStrSmart((string)$outKey, (string)$inKey); }

    for ($i = 1; $i <= $classCount; $i++) {
        $qKey = 'Q_Class'.$i; $pkeyKey = 'Class'.$i.'_PKey';
        if (isset($filter[$qKey]) && $filter[$qKey] !== '' && is_numeric($filter[$qKey])) {
            $val = function_exists('SqlFilter') ? SqlFilter($filter[$qKey], 'int') : (int)$filter[$qKey];
            $params['Class'.$i] = (string)$val;
        } elseif (isset($filter[$pkeyKey]) && $filter[$pkeyKey] !== '' && is_numeric($filter[$pkeyKey])) {
            $val = function_exists('SqlFilter') ? SqlFilter($filter[$pkeyKey], 'int') : (int)$filter[$pkeyKey];
            $params['Class'.$i.'_PKey'] = (string)$val;
        }
    }

    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    return $base . ($query ? ('?' . $query) : '');
}

/** alert + redirect */
function redirectWithAlert(string $message, string $url): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['flash_alert'] = $message;
    }
    if (!headers_sent()) {
        header('Location: ' . $url, true, 302);
    } else {
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
    }
    exit;
}

/* =========================================================
 *  檔案刪除工具
 * ======================================================= */
function DelFileSafe(string $path): void {
    if ($path === '') return;
    if (function_exists('DelFile')) { DelFile($path); return; }
    if (is_file($path)) { @unlink($path); }
}

/** 刪一張圖的所有衍生檔（原檔 / .webp / thumb_ / s_） */
function deleteImageFiles(string $baseDir, string $filename): void {
    $baseDir = rtrim($baseDir, "/\\");
    if ($baseDir === '' || $filename === '') return;

    $orig  = $baseDir . '/' . $filename;

    $webp  = preg_replace('/\.[^.]+$/', '.webp', $filename);
    if ($webp === null || $webp === $filename) $webp = $filename . '.webp';
    $origW = $baseDir . '/' . $webp;

    $thumb  = $baseDir . '/thumb_' . $filename;
    $thumbW = $baseDir . '/thumb_' . $webp;
    $small  = $baseDir . '/s_' . $filename;
    $smallW = $baseDir . '/s_' . $webp;

    DelFileSafe($orig);
    DelFileSafe($origW);
    DelFileSafe($thumb);
    DelFileSafe($thumbW);
    DelFileSafe($small);
    DelFileSafe($smallW);
}

/**
 * 表單 Hidden 欄位 Helpers
 * - 維持 PHP 7.0 相容
 * - 避免隱性 nullable deprecation（$id 預設空字串）
 * - 全面使用 htmlspecialchars 輸出
 */

if (!function_exists('hiddenNumeric')) {
    function hiddenNumeric(string $name, $value = null, string $id = ''): string {
        $idFinal = $id !== '' ? $id : $name;
        $val     = is_numeric($value) ? (string)(int)$value : '';
        return sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s">',
            htmlspecialchars($name,   ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($idFinal,ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($val,    ENT_QUOTES, 'UTF-8')
        );
    }
}

if (!function_exists('hiddenText')) {
    function hiddenText(string $name, $value = '', string $id = ''): string {
        $idFinal = $id !== '' ? $id : $name;
        $val     = (string)$value;
        return sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s">',
            htmlspecialchars($name,   ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($idFinal,ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($val,    ENT_QUOTES, 'UTF-8')
        );
    }
}

if (!function_exists('hiddenYesNo')) {
    function hiddenYesNo(string $name, $value = '', string $id = ''): string {
        $idFinal = $id !== '' ? $id : $name;
        $v       = ($value === 'Yes' || $value === 'No') ? (string)$value : '';
        return sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s">',
            htmlspecialchars($name,   ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($idFinal,ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($v,      ENT_QUOTES, 'UTF-8')
        );
    }
}

/**
 * 建立 hidden input (數值陣列型)
 * @param string   $name   欄位名稱 (會輸出成 name="{$name}[]")
 * @param int[]    $values 數值陣列
 * @param string   $idBase 基底 id，會加上索引，例如 id="{$idBase}_0"
 */
if (!function_exists('hiddenNumericArray')) {
    function hiddenNumericArray(string $name, array $values, string $idBase = ''): string {
        $out = '';
        foreach (array_values($values) as $i => $v) {
            if (!is_numeric($v)) continue;
            $idFinal = $idBase !== '' ? $idBase . '_' . $i : $name . '_' . $i;
            $out .= sprintf(
                '<input type="hidden" name="%s[]" id="%s" value="%s">' . "\n",
                htmlspecialchars($name,   ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($idFinal,ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string)(int)$v, ENT_QUOTES, 'UTF-8')
            );
        }
        return $out;
    }
}

/**
 * 建立 hidden input (僅當值存在時才輸出)
 * @param string $name 欄位名稱
 * @param mixed  $value 輸出值；若空字串/空陣列/null 則不輸出
 * @param string $id
 */
if (!function_exists('hiddenIfSet')) {
    function hiddenIfSet(string $name, $value, string $id = ''): string {
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            return '';
        }
        $idFinal = $id !== '' ? $id : $name;
        return sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s">',
            htmlspecialchars($name,   ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($idFinal,ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')
        );
    }
}

/**
 * 建立 hidden input (CSRF Token)
 * - 會自動產生並儲存到 $_SESSION['csrf_token']
 * - 可指定 $name（預設 "csrf_token"）
 */
if (!function_exists('hiddenToken')) {
    function hiddenToken(string $name = 'csrf_token', string $id = ''): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (empty($_SESSION[$name])) {
            $_SESSION[$name] = bin2hex(random_bytes(32)); // 64字元隨機字串
        }
        $idFinal = $id !== '' ? $id : $name;
        return sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s">',
            htmlspecialchars($name,   ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($idFinal,ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($_SESSION[$name], ENT_QUOTES, 'UTF-8')
        );
    }
}
if (!function_exists('getStatusBadge')) {
	function getStatusBadge($status)
	{
		switch($status){
			case 'published':
				return '<div class="badge badge--published"><span class="badge__dot"></span>已發佈</div>';
				break;
			case 'published':
				return '<div class="badge badge--scheduled"><i class="bi bi-clock"></i>已排程</div>';
				break;
			default:
				return '<div class="badge badge--archived"><i class="bi bi-archive"></i>已下架</div>';
				break;
			
		}
	}	
}

