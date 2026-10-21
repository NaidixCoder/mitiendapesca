-- 20260131_01_suppliers.sql
-- Proveedores (base operativa para compras / umbrales / gestiones)
-- Reglas de Oro DB: utf8mb4 + collation consistente

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(190) NOT NULL,
  `cuit` VARCHAR(11) NOT NULL,
  `email` VARCHAR(190) NOT NULL DEFAULT '',
  `telefono` VARCHAR(64) NOT NULL DEFAULT '',
  `condicion_iva` VARCHAR(32) NOT NULL DEFAULT '',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_suppliers_cuit` (`cuit`),
  KEY `idx_suppliers_nombre` (`nombre`),
  KEY `idx_suppliers_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
