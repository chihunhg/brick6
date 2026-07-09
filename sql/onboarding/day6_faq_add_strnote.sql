-- Day 6 實戰：FAQ 語系子表新增備註欄位 strNote
-- 僅供 dev／測試環境練習；正式機請依變更流程另行執行。
-- 執行：mysql -u USER -p DB_NAME < sql/onboarding/day6_faq_add_strnote.sql

ALTER TABLE faq_lang
    ADD COLUMN strNote VARCHAR(255) NOT NULL DEFAULT '' COMMENT '備註（Day6 onboarding）'
    AFTER strName;

-- view_faq 若為 SELECT * 或已 JOIN faq_lang.*，通常無需重建。
-- 若不確定，請在 phpMyAdmin 執行：SHOW CREATE VIEW view_faq\G
-- 確認 SELECT 清單含 strNote；若 view 為固定欄位列表，需 DROP/CREATE VIEW 加入 strNote。
