/**
 * 後台「樣式」下拉。項目對應舊 CKEditor styles.js 裡預設會出現的樣式
 * （不含需額外 widget 外掛才看得到的項目，例如 Clean Image、embed 解析度）。
 *
 * 設計師只改這個檔。每一筆：
 *   name        下拉顯示的名稱
 *   element     套到哪個標籤
 *   attributes  選填，HTML 屬性。class 用 "class"
 *   styles      選填，行內樣式。屬性名用 font-style、background-color 這種寫法
 *
 * 段落類（p、div、h1–h6、pre、address、blockquote）套在游標所在的那段，再點一次取消額外樣式。
 * 行內類（span、code、big…）套在選取的文字上，再點一次取消。
 * table、ul、img 要先把游標放在那個元素上。
 *
 * 前台若要看到 class 樣式（例如 marker、圖片靠左），請在前台 CSS 加上對應規則。
 * 編輯器裡的預覽規則在下方 JODIT_STYLE_CSS。
 */
window.JODIT_STYLES = [
  { name: "斜體標題", element: "h2", styles: { "font-style": "italic" } },
  { name: "副標題", element: "h3", styles: { color: "#aaa", "font-style": "italic" } },
  {
    name: "特殊區塊",
    element: "div",
    styles: {
      padding: "5px 10px",
      background: "#eee",
      border: "1px solid #ccc"
    }
  },
  { name: "裝飾", element: "div", attributes: { "class": "decoTt" } },

  { name: "螢光標示", element: "span", attributes: { "class": "marker" } },
  { name: "較大文字", element: "big" },
  { name: "較小文字", element: "small" },
  { name: "打字機", element: "tt" },
  { name: "程式碼", element: "code" },
  { name: "鍵盤字", element: "kbd" },
  { name: "範例文字", element: "samp" },
  { name: "變數", element: "var" },
  { name: "刪除文字", element: "del" },
  { name: "插入文字", element: "ins" },
  { name: "引用作品", element: "cite" },
  { name: "行內引文", element: "q" },
  { name: "由右至左", element: "span", attributes: { dir: "rtl" } },
  { name: "由左至右", element: "span", attributes: { dir: "ltr" } },

  { name: "圖片靠左", element: "img", attributes: { "class": "left" } },
  { name: "圖片靠右", element: "img", attributes: { "class": "right" } },
  {
    name: "緊湊表格",
    element: "table",
    attributes: { cellpadding: "5", cellspacing: "0", border: "1", bordercolor: "#ccc" },
    styles: { "border-collapse": "collapse" }
  },
  {
    name: "無框表格",
    element: "table",
    styles: { "border-style": "hidden", "background-color": "#E6E6FA" }
  },
  { name: "方塊清單", element: "ul", styles: { "list-style-type": "square" } }
];

/** 編輯區預覽。class 樣式若前台也要生效，請把同一段規則加進前台 CSS。 */
window.JODIT_STYLE_CSS =
  ".marker{background-color:#ff0;}" +
  "img.left{border:1px solid #ccc;float:left;margin-right:15px;padding:5px;}" +
  "img.right{border:1px solid #ccc;float:right;margin-left:15px;padding:5px;}";
