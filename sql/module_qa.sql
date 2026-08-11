-- 美工頁 FAQ（module_qa）
-- 若表已存在可略過；正式／測試機請手動執行

CREATE TABLE IF NOT EXISTS `module_qa` (
  `PKey` int NOT NULL AUTO_INCREMENT,
  `Module_PKey` int NULL DEFAULT 0 COMMENT '模組主鍵',
  `intLang` int NULL DEFAULT 1 COMMENT '語系代碼',
  `isShow` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '語系顯示',
  `Sort` int NULL DEFAULT 1 COMMENT '排序',
  `Question` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '問題',
  `Answer` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '答案',
  `dtDate` datetime NULL DEFAULT NULL COMMENT '建立日期',
  PRIMARY KEY (`PKey`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '美工頁QA管理' ROW_FORMAT = Dynamic;
