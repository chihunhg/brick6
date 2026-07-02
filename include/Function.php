<?php
function filter_html($input, $type = 'html') {
    $input = trim((string)$input);
    if ($input === '') return '';

    $allowedTags = [
        'html' => ['div','p','b','strong','i','em','u','ul','ol','li','br','span','a','img','blockquote','h1','h2','h3','h4','h5','h6','hr'],
        'tab'  => ['div','table','thead','tbody','tfoot','tr','td','th','br','hr','ul','ol','li','p','b','strong','i','u','em','a','img','span','iframe','h1','h2','h3','h4','h5','h6','blockquote'],
    ];

    if (!isset($allowedTags[$type])) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // 若你不想讓掃描工具對 class 有意見，可把 class 全部移除
    $allowClass = false;

    $commonAttrs = ['id','data-*','aria-*'];
    if ($allowClass) {
        $commonAttrs[] = 'class';
    }

    $allowedAttrs = [
        'a'      => array_merge(['href','target','rel','title','name'], $commonAttrs),
        'img'    => array_merge(['src','alt','width','height','srcset','sizes','loading','referrerpolicy'], $commonAttrs),
        'iframe' => array_merge(['src','width','height','allow','allowfullscreen','referrerpolicy','loading','frameborder','sandbox'], $commonAttrs),
        'table'  => $commonAttrs,
        'thead'  => $commonAttrs,
        'tbody'  => $commonAttrs,
        'tfoot'  => $commonAttrs,
        'tr'     => $commonAttrs,
        'td'     => array_merge(['colspan','rowspan','headers'], $commonAttrs),
        'th'     => array_merge(['scope','colspan','rowspan','headers'], $commonAttrs),
        'div'    => $commonAttrs,
        'span'   => $commonAttrs,
        'p'      => $commonAttrs,
        'h1'     => $commonAttrs,
        'h2'     => $commonAttrs,
        'h3'     => $commonAttrs,
        'h4'     => $commonAttrs,
        'h5'     => $commonAttrs,
        'h6'     => $commonAttrs,
        'ul'     => $commonAttrs,
        'ol'     => $commonAttrs,
        'li'     => $commonAttrs,
        'blockquote' => array_merge(['cite'], $commonAttrs),
        'hr'     => $commonAttrs,
        'br'     => [],
    ];

    $allowedSchemes = ['http','https','mailto','tel'];

    $filtered = strip_tags($input, '<' . implode('><', $allowedTags[$type]) . '>');
    $wrapperId = 'wrap_' . bin2hex(random_bytes(6));
    $wrapped = '<div id="' . $wrapperId . '">' . $filtered . '</div>';

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;

    $html = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $wrapped;
    @$dom->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR | LIBXML_NOWARNING);

    $xpath = new DOMXPath($dom);
    $wrapNode = $xpath->query('//*[@id="'.$wrapperId.'"]')->item(0);
    if (!$wrapNode) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    foreach ($xpath->query('.//*', $wrapNode) as $el) {
        /** @var DOMElement $el */
        $tag = strtolower($el->nodeName);

        if (!in_array($tag, $allowedTags[$type], true)) {
            _dom_remove_keep_children($el);
            continue;
        }

        $removeAttrs = [];
        if ($el->hasAttributes()) {
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name  = strtolower($attr->name);
                $value = trim($attr->value);

                if (strpos($name, 'on') === 0 || $name === 'style') {
                    $removeAttrs[] = $name;
                    continue;
                }

                $okList = $allowedAttrs[$tag] ?? [];
                $isWildcard =
                    (strpos($name, 'data-') === 0 && in_array('data-*', $okList, true)) ||
                    (strpos($name, 'aria-') === 0 && in_array('aria-*', $okList, true));

                if (!$isWildcard && !in_array($name, $okList, true)) {
                    $removeAttrs[] = $name;
                    continue;
                }

                if (in_array($name, ['href','src'], true)) {
                    if (!is_safe_url($value, $allowedSchemes, true)) {
                        $removeAttrs[] = $name;
                        continue;
                    }
                }

                if ($name === 'srcset') {
                    $valid = [];
                    foreach (preg_split('/\s*,\s*/', $value) as $part) {
                        $bits = preg_split('/\s+/', trim($part));
                        $url  = $bits[0] ?? '';
                        if ($url !== '' && is_safe_url($url, ['http','https'], true)) {
                            $valid[] = $part;
                        }
                    }
                    if ($valid) {
                        $el->setAttribute('srcset', implode(', ', $valid));
                    } else {
                        $removeAttrs[] = 'srcset';
                    }
                }
            }
        }

        foreach ($removeAttrs as $attrName) {
            $el->removeAttribute($attrName);
        }

        if ($tag === 'a') {
            $href = trim($el->getAttribute('href'));
            if ($href === '') {
                _dom_remove_keep_children($el);
                continue;
            }

            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer ugc');
        }

        if ($tag === 'iframe') {
            $src = trim($el->getAttribute('src'));
            $ok = false;
            if ($src !== '') {
                $ok = (bool)preg_match(
                    '#^https?://(?:www\.youtube\.com/embed/|player\.vimeo\.com/video/|docs\.google\.com/|maps\.google\.com/)#i',
                    $src
                );
            }

            if (!$ok) {
                $el->parentNode->removeChild($el);
                continue;
            }

            if (!$el->hasAttribute('loading')) {
                $el->setAttribute('loading', 'lazy');
            }
            if (!$el->hasAttribute('referrerpolicy')) {
                $el->setAttribute('referrerpolicy', 'no-referrer');
            }

            // 比你原本更保守
            $el->setAttribute('sandbox', 'allow-scripts allow-presentation');
            $el->removeAttribute('style');
        }

        if ($tag === 'img') {
            $src = trim($el->getAttribute('src'));
            if ($src === '' || !is_safe_url($src, ['http','https'], true)) {
                $el->parentNode->removeChild($el);
                continue;
            }

            if (!$el->hasAttribute('alt')) {
                $el->setAttribute('alt', '');
            }
            if (!$el->hasAttribute('loading')) {
                $el->setAttribute('loading', 'lazy');
            }
            if (!$el->hasAttribute('referrerpolicy')) {
                $el->setAttribute('referrerpolicy', 'no-referrer');
            }
        }
    }

    $out = _dom_inner_html($wrapNode);
    $out = html_entity_decode($out, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return trim($out);
}

