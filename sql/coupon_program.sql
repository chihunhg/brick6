-- 折價券後台功能模組（program 選單）
-- 執行後可於「單元設定」新增 coupon / couponreg 單元
-- mysql -u USER -p DB_NAME < sql/coupon_program.sql

INSERT INTO program (Sort, strName, strLink, MaxLayer, isList, isDetail, isColum, Home)
SELECT 15, '活動優惠券', 'coupon', 0, 0, 0, 0, ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM program WHERE strLink = 'coupon');

INSERT INTO program (Sort, strName, strLink, MaxLayer, isList, isDetail, isColum, Home)
SELECT 15, '會員優惠券', 'couponreg', 0, 0, 0, 0, ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM program WHERE strLink = 'couponreg');
