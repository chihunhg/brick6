-- 前台單元瀏覽記錄（當月熱表；歷史資料由 scripts/frontend_visit_log_archive.php 每月封存）
DROP TABLE IF EXISTS `frontend_visit_log`;
CREATE TABLE `frontend_visit_log` (
    `PKey` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `Module_PKey` INT UNSIGNED NULL DEFAULT 0 COMMENT '單元主鍵',
    `strLink` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '頁面連結',
    `UserIP` VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '來源IP',
    `strCountry` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '國家',
    `strCountryCode` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '國家代碼',
    `dtDate` DATETIME NOT NULL,
    PRIMARY KEY (`PKey`) USING BTREE,
    INDEX `idx_frontend_visit_module_date` (`Module_PKey`, `dtDate`) USING BTREE,
    INDEX `idx_frontend_visit_date` (`dtDate`) USING BTREE,
    INDEX `idx_frontend_visit_ip` (`UserIP`) USING BTREE
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='單元瀏覽記錄（當月）' ROW_FORMAT=Dynamic;

-- 封存表示例：frontend_visit_log_202606（由 job 自動建立，結構同上）
