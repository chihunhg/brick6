<?php
declare(strict_types=1);

// 自函式內 require 時（如 manage_child_list_render）需從 $GLOBALS 取語系變數
if (!isset($lang_text) || !is_array($lang_text)) {
    $lang_text = is_array($GLOBALS['lang_text'] ?? null)
        ? $GLOBALS['lang_text']
        : ['lang' => [1 => 'lang="zh-Hant-TW"', 2 => 'lang="en"']];
}
$this_lang = (int)($this_lang ?? $GLOBALS['this_lang'] ?? 1);
$htmlLangAttr = (string)($lang_text['lang'][$this_lang] ?? $lang_text['lang'][1] ?? 'lang="zh-Hant-TW"');
?>
<!DOCTYPE html>
<html <?php echo $htmlLangAttr; ?>>

<head>
    <?php require_once __DIR__ . '/_in_code_head.php'; ?>
    <?php require_once __DIR__ . '/_in_javascript.php'; ?>
