<?php
//gaCode--begin--------------------------------------------------
if ( !empty($Web_gaCode) ){
?>
<!-- Global site tag (gtag.js) - Google Analytics -->
<?php echo script_src_tag('https://www.googletagmanager.com/gtag/js?id=' . rawurlencode((string)$Web_gaCode), ['async' => true]); ?>
<?php echo manage_inline_script(
    "window.dataLayer = window.dataLayer || [];\n"
    . "function gtag(){dataLayer.push(arguments);}\n"
    . "gtag('js', new Date());\n"
    . "gtag('config', " . json_encode((string)$Web_gaCode, JSON_UNESCAPED_UNICODE) . ");"
); ?>
<?php
}
//gaCode--end--------------------------------------------------

//gtmCode--begin--------------------------------------------------
if (!empty($Web_gtmCode)) {
    echo manage_inline_script(
        "window.dataLayer = window.dataLayer || [];\n"
        . "window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});"
    );
    echo script_src_tag(
        'https://www.googletagmanager.com/gtm.js?id=' . rawurlencode((string)$Web_gtmCode),
        ['async' => true]
    );
}
//gtmCode--end--------------------------------------------------
?>