function is_safe_url(string $url, array $allowedSchemes = ['http','https'], bool $allowRelative = true): bool {
    $url = trim($url);
    if ($url === '') return false;
    if (preg_match('/[\x00-\x1F\x7F]/u', $url)) return false;

    $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $decoded = preg_replace('/\s+/', '', $decoded);

    if (preg_match('/^(javascript|data|vbscript):/i', $decoded)) {
        return false;
    }

    $scheme = parse_url($decoded, PHP_URL_SCHEME);
    if ($scheme === null || $scheme === false) {
        return $allowRelative;
    }

    return in_array(strtolower($scheme), $allowedSchemes, true);
}

function _dom_inner_html(DOMNode $node): string {
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }
    return $html;
}

function _dom_remove_keep_children(DOMNode $node): void {
    $parent = $node->parentNode;
    if (!$parent) return;

    while ($node->firstChild) {
        $parent->insertBefore($node->firstChild, $node);
    }
    $parent->removeChild($node);
}

/**
 * 驗證是否為合法整數，並限制在指定整數範圍內（預設為 MySQL INT 範圍）
 */
function is_valid_int($value, $min = -2147483648, $max = 2147483647) {
    if (filter_var($value, FILTER_VALIDATE_INT) === false) return false;
    $intVal = (int)$value;
    return ($intVal >= $min && $intVal <= $max);
}

/**
 * 通用 SQL 輸入過濾器
 * 注意：此版本在 'str' 會做 htmlspecialchars，適合直接輸出到 HTML。
 * 若要存 DB 後再輸出才 escape，請自行調整。
 */
function SqlFilter($input, $type = 'str') {
    // 修正點：如果是陣列且為空，直接回傳該型別的預設值
    if (is_array($input)) {
        if (empty($input)) return $type === 'int' || $type === 'float' ? 0 : '';
        
        foreach ($input as $k => $v) $input[$k] = SqlFilter($v, $type);
        return $input;
    }
    if (!is_scalar($input)) return '';

    $input = trim((string)$input);
    switch ($type) {
        case 'str':
            $input = strip_tags($input);
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        case 'html':
        case 'tab':
            return filter_html($input, $type);
        case 'int':
            return is_valid_int($input) ? (int)$input : 0;
        case 'float':
            return is_numeric($input) ? (float)$input : 0.0;
        case 'bool':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) ?: '';
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) ?: '';
        default:
            return '';
    }
}

/**
 * 列表關鍵字：使用者輸入去掉各類 Unicode 空白（與 list_search_sql_ws_remove_expr 搭配）
 */
if (!function_exists('list_search_keyword_normalize')) {
    function list_search_keyword_normalize(string $input): string {
        return preg_replace('/\s+/u', '', $input);
    }
}

/**
 * SQL 運算式：欄位去掉常見空白後供 LOCATE 比對（$column 僅允許英數底線，避免注入）
 */
if (!function_exists('list_search_sql_ws_remove_expr')) {
    function list_search_sql_ws_remove_expr(string $column): string {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            return "IFNULL('', '')";
        }
        $e = "IFNULL({$column},'')";
        foreach ([' ', "\t", "\n", "\r", "\xc2\xa0", "\xe3\x80\x80"] as $ch) {
            $lit = str_replace("'", "''", $ch);
            $e = "REPLACE({$e},'{$lit}','')";
        }
        return $e;
    }
}

/**
 * 多欄 OR 子字串比對（LOCATE + PDO 命名參數；防注入依綁定值，勿依賴刪單引號）
 *
 * @param list<string> $columns 白名單欄位名
 * @return array{fragment: string, bind: array<string,string>}
 */
if (!function_exists('list_search_locate_any_fragment')) {
    function list_search_locate_any_fragment(array $columns, string $kwNorm, string $paramPrefix = 'KwS'): array {
        $parts = [];
        $bind = [];
        $n = 0;
        foreach ($columns as $col) {
            if (!is_string($col)) {
                continue;
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $col)) {
                continue;
            }
            $n++;
            $p = $paramPrefix . $n;
            $parts[] = 'LOCATE(:' . $p . ', ' . list_search_sql_ws_remove_expr($col) . ') > 0';
            $bind[$p] = $kwNorm;
        }
        if ($parts === []) {
            return ['fragment' => '', 'bind' => []];
        }
        return ['fragment' => ' AND (' . implode(' OR ', $parts) . ')', 'bind' => $bind];
    }
}

/** 請求參數僅接受純量字串（拒絕 strID[] 等陣列／物件，避免 trim 型別錯誤） */
if (!function_exists('request_scalar_string')) {
    function request_scalar_string(mixed $value, string $default = ''): string {
        if (!is_scalar($value)) {
            return $default;
        }
        return trim((string)$value);
    }
}

/** 從 $filter_array 取單一純量並經 SqlFilter（非純量回傳空字串） */
if (!function_exists('filter_request_scalar')) {
    function filter_request_scalar(array $filter, string $key, string $type = 'str'): string {
        if (!isset($filter[$key]) || !is_scalar($filter[$key])) {
            return '';
        }
        $out = SqlFilter($filter[$key], $type);
        return is_string($out) ? $out : '';
    }
}

// 處理 $_GET, $_POST, $_FILES（保留原樣，避免雙重 escape）
$filter_array = [];
$file_array   = [];
foreach ($_GET as $key => $value)  { $filter_array[$key] = $value; }
foreach ($_POST as $key => $value) { $filter_array[$key] = $value; }
foreach ($_FILES as $key => $value){ $file_array[$key]   = $value; }

// 取得「基底資料夾」的實際路徑（支援相對/絕對），不是資料夾就回 null
function __resolve_base_dir(string $base): ?string {
    $base = str_replace('\\', '/', trim($base));
    if ($base === '') return null;

    // 相對路徑 → 以本檔案位置為基準
    if (!preg_match('#^(?:/|[A-Za-z]:[\\/]|\\\\\\\\)#', $base)) {
        $base = __DIR__ . '/' . $base;
    }
    $real = realpath($base);
    if ($real === false || !is_dir($real)) return null;
    return rtrim(str_replace('\\', '/', $real), '/');
}

