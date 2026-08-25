# 前台 JSON-LD 使用說明

> 前台結構化資料（schema.org）統一以 PHP 陣列組裝，再經 `json_ld_script_tag()` 輸出。  
> **不要**手寫 `<script type="application/ld+json">`，也**不要**在組 JSON-LD 前對內容做 `e()` / `htmlspecialchars()`。

相關程式：

| 檔案 | 職責 |
|------|------|
| `include/sec.php` | 安全輸出：`json_script()`、`json_ld_script_tag()` |
| `include/frontend_helpers.php` | 各類型 builder（`frontend_*_ldjson()`） |
| `_in_code_head.php` | `<head>` 內實際 `echo` JSON-LD |
| `manage/control/webset.php` | ProfessionalService 全站欄位 |

---

## 1. 輸出函式

定義於 `include/sec.php`。

```php
json_ld_script_tag($data): string
```

- **參數**：PHP 陣列（schema.org 結構）。`null` / 空陣列請自行判斷，不要傳入後再輸出空 script。
- **行為**：
  1. 用 `json_script()` 做 `json_encode`（`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`，並 hex 跳脫 `<`、`&`、`'`、`"`）。
  2. 包成 `<script type="application/ld+json" nonce="...">...</script>`，符合 CSP `script-src`。

```php
echo json_ld_script_tag($ldjson);
```

底層 `json_script()` 僅產生 JSON 字串；前台請一律用 `json_ld_script_tag()`，才能帶 nonce。

---

## 2. 標準流程

```
頁面 PHP
  ├─ frontend_init_breadcrumb()          ← 組 $bread_name / $break_link
  ├─ （列表）frontend_apply_class1_filter()
  ├─ （內頁）frontend_apply_detail_class1_breadcrumb()
  ├─ （內頁）frontend_append_detail_breadcrumb($strName)
  └─ $ldjson = frontend_breadcrumb_ldjson()
        ↓
require('_in_code_head.php')
  ├─ echo json_ld_script_tag($ldjson)     ← BreadcrumbList（頁面提供）
  ├─ Organization / Store                  ← 條件符合才輸出
  ├─ frontend_website_ldjson()             ← 自動
  ├─ frontend_professional_service_ldjson()← 自動
  ├─ frontend_article_ldjson($fb_img)      ← 自動（非產品內頁）
  ├─ frontend_product_ldjson($fb_img)      ← 自動（產品內頁）
  └─ frontend_faqpage_ldjson()             ← 自動（美工頁 FAQ）
```

麵包屑 HTML（`_banner.php`）與 JSON-LD 共用 `$bread_name` / `$break_link`，必須成對維護。

---

## 3. 頁面怎麼寫

### 3.1 列表頁（例：`news.php`）

```php
frontend_init_breadcrumb($Module_Name, $Module_Link);
$Class1_Name = frontend_apply_class1_filter($PDO_Cond, $Cond_Array, $Class1, $class1ItemCount);
$ldjson = frontend_breadcrumb_ldjson();
?>
<head>
<?php require('_in_code_head.php'); ?>
```

參考：`news.php`、`product.php`、`faq.php`、`album.php`。

### 3.2 內頁（例：`news-detail.php`）

```php
frontend_init_breadcrumb($Module_Name, $Module_Link);
frontend_apply_detail_class1_breadcrumb($Class1, $class1ItemCount);
frontend_append_detail_breadcrumb($strName);

$aiSummary = frontend_lang_summary($detailRow);
$ldjson = frontend_breadcrumb_ldjson();
?>
<head>
<?php require('_in_code_head.php'); ?>
```

參考：`news-detail.php`、`product-detail.php`、`paper-detail.php`。

內頁只要 `$PKey`、`$strName`（或 `$seoTitle`）、`$page_link` 等變數齊全，`_in_code_head.php` 會自動輸出 Article 或 Product，不必再 `echo`。

### 3.3 靜態／導覽頁

先組麵包屑陣列，再呼叫 builder（勿手組 BreadcrumbList）：

