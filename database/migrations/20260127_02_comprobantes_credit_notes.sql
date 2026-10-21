-- 20260127_02_comprobantes_credit_notes.sql
-- Permite múltiples comprobantes por pedido (facturas + notas) y referencias.
-- Seguro para correr más de una vez (usa checks en information_schema).

-- 1) Columna kind
SET @col_kind := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND column_name = 'kind'
);
SET @sql1 := IF(@col_kind = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN kind ENUM('invoice','credit_note') NOT NULL DEFAULT 'invoice' AFTER order_id",
  "SELECT 1"
);
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

-- 2) Columna related_comprobante_id
SET @col_rel := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND column_name = 'related_comprobante_id'
);
SET @sql2 := IF(@col_rel = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN related_comprobante_id BIGINT UNSIGNED NULL AFTER kind",
  "SELECT 1"
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- 3) Drop unique por order (si existe)
SET @idx_uk := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND index_name = 'uk_comprobantes_fiscales_order'
);
SET @sql3 := IF(@idx_uk > 0,
  "ALTER TABLE comprobantes_fiscales DROP INDEX uk_comprobantes_fiscales_order",
  "SELECT 1"
);
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- 4) Crear índice simple por order (si falta)
SET @idx_order := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND index_name = 'idx_comprobantes_fiscales_order'
);
SET @sql4 := IF(@idx_order = 0,
  "ALTER TABLE comprobantes_fiscales ADD INDEX idx_comprobantes_fiscales_order (order_id)",
  "SELECT 1"
);
PREPARE stmt4 FROM @sql4; EXECUTE stmt4; DEALLOCATE PREPARE stmt4;

-- 5) Índice kind+status (si falta)
SET @idx_kind := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND index_name = 'idx_comprobantes_fiscales_kind_status'
);
SET @sql5 := IF(@idx_kind = 0,
  "ALTER TABLE comprobantes_fiscales ADD INDEX idx_comprobantes_fiscales_kind_status (kind, status)",
  "SELECT 1"
);
PREPARE stmt5 FROM @sql5; EXECUTE stmt5; DEALLOCATE PREPARE stmt5;