// 把「相對目標路徑」正規化：移除重複斜線、處理 . / ..，不可含 NUL
function __normalize_rel_path(string $rel): ?string {
    if ($rel === '' || strpos($rel, "\0") !== false) return null;
    $rel = str_replace('\\', '/', $rel);

    // 拒絕以 / 或 磁碟機代號 開頭（不接受絕對路徑）
    if (preg_match('#^(?:/|[A-Za-z]:/)#', $rel)) return null;

    // 拒絕嘗試跳出（顯性 ..）
    if (preg_match('#(^|/)\.\.(/|$)#', $rel)) return null;

    // 折疊路徑片段
    $parts = explode('/', $rel);
    $stack = [];
    foreach ($parts as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($stack); continue; }
        $stack[] = $seg;
    }
    $out = implode('/', $stack);
    if ($out === '') return null;
    return $out;
}

// 安全組合路徑並確認仍位於基底資料夾下
function __safe_join(string $baseDir, string $rel): ?string {
    $base = __resolve_base_dir($baseDir);
    if ($base === null) return null;

    $relNorm = __normalize_rel_path($rel);
    if ($relNorm === null) return null;

    // 目標路徑（不直接 realpath 檔案，因為檔案可能不存在；先 realpath 父層）
    $target = $base . '/' . $relNorm;
    $parent = realpath(dirname($target));
    if ($parent === false) return null;

    $parent = rtrim(str_replace('\\', '/', $parent), '/');

    // 確認 parent 仍在 base 之內
    if (strpos($parent . '/', $base . '/') !== 0) return null;

    return $target;
}

/**
 * 安全刪檔（只允許 UPLOAD_BASE 之下的檔案）
 * 相容呼叫：
 *  - DelFile($file)                → 刪除 $file
 *  - DelFile($anyBase, $file)      → 忽略 $anyBase，仍只在 UPLOAD_BASE 底下刪 $file
 */
function DelFile(string $Forder = '', string $sFileName = ''): bool {
    try {
        // 兼容：若只傳一參數，視為「檔名/相對路徑」
        $rel = ($sFileName !== '') ? $sFileName : $Forder;

        // 只以 UPLOAD_BASE 為基底
        $target = __safe_join(UPLOAD_BASE, $rel);
        if ($target === null) {
            error_log("[DelFile] rejected path under UPLOAD_BASE: file={$rel}");
            return false;
        }

        // 僅允許刪一般檔案（避免資料夾/裝置等）
        if (!is_file($target)) return false;

        return unlink($target);
    } catch (Throwable $e) {
        error_log('[DelFile] error: ' . $e->getMessage());
        return false;
    }
}

function CheckMail($email){
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

// 台灣身份證字號檢查
function Checkid($id){
    $id = strtoupper($id);
    $headPoint = [
        'A'=>1,'I'=>39,'O'=>48,'B'=>10,'C'=>19,'D'=>28,
        'E'=>37,'F'=>46,'G'=>55,'H'=>64,'J'=>73,'K'=>82,
        'L'=>2,'M'=>11,'N'=>20,'P'=>29,'Q'=>38,'R'=>47,
        'S'=>56,'T'=>65,'U'=>74,'V'=>83,'W'=>21,'X'=>3,
        'Y'=>12,'Z'=>30
    ];
    $multiply = [8,7,6,5,4,3,2,1];
    if (preg_match('/^[A-Z][1-2][0-9]{8}$/',$id) && $id !== 'A123456789'){
        $arr = str_split($id);
        $total = $headPoint[array_shift($arr)] ?? 0;
        $point = (int)array_pop($arr);
        foreach ($arr as $i => $ch) $total += ((int)$ch) * $multiply[$i];
        $last = (($total % 10) === 0) ? 0 : (10 - ($total % 10));
        return ($last === $point);
    }
    return false;
}

function CheckURL($url){
    return (filter_var($url, FILTER_VALIDATE_URL) !== false) ? 1 : 0;
}

function AddZero($strNumber=0){ return sprintf('%02d', $strNumber); }

function right($value, $count=0){ return mb_substr($value, mb_strlen($value,'utf-8')-$count, $count, 'UTF-8'); }
function left($string='', $count=0){ return mb_substr($string, 0, $count, 'UTF-8'); }
function mid($string,$start,$length=0){ return mb_substr($string, $start, $length, 'utf-8'); }

function alert($message){
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['flash_alert'] = (string)$message;
        return;
    }
    echo '<div class="alert-fallback">'.htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8').'</div>';
}
function location_href($uurl){
    $target = (string)$uurl;
    if (!headers_sent()) {
        header('Location: '.$target, true, 302);
    } else {
        echo '<meta http-equiv="refresh" content="0;url='.htmlspecialchars($target, ENT_QUOTES, 'UTF-8').'">';
    }
    exit;
}

function chkDate($value){
    if (!$value) return false;
    try {
		new DateTime($value);
		return true;
	}
	catch (Exception $e) {
		return false;
	}
}

function Date_EN($dtDate,$num){
    if (!chkDate($dtDate)) return null;
    switch ($num) {
        case 0: return date('Y/m/d H:i', strtotime($dtDate));
        case 1: return date('Y/m/d', strtotime($dtDate));
        case 2: return date('Y-m-d', strtotime($dtDate));
        case 3: return date('Y.m.d', strtotime($dtDate));
        case 4: return date('Ymd',   strtotime($dtDate));
        case 5: return date('Y/m/d', strtotime($dtDate)).' <span>'.date('H:i',strtotime($dtDate)).'</span>';
        case 6: return date('Y/m/d H:i:s', strtotime($dtDate));
        case 7: return '<span>'.date('Y-m-d',strtotime($dtDate)).'</span><span>'.date('H:i',strtotime($dtDate)).'</span>';
        case 8: $w = date('w', strtotime($dtDate)); $wk=['日','一','二','三','四','五','六']; return date('m/d',strtotime($dtDate)).'('.$wk[$w].')';
        case 9: return date('M',strtotime($dtDate)).' '.date('d',strtotime($dtDate)).' '.','.date('Y',strtotime($dtDate));
    }
    return null;
}

