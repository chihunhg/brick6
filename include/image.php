<?php

/**
 * 圖片處理模組：WebP 轉檔、GD 縮圖（ImageResizer）、上傳與縮圖工具
 */

/**
 * 穩健判斷指定檔案是否為 WebP：
 * 1) finfo MIME 檢查
 * 2) 檔頭 "RIFF....WEBP" 簽名
 * 3) exif_imagetype（若主機支援）
 */
function is_really_webp(string $path): bool
{
    if (!is_file($path) || !is_readable($path)) return false;

    // 1) finfo MIME
    if (function_exists('finfo_open')) {
        $fi = @finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) {
            $mime = @finfo_file($fi, $path);
            if (is_string($mime) && stripos($mime, 'image/webp') === 0) {
                return true;
            }
        }
    }

    // 2) 簽名檢查：前 12 bytes = "RIFF....WEBP"
    $fp = @fopen($path, 'rb');
    if ($fp) {
        $hdr = @fread($fp, 12);
        @fclose($fp);
        if (is_string($hdr) && strlen($hdr) === 12) {
            if (substr($hdr, 0, 4) === "RIFF" && substr($hdr, 8, 4) === "WEBP") {
                return true;
            }
        }
    }

    // 3) exif_imagetype（新 PHP 會回 IMAGETYPE_WEBP=18）
    if (function_exists('exif_imagetype')) {
        $t = @exif_imagetype($path);
        $webpConst = defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : 18;
        if ($t === $webpConst) return true;
    }

    return false;
}

/**
 * 將上傳檔／路徑／檔名轉為 WebP（Imagick 優先，其次 GD）
 *
 * @param array|string $input   $_FILES 欄位、路徑或檔名
 * @param string       $destDir 輸出目錄
 * @param int          $quality WebP 品質 0–100
 * @return string|false 成功回傳 .webp 完整路徑，失敗回傳 false
 */
