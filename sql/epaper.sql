-- 訂閱電子報（epaper）資料表與後台 program 選單
-- mysql -u USER -p DB_NAME < sql/epaper.sql

CREATE TABLE IF NOT EXISTS `epaper` (
  `PKey` int NOT NULL AUTO_INCREMENT,
  `Module_PKey` int NULL DEFAULT 0 COMMENT '模組主鍵',
  `EMail` varchar(100) NULL DEFAULT '' COMMENT '電子信箱',
  `UserID` varchar(20) NULL DEFAULT 'Admin' COMMENT '使用者帳號',
  `dtUDate` datetime NULL DEFAULT NULL COMMENT '更新日期',
  `dtDate` datetime NULL DEFAULT NULL COMMENT '建立日期',
  PRIMARY KEY (`PKey`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='訂閱電子報管理';

INSERT INTO program (Sort, strName, strLink, MaxLayer, isList, isDetail, isColum, Home)
SELECT 11, '訂閱電子報(無前台)', 'epaper', 0, 0, 0, 0, ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM program WHERE strLink = 'epaper');