function Date_CH($dtDate,$num){
    if (!chkDate($dtDate)) return null;
    switch ($num) {
        case 1: return date('Y',strtotime($dtDate)).'年'.date('m',strtotime($dtDate)).'月'.date('d',strtotime($dtDate)).'日';
        case 2: return date('Y-m-d', strtotime($dtDate));
        case 3: return date('Y.m.d', strtotime($dtDate));
        case 4: return date('d.m.Y', strtotime($dtDate));
        case 5: return date('Y/m/d H:i', strtotime($dtDate));
        case 6: return date('Y/m/d H:i:s', strtotime($dtDate));
    }
    return null;
}

/****模擬sqlserver中的dateadd函數*******
$part 類型：string
取值範圍：year,month,day,hour,min,sec
表示：要增加的日期的哪個部分
$n 類型：數值
表示：要增加多少，根據$part決定增加哪個部分
可為負數
$datetime類型：timestamp
表示：增加的基數
返回 類型：timestamp
**************結束**************/
function dateadd($part,$n,$datetime){
	$year=date("Y",$datetime);
	$month=date("m",$datetime);
	$day=date("d",$datetime);
	$hour=date("H",$datetime);
	$min=date("i",$datetime);
	$sec=date("s",$datetime);
	$part=strtolower($part);
	$ret=0;
	switch ($part) {
	case "year":
		$year+=$n;
		break;
	case "month":
		$month+=$n;
		break;
	case "day":
		$day+=$n;
		break;
	case "hour":
		$hour+=$n;
		break;
	case "min":
		$min+=$n;
		break;
	case "sec":
		$sec+=$n;
		break;
	default:
		return $ret;
		break;
	}
	$ret=mktime($hour,$min,$sec,$month,$day,$year);
	return $ret;
}

function add_date($givendate,$day=0,$mth=0,$yr=0) {
    $cd = strtotime($givendate);
    return date('Y/m/d H:i:s', mktime(date('H',$cd), date('i',$cd), date('s',$cd), date('m',$cd)+$mth, date('d',$cd)+$day, date('Y',$cd)+$yr));
}

function datediff($interval, $datefrom, $dateto, $using_timestamps = false) {
    if (!$using_timestamps) { $datefrom = strtotime($datefrom, 0); $dateto = strtotime($dateto, 0); }
    $difference = $dateto - $datefrom;
    switch($interval) {
        case 'yyyy':
            $years = floor($difference / 31536000);
            if (mktime(date('H',$datefrom),date('i',$datefrom),date('s',$datefrom),date('n',$datefrom),date('j',$datefrom),date('Y',$datefrom)+$years) > $dateto) $years--;
            if (mktime(date('H',$dateto),date('i',$dateto),date('s',$dateto),date('n',$dateto),date('j',$dateto),date('Y',$dateto)-($years+1)) > $datefrom) $years++;
            return $years;
        case 'q': return floor($difference / 8035200); // 3 個月
        case 'm':
            $months = floor($difference / 2678400);
            while (mktime(date('H',$datefrom),date('i',$datefrom),date('s',$datefrom),date('n',$datefrom)+$months,date('j',$dateto),date('Y',$datefrom)) < $dateto) $months++;
            $months--; return $months;
        case 'y': return date('z',$dateto) - date('z',$datefrom);
        case 'd': return floor($difference / 86400);
        case 'w':
            $days = floor($difference / 86400);
            $weeks = floor($days / 7);
            $first = date('w',$datefrom);
            $rem = $days % 7; $odd = $first + $rem;
            if ($odd > 7) $rem--; if ($odd > 6) $rem--;
            return ($weeks * 5) + $rem;
        case 'ww': return floor($difference / 604800);
        case 'h':  return floor($difference / 3600);
        case 'n':  return floor($difference / 60);
        default:   return $difference;
    }
}

function show_contents($num=1){
    switch($num){
        case 1: return '上圖下文';
        case 2: return '左圖右文';
        case 3: return '左文右圖';
        case 4: return '上文下圖';
    }
    return '';
}

function Lastday($date){
    $firstday = date('Y-m-01', strtotime($date));
    return date('Y-m-d', strtotime("$firstday +1 month -1 day"));
}

/**
 * 安全版 Authcode：加密/解密 + 過期驗證
 */
function Authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    if (!extension_loaded('sodium')) {
        throw new Exception('Libsodium 未啟用。');
    }
    $operation = strtoupper($operation);
    $key = hash('sha256', $key ?: ($_ENV['APP_SECRET_KEY'] ?? 'default_key'), true); // 32 bytes
    $nonce_len = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

    if ($operation === 'ENCODE') {
        $expiry_time = $expiry > 0 ? (time() + $expiry) : 0;
        $data = pack('N', $expiry_time) . $string;
        $nonce = random_bytes($nonce_len);
        $ciphertext = sodium_crypto_secretbox($data, $nonce, $key);
        return base64url_encode($nonce . $ciphertext);
    }

    if ($operation === 'DECODE') {
        $decoded = base64url_decode($string);
        if ($decoded === false || strlen($decoded) < $nonce_len) return '';
        $nonce = substr($decoded, 0, $nonce_len);
        $ciphertext = substr($decoded, $nonce_len);
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        if ($decrypted === false) return '';
        $expiry_time = unpack('N', substr($decrypted, 0, 4))[1];
        if ($expiry_time !== 0 && $expiry_time < time()) return '';
        return substr($decrypted, 4);
    }
    return '';
}