```php
$bread_name = $bread_name ?? [];
$break_link = $break_link ?? [];
array_push($bread_name, $lang_text['home'][$this_lang] ?? '首頁');
array_push($break_link, $web_root);
array_push($bread_name, $Module_Name);
array_push($break_link, $Module_Link);

$ldjson = frontend_breadcrumb_ldjson();
```

`frontend_breadcrumb_ldjson()` 內部會 `strip_tags()` 麵包屑名稱，因此即使陣列裡已有 `e()` / `e_attr()` 也不會把 HTML entity 寫進 JSON-LD。

---

## 4. Builder 一覽

定義於 `include/frontend_helpers.php`。回傳 `null` 表示本頁不輸出該類型。

| 函式 | `@type` | 誰呼叫 | 何時有資料 |
|------|---------|--------|------------|
| `frontend_breadcrumb_ldjson()` | BreadcrumbList | **頁面設** `$ldjson` | `$bread_name` / `$break_link` 已組好 |
| `frontend_website_ldjson()` | WebSite | `_in_code_head.php` 自動 | `$Web_Name` 非空 |
| `frontend_professional_service_ldjson()` | ProfessionalService | 自動 | `$Web_Name` 非空；其餘欄位有填才附加 |
| `frontend_article_ldjson(?string $imageUrl)` | Article | 自動 | 內頁 `$PKey > 0` 且 headline 非空，且**不是** product 模組 |
| `frontend_product_ldjson(?string $fallbackImageUrl)` | Product | 自動 | 內頁 `$PKey > 0` 且 `frontend_module_config()['master'] === 'product'` |
| `frontend_faqpage_ldjson(?int $modulePKey)` | FAQPage | 自動 | 美工頁（`module_p.intType = 2`）且 `module_qa` 有上架問答 |
| `frontend_ldjson_date_ymd(?string $raw)` | （輔助） | Product Offer | 把日期轉成 `YYYY-MM-DD` |

輔助函式（一般不必直接呼叫）：

- `frontend_article_organization_entity()` — Article 的 author / publisher
- `frontend_article_headline_text()` — headline（優先 SEO Title，最長 120 字）
- `frontend_article_image_urls()` / `frontend_product_image_urls()` — 絕對 URL 圖陣列

---

## 5. `_in_code_head.php` 自動輸出

頁面 `require('_in_code_head.php')` 後會依序輸出（條件符合才 `echo`）：

| 順序 | 來源 | 條件 |
|------|------|------|
| 1 | `$ldjson` | `!empty($ldjson)`，通常是 BreadcrumbList |
| 2 | Organization | `$pageName === 'index'` |
| 3 | WebSite | `frontend_website_ldjson() !== null` |
| 4 | ProfessionalService | 同上 |
| 5 | Article | 非產品內頁且有標題／PKey |
| 6 | Product | 產品內頁 |
| 7 | FAQPage | 美工頁有 QA |
| 8 | Store | `!empty($Web_Address)` |

同一頁可以同時有多個 `<script type="application/ld+json">`，這是預期行為。

---

## 6. 各類型注意事項

### BreadcrumbList

- 必須先組 `$bread_name`、`$break_link`（索引對齊）。
- URL 由 `$web_url + $break_link[$i]` 經 `safe_href()` 組成絕對網址。
- 參考頁：`news.php`、`news-detail.php`。
- 舊頁（`about.php`、`contact.php`、`events.php` 等）仍手組陣列，新頁請改用 `frontend_breadcrumb_ldjson()`。

### Article

自動輸出，頁面需準備：

| 變數 | 用途 |
|------|------|
| `$PKey` | 主檔鍵（必須 `> 0`） |
| `$strName` / `$seoTitle` | headline |
| `$page_link` | `mainEntityOfPage.@id` |
| `$OpenDate` 或 `$strDate` | `datePublished` |
| `$dtUDate` 或 `$detailRow['dtUDate']` | `dateModified`（沒有則沿用 published） |
| `$m_description` / `$aiSummary` | `description` |
| `$fb_img` | 由 `_in_code_head.php` 傳入當圖片 fallback |