function convert_uploaded_to_webp($input, string $destDir, int $quality = 85): string|false
{
    // 正規化輸出資料夾
    $destDir = rtrim($destDir, "/\\");
    if ($destDir === '') {
        error_log("convert_uploaded_to_webp: destDir is empty");
        return false;
    }
    if (!is_dir($destDir) && !mkdir($destDir, 0777, true)) {
        error_log("convert_uploaded_to_webp: cannot create dest dir: {$destDir}");
        return false;
    }

    $fileArr = null;           // $_FILES['field'] 內容
    $srcPathFromDisk = null;   // 已存在的實體路徑

    // --- 解析輸入型別 ---
    if (is_array($input)) {
        // case 1: 直接給 $_FILES['field']
        $fileArr = $input;
    } elseif (is_string($input)) {
        // case 2a: 欄位名
        if (isset($_FILES[$input]) && is_array($_FILES[$input])) {
            $fileArr = $_FILES[$input];
        }
        // case 2b: 直接給路徑（絕對或相對）
        if ($srcPathFromDisk === null && is_file($input) && is_readable($input)) {
            $srcPathFromDisk = $input;
        }
        // case 2c: 只給檔名 -> 試著在 $destDir 裡找
        if ($srcPathFromDisk === null && preg_match('/^[^\\\\\\/]+\\.[A-Za-z0-9]+$/', $input)) {
            $candidate = $destDir . DIRECTORY_SEPARATOR . $input;
            if (is_file($candidate) && is_readable($candidate)) {
                $srcPathFromDisk = $candidate;
            }
        }
    } else {
        error_log("convert_uploaded_to_webp: invalid input type");
        return false;
    }

    // --- 取得原始檔名與副檔名 ---
    if ($fileArr) {
        if (!isset($fileArr['tmp_name']) || ($fileArr['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            error_log("convert_uploaded_to_webp: upload not ok");
            return false;
        }
        $origName = $fileArr['name'] ?? 'upload';
    } elseif ($srcPathFromDisk) {
        $origName = basename($srcPathFromDisk);
    } else {
        error_log("convert_uploaded_to_webp: cannot resolve input to file path or upload");
        return false;
    }

    $ext  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $name = pathinfo($origName, PATHINFO_FILENAME);
    if ($name === '' || $ext === '') {
        error_log("convert_uploaded_to_webp: invalid original name/ext");
        return false;
    }

    $srcPath  = $destDir . DIRECTORY_SEPARATOR . $name . '.' . $ext;
    $webpPath = $destDir . DIRECTORY_SEPARATOR . $name . '.webp';

    // --- 把來源檔放到 $destDir（若已在就跳過搬移） ---
    if ($fileArr) {
        if (!move_uploaded_file($fileArr['tmp_name'], $srcPath)) {
            error_log("convert_uploaded_to_webp: move_uploaded_file failed");
            return false;
        }
        @chmod($srcPath, 0666);
    } elseif (realpath($srcPathFromDisk) !== realpath($srcPath)) {
        // 來源是其他路徑 -> 複製到目標資料夾
        if (!@copy($srcPathFromDisk, $srcPath)) {
            error_log("convert_uploaded_to_webp: copy from disk failed ({$srcPathFromDisk} -> {$srcPath})");
            return false;
        }
        @chmod($srcPath, 0666);
    }

    // --- 轉檔：Imagick 優先，其次 GD（GD 只支援靜態） ---
    if (class_exists('Imagick')) {
        try {
            $im = new Imagick($srcPath);

            // 可選：調整 webp 參數以提升相容性/品質
            // $im->setOption('webp:method', '6'); // 0-6
            // 若要無損可開啟（PNG 多半適用），不需要可註解
            // if ($ext === 'png') { $im->setOption('webp:lossless', 'true'); }

            if ($ext === 'gif' && $im->getNumberImages() > 1) {
                // 動態 GIF → 動態 WebP
                $im = $im->coalesceImages();
                foreach ($im as $frame) {
                    $frame->setImageFormat('webp');
                    $frame->setImageCompressionQuality($quality);
                }
                $ok = $im->writeImages($webpPath, true);
            } else {
                // 靜態
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality($quality);
                $ok = $im->writeImage($webpPath);
            }

            $im->clear(); $im->destroy();

            if ($ok) {
                @chmod($webpPath, 0666);
                return $webpPath;
            }
            return false;

        } catch (Throwable $e) {
            error_log("Imagick failed: " . $e->getMessage());
            // 繼續嘗試 GD
        }
    }

    if (function_exists('imagewebp')) {
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $img = @imagecreatefromjpeg($srcPath); break;
            case 'png':
                $img = @imagecreatefrompng($srcPath);
                if ($img) {
                    if (function_exists('imagepalettetotruecolor')) { @imagepalettetotruecolor($img); }
                    imagealphablending($img, false); imagesavealpha($img, true);
                }
                break;
            case 'gif':
                $img = @imagecreatefromgif($srcPath); break;
            default:
                error_log("GD: unsupported extension: {$ext}");
                return false;
        }
        if (!$img) return false;

        $ok = @imagewebp($img, $webpPath, $quality);
        //gd_image_free($img);

        if ($ok) {
            @chmod($webpPath, 0666);
            return $webpPath;
        }
        return false;
    }

    error_log("No WebP support found (need Imagick or GD)");
    return false;
}

if (!function_exists('gd_image_free')) {
    /**
     * 釋放 GD 圖片資源（PHP 8.0+ imagedestroy 無效果；8.5+ 已棄用，改由 GC 回收）
     */
    function gd_image_free($image): void
    {
        if ($image === null || $image === false) {
            return;
        }
        if (PHP_VERSION_ID < 80000 && function_exists('imagedestroy')) {
            imagedestroy($image);
        }
    }
}

class ImageResizer
{
    private $img;           // GD resource
    private $type;          // IMAGETYPE_*
    private $w = 0;
    private $h = 0;

    /** 載入圖片並依 JPEG EXIF 自動旋轉 */
    public function load(string $filename): void
    {
        if (!is_file($filename)) {
            throw new RuntimeException("File not found: {$filename}");
        }
        $info = getimagesize($filename);
        if ($info === false) {
            throw new RuntimeException("Not an image: {$filename}");
        }
        $this->type = $info[2];

        switch ($this->type) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($filename);
                $img = $this->autoRotateFromExif($img, $filename);
                break;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($filename);
                if ($img) { imagealphablending($img, false); imagesavealpha($img, true); }
                break;
            case IMAGETYPE_GIF:
                // 只載入第一幀（GD 不支援動圖）
                $img = @imagecreatefromgif($filename);
                break;
            default:
                // 其它類型盡量嘗試
                $data = file_get_contents($filename);
                $img  = $data !== false ? @imagecreatefromstring($data) : false;
        }
        if (!$img) {
            throw new RuntimeException("Load failed: {$filename}");
        }

        $this->img = $this->ensureTrueColor($img);
        $this->w   = imagesx($this->img);
        $this->h   = imagesy($this->img);
    }

    /** @return int 目前圖片寬度（px） */
    public function width(): int  { return $this->w; }
    /** @return int 目前圖片高度（px） */
    public function height(): int { return $this->h; }

    /** 等比縮至目標寬度；$noUpscale 為 true 時不放大 */
    public function resizeToWidth(int $targetW, bool $noUpscale = true): void
    {
        if ($noUpscale && $this->w <= $targetW) return;
        $ratio = $targetW / $this->w;
        $this->resize((int)round($targetW), (int)round($this->h * $ratio));
    }

    /** 等比縮至目標高度；$noUpscale 為 true 時不放大 */
    public function resizeToHeight(int $targetH, bool $noUpscale = true): void
    {
        if ($noUpscale && $this->h <= $targetH) return;
        $ratio = $targetH / $this->h;
        $this->resize((int)round($this->w * $ratio), (int)round($targetH));
    }

    /** 最長邊不超過 maxW×maxH，等比縮放；$noUpscale 為 true 時不放大 */
    public function resizeMax(int $maxW, int $maxH, bool $noUpscale = true): void
    {
        $nw = $this->w;
        $nh = $this->h;

        if ($maxW > 0 && $nw > $maxW) {
            $ratio = $maxW / $nw; $nw = $maxW; $nh = (int)round($nh * $ratio);
        }
        if ($maxH > 0 && $nh > $maxH) {
            $ratio = $maxH / $nh; $nh = $maxH; $nw = (int)round($nw * $ratio);
        }

        if ($noUpscale && $nw >= $this->w && $nh >= $this->h) return;
        $this->resize($nw, $nh);
    }

    /** contain：等比塞入框內並置中，可選透明或白底 */
    public function resizeContain(int $boxW, int $boxH, bool $transparent = true): void
    {
        $ratio = min($boxW / $this->w, $boxH / $this->h);
        $nw = (int)round($this->w * $ratio);
        $nh = (int)round($this->h * $ratio);

        $canvas = imagecreatetruecolor($boxW, $boxH);
        if ($transparent) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparentColor = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $boxW, $boxH, $transparentColor);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $boxW, $boxH, $white);
        }

        $dstX = (int)(($boxW - $nw) / 2);
        $dstY = (int)(($boxH - $nh) / 2);

        imagecopyresampled($canvas, $this->img, $dstX, $dstY, 0, 0, $nw, $nh, $this->w, $this->h);
        gd_image_free($this->img);
        $this->img = $canvas; $this->w = $boxW; $this->h = $boxH;
    }

    /** cover：等比放大後居中裁切以填滿框 */
    public function resizeCover(int $boxW, int $boxH): void
    {
        $srcRatio = $this->w / $this->h;
        $dstRatio = $boxW / $boxH;

        if ($srcRatio > $dstRatio) {
            // 寬裁切
            $newH = $boxH;
            $newW = (int)round($boxH * $srcRatio);
        } else {
            // 高裁切
            $newW = $boxW;
            $newH = (int)round($boxW / $srcRatio);
        }
        $this->resize($newW, $newH);

        // 居中裁切
        $x = (int)(($this->w - $boxW) / 2);
        $y = (int)(($this->h - $boxH) / 2);
        $crop = imagecreatetruecolor($boxW, $boxH);
        imagealphablending($crop, false); imagesavealpha($crop, true);
        imagecopy($crop, $this->img, 0, 0, $x, $y, $boxW, $boxH);
        gd_image_free($this->img);
        $this->img = $crop; $this->w = $boxW; $this->h = $boxH;
    }

    /** 低階縮放至指定寬高（px） */
    public function resize(int $nw, int $nh): void
    {
        $nw = max(1, (int)$nw);
        $nh = max(1, (int)$nh);

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $this->img, 0, 0, 0, 0, $nw, $nh, $this->w, $this->h);
        gd_image_free($this->img);
        $this->img = $dst; $this->w = $nw; $this->h = $nh;
    }

    /**
     * 儲存圖片（依副檔名或 $imageType 決定格式）
     *
     * @param int $quality JPEG/WebP 0–100；PNG 壓縮等級反向映射
     */
    public function save(string $filename, int $imageType = 0, int $quality = 85): void
    {
        if ($imageType === 0) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $map = ['jpg'=>IMAGETYPE_JPEG, 'jpeg'=>IMAGETYPE_JPEG, 'png'=>IMAGETYPE_PNG, 'gif'=>IMAGETYPE_GIF, 'webp'=>defined('IMAGETYPE_WEBP')?IMAGETYPE_WEBP:18];
            $imageType = $map[$ext] ?? IMAGETYPE_JPEG;
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->img, $filename, max(0, min(100, $quality)));
                break;
            case IMAGETYPE_PNG:
                $level = (int)round((100 - max(0, min(100, $quality))) * 9 / 100);
                imagepng($this->img, $filename, $level);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->img, $filename);
                break;
            default: // WebP
                if (!function_exists('imagewebp')) {
                    throw new RuntimeException('GD has no WebP support');
                }
                imagewebp($this->img, $filename, max(0, min(100, $quality)));
        }
        @chmod($filename, 0755);
    }

    /** 快捷儲存為 WebP */
    public function saveWebp(string $filename, int $quality = 85): void
    {
        $this->save($filename, defined('IMAGETYPE_WEBP')?IMAGETYPE_WEBP:18, $quality);
    }

    /** 釋放 GD 圖片資源 */
    public function destroy(): void
    {
        if ($this->img) { gd_image_free($this->img); }
        $this->img = null;
    }

    /** 轉為 TrueColor 並保留 alpha 透明通道 */
    private function ensureTrueColor($img)
    {
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($img);
        }
        $w = imagesx($img); $h = imagesy($img);
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopy($dst, $img, 0, 0, 0, 0, $w, $h);
        gd_image_free($img);
        return $dst;
    }

    /** 依 JPEG EXIF Orientation 自動旋轉（無 EXIF 時原樣回傳） */
    private function autoRotateFromExif($img, string $path)
    {
        if (!function_exists('exif_read_data') || !$img) return $img;
        $exif = @exif_read_data($path);
        if (!$exif || empty($exif['Orientation'])) return $img;

        switch ((int)$exif['Orientation']) {
            case 3:  $img = imagerotate($img, 180, 0); break;
            case 6:  $img = imagerotate($img, -90, 0); break;
            case 8:  $img = imagerotate($img, 90, 0); break;
        }
        return $img;
    }
}

