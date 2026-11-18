<?php

declare(strict_types=1);

/**
 * 輕量 Excel（.xlsx）寫入／讀取：僅用 PHP ZipArchive + XML，不依賴 PhpSpreadsheet。
 */

if (!function_exists('xlsx_xml_escape')) {
    function xlsx_xml_escape(string $value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        return htmlspecialchars((string)$clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('xlsx_col_letter')) {
    /** 1-based 欄號轉 A、B、…、AA */
    function xlsx_col_letter(int $index1): string
    {
        $n = max(1, $index1);
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }
}

if (!function_exists('xlsx_sheet_title')) {
    function xlsx_sheet_title(string $title): string
    {
        $title = str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $title);
        $title = trim($title);
        if ($title === '') {
            $title = 'Sheet1';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($title, 0, 31, 'UTF-8');
        }
        return substr($title, 0, 31);
    }
}

if (!function_exists('xlsx_write_file')) {
    /**
     * 將列資料寫成 .xlsx（每個 cell 以文字寫入，避免科學記號）。
     *
     * @param list<list<scalar|null>> $rows
     */
    function xlsx_write_file(array $rows, string $path, string $sheetTitle = 'Sheet1'): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('伺服器未啟用 ZipArchive，無法產生 Excel');
        }

        $sheetTitle = xlsx_sheet_title($sheetTitle);
        $sheetXml = xlsx_sheet_xml($rows);
        $nowIso = gmdate('Y-m-d\TH:i:s\Z');
        $titleXml = xlsx_xml_escape($sheetTitle);

        $zip = new ZipArchive();
        $open = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($open !== true) {
            throw new RuntimeException('無法建立 Excel 檔');
        }

        // 補齊 OOXML 必要部件（docProps／styles），避免瀏覽器把檔案當成一般 ZIP 而跳出危險警告
        $parts = [
            '[Content_Types].xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML,
            '_rels/.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML,
            'docProps/app.xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
  <Application>Microsoft Excel</Application>
</Properties>
XML,
            'docProps/core.xml' => <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:creator>brick6</dc:creator>
  <cp:lastModifiedBy>brick6</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{$nowIso}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{$nowIso}</dcterms:modified>
</cp:coreProperties>
XML,
            'xl/_rels/workbook.xml.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML,
            'xl/styles.xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
  <borders count="1"><border/></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>
XML,
            'xl/workbook.xml' => <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <workbookPr/>
  <sheets>
    <sheet name="{$titleXml}" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML,
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        foreach ($parts as $name => $xml) {
            $zip->addFromString($name, $xml);
            $zip->setCompressionName($name, ZipArchive::CM_DEFLATE);
        }
        $zip->close();
    }
}

if (!function_exists('xlsx_sheet_xml')) {
    /** @param list<list<scalar|null>> $rows */
    function xlsx_sheet_xml(array $rows): string
    {
        $maxCol = 1;
        $maxRow = 0;
        $xmlRows = '';
        $r = 0;
        foreach ($rows as $line) {
            $r++;
            if (!is_array($line)) {
                continue;
            }
            $maxRow = $r;
            $xmlRows .= '<row r="' . $r . '">';
            $c = 0;
            foreach ($line as $cell) {
                $c++;
                $maxCol = max($maxCol, $c);
                $ref = xlsx_col_letter($c) . $r;
                $text = is_scalar($cell) ? (string)$cell : '';
                $xmlRows .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                    . xlsx_xml_escape($text) . '</t></is></c>';
            }
            $xmlRows .= '</row>';
        }
        if ($maxRow < 1) {
            $maxRow = 1;
        }
        $dim = 'A1:' . xlsx_col_letter($maxCol) . $maxRow;
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="' . $dim . '"/>'
            . '<sheetData>' . $xmlRows . '</sheetData></worksheet>';
    }
}

if (!function_exists('xlsx_download')) {
    /**
     * 以附件下載 .xlsx 後結束請求。
     *
     * @param list<list<scalar|null>> $rows
     */
    function xlsx_download(array $rows, string $filename, string $sheetTitle = 'Sheet1'): void
    {
        $filename = str_replace(['"', "\r", "\n"], '', $filename);
        if ($filename === '') {
            $filename = 'export.xlsx';
        }
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmp === false) {
            xlsx_fail_html('無法建立暫存檔，匯出失敗');
        }
        @unlink($tmp);
        $tmpFile = $tmp . '.xlsx';

        try {
            xlsx_write_file($rows, $tmpFile, $sheetTitle);
        } catch (Throwable $e) {
            @unlink($tmpFile);
            xlsx_fail_html('匯出失敗');
        }

        xlsx_emit_download($tmpFile, $filename);
    }
}

