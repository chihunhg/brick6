-- 最高權限：webcontrol.intType = 1（舊設定／權限管理帳號）
-- admin、tsguser 若先前誤改為其他值，改回 1 後重新登入
-- 後台進入時 manage_is_super_admin() 也會做同樣校正

UPDATE `webcontrol`
SET `intType` = 1,
    `dtUDate` = NOW()
WHERE `strID` IN ('admin', 'tsguser')
  AND `intType` <> 1;

-- 若尚有其他需最高權限的帳號，請自行指定：
-- UPDATE `webcontrol` SET `intType` = 1, `dtUDate` = NOW() WHERE `strID` IN ('your_admin_id');

-- 查詢目前最高權限帳號：
-- SELECT PKey, strID, strName, intType FROM webcontrol WHERE intType = 1;
