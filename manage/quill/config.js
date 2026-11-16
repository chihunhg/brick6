/**
 * 新聞內容用 Quill（本檔為本站自有，升級 quill.js 時不可覆蓋）。
 *
 * 只接管 textarea.quill-editor。工具列對齊 Jodit（manage/jodit/config.js）：
 * 原始碼、復原、段落、樣式、字型、字級、顏色、基本格式、連結、瀏覽伺服器、
 * 圖片、表格、影片、分隔線、符號、對齊、清單、縮排、全螢幕。
 * 樣式項目仍讀 manage/jodit/styles.js 的 JODIT_STYLES。
 *
 * 表單與 AI 仍呼叫 CKEDITOR.instances 的 getData／setData／updateElement。
 */
(function () {
  "use strict";

  var Quill = window.Quill;
  if (!Quill) {
    console.error("Quill 未載入（manage/quill/quill.js）");
    return;
  }

  var basePath = (
    (document.currentScript && document.currentScript.src) ||
    ""
  ).replace(/[^\/]*$/, "");
  var ELFINDER_URL = basePath + "../elFinder/elfinder_cke.html";
  var IMAGE_EXT = /\.(jpe?g|png|gif|webp|svg|bmp)(\?.*)?$/i;
  var IFRAME_HOSTS = [
    "www.youtube.com",
    "www.youtube-nocookie.com",
    "player.vimeo.com"
  ];
  var DENY_TAGS =
    "script,noscript,frame,frameset,object,embed,applet,style,link,meta,base," +
    "form,input,button,textarea,select,option,svg,math,template";
  var BLOCK_TAGS = {
    p: 1, div: 1, h1: 1, h2: 1, h3: 1, h4: 1, h5: 1, h6: 1,
    pre: 1, address: 1, blockquote: 1
  };
  var FONTS = [
    { value: "新細明體", label: "新細明體" },
    { value: "標楷體", label: "標楷體" },
    { value: "微軟正黑體", label: "微軟正黑體" },
    { value: "Arial, Helvetica, sans-serif", label: "Arial" },
    { value: "Courier New, Courier, monospace", label: "Courier New" },
    { value: "Georgia, serif", label: "Georgia" },
    { value: "Verdana, Geneva, sans-serif", label: "Verdana" },
    { value: "Tahoma, Geneva, sans-serif", label: "Tahoma" },
    { value: "Times New Roman, Times, serif", label: "Times New Roman" }
  ];
  var SIZES = [12, 14, 16, 18, 20, 24, 28, 32];
  var SYMBOLS = "©®™•…—–「」『』《》〈〉、。？！€¥£§¶·×÷±≈≠≤≥∞°℃★☆♥→←↑↓✓✗“”‘’".split("");
  var STYLE_DEFS = window.JODIT_STYLES || [];

  var editors = {};
  var byElfinder = {};
  var elfSeq = 0;
  var formatsReady = false;
  var dialogEl = null;

  function classListOf(def) {
    var attrs = def.attributes || {};
    return String(attrs["class"] || "").split(/\s+/).filter(Boolean);
  }

  function normColor(value) {
    var v = String(value || "").replace(/\s+/g, "").toLowerCase();
    var short = /^#([0-9a-f]{3})$/.exec(v);
    if (!short) {
      return v;
    }
    return "#" + short[1].split("").map(function (ch) {
      return ch + ch;
    }).join("");
  }

  function sameValue(got, want) {
    if (want === true) {
      return !!got;
    }
    if (typeof want === "number") {
      return Number(got) === want;
    }
    if (typeof want === "string" && want.charAt(0) === "#") {
      return normColor(got) === normColor(want);
    }
    return String(got == null ? "" : got) === String(want);
  }

  function isAllowedIframe(src) {
    var match = /^(?:https:)?\/\/([^\/?#:]+)[\/?#]/i.exec(String(src || "").trim());
    return Boolean(match && IFRAME_HOSTS.indexOf(match[1].toLowerCase()) !== -1);
  }

  function toEmbedUrl(url) {
    var raw = String(url || "").trim();
    var yt = raw.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/i);
    if (yt) {
      return "https://www.youtube.com/embed/" + yt[1];
    }
    var vimeo = raw.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
    if (vimeo) {
      return "https://player.vimeo.com/video/" + vimeo[1];
    }
    if (isAllowedIframe(raw)) {
      return raw;
    }
    return "";
  }

  function sanitize(html) {
    var tpl = document.createElement("template");
    tpl.innerHTML = String(html || "");
    tpl.content.querySelectorAll(DENY_TAGS).forEach(function (el) {
      el.remove();
    });
    tpl.content.querySelectorAll("iframe").forEach(function (el) {
      if (!isAllowedIframe(el.getAttribute("src") || "")) {
        el.remove();
      }
    });
    tpl.content.querySelectorAll("*").forEach(function (el) {
      Array.from(el.attributes).forEach(function (attr) {
        var name = attr.name.toLowerCase();
        var val = String(attr.value || "");
        if (name.indexOf("on") === 0) {
          el.removeAttribute(attr.name);
        }
        if ((name === "href" || name === "src") && /^\s*javascript:/i.test(val)) {
          el.removeAttribute(attr.name);
        }
      });
    });
    return tpl.innerHTML;
  }

  function applyDef(node, def) {
    var styles = def.styles || {};
    Object.keys(styles).forEach(function (key) {
      node.style.setProperty(key, styles[key]);
    });
    var attrs = def.attributes || {};
    Object.keys(attrs).forEach(function (key) {
      if (key === "class") {
        classListOf(def).forEach(function (cls) {
          node.classList.add(cls);
        });
        return;
      }
      node.setAttribute(key, String(attrs[key]));
    });
  }

  function styleMatches(node, def) {
    if (!node || node.nodeType !== 1) {
      return false;
    }
    if (node.tagName.toLowerCase() !== String(def.element || "").toLowerCase()) {
      return false;
    }
    var classes = classListOf(def);
    for (var i = 0; i < classes.length; i++) {
      if (!node.classList.contains(classes[i])) {
        return false;
      }
    }
    var attrs = def.attributes || {};
    var attrNames = Object.keys(attrs);
    for (var a = 0; a < attrNames.length; a++) {
      if (attrNames[a] === "class") {
        continue;
      }
      if (node.getAttribute(attrNames[a]) !== String(attrs[attrNames[a]])) {
        return false;
      }
    }
    var styles = def.styles || {};
    var styleNames = Object.keys(styles);
    for (var s = 0; s < styleNames.length; s++) {
      var prop = styleNames[s];
      var expected = String(styles[prop]).replace(/\s+/g, "").toLowerCase();
      var actual = (node.style.getPropertyValue(prop) || "").replace(/\s+/g, "").toLowerCase();
      if (normColor(actual) !== normColor(expected) && actual !== expected) {
        return false;
      }
    }
    return true;
  }

  function paintDelta(delta, name, block) {
    (delta.ops || []).forEach(function (op) {
      if (op.insert == null) {
        return;
      }
      if (block) {
        if (typeof op.insert !== "string" || !/^\n+$/.test(op.insert)) {
          return;
        }
      } else if (op.insert === "\n") {
        return;
      }
      op.attributes = op.attributes || {};
      op.attributes[name] = true;
    });
    return delta;
  }

  function registerFormats() {
    if (formatsReady) {
      return;
    }
    formatsReady = true;

    var icons = Quill.import("ui/icons");
    icons.undo = '<svg viewbox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="6 10 4 12 2 10 6 10"/><path class="ql-stroke" d="M8.09,13.91A4.6,4.6,0,0,0,9,14,5,5,0,1,0,4,9"/></svg>';
    icons.redo = '<svg viewbox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="12 10 14 12 16 10 12 10"/><path class="ql-stroke" d="M9.91,13.91A4.6,4.6,0,0,1,9,14a5,5,0,1,1,5-5"/></svg>';
    icons.source = '<svg viewbox="0 0 18 18"><polyline class="ql-stroke" points="5 6 2 9 5 12"/><polyline class="ql-stroke" points="13 6 16 9 13 12"/><line class="ql-stroke" x1="10" x2="8" y1="4" y2="14"/></svg>';
    icons.elfinder = '<svg viewbox="0 0 18 18"><path class="ql-stroke" d="M2 5h5l1 2h8v7H2z"/><path class="ql-stroke" d="M2 5V4h4l1 1"/></svg>';
    icons.hr = '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="3" x2="15" y1="9" y2="9"/></svg>';
    icons.symbols = '<svg viewbox="0 0 18 18"><text x="3" y="14" class="ql-fill" font-size="12">Ω</text></svg>';
    icons.fullsize = '<svg viewbox="0 0 18 18"><polyline class="ql-stroke" points="3 7 3 3 7 3"/><polyline class="ql-stroke" points="11 3 15 3 15 7"/><polyline class="ql-stroke" points="15 11 15 15 11 15"/><polyline class="ql-stroke" points="7 15 3 15 3 11"/></svg>';

    var fontStyle = Quill.import("attributors/style/font");
    fontStyle.whitelist = FONTS.map(function (item) {
      return item.value;
    });
    Quill.register(fontStyle, true);

    var sizeStyle = Quill.import("attributors/style/size");
    sizeStyle.whitelist = SIZES.map(function (n) {
      return n + "px";
    });
    Quill.register(sizeStyle, true);

    Quill.register(Quill.import("attributors/style/align"), true);

    var indent = Quill.import("formats/indent");
    indent.add = function (node, value) {
      var current = this.value(node) || 0;
      var next = 0;
      if (value === "+1" || value === "-1") {
        next = value === "+1" ? current + 1 : current - 1;
      } else {
        next = parseInt(value, 10) || 0;
      }
      if (next <= 0) {
        this.remove(node);
        return true;
      }
      if (next > 8) {
        next = 8;
      }
      node.style.paddingLeft = (next * 3) + "em";
      return true;
    };
    indent.remove = function (node) {
      node.style.paddingLeft = "";
      if (!node.getAttribute("style")) {
        node.removeAttribute("style");
      }
    };
    indent.value = function (node) {
      var match = /^(\d+(?:\.\d+)?)em$/.exec(node.style.paddingLeft || "");
      if (!match) {
        return undefined;
      }
      var level = Math.round(parseFloat(match[1]) / 3);
      return level > 0 ? level : undefined;
    };
    indent.canAdd = function () {
      return true;
    };

    var StyleAttributor = sizeStyle.constructor;
    var listType = new StyleAttributor("listtype", "list-style-type", {
      scope: indent.scope,
      whitelist: ["square"]
    });
    Quill.register(listType, true);

    var BaseImage = Quill.import("formats/image");
    class SiteImage extends BaseImage {
      static formats(domNode) {
        var formats = BaseImage.formats(domNode) || {};
        var extra = (domNode.getAttribute("class") || "").split(/\s+/).filter(function (cls) {
          return cls && cls.indexOf("ql-") !== 0;
        });
        if (extra.length) {
          formats.imgclass = extra.join(" ");
        }
        return formats;
      }

      format(name, value) {
        if (name !== "imgclass") {
          BaseImage.prototype.format.call(this, name, value);
          return;
        }
        var ql = (this.domNode.getAttribute("class") || "").split(/\s+/).filter(function (cls) {
          return cls.indexOf("ql-") === 0;
        });
        var extra = String(value || "").split(/\s+/).filter(Boolean);
        var all = ql.concat(extra);
        if (all.length) {
          this.domNode.setAttribute("class", all.join(" "));
        } else {
          this.domNode.removeAttribute("class");
        }
      }
    }
    SiteImage.blotName = "image";
    SiteImage.tagName = "IMG";
    Quill.register(SiteImage, true);

    var BlockEmbed = Quill.import("blots/block/embed");
    class HrBlot extends BlockEmbed {
      static blotName = "hr";
      static tagName = "HR";

      static value() {
        return true;
      }
    }
    Quill.register(HrBlot, true);

    var Inline = Quill.import("blots/inline");
    var Block = Quill.import("blots/block");
    STYLE_DEFS.forEach(function (def, index) {
      var tag = String(def.element || "span").toLowerCase();
      if (tag === "img" || tag === "table" || tag === "ul") {
        def._kind = tag;
        return;
      }
      if (tag === "h2" && def.styles && def.styles["font-style"] === "italic" && !def.attributes) {
        def._kind = "combo";
        def._combo = { header: 2, italic: true };
        return;
      }
      if (tag === "h3" && def.styles && def.styles.color) {
        def._kind = "combo";
        def._combo = { header: 3, italic: true, color: def.styles.color };
        return;
      }
      if (tag === "code" && !def.attributes && !def.styles) {
        def._kind = "combo";
        def._combo = { code: true };
        return;
      }

      var Parent = BLOCK_TAGS[tag] ? Block : Inline;
      var blotName = "jstyle" + index;
      class StyleBlot extends Parent {}
      StyleBlot.blotName = blotName;
      StyleBlot.tagName = tag.toUpperCase();
      var classes = classListOf(def);
      if (classes.length) {
        StyleBlot.className = classes.join(" ");
      } else if (tag === "span" || tag === "div" || tag === "p") {
        StyleBlot.className = "ql-jstyle-" + index;
      }
      StyleBlot.create = function (value) {
        var node = Parent.create.call(this, value);
        applyDef(node, def);
        return node;
      };
      StyleBlot.formats = function () {
        return true;
      };
      Quill.register(StyleBlot, true);
      def._blot = blotName;
      def._kind = Parent === Block ? "block" : "inline";
      def._index = index;
    });
  }

  function button(name, title, value) {
    var el = document.createElement("button");
    el.type = "button";
    el.className = "ql-" + name;
    el.title = title;
    el.setAttribute("aria-label", title);
    if (value != null) {
      el.value = value;
      el.setAttribute("value", value);
    }
    return el;
  }

  function selectBox(className, title, options) {
    var el = document.createElement("select");
    el.className = className;
    el.title = title;
    el.setAttribute("aria-label", title);
    options.forEach(function (opt) {
      var option = document.createElement("option");
      if (opt.value == null || opt.value === false) {
        option.value = "";
        option.setAttribute("selected", "selected");
      } else {
        option.value = String(opt.value);
      }
      option.textContent = opt.label;
      el.appendChild(option);
    });
    return el;
  }

  function group(nodes) {
    var el = document.createElement("span");
    el.className = "ql-formats";
    nodes.forEach(function (node) {
      el.appendChild(node);
    });
    return el;
  }

  function buildToolbar() {
    var bar = document.createElement("div");
    var paragraph = selectBox("quill-paragraph", "段落", [
      { value: false, label: "段落" },
      { value: "h2", label: "標題 2" },
      { value: "h3", label: "標題 3" },
      { value: "blockquote", label: "引用" },
      { value: "pre", label: "預先格式化文字" }
    ]);
    var styles = selectBox("quill-sitestyle", "樣式", [{ value: false, label: "樣式" }].concat(
      STYLE_DEFS.map(function (def, index) {
        return { value: String(index), label: def.name };
      })
    ));
    var font = selectBox("ql-font", "字型", [{ value: false, label: "字型" }].concat(FONTS));
    var size = selectBox("ql-size", "字級", [{ value: false, label: "字級" }].concat(
      SIZES.map(function (n) {
        return { value: n + "px", label: String(n) };
      })
    ));
    var color = document.createElement("select");
    color.className = "ql-color";
    color.title = "文字顏色";
    var background = document.createElement("select");
    background.className = "ql-background";
    background.title = "背景顏色";
    var align = document.createElement("select");
    align.className = "ql-align";
    align.title = "對齊";

    [
      group([button("source", "原始碼")]),
      group([button("undo", "復原"), button("redo", "重做")]),
      group([paragraph]),
      group([styles]),
      group([font]),
      group([size]),
      group([color, background]),
      group([
        button("bold", "粗體"),
        button("italic", "斜體"),
        button("underline", "底線"),
        button("strike", "刪除線")
      ]),
      group([button("script", "下標", "sub"), button("script", "上標", "super")]),
      group([button("clean", "清除格式")])
    ].forEach(function (node) {
      bar.appendChild(node);
    });

    var breakLine = document.createElement("span");
    breakLine.className = "quill-toolbar-break";
    bar.appendChild(breakLine);

    [
      group([
        button("link", "連結"),
        button("elfinder", "瀏覽伺服器"),
        button("image", "圖片"),
        button("table", "表格"),
        button("video", "影片"),
        button("hr", "分隔線"),
        button("symbols", "符號")
      ]),
      group([align]),
      group([button("list", "項目符號", "bullet"), button("list", "編號清單", "ordered")]),
      group([button("indent", "減少縮排", "-1"), button("indent", "增加縮排", "+1")]),
      group([button("fullsize", "全螢幕")])
    ].forEach(function (node) {
      bar.appendChild(node);
    });

    bar._paragraph = paragraph;
    bar._styles = styles;
    return bar;
  }

  function closeDialog() {
    if (dialogEl) {
      dialogEl.remove();
      dialogEl = null;
    }
  }

  function openForm(title, fields, onOk) {
    closeDialog();
    var root = document.createElement("div");
    root.className = "quill-dialog";
    var panel = document.createElement("div");
    panel.className = "quill-dialog__panel";
    panel.setAttribute("role", "dialog");
    panel.setAttribute("aria-modal", "true");
    var heading = document.createElement("h3");
    heading.className = "quill-dialog__title";
    heading.textContent = title;
    panel.appendChild(heading);
    var inputs = {};
    fields.forEach(function (field) {
      var row = document.createElement("div");
      row.className = "quill-dialog__row";
      var label = document.createElement("label");
      var input = document.createElement("input");
      var id = "quillDlg_" + field.name;
      label.htmlFor = id;
      label.textContent = field.label;
      input.id = id;
      input.name = field.name;
      input.type = field.type || "text";
      input.value = field.value == null ? "" : String(field.value);
      input.className = "formInput";
      row.appendChild(label);
      row.appendChild(input);
      panel.appendChild(row);
      inputs[field.name] = input;
    });
    var actions = document.createElement("div");
    actions.className = "quill-dialog__actions";
    var cancel = document.createElement("button");
    cancel.type = "button";
    cancel.className = "btnStyle btnStyle--outline btnStyle--sm";
    cancel.textContent = "取消";
    var ok = document.createElement("button");
    ok.type = "button";
    ok.className = "btnStyle btnStyle--sm";
    ok.textContent = "確定";
    actions.appendChild(cancel);
    actions.appendChild(ok);
    panel.appendChild(actions);
    root.appendChild(panel);
    root.addEventListener("click", function (event) {
      if (event.target === root) {
        closeDialog();
      }
    });
    cancel.addEventListener("click", closeDialog);
    ok.addEventListener("click", function () {
      var data = {};
      Object.keys(inputs).forEach(function (key) {
        data[key] = inputs[key].value;
      });
      closeDialog();
      onOk(data);
    });
    document.body.appendChild(root);
    dialogEl = root;
    var first = fields[0] ? inputs[fields[0].name] : null;
    if (first) {
      first.focus();
      first.select();
    }
  }

  function openSymbols(quill) {
    closeDialog();
    var root = document.createElement("div");
    root.className = "quill-dialog";
    var panel = document.createElement("div");
    panel.className = "quill-dialog__panel";
    var heading = document.createElement("h3");
    heading.className = "quill-dialog__title";
    heading.textContent = "插入符號";
    var grid = document.createElement("div");
    grid.className = "quill-symbols";
    SYMBOLS.forEach(function (ch) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btnStyle btnStyle--outline btnStyle--sm";
      btn.textContent = ch;
      btn.addEventListener("click", function () {
        var range = quill.getSelection(true);
        var index = range ? range.index : Math.max(0, quill.getLength() - 1);
        quill.insertText(index, ch, "user");
        quill.setSelection(index + ch.length, 0, "user");
        closeDialog();
      });
      grid.appendChild(btn);
    });
    var actions = document.createElement("div");
    actions.className = "quill-dialog__actions";
    var cancel = document.createElement("button");
    cancel.type = "button";
    cancel.className = "btnStyle btnStyle--outline btnStyle--sm";
    cancel.textContent = "關閉";
    cancel.addEventListener("click", closeDialog);
    actions.appendChild(cancel);
    panel.appendChild(heading);
    panel.appendChild(grid);
    panel.appendChild(actions);
    root.appendChild(panel);
    root.addEventListener("click", function (event) {
      if (event.target === root) {
        closeDialog();
      }
    });
    document.body.appendChild(root);
    dialogEl = root;
  }

  function comboOn(quill, spec) {
    var fmt = quill.getFormat();
    return Object.keys(spec).every(function (key) {
      return sameValue(fmt[key], spec[key]);
    });
  }

  function applyCombo(quill, spec) {
    var on = comboOn(quill, spec);
    Object.keys(spec).forEach(function (key) {
      quill.format(key, on ? false : spec[key], "user");
    });
  }

  function clearStyleBlots(quill) {
    var fmt = quill.getFormat();
    STYLE_DEFS.forEach(function (def) {
      if (def._blot && fmt[def._blot]) {
        quill.format(def._blot, false, "user");
      }
      if (def._combo && comboOn(quill, def._combo)) {
        Object.keys(def._combo).forEach(function (key) {
          quill.format(key, false, "user");
        });
      }
    });
  }

  function applyStyle(quill, def) {
    if (!def) {
      clearStyleBlots(quill);
      return;
    }
    if (def._kind === "combo") {
      applyCombo(quill, def._combo);
      return;
    }
    if (def._kind === "img") {
      toggleImageClass(quill, classListOf(def)[0] || "");
      return;
    }
    if (def._kind === "table") {
      toggleTableDef(quill, def);
      return;
    }
    if (def._kind === "ul") {
      var current = quill.getFormat();
      if (current.list !== "bullet" && current.list !== "ordered") {
        quill.format("list", "bullet", "user");
      }
      quill.format("listtype", current.listtype === "square" ? false : "square", "user");
      return;
    }
    if (def._blot) {
      var on = !!quill.getFormat()[def._blot];
      quill.format(def._blot, on ? false : true, "user");
    }
  }

  function findImage(quill) {
    var range = quill.getSelection(true);
    if (!range) {
      return null;
    }
    var indexes = [range.index];
    if (range.index > 0) {
      indexes.push(range.index - 1);
    }
    for (var i = 0; i < indexes.length; i++) {
      var leaf = quill.getLeaf(indexes[i]);
      var node = leaf && leaf[0] && leaf[0].domNode;
      if (node && node.tagName === "IMG") {
        return node;
      }
    }
    return null;
  }

  function toggleImageClass(quill, className) {
    if (!className) {
      return;
    }
    var img = findImage(quill);
    if (!img) {
      window.alert("請先選取圖片");
      return;
    }
    var blot = Quill.find(img);
    if (!blot || typeof blot.format !== "function") {
      return;
    }
    var current = (img.getAttribute("class") || "").split(/\s+/).filter(function (cls) {
      return cls && cls !== "left" && cls !== "right" && cls.indexOf("ql-") !== 0;
    });
    if (!img.classList.contains(className)) {
      current.push(className);
    }
    blot.format("imgclass", current.join(" "));
  }

  function toggleTableDef(quill, def) {
    var tableMod = quill.getModule("table");
    var info = tableMod && tableMod.getTable ? tableMod.getTable() : null;
    var tableBlot = info && info[0];
    if (!tableBlot || !tableBlot.domNode) {
      window.alert("請把游標放在表格裡");
      return;
    }
    var table = tableBlot.domNode;
    if (styleMatches(table, def)) {
      Object.keys(def.styles || {}).forEach(function (key) {
        table.style.removeProperty(key);
      });
      Object.keys(def.attributes || {}).forEach(function (key) {
        if (key !== "class") {
          table.removeAttribute(key);
        }
      });
      if (!table.getAttribute("style")) {
        table.removeAttribute("style");
      }
      return;
    }
    applyDef(table, def);
  }

  function applyParagraph(quill, value) {
    quill.format("header", false, "user");
    quill.format("blockquote", false, "user");
    quill.format("code-block", false, "user");
    if (value === "h2") {
      quill.format("header", 2, "user");
    } else if (value === "h3") {
      quill.format("header", 3, "user");
    } else if (value === "blockquote") {
      quill.format("blockquote", true, "user");
    } else if (value === "pre") {
      quill.format("code-block", true, "user");
    }
  }

  function paragraphValue(quill) {
    var fmt = quill.getFormat();
    if (fmt["code-block"]) {
      return "pre";
    }
    if (fmt.blockquote) {
      return "blockquote";
    }
    if (Number(fmt.header) === 2) {
      return "h2";
    }
    if (Number(fmt.header) === 3) {
      return "h3";
    }
    return "";
  }

  function activeStyleValue(quill) {
    var fmt = quill.getFormat();
    for (var i = 0; i < STYLE_DEFS.length; i++) {
      var def = STYLE_DEFS[i];
      if (def._blot && fmt[def._blot]) {
        return String(i);
      }
      if (def._combo && comboOn(quill, def._combo)) {
        return String(i);
      }
      if (def._kind === "ul" && fmt.listtype === "square") {
        return String(i);
      }
    }
    return "";
  }

  function syncCustomSelect(select, value) {
    var index = 0;
    Array.from(select.options).forEach(function (opt, i) {
      var current = opt.hasAttribute("value") ? opt.value : "";
      if (current === value) {
        index = i;
      }
    });
    select.selectedIndex = index;
    var picker = select.previousElementSibling;
    if (!picker || !picker.classList.contains("ql-picker")) {
      return;
    }
    var item = picker.querySelectorAll(".ql-picker-item")[index];
    if (!item) {
      return;
    }
    picker.querySelectorAll(".ql-selected").forEach(function (node) {
      node.classList.remove("ql-selected");
    });
    item.classList.add("ql-selected");
    var label = picker.querySelector(".ql-picker-label");
    if (!label) {
      return;
    }
    if (item.hasAttribute("data-value")) {
      label.setAttribute("data-value", item.getAttribute("data-value"));
    } else {
      label.removeAttribute("data-value");
    }
    if (item.hasAttribute("data-label")) {
      label.setAttribute("data-label", item.getAttribute("data-label"));
    } else {
      label.removeAttribute("data-label");
    }
    label.classList.toggle("ql-active", index > 0);
  }

  function openElfinder(quill) {
    if (!quill.__elfId) {
      quill.__elfId = "q" + (++elfSeq);
      byElfinder[quill.__elfId] = quill;
    }
    window.open(
      ELFINDER_URL + "?editor=" + encodeURIComponent(quill.__elfId),
      "elfinder",
      "width=1000,height=640,resizable=yes,scrollbars=yes"
    );
  }

  function insertPicked(quill, url) {
    var range = quill.getSelection(true);
    if (!range || !url) {
      return;
    }
    if (IMAGE_EXT.test(url)) {
      quill.insertEmbed(range.index, "image", url, "user");
      quill.setSelection(range.index + 1, 0, "user");
      return;
    }
    if (range.length) {
      quill.format("link", url, "user");
      return;
    }
    var name = decodeURIComponent(url.split("/").pop().split("?")[0]) || url;
    quill.insertText(range.index, name, { link: url }, "user");
    quill.setSelection(range.index + name.length, 0, "user");
  }

  function normalizeLists(root) {
    Array.from(root.querySelectorAll("ol")).forEach(function (ol) {
      var parent = ol.parentNode;
      if (!parent) {
        return;
      }
      var groups = [];
      var current = null;
      Array.from(ol.children).forEach(function (li) {
        if (!li || li.tagName !== "LI") {
          return;
        }
        var kind = li.getAttribute("data-list") || "ordered";
        if (!current || current.kind !== kind) {
          current = { kind: kind, items: [] };
          groups.push(current);
        }
        current.items.push(li);
      });
      var frag = document.createDocumentFragment();
      groups.forEach(function (item) {
        var list = document.createElement(item.kind === "ordered" ? "ol" : "ul");
        item.items.forEach(function (li) {
          li.removeAttribute("data-list");
          li.querySelectorAll(".ql-ui").forEach(function (ui) {
            ui.remove();
          });
          list.appendChild(li);
        });
        frag.appendChild(list);
      });
      parent.replaceChild(frag, ol);
    });
  }

  function exportHtml(quill) {
    var text = quill.getText().replace(/\u00a0/g, " ").trim();
    var hasEmbed = !!quill.root.querySelector("img,iframe,hr,table,video");
    if (!text && !hasEmbed) {
      return "";
    }
    var box = document.createElement("div");
    box.innerHTML = quill.root.innerHTML;
    box.querySelectorAll(".ql-ui, .ql-cursor").forEach(function (node) {
      node.remove();
    });
    box.querySelectorAll(".ql-code-block-container").forEach(function (node) {
      var pre = document.createElement("pre");
      var lines = [];
      node.querySelectorAll(".ql-code-block").forEach(function (line) {
        lines.push(line.textContent.replace(/\uFEFF/g, ""));
      });
      pre.textContent = lines.join("\n");
      node.replaceWith(pre);
    });
    normalizeLists(box);
    box.querySelectorAll("[class]").forEach(function (el) {
      var keep = (el.getAttribute("class") || "").split(/\s+/).filter(function (cls) {
        return cls && cls.indexOf("ql-") !== 0;
      });
      if (keep.length) {
        el.setAttribute("class", keep.join(" "));
      } else {
        el.removeAttribute("class");
      }
    });
    box.querySelectorAll("[contenteditable], [spellcheck]").forEach(function (el) {
      el.removeAttribute("contenteditable");
      el.removeAttribute("spellcheck");
    });
    box.querySelectorAll("[data-row]").forEach(function (el) {
      el.removeAttribute("data-row");
    });
    box.querySelectorAll("table").forEach(function (table) {
      if ((table.style.borderStyle || "") === "hidden") {
        return;
      }
      table.querySelectorAll("td, th").forEach(function (cell) {
        if (!cell.style.border) {
          cell.style.border = "1px solid #000";
        }
      });
    });
    return sanitize(box.innerHTML);
  }

  function restoreChrome(originalHtml, root) {
    var src = document.createElement("div");
    src.innerHTML = sanitize(originalHtml);
    var srcTables = src.querySelectorAll("table");
    var dstTables = root.querySelectorAll("table");
    Array.from(srcTables).forEach(function (table, index) {
      var dst = dstTables[index];
      if (!dst) {
        return;
      }
      ["style", "border", "cellpadding", "cellspacing", "bordercolor", "class"].forEach(function (attr) {
        if (table.hasAttribute(attr)) {
          dst.setAttribute(attr, table.getAttribute(attr));
        }
      });
    });
    var srcImgs = src.querySelectorAll("img");
    var dstImgs = root.querySelectorAll("img");
    Array.from(srcImgs).forEach(function (img, index) {
      var dst = dstImgs[index];
      var cls = img.getAttribute("class");
      if (!dst || !cls) {
        return;
      }
      var blot = Quill.find(dst);
      if (blot && typeof blot.format === "function") {
        blot.format("imgclass", cls);
      }
    });
  }

  function loadHtml(quill, html) {
    var clean = sanitize(html || "");
    quill.setText("", "silent");
    if (clean) {
      quill.clipboard.dangerouslyPasteHTML(clean);
      restoreChrome(clean, quill.root);
    }
  }

  function syncField(item) {
    if (!item || !item.el) {
      return;
    }
    if (item.field.classList.contains("is-source")) {
      item.el.value = sanitize(item.source.value);
      return;
    }
    item.el.value = exportHtml(item.quill);
  }

  function bindMatchers(quill) {
    quill.clipboard.addMatcher("HR", function (node, delta) {
      var Delta = delta.constructor;
      return new Delta().insert({ hr: true });
    });
    quill.clipboard.addMatcher("IFRAME", function (node, delta) {
      var Delta = delta.constructor;
      var src = toEmbedUrl(node.getAttribute("src") || "");
      if (!src) {
        return new Delta();
      }
      return new Delta().insert({ video: src });
    });
    quill.clipboard.addMatcher(Node.ELEMENT_NODE, function (node, delta) {
      var match = /^(\d+(?:\.\d+)?)em$/.exec((node.style && node.style.paddingLeft) || "");
      if (!match) {
        return delta;
      }
      var level = Math.round(parseFloat(match[1]) / 3);
      if (level <= 0) {
        return delta;
      }
      (delta.ops || []).forEach(function (op) {
        if (typeof op.insert === "string" && /^\n+$/.test(op.insert)) {
          op.attributes = op.attributes || {};
          if (op.attributes.indent == null) {
            op.attributes.indent = level;
          }
        }
      });
      return delta;
    });
    quill.clipboard.addMatcher("UL, OL", function (node, delta) {
      var type = ((node.style && node.style.listStyleType) || "").toLowerCase();
      if (type !== "square") {
        return delta;
      }
      (delta.ops || []).forEach(function (op) {
        if (typeof op.insert === "string" && /^\n+$/.test(op.insert)) {
          op.attributes = op.attributes || {};
          op.attributes.listtype = "square";
        }
      });
      return delta;
    });
    STYLE_DEFS.forEach(function (def) {
      if (!def._blot) {
        return;
      }
      quill.clipboard.addMatcher(def.element, function (node, delta) {
        if (!styleMatches(node, def)) {
          return delta;
        }
        return paintDelta(delta, def._blot, def._kind === "block");
      });
    });
  }

  function placeTableCursor(quill) {
    quill.focus();
    if (!quill.getSelection()) {
      quill.setSelection(Math.max(0, quill.getLength() - 1), 0, "silent");
    }
    var range = quill.getSelection();
    if (!range) {
      return;
    }
    var line = quill.getLine(range.index);
    var blot = line && line[0];
    var offset = line ? line[1] : 0;
    if (!blot) {
      return;
    }
    var lineText = quill.getText(range.index - offset, Math.max(0, blot.length() - 1));
    if (lineText.replace(/\u00a0/g, " ").trim() !== "") {
      var end = range.index - offset + blot.length() - 1;
      quill.insertText(end, "\n", "user");
      quill.setSelection(end + 1, 0, "user");
      return;
    }
    if (offset !== 0) {
      quill.setSelection(range.index - offset, 0, "user");
    }
  }

  function toggleSource(item) {
    var on = !item.field.classList.contains("is-source");
    if (on) {
      item.source.value = exportHtml(item.quill);
      item.field.classList.add("is-source");
    } else {
      loadHtml(item.quill, item.source.value);
      item.field.classList.remove("is-source");
    }
    var btn = item.toolbar.querySelector(".ql-source");
    if (btn) {
      btn.classList.toggle("ql-active", on);
    }
    syncField(item);
  }

  function toggleFullsize(item) {
    var on = !item.field.classList.contains("quill-fullsize");
    item.field.classList.toggle("quill-fullsize", on);
    var btn = item.toolbar.querySelector(".ql-fullsize");
    if (btn) {
      btn.classList.toggle("ql-active", on);
    }
    if (on) {
      item.quill.focus();
    }
  }

  function handlersFor(item) {
    return {
      source: function () {
        toggleSource(item);
      },
      undo: function () {
        item.quill.history.undo();
      },
      redo: function () {
        item.quill.history.redo();
      },
      elfinder: function () {
        openElfinder(item.quill);
      },
      link: function (value) {
        var quill = item.quill;
        var range = quill.getSelection(true);
        if (!range) {
          return;
        }
        if (!value) {
          quill.format("link", false, "user");
          return;
        }
        openForm("插入連結", [
          { name: "url", label: "網址", value: "https://" },
          { name: "text", label: "文字", value: range.length ? quill.getText(range) : "" }
        ], function (data) {
          var url = String(data.url || "").trim();
          if (!url) {
            return;
          }
          if (!/^(https?:|mailto:|tel:|\/)/i.test(url)) {
            window.alert("只接受 http、https、站內路徑、mailto 或 tel");
            return;
          }
          quill.focus();
          var current = quill.getSelection(true);
          if (!current) {
            return;
          }
          if (current.length) {
            quill.format("link", url, "user");
            return;
          }
          var text = String(data.text || "").trim() || url;
          quill.insertText(current.index, text, { link: url }, "user");
          quill.setSelection(current.index + text.length, 0, "user");
        });
      },
      image: function () {
        var quill = item.quill;
        openForm("插入圖片", [
          { name: "url", label: "網址", value: "https://" },
          { name: "alt", label: "替代文字", value: "" }
        ], function (data) {
          var url = String(data.url || "").trim();
          if (!url || /^\s*javascript:/i.test(url)) {
            return;
          }
          quill.focus();
          var range = quill.getSelection(true);
          if (!range) {
            return;
          }
          quill.insertEmbed(range.index, "image", url, "user");
          var leaf = quill.getLeaf(range.index);
          var blot = leaf && leaf[0];
          if (blot && data.alt && typeof blot.format === "function") {
            blot.format("alt", data.alt);
          }
          quill.setSelection(range.index + 1, 0, "user");
        });
      },
      table: function () {
        openForm("插入表格", [
          { name: "rows", label: "列數", type: "number", value: "3" },
          { name: "cols", label: "欄數", type: "number", value: "3" }
        ], function (data) {
          var rows = Math.min(20, Math.max(1, parseInt(data.rows, 10) || 1));
          var cols = Math.min(12, Math.max(1, parseInt(data.cols, 10) || 1));
          var quill = item.quill;
          placeTableCursor(quill);
          var table = quill.getModule("table");
          if (table) {
            table.insertTable(rows, cols);
          }
        });
      },
      video: function () {
        openForm("插入影片", [
          { name: "url", label: "網址", value: "https://www.youtube.com/watch?v=" }
        ], function (data) {
          var embed = toEmbedUrl(data.url || "");
          if (!embed) {
            window.alert("只支援 YouTube 或 Vimeo 的影片網址");
            return;
          }
          var quill = item.quill;
          quill.focus();
          var range = quill.getSelection(true);
          if (!range) {
            return;
          }
          quill.insertEmbed(range.index, "video", embed, "user");
          var leaf = quill.getLeaf(range.index);
          var blot = leaf && leaf[0];
          if (blot && typeof blot.format === "function") {
            blot.format("width", "560");
            blot.format("height", "315");
          }
          quill.setSelection(range.index + 1, 0, "user");
        });
      },
      hr: function () {
        var quill = item.quill;
        var range = quill.getSelection(true);
        if (!range) {
          return;
        }
        quill.insertEmbed(range.index, "hr", true, "user");
        quill.setSelection(range.index + 1, 0, "user");
      },
      symbols: function () {
        openSymbols(item.quill);
      },
      fullsize: function () {
        toggleFullsize(item);
      }
    };
  }

  function attachBridge(item) {
    var el = item.el;
    if (!el.id) {
      return;
    }
    window.CKEDITOR = window.CKEDITOR || { instances: {} };
    window.CKEDITOR.instances[el.id] = {
      getData: function () {
        syncField(item);
        return item.el.value;
      },
      setData: function (html) {
        if (item.field.classList.contains("is-source")) {
          item.source.value = String(html || "");
        } else {
          loadHtml(item.quill, html);
        }
        syncField(item);
      },
      updateElement: function () {
        syncField(item);
      },
      resize: function () {},
      destroy: function () {
        closeDialog();
        item.quill.disable();
        delete window.CKEDITOR.instances[el.id];
        delete editors[el.id];
      },
      insertHtml: function (html) {
        var range = item.quill.getSelection(true);
        var index = range ? range.index : Math.max(0, item.quill.getLength() - 1);
        item.quill.clipboard.dangerouslyPasteHTML(index, sanitize(html || ""));
        syncField(item);
      },
      mode: "wysiwyg"
    };
  }

  function initElement(el) {
    if (!el || el.dataset.quillReady === "1") {
      return;
    }
    el.dataset.quillReady = "1";
    var field = document.createElement("div");
    field.className = "quill-field";
    var toolbar = buildToolbar();
    var mount = document.createElement("div");
    var source = document.createElement("textarea");
    source.className = "quill-source";
    source.setAttribute("aria-label", "HTML 原始碼");
    el.parentNode.insertBefore(field, el);
    field.appendChild(toolbar);
    field.appendChild(mount);
    field.appendChild(source);
    field.appendChild(el);

    var item = { el: el, field: field, toolbar: toolbar, source: source, quill: null };
    var quill = new Quill(mount, {
      theme: "snow",
      bounds: document.body,
      modules: {
        history: { userOnly: true },
        table: true,
        uploader: { mimetypes: [], handler: function () {} },
        toolbar: {
          container: toolbar,
          handlers: handlersFor(item)
        }
      }
    });
    item.quill = quill;
    if (el.id) {
      editors[el.id] = item;
    }
    bindMatchers(quill);
    toolbar.querySelectorAll(".ql-font .ql-picker-item").forEach(function (node) {
      var value = node.getAttribute("data-value");
      if (value) {
        node.style.fontFamily = value;
      }
    });
    toolbar.querySelectorAll(".ql-picker").forEach(function (picker) {
      var label = picker.querySelector(".ql-picker-label");
      var name = picker.getAttribute("aria-label") || picker.getAttribute("title");
      if (label && name) {
        label.setAttribute("aria-label", name);
      }
    });
    loadHtml(quill, el.value);
    syncField(item);
    if (el.disabled) {
      quill.enable(false);
      field.classList.add("is-disabled");
    }
    quill.on("text-change", function () {
      if (!field.classList.contains("is-source")) {
        syncField(item);
      }
      syncCustomSelect(toolbar._paragraph, paragraphValue(quill));
      syncCustomSelect(toolbar._styles, activeStyleValue(quill));
    });
    quill.on("selection-change", function () {
      syncCustomSelect(toolbar._paragraph, paragraphValue(quill));
      syncCustomSelect(toolbar._styles, activeStyleValue(quill));
    });
    source.addEventListener("input", function () {
      syncField(item);
    });
    toolbar._paragraph.addEventListener("change", function () {
      var value = toolbar._paragraph.selectedIndex <= 0 ? "" : (toolbar._paragraph.value || "");
      applyParagraph(quill, value);
      syncCustomSelect(toolbar._paragraph, paragraphValue(quill));
    });
    toolbar._styles.addEventListener("change", function () {
      if (toolbar._styles.selectedIndex <= 0) {
        clearStyleBlots(quill);
      } else {
        applyStyle(quill, STYLE_DEFS[parseInt(toolbar._styles.value, 10)]);
      }
      syncCustomSelect(toolbar._styles, activeStyleValue(quill));
    });
    el.addEventListener("focus", function () {
      if (!field.classList.contains("is-source")) {
        quill.focus();
      } else {
        source.focus();
      }
    });
    attachBridge(item);
  }

  function syncEnabled() {
    Object.keys(editors).forEach(function (id) {
      var item = editors[id];
      var off = !!item.el.disabled;
      item.quill.enable(!off);
      item.field.classList.toggle("is-disabled", off);
    });
  }

  function flushEditors() {
    Object.keys(editors).forEach(function (id) {
      syncField(editors[id]);
    });
  }

  function initAll() {
    registerFormats();
    document.querySelectorAll("textarea.quill-editor").forEach(function (el) {
      try {
        initElement(el);
      } catch (err) {
        console.error("Quill 初始化失敗：", el.name || el.id, err);
      }
    });
    syncEnabled();

    if (!window.__quillFormFlush) {
      window.__quillFormFlush = true;
      document.addEventListener("submit", flushEditors, true);
      document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") {
          return;
        }
        if (dialogEl) {
          closeDialog();
          return;
        }
        Object.keys(editors).forEach(function (id) {
          editors[id].field.classList.remove("quill-fullsize");
          var btn = editors[id].toolbar.querySelector(".ql-fullsize");
          if (btn) {
            btn.classList.remove("ql-active");
          }
        });
      });
    }

    if (typeof window.is_show === "function" && !window.is_show.__quillWrapped) {
      var origShow = window.is_show;
      window.is_show = function (type) {
        var result = origShow.apply(this, arguments);
        syncEnabled();
        return result;
      };
      window.is_show.__quillWrapped = true;
    }

    var prevPick = window.editorElfinderPick;
    window.editorElfinderPick = function (editorId, url) {
      var quill = byElfinder[editorId];
      if (quill && url) {
        insertPicked(quill, url);
        return true;
      }
      if (typeof prevPick === "function") {
        return prevPick(editorId, url);
      }
      return false;
    };
  }

  if (document.readyState === "loading" || document.readyState === "interactive") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();
