# brick6 新進開發人員 — 第一週上手指南

> 本文件協助第一次接觸 brick6 的 PHP 開發人員，快速理解前後端架構、維運方式，以及局部功能修改（欄位增減、模組複製）。
>
> **實戰 SQL 與程式 patch** 已納入版控，可直接在 dev 環境執行 Day 6 / Day 7 練習。

---

## 目錄

1. [專案概觀](#1-專案概觀)
2. [目錄結構](#2-目錄結構)
3. [請求流程](#3-請求流程)
4. [資料庫與模組模式](#4-資料庫與模組模式)
5. [建議學習路徑 Day 1～5](#5-建議學習路徑-day-15)
6. [Day 6 實戰：FAQ 新增 strNote 欄位](#6-day-6-實戰faq-新增-strnote-欄位)
7. [Day 7 實戰：複製 FAQ 為 faqdemo 模組](#7-day-7-實戰複製-faq-為-faqdemo-模組)
8. [開發規範與維運](#8-開發規範與維運)
9. [附錄：模組對照表](#9-附錄模組對照表)

---

## 1. 專案概觀

brick6 是 **PHP 8.4 單體式 CMS**，採「根目錄 PHP 頁面 + 共用 include + 模組化 CRUD」架構。

| 區塊 | 位置 | 說明 |
|------|------|------|
| 前台 | 根目錄 `*.php` | 訪客網站 |
| 後台 | `manage/{module}/` | 內容 CRUD |
| 共用 | `include/` | DB、CRUD、前台 helper |
| 設定 | `.env` | 帳密、API（**不可提交 Git**） |
| 路由 | `.htaccess` | `news.htm` → `news.php` |

**核心原則**：每模組的 schema 以 `manage/{module}/_config.php` 為唯一來源；前台 `require` 合併同一份設定。

---

## 2. 目錄結構

```
brick6/
├── _inc.php                 ← 前台 bootstrap
├── news.php / faq.php       ← 前台模組頁
├── manage/
│   ├── _inc.php             ← 後台 bootstrap
│   ├── faq/                 ← CRUD 四件套 + _config.php
│   └── faqdemo/             ← Day 7 練習模組
├── include/
│   ├── crud_helpers.php     ← 後台 CRUD 核心
│   └── frontend_helpers.php ← 前台查詢／SEO
├── sql/onboarding/          ← Day 6/7 練習 SQL
├── docs/                    ← 本文件
└── Upload/                  ← 上傳（不進 Git）
```

### 後台模組標準檔案

| 檔案 | 職責 |
|------|------|
| `_config.php` | 表名、FK、CSRF、上傳槽位 |
| `_form_data.php` | 表單預設值、DB 載入 |
| `_detail.php` | 表單 HTML |
| `list.php` | 列表 |
| `add.php` / `update.php` | 新增／編輯入口 |
| `addin.php` | POST 寫入 |

---

## 3. 請求流程

### 前台

```
Browser → .htaccess → faq.php → _inc.php → frontend_module_set_config
       → frontend_fetch_* → _header + 內容 + _footer
```

### 後台 CRUD

```
list.php → add.php/update.php → _detail.php（表單）
         → addin.php（驗證、寫 DB）→ redirect list.php
```

---

## 4. 資料庫與模組模式

### 連線

- `include/host.php` 載入 `.env`
- `sql_conn()`（`include/Conn.php`）建立 PDO
- 查詢一律 **Prepared Statements**

### 典型表結構（以 faq 為例）

```
faq           ← 主檔
├── faq_lang  ← 語系（strName、strNote…）
├── faq_msg   ← CKEditor 內文
└── faq_img   ← 列表圖
view_faq      ← 前台查詢用 View
```

### `_config.php` 範本

參考 `manage/_detail_config.sample.php` 或 `manage/faq/_config.php`。

---

## 5. 建議學習路徑 Day 1～5

每日預估 **3～4 小時**。詳細任務說明見本文件各節或團隊 Word 版。

| 天 | 目標 | 關鍵檔案 |
|----|------|----------|
| Day 1 | Bootstrap 與全域變數 | `_inc.php`、`manage/_inc.php`、`include/Function.php` |
| Day 2 | 後台 CRUD 全流程 | `manage/faq/list.php`～`addin.php` |
| Day 3 | 前台對接後台 | `faq.php`、`.htaccess` |
| Day 4 | CRUD 共用函式 | `include/crud_helpers.php` |
| Day 5 | 前台 helper 與小改動 | `include/frontend_helpers.php`、`css/style.css` |

---

## 6. Day 6 實戰：FAQ 新增 strNote 欄位

### 6.1 目標

在 FAQ 語系子表新增 **備註 `strNote`**，完成後台表單 → 寫入 → 前台顯示。

### 6.2 執行步驟（dev）

#### Step 1 — 執行 SQL

```bash
mysql -u USER -p DB_NAME < sql/onboarding/day6_faq_add_strnote.sql
```

或在 phpMyAdmin 執行 `sql/onboarding/day6_faq_add_strnote.sql`。

#### Step 2 — 確認程式 patch（已含於版控）

| 檔案 | 變更 |
|------|------|
| `include/crud_helpers.php` | `strNote` 語系欄位讀寫（`crud_load_lang_slots_data` / `crud_save_lang_slots`） |
| `manage/faq/_form_data.php` | 預設值與載入 `strNote` |
| `manage/faq/_detail.php` | 表單欄位 `strNote{n}` |
| `include/frontend_helpers.php` | `frontend_fetch_faq_items()` 回傳 `note` |
| `faq.php` | 答案區上方顯示備註 |
| `css/style.css` | `.faqItem__note` 樣式 |

> `manage/faq/addin.php` 透過 `crud_save_lang_slots()` 自動寫入 `strNote{n}`，無需另改。

#### Step 3 — 驗收

1. 後台 FAQ → 新增一筆，備註填 `[Day6-test]`
2. phpMyAdmin 確認 `faq_lang.strNote` 有值
3. 前台 `faq.htm` 展開該題，答案上方顯示備註
4. 編輯頁可帶出備註

### 6.3 欄位增減 SOP（可套用到其他模組）

1. SQL migration → `sql/*.sql`
2. `_form_data.php`（init + load）
3. `_detail.php`（表單 UI）
4. 若為語系欄位：確認 `crud_save_lang_slots` 支援，或擴充 `crud_helpers.php`
5. 前台頁 + helper 讀取
6. 端到端測試

---

## 7. Day 7 實戰：複製 FAQ 為 faqdemo 模組

### 7.1 目標

複製完整 FAQ 模組為 **faqdemo**（獨立資料表、後台選單、前台 `faqdemo.htm`），與原 faq 資料完全隔離。

### 7.2 執行步驟（dev）

#### Step 1 — 執行 SQL（表 + 選單）

```bash
mysql -u USER -p DB_NAME < sql/onboarding/day7_faqdemo_module.sql
```

此 SQL 會：

- `CREATE TABLE faqdemo*`（LIKE faq）
- 子表 FK 改為 `FAQDemo_PKey`
- 新增 `module_p` / `module_lang`（PageLink = `faqdemo.htm`）

#### Step 2 — 建立 view_faqdemo

**建議**（自動自 `view_faq` 複製）：

```bash
php scripts/onboarding_install_view_faqdemo.php
```

或手動：`SHOW CREATE VIEW view_faq` → 替換表名／FK → 建立 `view_faqdemo`。

#### Step 3 — 確認程式 patch（已含於版控）

| 路徑 | 說明 |
|------|------|
| `manage/faqdemo/` | 完整 CRUD（自 faq 複製，`_config.php` 已改） |
| `faqdemo.php` | 前台列表頁 |
| `js/faqdemo-page.js` | 手風琴互動 |
| `.htaccess` | `faqdemo.htm` / `en/faqdemo.htm` |
| `include/crud_helpers.php` | `view_faqdemo` 白名單 |

#### Step 4 — 驗收

| 測試 | 預期 |
|------|------|
| 後台「FAQ Demo」列表 | 可開啟 |
| 新增一筆 | `Upload/faqdemo_{PKey}/` 可上傳圖 |
| 前台 `faqdemo.htm` | 顯示 faqdemo 資料 |
| 原 `faq.htm` | 不受影響 |
| 刪除 faqdemo 一筆 | 僅刪 faqdemo 表 |

#### Step 5 — 練習結束清理（可選）

執行 `day7_faqdemo_module.sql` 末尾註解區的 DROP / DELETE 語句。

### 7.3 複製模組 Checklist

- [ ] 複製 `manage/{module}/` 並改 `_config.php`
- [ ] SQL 建表 + View + FK 欄位名
- [ ] `module_p` / `module_lang` 選單
- [ ] 前台 `{module}.php` + `.htaccess`
- [ ] `view_*` 白名單（`crud_helpers.php`）
- [ ] 端到端測試

---

## 8. 開發規範與維運

### 必讀

- `.cursorrules` — PDO、jQuery、XSS
- `.cursor/rules/git-version-control.mdc` — 提交規範

### 重點

1. 敏感資訊只讀 `.env`
2. HTML 輸出使用 `e()` / `e_attr()`
3. AJAX 使用 `$.ajax().done().fail()`
4. `sql/*.sql` 提交後各環境**手動執行**
5. 不提交 `.env`、`Upload/`

### 本機 WAMP

1. 複製 `.env.example` → `.env`
2. 前台：`http://localhost/brick6/faq.htm`
3. 後台：`manage/login/index.php`

---

## 9. 附錄：模組對照表

| 後台 | 前台 | 備註 |
|------|------|------|
| `manage/faq/` | `faq.php` | Day 6 練習 |
| `manage/faqdemo/` | `faqdemo.php` | Day 7 練習 |
| `manage/news/` | `news.php` / `news-detail.php` | 標準 CRUD 範本 |
| `manage/product/` | `product.php` | 多圖／Tab |

---

## 變更檔案索引（Day 6 + Day 7 patch）

```
sql/onboarding/day6_faq_add_strnote.sql
sql/onboarding/day7_faqdemo_module.sql
scripts/onboarding_install_view_faqdemo.php
include/crud_helpers.php
include/frontend_helpers.php
manage/faq/_form_data.php
manage/faq/_detail.php
manage/faqdemo/          （整目錄）
faq.php
faqdemo.php
js/faqdemo-page.js
css/style.css
.htaccess
docs/ONBOARDING-WEEK1.md
文件/ONBOARDING-WEEK1.docx
```

---

*文件版本：2026-07-09 · brick6 onboarding week 1*
