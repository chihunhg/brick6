-- 產品售價與 MPN（Product JSON-LD offers 用）
ALTER TABLE `product`
  ADD COLUMN `Price` INT NULL DEFAULT NULL COMMENT '售價（TWD，0 或 NULL 表示不顯示價格）' AFTER `strNo`,
  ADD COLUMN `MPN` VARCHAR(50) NULL DEFAULT NULL COMMENT '製造商零件編號' AFTER `Price`;