function base64url_encode($data) { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
function base64url_decode($data) {
    $replaced = strtr($data, '-_', '+/');
    $pad = strlen($replaced) % 4; if ($pad) $replaced .= str_repeat('=', 4 - $pad);
    return base64_decode($replaced);
}

// 抓取 URL 內容（注意：未做超時/重試，必要時可擴充）
function getUrlContent($url){
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_URL            => $url,
        CURLOPT_REFERER        => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $result = curl_exec($ch);
    return $result;
}

/**
 * 還原登入頁前端對 g-recaptcha-response 做的 btoa 包一層 Base64。
 * 注意：application/x-www-form-urlencoded 會把 Base64 內的「+」變成空白，須先還原。
 */
if (!function_exists('recaptcha_decode_double_base64_token')) {
    function recaptcha_decode_double_base64_token(string $encoded): string {
        $encoded = trim($encoded);
        if ($encoded === '') {
            return '';
        }
        $encoded = str_replace([' ', "\t", "\n", "\r", "\x0c"], '+', $encoded);
        $decoded = base64_decode($encoded, true);
        if ($decoded !== false && $decoded !== '') {
            return $decoded;
        }
        $try = rawurldecode($encoded);
        if ($try !== $encoded) {
            $try = str_replace([' ', "\t", "\n", "\r"], '+', $try);
            $decoded = base64_decode($try, true);
            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }
        $decoded = (string)base64_decode($encoded, false);
        return $decoded;
    }
}

/**
 * 取得 Google siteverify 用的 response token（優先解 Base64 隱藏欄，失敗則用備援明文欄或官方欄位）。
 */
if (!function_exists('recaptcha_resolve_response_token_from_request')) {
    function recaptcha_resolve_response_token_from_request(array $filter): string {
        $t = recaptcha_decode_double_base64_token((string)($filter['encoded_recaptcha_token'] ?? ''));
        if ($t !== '' && strlen($t) >= 20) {
            return $t;
        }
        $t = trim((string)($filter['recaptcha_response_raw'] ?? ''));
        if ($t !== '' && strlen($t) >= 20) {
            return $t;
        }
        $t = trim((string)($filter['g-recaptcha-response'] ?? ''));
        if ($t !== '' && strlen($t) >= 20) {
            return $t;
        }
        return '';
    }
}

/** 前台／後台共用：reCAPTCHA 網站金鑰（.env RECAPTCHA_SITE_KEY） */
if (!function_exists('recaptcha_site_key')) {
    function recaptcha_site_key(): string {
        $v = $_ENV['RECAPTCHA_SITE_KEY'] ?? getenv('RECAPTCHA_SITE_KEY') ?? '';
        return trim((string)$v);
    }
}

/** 前台／後台共用：reCAPTCHA 密鑰（.env RECAPTCHA_SECRET） */
if (!function_exists('recaptcha_secret_key')) {
    function recaptcha_secret_key(): string {
        $v = $_ENV['RECAPTCHA_SECRET'] ?? getenv('RECAPTCHA_SECRET') ?? '';
        return trim((string)$v);
    }
}

/**
 * Google reCAPTCHA siteverify（官方建議用 POST，避免 response 過長被 GET 截斷）。
 *
 * @return array{success:bool, error-codes:array<int, string>}
 */
if (!function_exists('recaptcha_siteverify')) {
    function recaptcha_siteverify(string $secret, string $responseToken, string $remoteIp = ''): array {
        $out = ['success' => false, 'error-codes' => []];
        if ($secret === '' || $responseToken === '') {
            $out['error-codes'][] = 'missing-input-secret-or-response';
            return $out;
        }
        $post = [
            'secret'   => $secret,
            'response' => $responseToken,
        ];
        if ($remoteIp !== '') {
            $post['remoteip'] = $remoteIp;
        }
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($post),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (!is_string($raw) || $raw === '') {
            $out['error-codes'][] = 'network-error';
            return $out;
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            $out['error-codes'][] = 'invalid-json';
            return $out;
        }
        $out['success'] = !empty($json['success']);
        if (!empty($json['error-codes']) && is_array($json['error-codes'])) {
            $out['error-codes'] = array_values(array_map('strval', $json['error-codes']));
        }
        return $out;
    }
}

// RWD helpers（留空殼，以後可加強）
function rwd_width($txt){
	return $txt;
}

function rwd_table($txt,$num){
    $txt = (string)$txt;
    if (function_exists('manage_enhance_content_tables')) {
        $txt = manage_enhance_content_tables($txt);
    } else {
        $txt = preg_replace('/<table/i', '<div class="tableContainer"><table class="ai-table"', $txt);
        $txt = preg_replace('/<\/table>/', '</table></div>', $txt);
    }
    $txt = preg_replace('/target="_blank"/i','target="_blank" rel="noopener noreferrer"', $txt);
    return $txt;
}

function RemoveHTML($Contents){
	return preg_replace('/<[^>]*>/', '', (string)$Contents);
}
function RemoveMobile($Contents){
	return str_replace(['+','-',' '],'',(string)$Contents);
}

function mbstringtoarray($str,$cut_len,$charset='UTF-8',$inter="/"){
    $strlen=mb_strlen($str,$charset); $array=[];
    while($strlen){ $array[]=mb_substr($str,0,$cut_len,$charset); $str=mb_substr($str,$cut_len,$strlen-$cut_len,$charset); $strlen=mb_strlen($str,$charset);} 
    return implode($inter,$array);
}

function GetIP(){
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k]) && strcasecmp($_SERVER[$k], 'unknown')) return $_SERVER[$k];
    }
    return 'unknown';
}

function IPCountry($ip=''){
    $url = 'https://api.cleantalk.org/?method_name=ip_info&ip='.urlencode($ip);
    $json = getUrlContent($url);
    if (!$json) return '';
    $obj = json_decode($json);
    return $obj->data->ip ?? '';
}

// 寄信（將敏感設定搬到 .env）
function SendMail($SendName, $SendMail, $FromName, $FromMail, $Subject, $MailBody){
    $ip        = UserIP();
    $base_url  = $_ENV['MAIL_API_URL'] ?? 'http://webmail.tsg.com.tw/mail.php';
    $Mail_List = explode(';', (string)$SendMail);
    $lastResp  = null;

    foreach ($Mail_List as $to_mail) {
        $to_mail = trim($to_mail);
        if (!CheckMail($to_mail)) continue;

        $data = [
            'Domain'   => $SERVER_NAME  ?? '',
            'WebUrl'   => ($SERVER_NAME  ?? '').($REQUEST_URI_PATH ?? ''),
            'FromName' => $FromName,
            'FromMail' => $FromMail,
            'toName'   => $SendName,
            'toMail'   => $to_mail,
            'Subject'  => $Subject,
            'MailBody' => $MailBody,
            'UserIP'   => $ip,
        ];

        $header = ['Content-Type: application/json'];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $base_url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($curl);

        $parts = explode('|', (string)$body);
        $resp  = json_decode($parts[0] ?? '{}');
        $Message = $resp->Message ?? '';
        $Result  = $resp->Result  ?? '';
        $lastResp = $resp;

        // 寫入發送信件 log（不記密碼/Token 等敏感資訊）
        $data_array = [
            'Domain'   => $_SERVER['SERVER_NAME'] ?? '',
            'WebUrl'   => ($_SERVER['SERVER_NAME'] ?? '').($GLOBALS['page_link'] ?? ''),
            'FromName' => $FromName,
            'FromMail' => $FromMail,
            'toName'   => $SendName,
            'toMail'   => $to_mail,
            'Subject'  => $Subject,
            'MailBody' => $MailBody,
            'Result'   => $Result,
            'Message'  => $Message,
            'UserIP'   => $ip,
            'dtDate'   => date('Y-m-d H:i:s'),
        ];
        if (class_exists('dbPDO')) {
            $pdo = new dbPDO();
            $pdo->insert('maillog', $data_array);
            $PKey = $pdo->getLastId();
            $SQL_U = $pdo->getLastSql()."\n".array_to_string($data_array).',PKey='.$PKey;
            if ($pdo->getErrorMessage()) {
                // 寫入資料庫存取錯誤記錄
                sql_error($SQL_U, $pdo->getErrorMessage(), ($GLOBALS['WorkFile'] ?? ''), 'system');
            }
            $pdo->close();
        }
    }
    return $lastResp;
}

