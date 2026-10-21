-- FIX536: asegurar products.supplier_id (idempotente)
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'products'
    AND COLUMN_NAME = 'supplier_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE products ADD COLUMN supplier_id BIGINT UNSIGNED NULL AFTER category_id, ADD KEY idx_products_supplier_id (supplier_id), ADD CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
