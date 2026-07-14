-- 歷史沿革（history）資料表
-- 結構以 faq 為底，主檔新增 intYear（年份，列表排序用）
-- 執行：mysql -u USER -p DB_NAME < sql/history.sql

CREATE TABLE IF NOT EXISTS history LIKE faq;
CREATE TABLE IF NOT EXISTS history_lang LIKE faq_lang;
CREATE TABLE IF NOT EXISTS history_msg LIKE faq_msg;
CREATE TABLE IF NOT EXISTS history_img LIKE faq_img;

-- 子表外鍵改為 History_PKey
ALTER TABLE history_lang CHANGE COLUMN FAQ_PKey History_PKey INT NOT NULL DEFAULT 0;
ALTER TABLE history_msg CHANGE COLUMN FAQ_PKey History_PKey INT NOT NULL DEFAULT 0;
ALTER TABLE history_img CHANGE COLUMN FAQ_PKey History_PKey INT NOT NULL DEFAULT 0;

-- 主檔年份（若不存在才新增）
SET @history_has_year := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'history'
      AND COLUMN_NAME = 'intYear'
);
SET @history_sql := IF(
    @history_has_year = 0,
    'ALTER TABLE history ADD COLUMN intYear INT NOT NULL DEFAULT 0 COMMENT ''年份'' AFTER Sort',
    'SELECT 1'
);
PREPARE stmt_history_year FROM @history_sql;
EXECUTE stmt_history_year;
DEALLOCATE PREPARE stmt_history_year;

DROP VIEW IF EXISTS view_history;
-- View 請優先執行：php scripts/install_view_history.php
-- （自 view_faq 複製並加入 intYear，避免硬編碼不存在欄位如 Home）
-- 若需手動建 VIEW，僅使用本站 history 確實有的欄位，範例：
CREATE VIEW view_history AS
SELECT
    m.PKey,
    m.Module_PKey,
    m.Class1_PKey,
    m.Class2_PKey,
    m.Class3_PKey,
    m.Sort,
    m.intYear,
    m.Upload,
    m.dtUDate,
    m.UserID,
    l.intLang,
    l.isShow,
    l.strName
FROM history AS m
INNER JOIN history_lang AS l ON l.History_PKey = m.PKey;
