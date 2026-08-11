-- paper 語系子表：AI 搜尋核心摘要欄位
ALTER TABLE `paper_lang`
    ADD COLUMN `Summary` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'AI搜尋核心摘要' AFTER `Subject`;
