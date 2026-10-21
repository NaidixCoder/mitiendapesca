-- Agrega soporte para múltiples comprobantes por pedido (Factura + Nota de Crédito).
-- Safe migration: ejecuta ALTER solo si faltan columnas/índices.
-- Requiere: MySQL/MariaDB con PREPARE.

SET @db := DATABASE();

-- kind
SET @has_kind := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='comprobantes_fiscales' AND COLUMN_NAME='kind'
);
SET @sql := IF(@has_kind = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN kind ENUM('invoice','credit_note') NOT NULL DEFAULT 'invoice' AFTER order_id",
  "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- related_comprobante_id
SET @has_rel := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='comprobantes_fiscales' AND COLUMN_NAME='related_comprobante_id'
);
SET @sql := IF(@has_rel = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN related_comprobante_id BIGINT UNSIGNED NULL AFTER kind",
  "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice en related_comprobante_id (para buscar NC por factura)
SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='comprobantes_fiscales' AND INDEX_NAME='idx_cf_related_comprobante'
);
SET @sql := IF(@has_idx = 0,
  "ALTER TABLE comprobantes_fiscales ADD INDEX idx_cf_related_comprobante (related_comprobante_id)",
  "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice auxiliar kind (filtrado rápido)
SET @has_idx2 := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='comprobantes_fiscales' AND INDEX_NAME='idx_cf_kind'
);
SET @sql := IF(@has_idx2 = 0,
  "ALTER TABLE comprobantes_fiscales ADD INDEX idx_cf_kind (kind)",
  "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
