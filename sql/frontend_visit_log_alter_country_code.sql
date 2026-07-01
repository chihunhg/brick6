-- 既有環境：國家名稱與代碼拆欄（Taiwan (TW) → strCountry + strCountryCode）
-- 主表
ALTER TABLE `frontend_visit_log`
    ADD COLUMN `strCountryCode` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '國家代碼' AFTER `strCountry`;

-- 將舊格式「Taiwan (TW)」拆成兩欄（其餘資料維持不變）
UPDATE `frontend_visit_log`
SET
    `strCountryCode` = UPPER(TRIM(TRAILING ')' FROM TRIM(SUBSTRING_INDEX(`strCountry`, '(', -1)))),
    `strCountry` = TRIM(SUBSTRING_INDEX(`strCountry`, '(', 1))
WHERE `strCountry` LIKE '%(%'
  AND `strCountryCode` = '';

-- 已建立的封存表請依序執行（將 YYYYMM 替換為實際月份）：
-- ALTER TABLE `frontend_visit_log_202607`
--     ADD COLUMN `strCountryCode` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '國家代碼' AFTER `strCountry`;
-- UPDATE `frontend_visit_log_202607`
-- SET
--     `strCountryCode` = UPPER(TRIM(TRAILING ')' FROM TRIM(SUBSTRING_INDEX(`strCountry`, '(', -1)))),
--     `strCountry` = TRIM(SUBSTRING_INDEX(`strCountry`, '(', 1))
-- WHERE `strCountry` LIKE '%(%'
--   AND `strCountryCode` = '';
