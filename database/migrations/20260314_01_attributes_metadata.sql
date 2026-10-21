-- 20260314_01_attributes_metadata
-- Metadatos funcionales para atributos filtrables, visibles y ejes de variante.

ALTER TABLE `attributes`
  ADD COLUMN IF NOT EXISTS `code` VARCHAR(120) NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `is_filterable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`,
  ADD COLUMN IF NOT EXISTS `is_public` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_filterable`,
  ADD COLUMN IF NOT EXISTS `is_variant_axis` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_public`,
  ADD COLUMN IF NOT EXISTS `facet_sort` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_variant_axis`,
  ADD COLUMN IF NOT EXISTS `group_name` VARCHAR(80) NULL AFTER `facet_sort`,
  ADD COLUMN IF NOT EXISTS `unit` VARCHAR(32) NULL AFTER `group_name`;

ALTER TABLE `attributes`
  ADD UNIQUE KEY `uq_attr_code` (`code`);

ALTER TABLE `attributes`
  ADD KEY `idx_attributes_filterable_sort` (`is_filterable`, `facet_sort`, `name`);
