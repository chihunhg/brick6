-- 會員敏感欄位改以 sec_encrypt 密文儲存，欄位長度需足夠容納密文
-- 請於各環境手動執行本 migration

ALTER TABLE `member`
  MODIFY COLUMN `EMail`  VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY COLUMN `Tel`    VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY COLUMN `Mobile` VARCHAR(255) NOT NULL DEFAULT '';
