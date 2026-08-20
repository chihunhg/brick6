-- 後台 webcontrol TOTP 多因素驗證欄位
-- 各環境手動執行一次

ALTER TABLE webcontrol
    ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否啟用 TOTP' AFTER strPW,
    ADD COLUMN totp_secret VARCHAR(512) NOT NULL DEFAULT '' COMMENT '加密後 TOTP secret' AFTER mfa_enabled,
    ADD COLUMN mfa_backup_codes TEXT NULL COMMENT '備用碼 hash JSON' AFTER totp_secret;
