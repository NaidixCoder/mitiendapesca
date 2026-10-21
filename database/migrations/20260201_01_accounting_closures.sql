-- 20260201_01_accounting_closures.sql
-- Snapshot contable mensual (cierre) - IVA Ventas/Compras y saldo
-- Importes guardados en pesos (ARS) como INT.

CREATE TABLE IF NOT EXISTS `accounting_closures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `period` VARCHAR(7) NOT NULL, -- YYYY-MM
  `iva_ventas` INT NOT NULL DEFAULT 0,
  `iva_compras` INT NOT NULL DEFAULT 0,
  `saldo_iva` INT NOT NULL DEFAULT 0,
  `ventas_json` JSON NULL,
  `compras_json` JSON NULL,
  `closed_by_user_id` BIGINT UNSIGNED NOT NULL,
  `closed_at` DATETIME NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_accounting_closures_period` (`period`),
  KEY `idx_accounting_closures_closed_at` (`closed_at`),
  KEY `idx_accounting_closures_closed_by` (`closed_by_user_id`),
  CONSTRAINT `fk_accounting_closures_closed_by` FOREIGN KEY (`closed_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
