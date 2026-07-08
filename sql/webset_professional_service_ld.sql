-- webset：ProfessionalService JSON-LD 擴充欄位
--
-- 【依語系 intLang 維護】
--   Address      — 既有欄位（街道地址），無需新增
--   PostCode     — 郵遞區號
--   strCounty    — 行政區
--   strCity      — 城市／縣市
--
-- 【基本設定全站共用，儲存時同步寫入各語系列】
--   ServiceDescription, PriceRange, GeoLat, GeoLng, HasMap,
--   ContactAreaServed, ContactLanguage, AreaServed,
--   OpeningDays, Opens, Closes
--
-- 若部分欄位已存在，請刪除對應 ADD COLUMN 行後再執行。

ALTER TABLE webset
    ADD COLUMN PostCode VARCHAR(10) NULL DEFAULT NULL COMMENT '郵遞區號' AFTER Address,
    ADD COLUMN strCounty VARCHAR(50) NULL DEFAULT NULL COMMENT '行政區' AFTER PostCode,
    ADD COLUMN strCity VARCHAR(50) NULL DEFAULT NULL COMMENT '城市／縣市' AFTER strCounty,
    ADD COLUMN ServiceDescription TEXT NULL COMMENT 'ProfessionalService description' AFTER Description,
    ADD COLUMN PriceRange VARCHAR(10) NULL DEFAULT NULL COMMENT '價格區間 $～$$$$' AFTER ServiceDescription,
    ADD COLUMN GeoLat VARCHAR(20) NULL DEFAULT NULL COMMENT '緯度' AFTER strCity,
    ADD COLUMN GeoLng VARCHAR(20) NULL DEFAULT NULL COMMENT '經度' AFTER GeoLat,
    ADD COLUMN HasMap VARCHAR(500) NULL DEFAULT NULL COMMENT 'Google Maps 連結' AFTER GeoLng,
    ADD COLUMN ContactAreaServed VARCHAR(255) NULL DEFAULT NULL COMMENT 'contactPoint areaServed' AFTER HasMap,
    ADD COLUMN ContactLanguage VARCHAR(100) NULL DEFAULT 'zh-Hant' COMMENT 'contactPoint availableLanguage' AFTER ContactAreaServed,
    ADD COLUMN AreaServed VARCHAR(500) NULL DEFAULT NULL COMMENT '服務涵蓋範圍（逗號分隔）' AFTER ContactLanguage,
    ADD COLUMN OpeningDays VARCHAR(200) NULL DEFAULT NULL COMMENT '營業日（Monday,Tuesday…）' AFTER AreaServed,
    ADD COLUMN Opens VARCHAR(10) NULL DEFAULT NULL COMMENT '開門時間 HH:MM' AFTER OpeningDays,
    ADD COLUMN Closes VARCHAR(10) NULL DEFAULT NULL COMMENT '關門時間 HH:MM' AFTER Opens;