產品模組（`master === 'product'`）**不會**輸出 Article，改走 Product。

### Product

自動輸出條件：`frontend_module_config()['master'] === 'product'`。

- `Price > 0`：完整 Offer（`price`、`priceCurrency=TWD`、`itemCondition`、`availability`；有 `EndDate` 則加 `priceValidUntil`）。
- `Price` 為 0 或空：仍輸出 Product + 基本 Offer（僅 `availability`），並加上 brand / manufacturer。
- SKU 來自 `$strNo`；MPN 來自明細 `MPN` 欄（有售價時才輸出）。

後台欄位與 migration 見 `sql/product_price_mpn.sql`。

### FAQPage

- 僅美工頁（`module_p.intType = 2` 且已上架）。
- 資料來源：`module_qa`（`isShow != No`，Question / Answer 皆非空）。
- 可選參數：`frontend_faqpage_ldjson($modulePKey)`；省略則用全域 `$Module_PKey`。

### ProfessionalService / Store

- ProfessionalService 欄位在後台 **網站設定 → ProfessionalService 結構化資料**（`manage/control/webset.php`）。
- 除地址外為全站共用，儲存時同步各語系；地址（`$Web_Address` 等）依語系 tab。
- `$Web_Name` 為空則整包不輸出。
- 有 `$Web_Address` 時，`_in_code_head.php` 另外輸出 Store（與 ProfessionalService 並存）。

---

## 7. 自訂 schema

少數結構（首頁 Organization、有地址時的 Store）直接在 `_in_code_head.php` 組陣列。新頁若要加自訂類型：

```php
$custom_ld = [
    '@context' => 'https://schema.org',
    '@type'    => 'Event',
    'name'     => $strName,
    'url'      => safe_href($web_url . $page_link),
];
echo json_ld_script_tag($custom_ld);
```

規則：

1. 值用原始字串，**不要**先 `e()`。
2. URL 用 `safe_href()` / `frontend_absolute_public_url()`。
3. 輸出只用 `json_ld_script_tag()`。
4. 能放進 `_in_code_head.php` 的全站／模組邏輯，優先寫成 `frontend_*_ldjson()` helper，不要散落各頁。

---

## 8. 禁止事項

| 不要這樣 | 原因 |
|----------|------|
| 手寫 `<script type="application/ld+json">` | 缺 CSP nonce，且跳脫不一致 |
| 組陣列前對欄位 `e()` / `htmlspecialchars()` | JSON 會出現 `&amp;` 等雙重轉義 |
| 用 `json_encode()` 直接 echo | 未 hex 跳脫，有 XSS 風險 |
| 只改 `_banner.php` 麵包屑、不設 `$ldjson` | 畫面與結構化資料不一致 |
| 在產品內頁再手動輸出 Article | `_in_code_head.php` 已依模組分流 |

---

## 9. 驗證

1. 瀏覽器檢視原始碼，確認 `<head>` 內有一或多個 `type="application/ld+json"`。
2. [Google Rich Results Test](https://search.google.com/test/rich-results)
3. [Schema Markup Validator](https://validator.schema.org/)

常見問題：麵包屑 `item` 不是絕對 URL、Article 缺 `headline` / `image`、Product 有價格但 `price` 不是字串數字。

---

## 10. 新頁 Checklist

- [ ] `frontend_init_breadcrumb()`（或等價組好 `$bread_name` / `$break_link`）
- [ ] `$ldjson = frontend_breadcrumb_ldjson();`
- [ ] `require('_in_code_head.php');`（不要在 body 再輸出一份）
- [ ] 內頁已設 `$PKey`、`$strName`、`$page_link`、日期與描述（需要 Article / Product 時）
- [ ] 產品頁已 `frontend_module_set_config()` 且 `master` 為 `product`
- [ ] 美工頁 FAQ 走後台 `module_qa`，不必手組 FAQPage
- [ ] 未對 JSON-LD 欄位做 HTML escape
