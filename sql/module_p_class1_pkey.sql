-- module_p：模組類別外鍵（module_class.PKey）
ALTER TABLE `module_p`
    ADD COLUMN `Class1_PKey` int NULL DEFAULT 0 COMMENT '模組類別（module_class.PKey）' AFTER `intUse`;
