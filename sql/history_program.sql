-- 歷史沿革後台功能模組（program 選單）
-- mysql -u USER -p DB_NAME < sql/history_program.sql

INSERT INTO program (Sort, strName, strLink, MaxLayer, isList, isDetail, isColum, Home, intType, Upload)
SELECT 12, '歷史沿革', 'history', 0, 1, 0, 0, '', 1, 'Yes'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM program WHERE strLink = 'history');
