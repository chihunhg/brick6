-- 後台最高權限改由 include/Function.php 的 manage_named_super_admin_ids() 帳號陣列判斷
-- 不再以 webcontrol.intType = 1 放行單元設定、語系設定或全模組
-- 調整最高權限請改該陣列後重新進入後台，不必更新 intType

-- 權限管理列表仍以 intType = 1 篩選，與最高權限帳號陣列分開
-- SELECT PKey, strID, strName, intType FROM webcontrol WHERE intType = 1;