if (!function_exists('xlsx_download_encrypted_zip')) {
    /**
     * 產生 .xlsx 後打包為 AES-256 加密 ZIP 再下載（結束請求）。
     * 需 PHP ZipArchive + libzip 支援 EM_AES_256。
     *
     * @param list<list<scalar|null>> $rows
     */
    function xlsx_download_encrypted_zip(
        array $rows,
        string $xlsxFilename,
        string $password,
        string $sheetTitle = 'Sheet1',
        ?string $zipFilename = null
    ): void {
        $password = trim($password);
        if ($password === '') {
            xlsx_fail_html('請設定 ZIP 開啟密碼');
        }
        if (strlen($password) < 6) {
            xlsx_fail_html('ZIP 密碼至少需 6 碼');
        }
        if (!class_exists('ZipArchive') || !defined('ZipArchive::EM_AES_256')) {
            xlsx_fail_html('伺服器未支援 ZIP AES 加密，無法匯出');
        }
        if (!method_exists(ZipArchive::class, 'setEncryptionName')) {
            xlsx_fail_html('伺服器 ZipArchive 無法設定加密，無法匯出');
        }

        $xlsxFilename = str_replace(['"', "\r", "\n", '/', '\\'], '', $xlsxFilename);
        if ($xlsxFilename === '') {
            $xlsxFilename = 'export.xlsx';
        }
        if (!str_ends_with(strtolower($xlsxFilename), '.xlsx')) {
            $xlsxFilename .= '.xlsx';
        }

        if ($zipFilename === null || trim($zipFilename) === '') {
            $zipFilename = preg_replace('/\.xlsx$/i', '', $xlsxFilename) . '.zip';
        }
        $zipFilename = str_replace(['"', "\r", "\n", '/', '\\'], '', (string)$zipFilename);
        if (!str_ends_with(strtolower($zipFilename), '.zip')) {
            $zipFilename .= '.zip';
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmpBase === false) {
            xlsx_fail_html('無法建立暫存檔，匯出失敗');
        }
        @unlink($tmpBase);
        $tmpXlsx = $tmpBase . '.xlsx';
        $tmpZip = $tmpBase . '.zip';

        try {
            xlsx_write_file($rows, $tmpXlsx, $sheetTitle);

            $zip = new ZipArchive();
            $open = $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($open !== true) {
                throw new RuntimeException('無法建立 ZIP');
            }
            if (!$zip->addFile($tmpXlsx, $xlsxFilename)) {
                $zip->close();
                throw new RuntimeException('無法將 Excel 加入 ZIP');
            }
            if (!$zip->setEncryptionName($xlsxFilename, ZipArchive::EM_AES_256, $password)) {
                $zip->close();
                throw new RuntimeException('無法設定 ZIP 加密');
            }
            if (!$zip->close()) {
                throw new RuntimeException('無法完成 ZIP');
            }
        } catch (Throwable $e) {
            @unlink($tmpXlsx);
            @unlink($tmpZip);
            xlsx_fail_html('加密 ZIP 匯出失敗');
        }

        @unlink($tmpXlsx);
        xlsx_emit_binary_download(
            $tmpZip,
            $zipFilename,
            'application/zip'
        );
    }
}

if (!function_exists('xlsx_emit_binary_download')) {
    /** 送出二進位附件標頭並結束請求 */
    function xlsx_emit_binary_download(string $tmpFile, string $filename, string $contentType): void
    {
        @ini_set('zlib.output_compression', '0');
        @ini_set('default_mimetype', '');
        @ini_set('default_charset', '');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (function_exists('header_remove')) {
            header_remove('Content-Type');
            header_remove('Content-Disposition');
        }

        $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'download.bin';
        $fileSize = filesize($tmpFile);

        header('X-Content-Type-Options: nosniff', true);
        header('X-Frame-Options: SAMEORIGIN', true);
        header('Content-Type: ' . $contentType, true);
        header(
            'Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
            true
        );
        header('Cache-Control: private, max-age=0, must-revalidate', true);
        header('Pragma: public', true);
        if ($fileSize !== false) {
            header('Content-Length: ' . (string)$fileSize, true);
        }
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }
}

if (!function_exists('xlsx_emit_download')) {
    /** 送出 Excel 附件標頭並結束請求（勿讓瀏覽器把內容當成 ZIP／.php） */
    function xlsx_emit_download(string $tmpFile, string $filename): void
    {
        $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'export.xlsx';
        if (!str_ends_with(strtolower($asciiName), '.xlsx')) {
            $asciiName .= '.xlsx';
        }
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }
        xlsx_emit_binary_download(
            $tmpFile,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}

if (!function_exists('xlsx_fail_html')) {
    function xlsx_fail_html(string $message): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=utf-8');
        echo $message;
        exit;
    }
}

if (!function_exists('xlsx_strip_xmlns')) {
    function xlsx_strip_xmlns(string $xml): string
    {
        $stripped = preg_replace('/\sxmlns(:\w+)?="[^"]*"/', '', $xml);
        return is_string($stripped) ? $stripped : $xml;
    }
}