// 發送驗證信
function send_check_sms($EMail,$SessionID,$chkType=1,$strLink='',$MSG=''){
    $chk_number = random_int(100000,999999);
    $endtime = time()+120;
    $sql_d = 'DELETE FROM smslog WHERE endtime < '.time();
    if (function_exists('execute_sql')) execute_sql($sql_d);

    $Content = '您的驗證碼為：'.$chk_number.'，'.$MSG.'。';
    if (CheckMail($EMail)){
        $ip = UserIP();
        $data_array = [
            'intType'    => (int)$chkType,
            'SessionID'  => $SessionID,
            'EMail'      => $EMail,
            'chk_number' => $chk_number,
            'Content'    => $Content,
            'strLink'    => $strLink,
            'endtime'    => $endtime,
            'UserIP'     => $ip,
            'dtDate'     => date('Y-m-d H:i:s'),
        ];
        if (class_exists('dbPDO')) {
            $pdo = new dbPDO();
            $pdo->insert('smslog', $data_array);
            if ($pdo->getErrorMessage()) {
                sql_error($pdo->getLastSql(), $pdo->getErrorMessage(), ($GLOBALS['WorkFile'] ?? ''), 'system');
            }
            $pdo->close();
        }

        // 發送通知信
        $mail_subject = ($GLOBALS['Web_Name'] ?? '網站').' - 驗證碼通知信';
        $BODY ='<!DOCTYPE html>\n<html lang="zh-tw"><head><meta charset="UTF-8"><title>驗證碼通知信</title>';
        $BODY.='<style>body{font-size:.9em;font-family:\'微軟正黑體\',sans-serif}.mail-wrap{width:600px;margin:0 auto}.header{padding:1em 0;display:table}.header span{display:table-cell;vertical-align:middle;padding:0 .5em;}.header img{width:140px}table{border-top:solid 1px #ddd;border-left:solid 1px #ddd}table tr td{border-right:solid 1px #ddd;border-bottom:solid 1px #ddd;padding:.5em;margin:0}.footer{background:#f7f7f7;padding:2em 1.5em}.footer img{width:80px}.footer p{margin:0;color:#9a9a9a;font-size:12px}</style></head><body>';
        $BODY.='<div class="mail-wrap"><div class="header"><img src="'.($GLOBALS['web_url'] ?? '').'images/all/logo.png" ><span>驗證碼通知信</span></div>';
        $BODY.='<p>您好</p><p>您的驗證碼為：'.$chk_number.'，'.$MSG.'。</p><br><p style="color: red">※此信件由系統自動發送，請勿直接回覆信件，謝謝。</p></div></body></html>';
        SendMail($EMail,$EMail, ($GLOBALS['Web_Name'] ?? '網站'), ($GLOBALS['m_from_mail'] ?? 'no-reply@example.com'), $mail_subject, $BODY);
    }
}

/**
 * 取得安全的正整數（僅允許 >0 的整數），否則回傳預設值
 *
 * @param mixed $value   輸入值
 * @param int   $default 預設值（預設 0）
 * @return int
 */
function safe_int(mixed $value, int $default = 0): int {
    if (is_int($value) && $value > 0) {
        return $value;
    }
    if (is_string($value)) {
        $value = trim($value);
        if ($value !== '' && ctype_digit($value)) {
            $intVal = (int)$value;
            if ($intVal > 0) {
                return $intVal;
            }
        }
    }
    return $default;
}

/**
 * 編輯頁主鍵：優先讀 URL 的 PKey（避免 list 表單 hidden PKey=空 經 POST 覆蓋 $filter_array）
 */
function manage_request_pkey(): int {
    global $filter_array;

    foreach (['PKey', 'pkey'] as $key) {
        if (isset($_GET[$key]) && is_scalar($_GET[$key])) {
            $v = safe_int((string)$_GET[$key]);
            if ($v > 0) {
                return $v;
            }
        }
    }

    if (!empty($_SERVER['QUERY_STRING']) && is_string($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $qs);
        if (is_array($qs)) {
            foreach (['PKey', 'pkey'] as $key) {
                if (isset($qs[$key]) && is_scalar($qs[$key])) {
                    $v = safe_int((string)$qs[$key]);
                    if ($v > 0) {
                        return $v;
                    }
                }
            }
        }
    }

    if (isset($filter_array['PKey']) && is_scalar($filter_array['PKey'])) {
        $v = safe_int((string)$filter_array['PKey']);
        if ($v > 0) {
            return $v;
        }
    }

    return 0;
}

/**
 * 取得安全的非負整數（允許 0），否則回傳預設值
 *
 * @param mixed $value   輸入值
 * @param int   $default 預設值（預設 0）
 * @return int
 */
function safe_uint(mixed $value, int $default = 0): int {
    if (is_int($value) && $value >= 0) {
        return $value;
    }
    if (is_string($value) && ctype_digit($value)) {
        $intVal = (int)$value;
        if ($intVal >= 0) {
            return $intVal;
        }
    }
    return $default;
}

