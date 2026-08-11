-- 模組類別管理
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `module_class`;
CREATE TABLE `module_class` (
  `PKey` int NOT NULL AUTO_INCREMENT,
  `Sort` int NULL DEFAULT 0 COMMENT '順序',
  `strName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '標題',
  `Upload` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '上下架',
  `UserID` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '使用者帳號',
  `dtUDate` datetime NULL DEFAULT NULL COMMENT '更新日期',
  `dtDate` datetime NULL DEFAULT NULL COMMENT '建立日期',
  PRIMARY KEY (`PKey`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '模組類別管理' ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
