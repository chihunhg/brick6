-- Day 7 實戰：複製 FAQ 為 faqdemo 模組（dev 練習用）
-- 執行前請確認 faq 表與 view_faq 已存在。
-- 執行：mysql -u USER -p DB_NAME < sql/onboarding/day7_faqdemo_module.sql

-- ── 1. 複製資料表結構 ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS faqdemo LIKE faq;
CREATE TABLE IF NOT EXISTS faqdemo_lang LIKE faq_lang;
CREATE TABLE IF NOT EXISTS faqdemo_msg LIKE faq_msg;
CREATE TABLE IF NOT EXISTS faqdemo_img LIKE faq_img;

-- 子表外鍵欄位須改為 FAQDemo_PKey（LIKE 複製後仍為 FAQ_PKey）
ALTER TABLE faqdemo_lang CHANGE COLUMN FAQ_PKey FAQDemo_PKey INT NOT NULL DEFAULT 0;
ALTER TABLE faqdemo_msg CHANGE COLUMN FAQ_PKey FAQDemo_PKey INT NOT NULL DEFAULT 0;
ALTER TABLE faqdemo_img CHANGE COLUMN FAQ_PKey FAQDemo_PKey INT NOT NULL DEFAULT 0;

-- 若 day6 已執行，faq_lang 已有 strNote；LIKE 複製時 faqdemo_lang 也會有。
-- 若僅執行 day7、未執行 day6，可取消下行註解：
-- ALTER TABLE faqdemo_lang ADD COLUMN strNote VARCHAR(255) NOT NULL DEFAULT '' AFTER strName;

-- ── 2. 建立 view_faqdemo ───────────────────────────────────
-- 方式 A（建議）：自 view_faq 複製定義
--   SHOW CREATE VIEW view_faq\G
--   將 faq → faqdemo、FAQ_PKey → FAQDemo_PKey 後執行。
--
-- 方式 B：執行 php scripts/onboarding_install_view_faqdemo.php（自 view_faq 自動替換）
-- 方式 C：若 view_faq 為標準 JOIN 結構，可嘗試下列（與各站 view 若不同請改方式 A/B）：
DROP VIEW IF EXISTS view_faqdemo;
CREATE VIEW view_faqdemo AS
SELECT
    m.PKey,
    m.Module_PKey,
    m.Class1_PKey,
    m.Class2_PKey,
    m.Class3_PKey,
    m.Sort,
    m.Upload,
    m.Home,
    m.OpenDate,
    m.EndDate,
    m.NoOpenDate,
    m.NoEndDate,
    m.strDate,
    m.dtUDate,
    m.UserID,
    m.Keywords,
    m.Description,
    l.intLang,
    l.isShow,
    l.strName,
    l.strNote,
    l.Interview,
    l.Movielink
FROM faqdemo AS m
INNER JOIN faqdemo_lang AS l ON l.FAQDemo_PKey = m.PKey;

-- ── 3. 後台選單（module_p）──────────────────────────────────
-- 請依實際 program、Sort 調整；PKey 請勿與現有衝突。
-- 可先查：SELECT MAX(PKey) FROM module_p;
INSERT INTO module_p (
    strName, PageLink, intLayer, intList, intDetail, intUse, Sort, Upload, dtUDate, UserID
)
SELECT
    'FAQ Demo',
    'faqdemo.htm',
    intLayer,
    intList,
    intDetail,
    intUse,
    (SELECT IFNULL(MAX(Sort), 0) + 1 FROM module_p AS x),
    'Yes',
    NOW(),
    'onboarding'
FROM module_p
WHERE PageLink = 'faq.htm'
LIMIT 1;

-- 若 site 無 faq.htm 選單，改用手動 INSERT（範例）：
-- INSERT INTO module_p (strName, PageLink, intLayer, intList, intDetail, intUse, Sort, Upload, dtUDate, UserID)
-- VALUES ('FAQ Demo', 'faqdemo.htm', 1, 1, 0, 1, 999, 'Yes', NOW(), 'onboarding');

-- module_lang（多語系選單名稱，依 language 表語系數調整）
INSERT INTO module_lang (Module_PKey, intLang, Sort, isShow, strName, dtDate)
SELECT
    mp.PKey,
    1,
    1,
    'Y',
    'FAQ Demo',
    NOW()
FROM module_p AS mp
WHERE mp.PageLink = 'faqdemo.htm'
  AND NOT EXISTS (
      SELECT 1 FROM module_lang AS ml
      WHERE ml.Module_PKey = mp.PKey AND ml.intLang = 1
  )
LIMIT 1;

INSERT INTO module_lang (Module_PKey, intLang, Sort, isShow, strName, dtDate)
SELECT
    mp.PKey,
    2,
    2,
    'Y',
    'FAQ Demo',
    NOW()
FROM module_p AS mp
WHERE mp.PageLink = 'faqdemo.htm'
  AND NOT EXISTS (
      SELECT 1 FROM module_lang AS ml
      WHERE ml.Module_PKey = mp.PKey AND ml.intLang = 2
  )
LIMIT 1;

-- ── 4. 清理（練習結束後可選執行）────────────────────────────
-- DELETE FROM module_lang WHERE Module_PKey IN (SELECT PKey FROM module_p WHERE PageLink = 'faqdemo.htm');
-- DELETE FROM module_p WHERE PageLink = 'faqdemo.htm';
-- DROP VIEW IF EXISTS view_faqdemo;
-- DROP TABLE IF EXISTS faqdemo_img, faqdemo_msg, faqdemo_lang, faqdemo;
