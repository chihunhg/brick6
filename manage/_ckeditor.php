<?php
$ckeJs = __DIR__ . '/ckeditor/ckeditor.js';
$ckeCfg = __DIR__ . '/ckeditor/config.js';
$ckeVer = is_file($ckeJs) ? (string)filemtime($ckeJs) : '1';
$cfgVer = is_file($ckeCfg) ? (string)filemtime($ckeCfg) : '1';

echo script_src_tag('ckeditor/ckeditor.js?ver=' . $ckeVer);
echo script_src_tag('ckeditor/config.js?ver=' . $cfgVer);
echo manage_inline_script(<<<'JS'
(function () {
  function initEditors() {
    if (typeof CKEDITOR === 'undefined') {
      return;
    }
    var nodes = document.querySelectorAll('textarea.ckeditor');
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      if (!el.id) {
        continue;
      }
      if (CKEDITOR.instances[el.id]) {
        continue;
      }
      CKEDITOR.replace(el.id);
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEditors);
  } else {
    initEditors();
  }
})();
JS);
