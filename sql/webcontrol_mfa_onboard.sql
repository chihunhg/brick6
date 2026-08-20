-- 後台帳號 MFA onboarding：Email、首次登入強制綁定旗標
-- 若已執行 webcontrol_totp_mfa.sql，本檔為增量 migration

ALTER TABLE webcontrol
    ADD COLUMN strEmail VARCHAR(100) NOT NULL DEFAULT '' COMMENT '管理者 Email（MFA 指引信）' AFTER strName,
    ADD COLUMN mfa_setup_pending TINYINT(1) NOT NULL DEFAULT 0 COMMENT '待完成首次 MFA 綁定' AFTER mfa_backup_codes;
