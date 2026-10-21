-- 20260131_02_purchase_invoices.sql
-- Compras (IVA compras) + integración con Proveedores
-- Importes guardados en pesos (ARS) como INT.

CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` BIGINT UNSIGNED NOT NULL,
  `kind` VARCHAR(20) NOT NULL DEFAULT 'invoice', -- invoice | credit_note
  `cbte_tipo` SMALLINT UNSIGNED NOT NULL, -- 1,6,11,3,8,13 (AFIP)
  `pto_vta` INT UNSIGNED NOT NULL DEFAULT 0,
  `cbte_nro` INT UNSIGNED NOT NULL DEFAULT 0,
  `issued_at` DATE NOT NULL,
  `imp_neto` INT NOT NULL DEFAULT 0,
  `imp_iva` INT NOT NULL DEFAULT 0,
  `imp_total` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'loaded', -- loaded | validated | void
  `notes` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `voided_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_invoices_supplier` (`supplier_id`),
  KEY `idx_purchase_invoices_issued_at` (`issued_at`),
  KEY `idx_purchase_invoices_kind` (`kind`),
  KEY `idx_purchase_invoices_status` (`status`),
  UNIQUE KEY `uniq_purchase_invoice_doc` (`supplier_id`,`cbte_tipo`,`pto_vta`,`cbte_nro`),
  CONSTRAINT `fk_purchase_invoices_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
