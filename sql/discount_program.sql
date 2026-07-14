-- 優惠折抵後台功能模組（program 選單）
-- 執行後可於「單元設定」新增 discount 單元（或直接用本 INSERT）
-- mysql -u USER -p DB_NAME < sql/discount_program.sql

INSERT INTO program (Sort, strName, strLink, MaxLayer, isList, isDetail, isColum, Home)
SELECT 16, '優惠折抵', 'discount', 0, 0, 0, 0, ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM program WHERE strLink = 'discount');
