<?php
// title
$pageName = (string)($pageName ?? 'index');
$pageTitle2 = (string)($pageTitle2 ?? '');
$this_lang = (int)($this_lang ?? 1);

if (!isset($pageTitle) || $pageTitle === '') {
    if ($pageName !== 'index' && isset($lang_text[$pageName][$this_lang][$pageName])) {
        $pageTitle = $lang_text[$pageName][$this_lang][$pageName] . ' ∣ ' . $pageTitle2;
    } else {
        $webName = (string)($WebName ?? $Web_Name ?? '');
        $pageTitle = $webName !== '' ? $webName . ' ∣ ' . $pageTitle2 : $pageTitle2;
    }
}
?>
<title><?php echo htmlspecialchars((string)$pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></title>
<?php
$faviconHref = function_exists('site_favicon_href') ? site_favicon_href() : '../../favicon.ico';
?>
<link rel="icon" href="<?php echo function_exists('e_attr') ? e_attr($faviconHref) : htmlspecialchars($faviconHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" type="image/x-icon">
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" >
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="all" />