if (!function_exists('xlsx_read_rows')) {
    /**
     * 讀取 .xlsx 第一個工作表（文字值）。舊版 .xls 不支援。
     *
     * @return list<list<string>>
     */
    function xlsx_read_rows(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('找不到 Excel 檔');
        }
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('伺服器未啟用 ZipArchive，無法讀取 Excel');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('無法開啟 Excel 檔（請使用 .xlsx）');
        }

        $shared = xlsx_parse_shared_strings((string)$zip->getFromName('xl/sharedStrings.xml'));
        $sheetXml = (string)$zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === '') {
            $sheetPath = xlsx_first_sheet_path($zip);
            if ($sheetPath !== '') {
                $sheetXml = (string)$zip->getFromName($sheetPath);
            }
        }
        $zip->close();

        if ($sheetXml === '') {
            throw new RuntimeException('Excel 內容無法解析');
        }

        return xlsx_parse_sheet_rows($sheetXml, $shared);
    }
}

if (!function_exists('xlsx_first_sheet_path')) {
    function xlsx_first_sheet_path(ZipArchive $zip): string
    {
        $rels = (string)$zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rels === '') {
            return '';
        }
        $xml = @simplexml_load_string(xlsx_strip_xmlns($rels), 'SimpleXMLElement', LIBXML_NONET);
        if ($xml === false) {
            return '';
        }
        foreach ($xml->Relationship as $rel) {
            $target = (string)$rel['Target'];
            if ($target === '') {
                continue;
            }
            $target = ltrim(str_replace('\\', '/', $target), '/');
            if (!str_starts_with($target, 'xl/')) {
                $target = 'xl/' . $target;
            }
            return $target;
        }
        return '';
    }
}

if (!function_exists('xlsx_parse_shared_strings')) {
    /** @return list<string> */
    function xlsx_parse_shared_strings(string $xml): array
    {
        if (trim($xml) === '') {
            return [];
        }
        $sx = @simplexml_load_string(xlsx_strip_xmlns($xml), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if ($sx === false) {
            return [];
        }
        $out = [];
        foreach ($sx->si as $si) {
            $out[] = trim(html_entity_decode(strip_tags($si->asXML() ?: ''), ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }
        return $out;
    }
}

if (!function_exists('xlsx_col_index')) {
    function xlsx_col_index(string $ref): int
    {
        if (!preg_match('/^([A-Za-z]+)/', $ref, $m)) {
            return 0;
        }
        $letters = strtoupper($m[1]);
        $n = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n;
    }
}

if (!function_exists('xlsx_parse_sheet_rows')) {
    /**
     * @param list<string> $shared
     * @return list<list<string>>
     */
    function xlsx_parse_sheet_rows(string $xml, array $shared): array
    {
        $sx = @simplexml_load_string(xlsx_strip_xmlns($xml), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if ($sx === false) {
            throw new RuntimeException('Excel 內容無法解析');
        }
        $grid = [];
        $maxCol = 0;
        $maxRow = 0;
        if (!isset($sx->sheetData) || !isset($sx->sheetData->row)) {
            return [];
        }
        foreach ($sx->sheetData->row as $row) {
            $rowNum = (int)($row['r'] ?? 0);
            foreach ($row->c as $cell) {
                $ref = (string)($cell['r'] ?? '');
                $col = xlsx_col_index($ref);
                if ($rowNum < 1 || $col < 1) {
                    continue;
                }
                $type = (string)($cell['t'] ?? '');
                $value = '';
                if ($type === 's') {
                    $idx = (int)($cell->v ?? 0);
                    $value = (string)($shared[$idx] ?? '');
                } elseif ($type === 'inlineStr') {
                    $value = trim(html_entity_decode(strip_tags($cell->is->asXML() ?: ''), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                } else {
                    $value = trim((string)($cell->v ?? ''));
                }
                $grid[$rowNum][$col] = $value;
                $maxCol = max($maxCol, $col);
                $maxRow = max($maxRow, $rowNum);
            }
        }
        $rows = [];
        for ($r = 1; $r <= $maxRow; $r++) {
            $line = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                $line[] = (string)($grid[$r][$c] ?? '');
            }
            $rows[] = $line;
        }
        return $rows;
    }
}

if (!function_exists('xlsx_read_column_a')) {
    /**
     * 讀取第一欄（略過標題列）。CSV 與 .xlsx 皆可。
     *
     * @return list<string>
     */
    function xlsx_read_column_a(string $path, int $startRow = 2): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $fh = fopen($path, 'rb');
            if ($fh === false) {
                throw new RuntimeException('無法讀取 CSV');
            }
            $values = [];
            $n = 0;
            while (($line = fgetcsv($fh)) !== false) {
                $n++;
                if ($n < $startRow) {
                    continue;
                }
                $v = trim((string)($line[0] ?? ''));
                if ($v !== '') {
                    $values[] = $v;
                }
            }
            fclose($fh);
            return $values;
        }

        $rows = xlsx_read_rows($path);
        $values = [];
        foreach ($rows as $idx => $line) {
            if (($idx + 1) < $startRow) {
                continue;
            }
            $v = trim((string)($line[0] ?? ''));
            if ($v !== '') {
                $values[] = $v;
            }
        }
        return $values;
    }
}
