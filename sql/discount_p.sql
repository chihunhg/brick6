-- 優惠折抵主檔（滿件／滿額折抵運費）
-- mysql -u USER -p DB_NAME < sql/discount_p.sql

CREATE TABLE IF NOT EXISTS `discount_p` (
  `PKey` int NOT NULL AUTO_INCREMENT,
  `Module_PKey` int NULL DEFAULT 0 COMMENT '模組主鍵',
  `strName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '活動名稱',
  `intType` smallint NULL DEFAULT 1 COMMENT '折抵方式(1.滿件;2.滿額)',
  `BuyQ` int NULL DEFAULT 0 COMMENT '滿件數量',
  `BuyPrice` int NULL DEFAULT 0 COMMENT '滿額金額',
  `Price` int NULL DEFAULT 100 COMMENT '折抵運費金額',
  `Interview` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '折抵說明',
  `Contents` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '內容',
  `OpenDate` datetime NULL DEFAULT NULL COMMENT '開始日期',
  `EndDate` datetime NULL DEFAULT NULL COMMENT '結束日期',
  `UserID` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Admin' COMMENT '使用者帳號',
  `dtUDate` datetime NULL DEFAULT NULL COMMENT '更新日期',
  `dtDate` datetime NULL DEFAULT NULL COMMENT '建立日期',
  PRIMARY KEY (`PKey`) USING BTREE,
  KEY `idx_discount_module` (`Module_PKey`),
  KEY `idx_discount_dates` (`OpenDate`, `EndDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
