-- Gemini 產文 token 明細（依單元、日期彙總；首次寫入時 PHP 也會 CREATE TABLE IF NOT EXISTS）
CREATE TABLE IF NOT EXISTS `gemini_token_usage` (
    `PKey` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dtDate` DATETIME NOT NULL COMMENT '呼叫時間',
    `strModule` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '單元代碼',
    `strFeature` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '功能',
    `strModel` VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型',
    `intPromptTokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '輸入 token',
    `intCandidatesTokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '輸出 token',
    `intThoughtsTokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '思考 token',
    `intTotalTokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '合計 token',
    `strLogin` VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '後台帳號',
    `strSuccess` CHAR(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y' COMMENT 'Y 成功 N 失敗',
    PRIMARY KEY (`PKey`) USING BTREE,
    INDEX `idx_gemini_token_date_module` (`dtDate`, `strModule`) USING BTREE,
    INDEX `idx_gemini_token_module_date` (`strModule`, `dtDate`) USING BTREE
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gemini token 用量明細' ROW_FORMAT=Dynamic;
