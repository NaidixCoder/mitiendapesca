-- FIX419: permitir 1 factura + 1 nota de crédito por pedido (sin romper installs existentes)
-- Problema: algunos esquemas viejos tenían UNIQUE(order_id) => impide guardar NC para un pedido con factura.
-- Solución: cambiar a UNIQUE(order_id, kind) y agregar índices requeridos.
--
-- Este script intenta ser "idempotente" usando information_schema (MySQL/MariaDB).

-- 1) Drop UNIQUE(order_id) si existe (uk_comprobantes_fiscales_order)
SET @has_uk_order := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND index_name = 'uk_comprobantes_fiscales_order'
);
SET @sql := IF(@has_uk_order > 0, 'ALTER TABLE comprobantes_fiscales DROP INDEX uk_comprobantes_fiscales_order', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Columnas kind / related_comprobante_id (solo si faltan)
SET @has_kind := (
  SELECT COUNT(1)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND column_name = 'kind'
);
SET @sql := IF(@has_kind = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN kind ENUM('invoice','credit_note') NOT NULL DEFAULT 'invoice' AFTER order_id",
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_related := (
  SELECT COUNT(1)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND column_name = 'related_comprobante_id'
);
SET @sql := IF(@has_related = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN related_comprobante_id BIGINT UNSIGNED NULL AFTER kind",
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) UNIQUE(order_id, kind)
SET @has_uk_order_kind := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND index_name = 'uk_cf_order_kind'
);
SET @sql := IF(@has_uk_order_kind = 0, 'ALTER TABLE comprobantes_fiscales ADD UNIQUE KEY uk_cf_order_kind (order_id, kind)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Índice para relación factura -> NC
SET @has_idx_related := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND index_name = 'idx_cf_related_comprobante'
);
SET @sql := IF(@has_idx_related = 0, 'ALTER TABLE comprobantes_fiscales ADD INDEX idx_cf_related_comprobante (related_comprobante_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