// 發送簡訊（使用 .env 參數）
function sendSMS($Member,$Content){
    $username = $_ENV['SMS_USERNAME'] ?? '';
    $password = $_ENV['SMS_PASSWORD'] ?? '';
    $dst      = str_replace('-', '', (string)$Member);
    $qs = http_build_query([
        'username'=>$username,
        'password'=>$password,
        'CharsetURL'=>'UTF8',
        'dstaddr'=>$dst,
        'smbody'=>$Content,
        'clientid'=>time(),
    ]);
    $smsurl = 'http://smsapi.mitake.com.tw/api/mtk/SmSend?'.$qs;
    return getUrlContent($smsurl);
}

function chkMobile($tel){ return (bool)preg_match('/^09[0-9]{8}$/', str_replace('-', '', $tel)); }

// 產生縮圖（列表用 thumb_ 前綴；使用 ImageResizer）
function ReSizeImg($Forder = '', $Photo = '', $Width = 150)
{
    if (function_exists('create_image_list_thumb')) {
        return create_image_list_thumb((string)$Forder, (string)$Photo, (int)$Width, 'thumb_');
    }
    return false;
}

// 計算縮圖寬高（防未定義變數）
function ReSize($PhotoUrl,$PhotoW,$PhotoH){
    $PhotoW = (int)$PhotoW; $PhotoH = (int)$PhotoH;
    if (!is_file($PhotoUrl)) return [max(0,$PhotoW), max(0,$PhotoH)];
    $src = getimagesize($PhotoUrl); $imgW = $src[0]; $imgH = $src[1];
    $cropW = $PhotoW ?: $imgW; $cropH = $PhotoH ?: $imgH;

    if ($imgW >= $imgH){
        $cropW = min($imgW, $cropW);
        $cropH = (int)ceil($imgH / ($imgW / max(1,$cropW)));
    } else {
        $cropH = min($imgH, $cropH);
        $cropW = (int)ceil($imgW / ($imgH / max(1,$cropH)));
    }
    if ($PhotoW && $cropW > $PhotoW) { $cropH = (int)ceil($cropH / ($cropW / $PhotoW)); $cropW = $PhotoW; }
    if ($PhotoH && $cropH > $PhotoH) { $cropW = (int)ceil($cropW / ($cropH / $PhotoH)); $cropH = $PhotoH; }
    return [$cropW, $cropH];
}

function getGUID($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $result = '';
    for ($i=0; $i<$length; $i++) $result .= $chars[random_int(0, strlen($chars) - 1)];
    return $result;
}

function array_to_string($data_array=array()){
    if (!is_array($data_array)) return '';
    $pairs = [];
    foreach ($data_array as $key => $value) $pairs[] = $key.'='.$value;
    return implode(',', $pairs);
}

function UserIP(){ return GetIP(); }

function makedirs($dirpath, $mode=0775) {
    $ok = is_dir($dirpath) || mkdir($dirpath, $mode, true);
    clearstatcache();
    return $ok;
}

function monthforder($str){
	$forder = explode('_',(string)$str); return left($forder[1] ?? '', 6) . '/';
}

