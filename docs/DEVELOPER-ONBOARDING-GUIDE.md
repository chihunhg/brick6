# brick6 新進開發人員上手指南

> 本文件協助第一次接觸 brick6 的 PHP 開發人員，快速理解前後端架構、日常維運，以及如何進行局部功能修改（後台欄位增減、複製模組、前台調整）。
>
> **第一週實戰練習**（Day 6 / Day 7 可執行 patch）另見：`docs/ONBOARDING-WEEK1.md`

---

## 目錄

1. [專案概觀](#1-專案概觀)
2. [目錄結構速查](#2-目錄結構速查)
3. [請求流程](#3-請求流程)
4. [資料庫與模組資料表模式](#4-資料庫與模組資料表模式)
5. [局部修改：後台欄位增減](#5-局部修改後台欄位增減)
6. [複製模組](#6-複製模組)
7. [前台架構與修改](#7-前台架構與修改)
8. [開發規範](#8-開發規範)
9. [維運與部署](#9-維運與部署)
10. [實務 Checklist](#10-實務-checklist)
11. [建議學習路徑（第一週）](#11-建議學習路徑第一週)
12. [主要模組對照表](#12-主要模組對照表)

---

## 1. 專案概觀

brick6 是一套 **PHP 8.4 單體式 CMS**，沒有 Laravel/Symfony 等框架，採「**根目錄 PHP 頁面 + 共用 include + 模組化 CRUD**」架構。

| 區塊 | 位置 | 說明 |
|------|------|------|
| 前台 | 根目錄 `*.php` | 訪客看到的網站 |
| 後台 | `manage/{module}/` | 內容管理 CRUD |
| 共用邏輯 | `include/` | DB、安全、CRUD helper、前台 helper |
| 設定 | `.env` | 資料庫、API 金鑰（**不可提交 Git**） |
| URL 路由 | `.htaccess` | 友好 URL（`news.htm` → `news.php`） |

**核心設計原則**：每個內容模組的 **資料表定義以後台 `manage/{module}/_config.php` 為唯一來源**，前台透過 `require` 合併同一份設定，避免前後台 schema 不一致。

---

## 2. 目錄結構速查

```
brick6/
├── _inc.php              ← 前台 bootstrap（所有前台頁必載）
├── _header.php / _footer.php / _banner.php …  ← 前台 partial
├── news.php / faq.php    ← 前台模組頁
├── .htaccess             ← URL rewrite + 安全規則
├── .env / .env.example   ← 環境變數
│
├── include/
│   ├── host.php          ← 載入 .env、URL 路徑
│   ├── Conn.php          ← PDO 連線 sql_conn()
│   ├── crud_helpers.php  ← 後台 CRUD 核心
│   ├── frontend_helpers.php ← 前台查詢/分頁/SEO
│   ├── sec.php           ← XSS 輸出 e() / e_attr()
│   └── Function.php      ← $filter_array（GET/POST 合併）
│
├── manage/
│   ├── _inc.php          ← 後台 bootstrap
│   ├── _module.php       ← 依 manNo/subNo 載入模組上下文
│   ├── _detail_config.sample.php ← 新模組 _config 範本
│   └── {module}/         ← 各 CRUD 模組
│       ├── _config.php   ← ★ 資料表/FK/上傳/CSRF 設定
│       ├── _form_data.php← 表單預設值、DB 載入、驗證
│       ├── _detail.php   ← 表單 HTML
│       ├── list.php      ← 列表
│       ├── add.php / update.php ← 新增/編輯頁
│       └── addin.php     ← POST 寫入處理
│
├── css/  js/  images/    ← 前台靜態資源
├── sql/                  ← DB migration（手動執行）
├── docs/                 ← 開發文件
├── 文件/                 ← Word 版文件
└── Upload/               ← 上傳檔案（不進 Git）
```

### 後台模組固定四件套

| 檔案 | 職責 |
|------|------|
| `list.php` | 列表、搜尋、分頁、批次刪除/排序 |
| `add.php` / `update.php` | 載入表單（共用 `_detail.php`） |
| `_detail.php` | 表單 HTML |
| `addin.php` | 接收 POST、驗證、寫 DB、上傳檔案 |

---

## 3. 請求流程

### 前台頁面載入

```
Browser → .htaccess rewrite → news.php
  → require _inc.php
      → host.php (.env)
      → Conn.php (PDO)
      → frontend_helpers.php
      → 查 view_module_lang 建選單
  → frontend_module_set_config(manage/news/_config + 前台覆寫)
  → frontend_fetch_list 等
  → _header.php + HTML + _footer.php
```

### 後台 CRUD

```
list.php → add.php/update.php → _detail.php（表單）
         → addin.php（驗證、寫 DB）→ redirect list.php
```

### AJAX

- 前台：`frontend-visit-log.php` → `json_out()`
- 後台 AI：`manage/generate_tdk.php`（SSE）
- 模組內：如 `manage/ajax/tag_relation_autocomplete.php`

**專案沒有獨立 `api/` 目錄**，JSON 端點分散在根目錄與 `manage/` 下。

---

## 4. 資料庫與模組資料表模式

### 連線方式

- 環境變數由 `include/host.php` 載入 `.env`（本機根目錄 `.env` 或 `config/env.path.php` 指向正式機路徑）
- 全域 PDO：`sql_conn()`（`include/Conn.php`）
- **所有查詢必須用 Prepared Statements**

### 典型模組表結構（以 news 為例）

```
news          ← 主檔（PKey, Module_PKey, OpenDate, Upload, Sort…）
├── news_lang ← 語系標題/摘要（Title, Description…）
├── news_msg  ← CKEditor 內文區塊
├── news_img  ← 圖片/檔案上傳槽位（Photo1～N）
└── news_link ← 相關連結
view_news     ← 前台查詢用 View（JOIN 語系與常用欄位）
```

### `_config.php` 範本

參考 `manage/_detail_config.sample.php`：

```php
return [
    'master'        => 'news',
    'img'           => 'news_img',
    'lang'          => 'news_lang',
    'msg'           => 'news_msg',
    'link'          => 'news_link',
    'fk'            => 'News_PKey',
    'module_pk_col' => 'Module_PKey',
    'csrf'          => 'news_addin',
    'has_sort'      => false,
    'img_slot_max'  => 7,
    'img_file_from' => 8,
    'forder_prefix' => 'news_',
];
```

### 選模組參考

| 複雜度 | 模組 | 特點 |
|--------|------|------|
| 簡單 | `faq` | 有排序、單一內文區塊、無 link 子表 |
| 標準 | `news` / `paper` / `company` | 完整 master/lang/msg/img/link |
| 子模組 | `album_d` | 依附父模組 album 的圖庫 |
| 系統 | `control` / `webset` | 帳號、網站設定 |

---

## 5. 局部修改：後台欄位增減

以在 `news` 模組新增主檔欄位 `SubTitle` 為例：

### Step 1 — 資料庫

```sql
-- sql/news_add_subtitle.sql
ALTER TABLE news ADD COLUMN SubTitle VARCHAR(255) DEFAULT '' AFTER Title;
```

各環境**手動執行** migration。

### Step 2 — `_form_data.php`

- `class1_detail_init_defaults()`：新增頁預設值
- `class1_detail_load($pkey)`：從 DB 載入到 `$GLOBALS['class1_form_vars']`

### Step 3 — `_detail.php`

加入 Bootstrap 表單欄位，輸出用 `e_attr()`。

### Step 4 — `addin.php`

在寫入主檔的 `$data_array` 加入新欄位。

若欄位在 **語系子表**，走 `crud_save_lang_slots()`；新語系欄位可能需擴充 `include/crud_helpers.php`（參考 `strNote` 實作）。

### Step 5～6 — 列表（可選）與前台

- `list.php` 加顯示欄
- 前台用 `crud_row_val($row, 'SubTitle')` + `e()` 輸出

---

## 6. 複製模組

### 情境 A：複製整個模組（news → events）

1. 複製 `manage/news/` → `manage/events/`
2. 修改 `_config.php`（表名、FK、csrf、forder_prefix）
3. 全域搜尋替換模組名稱
4. 建立 DB 表 + View（`view_events`）
5. 後台選單：`module_p` / `module_d`（PageLink = `events.htm`）
6. 複製 `news.php` → `events.php`、`news-detail.php` → `events-detail.php`
7. 修改 `frontend_module_pkey_for_page('events.htm')`
8. `.htaccess` 加入 RewriteRule
9. `_code_lang.php` 加多語系字串

### 情境 B：複製單筆資料（後台 UI）

列表「複製」→ 帶 `PKey` 到 `add.php`，載入資料但清空 PKey 與圖片，以新增模式送出。

### 情境 C：簡化模組

以 `faq.php` 為模板（無分頁、無 detail 內頁）。

### dev 練習

完整步驟見 `docs/ONBOARDING-WEEK1.md` Day 7（faqdemo 模組）。

---

## 7. 前台架構與修改

### 頁面 Shell 結構

```
_inc.php
├── _in_code_head.php       ← SEO
├── _in_javascript.php      ← CSS/JS
├── _header.php
├── _banner.php             ← Banner + 麵包屑
├── [main 內容區]
├── _footer.php
└── _in_code_bottom.php     ← 全站 JS + 瀏覽記錄
```

### 典型前台邏輯（faq.php）

```php
require('_inc.php');

$Module_PKey = frontend_module_pkey_for_page('faq.htm');
frontend_module_set_config(array_merge(
    require __DIR__ . '/manage/faq/_config.php',
    [
        'view'        => 'view_faq',
        'class_link'  => 'faq',
        'order_by'    => 'Sort ASC',
    ]
));
// 查詢 → HTML 輸出（只用 e() / frontend_render_html）
```

### 前台修改類型

| 需求 | 修改位置 |
|------|----------|
| 顯示後台新欄位 | 列表/內頁 PHP |
| 靜態 UI 文字 | `_code_lang.php` |
| 全站設定 | 後台 webset → `_footer.php` |
| 樣式 | `css/style.css`（Bootstrap 基礎：`css/bs-site.css`） |
| 頁面互動 | `js/{page}-page.js` |
| AJAX | 根目錄端點 + `$.ajax().done().fail()` |

### 常用前台 Helper

| 函式 | 用途 |
|------|------|
| `frontend_module_set_config()` | 註冊模組設定 |
| `frontend_list_where()` | 組列表 WHERE |
| `frontend_fetch_list()` | 通用列表 |
| `frontend_fetch_detail()` | 內頁主檔 |
| `frontend_fetch_msg_contents()` | CKEditor 內文 |
| `frontend_detail_href()` | 內頁友好 URL |
| `e()` / `e_attr()` | XSS 安全輸出 |

---

## 8. 開發規範

詳見 `.cursorrules` 與 `.cursor/rules/git-version-control.mdc`。

1. **環境變數**：禁止寫死帳密，讀 `$_ENV` / `getenv`
2. **SQL**：一律 PDO Prepared Statements
3. **XSS**：HTML 輸出用 `e()` / `htmlspecialchars`
4. **jQuery**：DOM/AJAX 用 `$()`；AJAX 用 `$.ajax().done().fail()`
5. **PHP 結構**：檔案頂部處理邏輯/POST，HTML 區只做讀取與輸出
6. **Git**：不提交 `.env`、`Upload/`

---

## 9. 維運與部署

### 本機 WAMP

1. 複製 `.env.example` → `.env`
2. 前台：`http://localhost/brick6/faq.htm`
3. 後台：`manage/login/index.php`

### 正式/測試機

- `.env` 各環境手動維護
- `sql/*.sql` 提交後需**手動執行**
- 後台/前台選單來源：`module_p`、`module_d`、`view_module_lang`

---

## 10. 實務 Checklist

### 新增後台欄位

- [ ] SQL migration
- [ ] `_form_data.php`
- [ ] `_detail.php`
- [ ] `addin.php`
- [ ] `list.php`（可選）
- [ ] 前台頁（若需顯示）
- [ ] 端到端測試

### 複製新模組

- [ ] 複製 `manage/{module}/` + 改 `_config.php`
- [ ] 建表 + View + FK 欄位名
- [ ] 註冊選單
- [ ] 前台 PHP + `.htaccess`
- [ ] `view_*` 白名單（`crud_helpers.php`）

---

## 11. 建議學習路徑（第一週）

每日預估 **3～4 小時**。Day 6 / Day 7 含可執行 patch，詳見 `docs/ONBOARDING-WEEK1.md`。

### Day 1：理解 Bootstrap

- **必讀**：`.env.example`、`include/host.php`、`Conn.php`、`_inc.php`、`manage/_inc.php`、`Function.php`
- **搞懂**：`$filter_array`、`$this_lang`、`$Array_MU_*`
- **產出**：請求啟動流程圖

### Day 2：後台 CRUD（faq）

- 走一遍 list → add → addin → update → 刪除
- 對照 `_config.php`、`_form_data.php`、`_detail.php`、`addin.php`
- **產出**：FAQ CRUD 流程圖

### Day 3：前台 faq.php

- 三段式：bootstrap → 模組設定 → HTML
- 讀 `.htaccess` 友好 URL
- **產出**：前台 Shell 結構圖

### Day 4：crud_helpers.php

- 掌握 `crud_cfg`、`crud_module_where`、`crud_process_list_actions`、`crud_save_lang_slots`
- **產出**：常用函式速查表

### Day 5：frontend_helpers 小改動

- 改 `order_by`、靜態文字、對照 `news.php`
- **產出**：2 個小改動 + 驗證記錄

### Day 6：實戰 — FAQ 新增 strNote

- 執行 `sql/onboarding/day6_faq_add_strnote.sql`
- 驗收後台表單與前台顯示

### Day 7：實戰 — 複製 faqdemo 模組

- 執行 `sql/onboarding/day7_faqdemo_module.sql`
- 執行 `php scripts/onboarding_install_view_faqdemo.php`
- 驗收 `faqdemo.htm` 與後台 FAQ Demo

---

## 12. 主要模組對照表

| 後台目錄 | 前台頁面 | 備註 |
|----------|----------|------|
| `manage/news/` | `news.php` / `news-detail.php` | 標準 CRUD 範本 |
| `manage/faq/` | `faq.php` | 簡化、無內頁 |
| `manage/faqdemo/` | `faqdemo.php` | Day 7 練習 |
| `manage/product/` | `product.php` / `product-detail.php` | 多圖/多 Tab |
| `manage/album/` + `album_d/` | `album.php` / `album-detail.php` | 含子模組 |
| `manage/webset/` | — | 全站設定 |
| `manage/module/` | — | 選單/模組設定 |

---

## 相關文件

| 文件 | 說明 |
|------|------|
| `docs/DEVELOPER-ONBOARDING-GUIDE.md` | 本文件（完整架構指南） |
| `docs/ONBOARDING-WEEK1.md` | 第一週實戰（Day 6/7 patch） |
| `文件/新進開發人員上手指南.docx` | 本文件 Word 版 |
| `文件/ONBOARDING-WEEK1.docx` | 第一週實戰 Word 版 |

---

*文件版本：2026-07-09 · brick6 新進開發人員上手指南*