if (!function_exists('create_image_list_thumb')) {
    /**
     * 產生列表用縮圖（預設 thumb_ 前綴，供後台列表預覽）
     */
    function create_image_list_thumb(
        string $folder,
        string $photo,
        int $width = 150,
        string $prefix = 'thumb_'
    ): bool {
        $folder = rtrim(str_replace('\\', '/', $folder), '/') . '/';
        $photo  = basename($photo);
        if ($photo === '') {
            return false;
        }
        $src = $folder . $photo;
        if (!is_file($src)) {
            return false;
        }
        $dest = $folder . $prefix . $photo;
        try {
            $resizer = new ImageResizer();
            $resizer->load($src);
            $resizer->resizeToWidth(max(1, $width), true);
            $resizer->save($dest);
            $resizer->destroy();
            @chmod($dest, 0664);
            return is_file($dest);
        } catch (Throwable $e) {
            error_log('create_image_list_thumb: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * 通用圖片上傳函式（多檔）
 *
 * @param array  $file_array     來源 $_FILES（或結構相同的陣列），鍵名預設為 Photo1, Photo2...
 * @param string $upload_folder  目標根目錄（結尾不必加斜線）
 * @param array  $options        自訂參數
 */
function upload_images(array $file_array, string $upload_folder, array $options = []): array
{
    // ---- 參數與預設 ----
    $forder_prefix   = (string)($options['forder_prefix']   ?? 'dbad_');
    $total_uploads   = (int)   ($options['total_uploads']   ?? 10);
    $size_bytes      = (int)   ($options['size_bytes']      ?? 2000 * 1024);
    $limitedext      =         ($options['limitedext']      ?? ['.gif','.jpg','.jpeg','.png']);
    $allowed_exts    =         ($options['allowed_exts']    ?? ['gif','jpg','jpeg','png']);
    $allowed_mimes   =         ($options['allowed_mimes']   ?? ['image/gif','image/jpeg','image/png']);
    $allowed_types   =         ($options['allowed_types']   ?? [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG]);
    $max_width       = (int)   ($options['max_width']       ?? 8000);
    $max_height      = (int)   ($options['max_height']      ?? 8000);
    $field_prefix    = (string)($options['field_prefix']    ?? 'Photo');
    $filter_array    =         ($options['filter_array']    ?? []);
    $convert_to_webp = (bool)  ($options['convert_to_webp'] ?? false);
    $webp_quality    = (int)   ($options['webp_quality']    ?? 100);

    // 統一路徑斜線與子目錄（YYYYMM/）
    $monthfolder  = trim(date('Ym') . '/', '/\\') . DIRECTORY_SEPARATOR;
    $upload_folder = rtrim($upload_folder ?? '', "/\\") . DIRECTORY_SEPARATOR;

    // 確保目錄存在
    if (!is_dir($upload_folder . $monthfolder)) {
        @mkdir($upload_folder . $monthfolder, 0775, true);
    }

    // 上傳錯誤碼訊息
    $upload_err_msg = [
        UPLOAD_ERR_OK         => '上傳成功',
        UPLOAD_ERR_INI_SIZE   => '超過 php.ini 上傳大小限制',
        UPLOAD_ERR_FORM_SIZE  => '超過表單大小限制',
        UPLOAD_ERR_PARTIAL    => '檔案僅部分上傳',
        UPLOAD_ERR_NO_FILE    => '未選擇檔案',
        UPLOAD_ERR_NO_TMP_DIR => '找不到暫存資料夾',
        UPLOAD_ERR_CANT_WRITE => '無法寫入硬碟',
        UPLOAD_ERR_EXTENSION  => '副檔名被擴充功能阻擋',
    ];

    // 共用 finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    // 回傳容器
    $messages = '';
    $photos   = [];   // $photos[$i] = 檔名（不含路徑）
    $details  = [];   // 每張的細節

    for ($i = 1; $i <= $total_uploads; $i++) {
        $key = $field_prefix . $i;
        $detail = [
            'index'    => $i,
            'field'    => $key,
            'name'     => null,
            'saved_as' => null,
            'webp_path'=> null,
            'error'    => null,
            'notice'   => [],
        ];

        if (empty($file_array[$key]) || !is_array($file_array[$key])) {
            $details[$i] = $detail;
            continue;
        }

        $f = $file_array[$key];

        $err       = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmp       = (string)($f['tmp_name'] ?? '');
        $orig_name = (string)($f['name'] ?? '');
        $size      = (int)($f['size'] ?? 0);
        $detail['name'] = $orig_name;

        // 忽略沒選檔案
        if ($err === UPLOAD_ERR_NO_FILE || $orig_name === '' || $tmp === '') {
            $details[$i] = $detail;
            continue;
        }

        // 上傳錯誤
        if ($err !== UPLOAD_ERR_OK) {
            $msgTxt = $upload_err_msg[$err] ?? ('錯誤碼 '.$err);
            $messages .= "檔案 {$i}: 上傳失敗（{$msgTxt}）\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }

        // 確認真的是 HTTP 上傳暫存檔
        if (!is_uploaded_file($tmp)) {
            $msgTxt = "非法上傳來源";
            $messages .= "檔案 {$i}: {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }

        // 取得副檔名（小寫）
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        // 與舊的 $limitedext 相容（其含 .ext）
        $dot_ext = '.' . $ext;
        if (!in_array($ext, $allowed_exts, true) && !in_array($dot_ext, $limitedext, true)) {
            $msgTxt = "副檔名不允許";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }

        // 真實 MIME
        $mime = $finfo ? (finfo_file($finfo, $tmp) ?: '') : (mime_content_type($tmp) ?: '');
        if (!in_array($mime, $allowed_mimes, true)) {
            $msgTxt = "內容類型不符（{$mime}）";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }

        // 魔數驗證
        $img_type = @exif_imagetype($tmp);
        if ($img_type === false || !in_array($img_type, $allowed_types, true)) {
            $msgTxt = "非有效圖片檔";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }

        // 尺寸檢查
        $dim = @getimagesize($tmp);
        if ($dim === false) {
            $msgTxt = "無法讀取尺寸";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }
        [$w, $h] = $dim;
        if ($w <= 0 || $h <= 0) {
            $msgTxt = "尺寸異常";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }
        if ($w > $max_width || $h > $max_height) {
            $msgTxt = "圖片過大（{$w}x{$h}）";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }

        // 大小限制
        if ($size <= 0 || $size > $size_bytes) {
            $kb = number_format($size_bytes / 1024, 0);
            $msgTxt = "檔案大小超過限制（最大 {$kb}KB）";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }

        // 產生安全檔名
        $safe_name = $forder_prefix . date('YmdHis') . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . '.' . $ext;

        // 寫入目標
        $dest = $upload_folder . $monthfolder . $safe_name;
        if (!move_uploaded_file($tmp, $dest)) {
            $msgTxt = "搬移失敗";
            $messages .= "檔案 {$i}: ({$orig_name}) {$msgTxt}\n";
            $detail['error'] = $msgTxt;
            $details[$i] = $detail;
            continue;
        }
        @chmod($dest, 0666);

        // 成功
        $photos[$i]     = $safe_name;
        $detail['saved_as'] = $safe_name;

        // 是否需要轉 WebP（兩種觸發：個別 filter 或全域 convert_to_webp）
        $need_webp = false;
        if (!empty($filter_array['intType' . $i]) && (int)$filter_array['intType' . $i] === 1) {
            $need_webp = true;
        } elseif ($convert_to_webp === true) {
            $need_webp = true;
        }

        if ($need_webp && function_exists('convert_uploaded_to_webp')) {
            $saveDir  = rtrim($upload_folder . $monthfolder,) . DIRECTORY_SEPARATOR;
            $result   = convert_uploaded_to_webp($safe_name, $saveDir, $webp_quality); // 成功回傳 webp 完整路徑

            $ok = (is_string($result) && $result !== '' && is_file($result) && is_really_webp($result));
            if (!$ok) {
                $messages .= "檔案 {$i}: ({$orig_name}) 轉換 WebP 失敗或格式驗證不符\n";
                $detail['notice'][] = "WebP 轉換/驗證不符";
            } else {
                $detail['webp_path'] = $result;
            }
        }

        $details[$i] = $detail;
    }

    if ($finfo) {
        //finfo_close($finfo);
    }

    return [
        'ok'       => count($photos) > 0,
        'monthdir' => $monthfolder,
        'photos'   => $photos,     // 存 DB 時可用
        'messages' => $messages,   // 顯示於畫面或 log
        'details'  => $details,    // 需要逐檔資訊可用
    ];
}

/**
 * 通用檔案上傳（支援多種類型與多欄位 Photo1..PhotoN）
 */
function upload_files(array $file_array, string $upload_root, array $options = []): array
{
    $field_prefix  = (string)($options['field_prefix']  ?? 'Photo');
    $total         = (int)   ($options['total_uploads'] ?? 2);
    $size_bytes    = (int)   ($options['size_bytes']    ?? 6000 * 1024);
    $name_prefix   = (string)($options['name_prefix']   ?? 'file_');

    // 白名單（副檔名）
    $limitedext    = $options['limitedext']   ?? ['.gif','.png','.jpg','.pdf','.doc','.docx','.ppt','.pptx','.xls','.xlsx','.txt','.zip','.rar'];
    $allowed_exts  = $options['allowed_exts'] ?? ['gif','png','jpg','jpeg','pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip','rar'];
    // MIME 白名單（留空代表不檢查 MIME）
    $allowed_mimes = $options['allowed_mimes'] ?? [];

    $filter_array  = $options['filter_array'] ?? [];
    $max_w         = (int)($options['max_width']  ?? 8000);
    $max_h         = (int)($options['max_height'] ?? 8000);

    // 路徑處理
    $upload_root  = rtrim($upload_root, "/\\") . DIRECTORY_SEPARATOR;
    $monthfolder  = date('Ym') . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_root . $monthfolder)) {
        @mkdir($upload_root . $monthfolder, 0775, true);
    }

    $messages = '';
    $files    = [];
    $details  = [];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    for ($i = 1; $i <= $total; $i++) {
        $key = $field_prefix . $i;
        $d   = [
            'index'    => $i,
            'field'    => $key,
            'name'     => null,
            'saved_as' => null,
            'error'    => null,
            'size'     => null,
            'ext'      => null,
            'mime'     => null,
            'width'    => null,
            'height'   => null,
            'webp_path'=> null,
        ];

        if (empty($file_array[$key]) || !is_array($file_array[$key])) {
            $details[$i] = $d;
            continue;
        }

        $f = $file_array[$key];
        $name = (string)($f['name'] ?? '');
        $tmp  = (string)($f['tmp_name'] ?? '');
        $size = (int)   ($f['size'] ?? 0);
        $err  = (int)   ($f['error'] ?? UPLOAD_ERR_NO_FILE);

        // 沒選檔案
        if ($err === UPLOAD_ERR_NO_FILE || $name === '' || $tmp === '') {
            $details[$i] = $d;
            continue;
        }

        // 基本上傳錯誤
        if ($err !== UPLOAD_ERR_OK) {
            $d['error'] = "上傳失敗（錯誤碼 {$err}）";
            $messages  .= "檔案 {$i}: {$d['error']}\n";
            $details[$i] = $d;
            continue;
        }

        // 來源驗證
        if (!is_uploaded_file($tmp)) {
            $d['error'] = '非法上傳來源';
            $messages  .= "檔案 {$i}: {$d['error']}\n";
            $details[$i] = $d;
            continue;
        }

        // 檔名前處理（僅用於顯示；實際會換安全新檔名）
        $display_name = str_replace(' ', '_', $name);
        $d['name'] = $display_name;

        // 副檔名（小寫、不含點）
        $ext = strtolower(pathinfo($display_name, PATHINFO_EXTENSION));
        $d['ext'] = $ext;

        // 白名單（副檔名）
        $dot_ext = '.' . $ext;
        if (!in_array($ext, $allowed_exts, true) && !in_array($dot_ext, $limitedext, true)) {
            $d['error'] = '副檔名不允許';
            $messages  .= "檔案 {$i}: ({$display_name}) {$d['error']}\n";
            $details[$i] = $d;
            continue;
        }

        // MIME（真實）
        $mime = $finfo ? (finfo_file($finfo, $tmp) ?: '') : '';
        $d['mime'] = $mime;
        if (!empty($allowed_mimes)) {
            if (!in_array($mime, $allowed_mimes, true)) {
                $d['error'] = "內容類型不符（{$mime}）";
                $messages  .= "檔案 {$i}: ({$display_name}) {$d['error']}\n";
                $details[$i] = $d;
                continue;
            }
        }

        // 檔案大小
        $d['size'] = $size;
        if ($size <= 0 || $size > $size_bytes) {
            $kb = number_format($size_bytes / 1024, 0);
            $d['error'] = "檔案大小超過限制（最大 {$kb}KB）";
            $messages  .= "檔案 {$i}: ({$display_name}) {$d['error']}\n";
            $details[$i] = $d;
            continue;
        }

        // 若是圖片，額外做尺寸驗證（不影響文件型別）
        $is_image = in_array($ext, ['gif','png','jpg','jpeg','bmp','ico'], true) || str_starts_with((string)$mime, 'image/');
        if ($is_image) {
            $dim = @getimagesize($tmp);
            if ($dim !== false) {
                [$w, $h] = $dim;
                $d['width'] = $w;
                $d['height'] = $h;
                if ($w <= 0 || $h <= 0 || $w > $max_w || $h > $max_h) {
                    $d['error'] = "圖片尺寸不符（{$w}x{$h}）";
                    $messages  .= "檔案 {$i}: ({$display_name}) {$d['error']}\n";
                    $details[$i] = $d;
                    continue;
                }
            }
        }

        // 產生安全新檔名
        $safe_name = $name_prefix . date('YmdHis') . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . '.' . $ext;
        $dest = $upload_root . $monthfolder . $safe_name;

        if (!move_uploaded_file($tmp, $dest)) {
            $d['error'] = '搬移失敗';
            $messages  .= "檔案 {$i}: ({$display_name}) {$d['error']}\n";
            $details[$i] = $d;
            continue;
        }
        @chmod($dest, 0666);

        // 成功：記錄
        $files[$i]     = $safe_name;
        $d['saved_as'] = $safe_name;

        // 若前端指定 intType{i}==1 且為圖片 → 嘗試轉 WebP
        if ($is_image && !empty($filter_array['intType' . $i]) && (int)$filter_array['intType' . $i] === 1) {

            // 寬高若前面沒抓到，再抓一次
            if ($d['width'] === null || $d['height'] === null) {
                $dim = @getimagesize($dest);
                if ($dim !== false) {
                    $d['width']  = $dim[0];
                    $d['height'] = $dim[1];
                }
            }

            $webp_full = null;
            if (function_exists('convert_uploaded_to_webp')) {
                $webp_full = convert_uploaded_to_webp($safe_name, $upload_root . $monthfolder, 100);
            } elseif (function_exists('covnert_webp')) {
                $webp_full = covnert_webp($upload_root . $monthfolder . $safe_name);
            }

            if (is_string($webp_full) && $webp_full !== '' && is_file($webp_full) && is_really_webp($webp_full)) {
                $d['webp_path'] = $webp_full;
            } else {
                $messages  .= "檔案 {$i}: ({$display_name}) WebP 轉換/驗證不符\n";
            }
        }

        $details[$i] = $d;
    }

    return [
        'ok'       => count($files) > 0 && $messages === '',
        'monthdir' => $monthfolder,
        'files'    => $files,
        'messages' => $messages,
        'details'  => $details,
    ];
}

/** 產生列表縮圖 thumb_（委派 create_image_list_thumb） */
function ReSizeImg($Forder = '', $Photo = '', $Width = 150)
{
    if (function_exists('create_image_list_thumb')) {
        return create_image_list_thumb((string)$Forder, (string)$Photo, (int)$Width, 'thumb_');
    }
    return false;
}

/** 依目標寬高計算等比縮圖尺寸 [w, h] */
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
