/**
 * Jodit 全站設定與初始化（本檔為本站自有，非套件檔；升級 Jodit 時不可覆蓋）。
 *
 * 套用方式：後台內容 textarea 掛 class="ckeditor"，本檔載入後逐一接管
 * （manage/_in_code_bottom.php 載入順序：jodit.min.js → styles.js → 本檔）。
 * 全站不在各頁寫 Jodit.make()。既有腳本仍呼叫 CKEDITOR.instances，這裡提供同等的
 * getData／setData／updateElement／resize／destroy／replace。
 *
 * 外觀：編輯區是 iframe，載前台 CSS、body 掛前台內文區的 class。
 * 授權：Jodit 是 MIT（見 LICENSE.txt、SOURCE.md）。
 */
(function () {
  "use strict";

  var J = window.Jodit;
  if (!J) {
    console.error("Jodit 未載入（manage/jodit/jodit.min.js）");
    return;
  }
  var Dom = J.modules.Dom;

  /**
   * 由本檔自己的 src 推出絕對路徑。不要寫相對「目前頁面」的路徑，
   * 後台頁在 /manage/{模組}/ 底下，相對路徑會指錯。
   */
  var basePath = (
    (document.currentScript && document.currentScript.src) ||
    ""
  ).replace(/[^\/]*$/, "");
  var ELFINDER_URL = basePath + "../elFinder/elfinder_cke.php";
  var CSS_VER =
    (document.currentScript && document.currentScript.dataset.cssVer) || "0";
  var IMAGE_EXT = /\.(jpe?g|png|gif|webp|svg|bmp)(\?.*)?$/i;

  var editors = {};
  var instances = {};
  var elfinderTokens = {};
  var seq = 0;

  var DENY_TAGS =
    "script,noscript,frame,frameset,object,embed,applet,style,link,meta,base," +
    "form,input,button,textarea,select,option,svg,math,template";

  var IFRAME_HOSTS = [
    "www.youtube.com",
    "player.vimeo.com",
    "www.google.com",
    "www.facebook.com"
  ];

  function isAllowedIframe(el) {
    var m = /^(?:https:)?\/\/([^\/?#:]+)[\/?#]/i.exec(
      (el.getAttribute("src") || "").trim()
    );
    return Boolean(m && IFRAME_HOSTS.indexOf(m[1].toLowerCase()) !== -1);
  }

  function removeUntrusted(root) {
    var removed = false;
    if (!root || !root.querySelectorAll) {
      return false;
    }
    root.querySelectorAll(DENY_TAGS).forEach(function (el) {
      el.remove();
      removed = true;
    });
    root.querySelectorAll("iframe").forEach(function (el) {
      if (!isAllowedIframe(el)) {
        el.remove();
        removed = true;
      }
    });
    return removed;
  }

  function preSanitize(html) {
    var tpl = document.createElement("template");
    tpl.innerHTML = html;
    removeUntrusted(tpl.content);
    return tpl.innerHTML;
  }

  function isPickedImage(url, mime) {
    if (mime && /^image\//i.test(String(mime))) {
      return true;
    }
    var path = String(url).split("#")[0].split("?")[0];
    return IMAGE_EXT.test(path);
  }

  function placeInEditor(editor, node) {
    try {
      if (editor.s.hasMarkers) {
        editor.s.restore();
      } else if (!editor.s.isFocused()) {
        editor.s.focus();
      }
      if (node.tagName === "IMG") {
        editor.s.insertImage(node);
      } else {
        editor.s.insertNode(node);
      }
    } catch (err) {
      editor.editor.appendChild(node);
    }
    if (!editor.editor.contains(node)) {
      editor.editor.appendChild(node);
    }
    return editor.editor.contains(node);
  }

  window.editorElfinderPick = function (editorId, url, mime) {
    var editor = editors[editorId];
    if (!editor || editor.isDestructed || !url) {
      return false;
    }
    var placed = false;
    if (isPickedImage(url, mime)) {
      var img = editor.createInside.element("img");
      img.setAttribute("src", url);
      img.setAttribute("alt", "");
      placed = placeInEditor(editor, img);
    } else if (!editor.s.isCollapsed()) {
      if (editor.s.hasMarkers) {
        editor.s.restore();
      }
      var range = editor.s.range;
      var link = editor.createInside.element("a", { href: url });
      link.appendChild(range.extractContents());
      range.insertNode(link);
      editor.s.select(link);
      placed = editor.editor.contains(link);
    } else {
      var name = decodeURIComponent(url.split("/").pop().split("?")[0]) || url;
      var a = editor.createInside.element("a", { href: url });
      a.textContent = name;
      placed = placeInEditor(editor, a);
    }
    if (!placed) {
      return false;
    }
    editor.updateElementValue();
    return true;
  };

  if (typeof BroadcastChannel === "function") {
    var elfinderChannel = new BroadcastChannel("brick6-elfinder");
    elfinderChannel.onmessage = function (ev) {
      var data = ev.data || {};
      var editorId = elfinderTokens[data.token];
      if (!editorId || editorId !== data.editorId || !data.url) {
        return;
      }
      delete elfinderTokens[data.token];
      window.editorElfinderPick(editorId, data.url, data.mime || "");
    };
  }

  var elfinderButton = {
    name: "elfinder",
    icon: "folder",
    tooltip: "瀏覽伺服器",
    exec: function (editor) {
      editor.s.save();
      Object.keys(elfinderTokens).forEach(function (key) {
        if (elfinderTokens[key] === editor.elfinderId) {
          delete elfinderTokens[key];
        }
      });
      var token = Math.random().toString(36).slice(2) + Date.now().toString(36);
      elfinderTokens[token] = editor.elfinderId;
      window.open(
        ELFINDER_URL +
          "?editor=" + encodeURIComponent(editor.elfinderId) +
          "&token=" + encodeURIComponent(token),
        "elfinder",
        "width=1000,height=640,resizable=yes,scrollbars=yes"
      );
    }
  };

  var videoButton = {
    name: "video",
    icon: "video",
    tooltip: "插入影片（YouTube／Vimeo 網址）",
    exec: function (editor) {
      editor.s.save();
      editor.prompt(
        "貼上 YouTube 或 Vimeo 的影片網址",
        "插入影片",
        function (url) {
          var code = J.modules.Helpers.convertMediaUrlToVideoEmbed(
            (url || "").trim()
          );
          if (!/^<iframe /.test(code)) {
            editor.alert("只支援 YouTube 或 Vimeo 的影片網址");
            return false;
          }
          editor.s.restore();
          editor.s.insertHTML(preSanitize(code));
          editor.updateElementValue();
        },
        "https://www.youtube.com/watch?v=…"
      );
    }
  };

  var STYLE_DEFINITIONS = window.JODIT_STYLES || [];
  var BLOCK_TAGS = {
    p: 1, div: 1, h1: 1, h2: 1, h3: 1, h4: 1, h5: 1, h6: 1,
    pre: 1, address: 1, blockquote: 1
  };
  var OBJECT_TAGS = { table: 1, ul: 1, ol: 1, img: 1 };

  function styleProp(name) {
    return String(name).replace(/[A-Z]/g, function (letter) {
      return "-" + letter.toLowerCase();
    });
  }

  function classListOf(def) {
    var attrs = def.attributes || {};
    return String(attrs["class"] || "").split(/\s+/).filter(Boolean);
  }

  function styleMatches(el, def) {
    if (!el || el.tagName.toLowerCase() !== String(def.element || "").toLowerCase()) {
      return false;
    }
    var classes = classListOf(def);
    for (var i = 0; i < classes.length; i++) {
      if (!el.classList.contains(classes[i])) {
        return false;
      }
    }
    var attrs = def.attributes || {};
    var attrNames = Object.keys(attrs);
    for (var a = 0; a < attrNames.length; a++) {
      if (attrNames[a] === "class") {
        continue;
      }
      if (el.getAttribute(attrNames[a]) !== String(attrs[attrNames[a]])) {
        return false;
      }
    }
    var styles = def.styles || {};
    var styleNames = Object.keys(styles);
    for (var s = 0; s < styleNames.length; s++) {
      var prop = styleProp(styleNames[s]);
      var expected = String(styles[styleNames[s]]);
      var actual = el.style.getPropertyValue(prop);
      if (actual === expected) {
        continue;
      }
      var probe = el.ownerDocument.createElement("span");
      probe.style.setProperty(prop, expected);
      if (actual !== probe.style.getPropertyValue(prop)) {
        return false;
      }
    }
    return true;
  }

  function findStyled(editor, def) {
    var node = editor.s.current();
    if (!node) {
      return null;
    }
    var el = Dom.closest(node, String(def.element).toLowerCase(), editor.editor);
    return el && styleMatches(el, def) ? el : null;
  }

  function paintStyle(el, def) {
    classListOf(def).forEach(function (cls) {
      el.classList.add(cls);
    });
    var attrs = def.attributes || {};
    Object.keys(attrs).forEach(function (key) {
      if (key !== "class") {
        el.setAttribute(key, attrs[key]);
      }
    });
    var styles = def.styles || {};
    Object.keys(styles).forEach(function (key) {
      el.style.setProperty(styleProp(key), styles[key]);
    });
  }

  function clearStyle(el, def) {
    classListOf(def).forEach(function (cls) {
      el.classList.remove(cls);
    });
    if (!el.className) {
      el.removeAttribute("class");
    }
    var attrs = def.attributes || {};
    Object.keys(attrs).forEach(function (key) {
      if (key !== "class" && el.getAttribute(key) === String(attrs[key])) {
        el.removeAttribute(key);
      }
    });
    var styles = def.styles || {};
    Object.keys(styles).forEach(function (key) {
      el.style.removeProperty(styleProp(key));
    });
    if (!el.getAttribute("style")) {
      el.removeAttribute("style");
    }
  }

  function unwrapElement(el) {
    var parent = el.parentNode;
    if (!parent) {
      return;
    }
    while (el.firstChild) {
      parent.insertBefore(el.firstChild, el);
    }
    parent.removeChild(el);
  }

  function replaceTag(editor, el, tag) {
    if (!el || el.tagName.toLowerCase() === tag) {
      return el;
    }
    var next = editor.createInside.element(tag);
    while (el.firstChild) {
      next.appendChild(el.firstChild);
    }
    el.parentNode.replaceChild(next, el);
    return next;
  }

  function currentBlock(editor) {
    return Dom.closest(editor.s.current(), function (node) {
      return node && node.nodeType === 1 && BLOCK_TAGS[node.tagName.toLowerCase()];
    }, editor.editor);
  }

  function toggleStyle(editor, def) {
    var tag = String(def.element || "").toLowerCase();
    var existing = findStyled(editor, def);
    if (existing) {
      clearStyle(existing, def);
      if (!BLOCK_TAGS[tag] && !OBJECT_TAGS[tag] && !existing.attributes.length) {
        unwrapElement(existing);
      }
      return;
    }

    if (OBJECT_TAGS[tag]) {
      var objectEl = Dom.closest(editor.s.current(), tag, editor.editor);
      if (!objectEl) {
        editor.alert(tag === "img" ? "請先選取圖片" : tag === "table" ? "請先把游標放在表格上" : "請先把游標放在清單上");
        return;
      }
      paintStyle(objectEl, def);
      return;
    }

    if (BLOCK_TAGS[tag]) {
      var block = currentBlock(editor);
      if (!block) {
        block = editor.createInside.element(tag);
        block.appendChild(editor.createInside.text("\u00a0"));
        editor.s.insertNode(block);
      } else {
        block = replaceTag(editor, block, tag);
      }
      paintStyle(block, def);
      editor.s.setCursorIn(block);
      return;
    }

    var inline = editor.createInside.element(tag);
    paintStyle(inline, def);
    if (editor.s.isCollapsed()) {
      inline.appendChild(editor.createInside.text("\u00a0"));
      editor.s.insertNode(inline);
      editor.s.setCursorIn(inline);
    } else {
      var range = editor.s.range;
      inline.appendChild(range.extractContents());
      range.insertNode(inline);
      editor.s.select(inline);
    }
  }

  var stylesButton = STYLE_DEFINITIONS.length
    ? {
        name: "styles",
        text: "樣式",
        tooltip: "樣式（項目定義在 manage/jodit/styles.js）",
        list: STYLE_DEFINITIONS.reduce(function (list, def, index) {
          list[index] = def.name;
          return list;
        }, {}),
        isChildActive: function (editor, button) {
          var def = STYLE_DEFINITIONS[button.control.args && button.control.args[0]];
          return Boolean(def && findStyled(editor, def));
        },
        exec: function (editor, current, options) {
          var args = options.control.args;
          var def = args && STYLE_DEFINITIONS[args[0]];
          if (!def) {
            return false;
          }
          toggleStyle(editor, def);
          editor.updateElementValue();
        }
      }
    : null;

  var SITE_ROOT = basePath + "../../";
  var BODY_CLASS = "tx01";
  var IFRAME_CSS = [
    "css/reset.css",
    "css/bs-iconSite.css",
    "css/bs-site.css",
    "css/style.css",
    "css/fonts.css"
  ].map(function (path) {
    return SITE_ROOT + path + "?ver=" + encodeURIComponent(CSS_VER);
  });

  var config = {
    language: "zh_tw",
    iframe: true,
    // 必須含 allow-scripts，否則 Chrome 會擋住 iframe 的 document.write，編輯器無法建立。
    iframeSandbox: "allow-same-origin allow-scripts",
    iframeCSSLinks: IFRAME_CSS,
    editorClassName: BODY_CLASS,
    iframeStyle:
      "html{margin:0;padding:0;min-height:100%;}" +
      "html,body{background:#fff !important;}" +
      "body{display:block !important;box-sizing:border-box;padding:1rem !important;margin:0;position:relative;z-index:2;user-select:auto;overflow:auto;outline:none;}" +
      "th,td{-webkit-user-select:text;user-select:text;}" +
      ".jodit_disabled{-webkit-user-select:none;user-select:none;}" +
      (window.JODIT_STYLE_CSS || ""),
    minHeight: 400,
    height: "auto",
    toolbarAdaptive: false,
    toolbarSticky: false,
    buttons: [
      "source",
      "|",
      "undo",
      "redo",
      "|",
      "paragraph",
      stylesButton,
      "|",
      "font",
      "fontsize",
      "brush",
      "|",
      "bold",
      "italic",
      "underline",
      "strikethrough",
      "subscript",
      "superscript",
      "eraser",
      "\n",
      "link",
      elfinderButton,
      "image",
      "table",
      videoButton,
      "hr",
      "symbols",
      "|",
      "align",
      "ul",
      "ol",
      "outdent",
      "indent",
      "|",
      "fullsize"
    ].filter(Boolean),
    disablePlugins: [
      "about",
      "ai-assistant",
      "speech-recognize",
      "powered-by-jodit",
      "print",
      "preview"
    ],
    controls: {
      paragraph: {
        list: J.atom({
          p: "段落",
          h2: "標題 2",
          h3: "標題 3",
          blockquote: "引用",
          pre: "預先格式化文字"
        })
      },
      font: {
        list: J.atom({
          "": "預設",
          新細明體: "新細明體",
          標楷體: "標楷體",
          微軟正黑體: "微軟正黑體",
          "Arial, Helvetica, sans-serif": "Arial",
          "'Courier New', Courier, monospace": "Courier New",
          "Georgia, serif": "Georgia",
          "Verdana, Geneva, sans-serif": "Verdana",
          "Tahoma, Geneva, sans-serif": "Tahoma",
          "'Times New Roman', Times, serif": "Times New Roman"
        })
      },
      fontsize: {
        list: J.atom([12, 14, 16, 18, 20, 24, 28, 32])
      }
    },
    defaultFontSizePoints: "px",
    link: {
      openInNewTabCheckbox: true,
      noFollowCheckbox: false
    },
    sourceEditor: "area",
    beautifyHTML: false,
    sourceEditorCDNUrlsJS: [],
    beautifyHTMLCDNUrlsJS: [],
    enableDragAndDropFileToEditor: false,
    uploader: { insertImageAsBase64URI: false },
    askBeforePasteHTML: false,
    askBeforePasteFromWord: false,
    defaultActionOnPaste: "insert_as_html",
    defaultActionOnPasteFromWord: "insert_clear_html",
    events: {
      beforePasteInsert: function (html) {
        if (typeof html === "string") {
          return preSanitize(html);
        }
        if (html && html.nodeType) {
          var box = document.implementation.createHTMLDocument("").body;
          box.appendChild(box.ownerDocument.importNode(html, true));
          return preSanitize(box.innerHTML);
        }
      }
    },
    cleanHTML: {
      replaceOldTags: false,
      removeEmptyElements: false,
      replaceNBSP: false,
      fillEmptyParagraph: false,
      denyTags: DENY_TAGS,
      sanitizer: preSanitize,
      removeEventAttributes: true,
      safeJavaScriptLink: true,
      safeLinksTarget: true,
      sandboxIframesInContent: false
    }
  };

  function flushEditors() {
    Object.keys(editors).forEach(function (id) {
      var editor = editors[id];
      if (editor && !editor.isDestructed) {
        editor.updateElementValue();
      }
    });
  }

  function resizeVisibleEditors() {
    Object.keys(editors).forEach(function (id) {
      var editor = editors[id];
      if (!editor || editor.isDestructed || !editor.container) {
        return;
      }
      if (editor.container.offsetParent !== null) {
        editor.events.fire("resize");
      }
    });
  }

  function attachEditor(el, editor) {
    el.dataset.joditReady = "1";
    editor.elfinderId = "e" + ++seq;
    editors[editor.elfinderId] = editor;
    editor.events.on("keyup input paste afterExec", function () {
      editor.updateElementValue();
    });
    editor.events.on("change afterInsertNode", function () {
      if (removeUntrusted(editor.editor)) {
        editor.updateElementValue();
      }
    });
    if (!el.id) {
      return editor;
    }
    instances[el.id] = {
      getData: function () {
        return editor.value;
      },
      setData: function (html) {
        editor.value = html == null ? "" : String(html);
      },
      updateElement: function () {
        editor.updateElementValue();
      },
      resize: function () {
        editor.events.fire("resize");
      },
      destroy: function () {
        if (!editor.isDestructed) {
          editor.destruct();
        }
        delete instances[el.id];
        delete editors[editor.elfinderId];
        delete el.dataset.joditReady;
      },
      insertHtml: function (html) {
        editor.s.insertHTML(html);
      },
      mode: "wysiwyg"
    };
    return instances[el.id];
  }

  function initElement(el) {
    if (!el || el.dataset.joditReady === "1") {
      return el && el.id ? instances[el.id] || null : null;
    }
    return attachEditor(el, J.make(el, config));
  }

  window.CKEDITOR = {
    instances: instances,
    replace: function (target) {
      var el = typeof target === "string" ? document.getElementById(target) : target;
      if (!el) {
        return null;
      }
      try {
        return initElement(el);
      } catch (err) {
        console.error("Jodit 初始化失敗：", el.name || el.id, err);
        return null;
      }
    }
  };

  function initAll() {
    document.querySelectorAll("textarea.ckeditor").forEach(function (el) {
      try {
        initElement(el);
      } catch (err) {
        console.error("Jodit 初始化失敗：", el.name || el.id, err);
      }
    });

    if (!window.__joditFormFlush) {
      window.__joditFormFlush = true;
      document.addEventListener("submit", flushEditors, true);
    }

    if (typeof window.view_ans === "function" && !window.view_ans.__joditWrapped) {
      var origView = window.view_ans;
      window.view_ans = function (num) {
        var result = origView.apply(this, arguments);
        window.setTimeout(resizeVisibleEditors, 0);
        return result;
      };
      window.view_ans.__joditWrapped = true;
    }

    if (typeof window.is_show === "function" && !window.is_show.__joditWrapped) {
      var origShow = window.is_show;
      window.is_show = function (type) {
        var result = origShow.apply(this, arguments);
        window.setTimeout(resizeVisibleEditors, 0);
        return result;
      };
      window.is_show.__joditWrapped = true;
    }

    if (typeof window.sbForm === "function" && !window.sbForm.__joditWrapped) {
      var origSubmit = window.sbForm;
      window.sbForm = function () {
        flushEditors();
        return origSubmit.apply(this, arguments);
      };
      window.sbForm.__joditWrapped = true;
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();