function chkTable($name){
    $name = trim((string)$name);
    if ($name === '' || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        return false;
    }
    if (function_exists('tableExists')) {
        return tableExists($name);
    }
    $pdo = function_exists('sql_conn') ? sql_conn() : null;
    if (!$pdo instanceof PDO) {
        return false;
    }
    if (function_exists('db_pdo_table_exists')) {
        return db_pdo_table_exists($pdo, $name);
    }
    try {
        $st = $pdo->prepare('SHOW TABLES LIKE ?');
        $st->execute([$name]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function check_str($str=''){
    $s = strlen($str); $m = mb_strlen($str,'utf-8');
    if ($s === $m) return [1, $s]; // 純英文
    if ($m>0 && $s % $m === 0 && $s % 3 === 0) return [2, $m]; // 純中文（UTF-8 粗略判）
    return [0,0];
}

function chr_asc($num){
    $num = (int)$num;
    if ($num < 27) return chr($num + 64);
    $int = floor($num / 26); $mod = $num % 26;
    switch($mod){ case 0: $mod = 90; $int--; break; case 1: $mod = 65; break; default: $mod += 64; break; }
    return chr_asc($int) . chr($mod);
}

function question_type($num){
    switch((int)$num){
        case 1: return '單選題';
        case 2: return '複選題';
        case 3: return '單行文字題';
        case 4: return '多行文字題';
        case 5: return '日期題';
        case 6: return 'EMail';
    }
    return '';
}

//全形或半形字符轉換
/*
*function：回傳轉換後全形或半形字符
*param string
*return 
參數解釋
$type：1全形轉半形；2.半形轉全形
*/	
function convertToHalfWidth($str='', $type=1){
	return ($type==2) ? mb_convert_kana($str,'AS') : mb_convert_kana($str,'as');
}

//產生上一筆和下一筆的PKey值
/*
*function：回傳table的上下筆資料的PKey
*param string
*return 
參數解釋
$table_name：資料名稱
$PDO_Cond：搜尋參數
$table_name：搜尋陣列
$table_name：目前資料的PKey
*/	
function Page_List($table_name='',$PDO_Cond='',$Cond_Array=array(),$PKey=0){
	$list_key = array();
	$sql = 'Select PKey From '.$table_name.$PDO_Cond.' order by PKey';
	$rs = new recordset($sql,$Cond_Array);
	while(! $rs->eof){
		array_push($list_key,$rs->field('PKey'));
	$rs->movenext();
	}
	$rs->close();
	unset($Cond_Array);
	
	$prev = 0;
	$next = 0;
	$now = array_search((int)$PKey, $list_key, true);
	$last = count($list_key)-1;
	if ($last >= 0) {
        if ($now === 0) { $next = $list_key[1] ?? 0; }
        if ($now === $last) { $prev = $list_key[$last-1] ?? 0; }
        if ($now > 0 && $now < $last) { $prev = $list_key[$now-1]; $next = $list_key[$now+1]; }
    }
    return ['prev'=>$prev, 'next'=>$next];
}

//檢查身份證性別
function getGenderFromID($id) {
    // 檢查長度
    if (strlen($id) !== 10) {
        return '格式錯誤';
    }

    // 擷取第二碼（數字部分第一碼）
    $genderDigit = strtoupper(substr($id, 1, 1));

    // 判斷性別
    if (in_array($genderDigit, ['1','8', 'A', 'C'])) {
        return '男';
    } elseif (in_array($genderDigit, ['2','9', 'B', 'D'])) {
        return '女';
    } else {
        return '未知';
    }
}

/**
 * 規則說明（純文字陣列）
 */
function cred_policy_lines(array $POLICY, int $minLen=8, int $maxLen=20): array {
    $k = max(2, min(4, (int)($POLICY['cred_min_complexity'] ?? 3)));
    $lines = [];
    $lines[] = "密碼長度為 {$minLen}~{$maxLen} 碼。";
    $lines[] = "需符合下列「至少 {$k} 種」類別：";
    $lines[] = "・英文大寫字元 (A–Z)";
    $lines[] = "・英文小寫字元 (a–z)";
    $lines[] = "・數字 (0–9)";
    $lines[] = "・特殊符號 (~!@#\$%^&*()-_=+{};:<,.>?)";
    if (!empty($POLICY['require_init_change'])) $lines[] = "首次登入必須變更密碼。";
    if (!empty($POLICY['disallow_reuse']))      $lines[] = "變更時不得重複使用舊密碼。";
    if (!empty($POLICY['force_change'])) {
        $d = (int)($POLICY['force_change_days'] ?? 180);
        $lines[] = "密碼須每 {$d} 天強制更新。";
    }
    if (!empty($POLICY['account_lock']))        $lines[] = "啟用帳戶鎖定機制（連續多次登入失敗將暫時鎖定）。";
    return $lines;
}

/**
 * 規則說明（輸出 <ul> HTML）
 */
function cred_policy_html(array $POLICY, int $minLen=8, int $maxLen=20): string {
    $lines = cred_policy_lines($POLICY, $minLen, $maxLen);
    $buf = '<ul class="set-tips">';
    foreach ($lines as $l) {
        $buf .= '<li>' . htmlspecialchars($l, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
    }
    $buf .= '</ul>';
    return $buf;
}

/**
 * 規則說明（合併成單一純文字段落）
 */
function cred_policy_text(array $POLICY, int $minLen=8, int $maxLen=20): string {
    return implode("\n", cred_policy_lines($POLICY, $minLen, $maxLen));
}

/**
 * 依等級產生符合規則的隨機初始密碼
 */
function generate_initial_secret(array $POLICY, int $minLen=12, int $maxLen=20): string {
    $need = max(2, min(4, (int)($POLICY['cred_min_complexity'] ?? 3)));
    $len  = max($minLen, min($maxLen, 14)); // 預設 14 碼左右

    $sets = [
        'U' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'L' => 'abcdefghijklmnopqrstuvwxyz',
        'D' => '0123456789',
        'S' => '~!@#$%^&*()-_=+{};:<,.>?',
    ];

    // 先保證至少 need 組類別
    $keys = array_keys($sets);
    shuffle($keys);
    $pickKeys = array_slice($keys, 0, $need);

    $pwChars = [];
    foreach ($pickKeys as $k) {
        $pool = $sets[$k];
        $pwChars[] = $pool[random_int(0, strlen($pool)-1)];
    }

    // 其餘隨機補齊長度
    $all = implode('', $sets);
    while (count($pwChars) < $len) {
        $pwChars[] = $all[random_int(0, strlen($all)-1)];
    }

    // 洗牌
    for ($i = count($pwChars)-1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$pwChars[$i], $pwChars[$j]] = [$pwChars[$j], $pwChars[$i]];
    }

    return implode('', $pwChars);
}

/**
 * 以「pepper 版 verify_password()」驗證；若 DB 是舊格式（無 pepper bcrypt 或 md5），
 * 驗證通過後自動遷移成新的 hash_password()。
 */
function secure_verify_and_migrate(string $plain, string $dbHash, int $pkey): bool {
    // 1) 首選：pepper 版（已內建需要時 rehash）
    try {
        $ok = verify_password($plain, $dbHash, function(string $newHash) use ($pkey) {
            $pdo = new dbPDO();
            $pdo->update('webcontrol', ['strPW'=>$newHash, 'dtUDate'=>date('Y-m-d H:i:s')], 'PKey', $pkey);
            $pdo->close();
        });
        if ($ok) return true;
    } catch (Throwable $e) {
        // 若環境暫無 PASSWORD_PEPPER 會丟例外，轉用舊格式檢查
    }

    // 2) 相容：舊 bcrypt（無 pepper）
    if ($dbHash !== '' && preg_match('/^\$(2y|2a|2b)\$/', $dbHash)) {
        if (password_verify($plain, $dbHash)) {
            // 立即遷移為新格式（pepper + password_hash）
            $pdo = new dbPDO();
            $pdo->update('webcontrol', ['strPW'=>hash_password($plain), 'dtUDate'=>date('Y-m-d H:i:s')], 'PKey', $pkey);
            $pdo->close();
            return true;
        }
    }

    // 3) 相容：非常舊 md5（如仍存在）
    if (preg_match('/^[a-f0-9]{32}$/i', $dbHash)) {
        if (hash_equals(strtolower($dbHash), md5($plain))) {
            $pdo = new dbPDO();
            $pdo->update('webcontrol', ['strPW'=>hash_password($plain), 'dtUDate'=>date('Y-m-d H:i:s')], 'PKey', $pkey);
            $pdo->close();
            return true;
        }
    }

    return false;
}

// ─────────────────────────────────────────
// Base64-URL 解碼工具（UTF-8 驗證）【新增】
// ─────────────────────────────────────────
function b64url_to_b64(string $b64url): string {
    $b64 = strtr($b64url, '-_', '+/');
    $pad = 4 - (strlen($b64) % 4);
    if ($pad < 4) $b64 .= str_repeat('=', $pad);
    return $b64;
}

/**
 * 嚴格將 Base64-URL 解碼成 UTF-8 字串
 * - $b64url 必須是字串（允許空字串）
 * - 回傳 null 代表格式不合法或超過大小上限
 */
function strict_b64url_decode_to_utf8(?string $b64url, int $maxBytes = 0): ?string {
    if (!is_string($b64url)) return null; // 欄位必須存在（字串），內容可為空
    $b64 = b64url_to_b64($b64url);
    $bin = base64_decode($b64, true);
    if ($bin === false) return null;
    if ($maxBytes > 0 && strlen($bin) > $maxBytes) return null;
    if (!mb_check_encoding($bin, 'UTF-8')) return null;
    return $bin;
}
?>
