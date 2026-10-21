-- 20251223_01_orders_payment_method.sql
-- Agrega método de pago al pedido (no destructivo / re-ejecutable)

SET @__has_col := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'orders'
    AND COLUMN_NAME = 'payment_method'
);

SET @__sql := IF(
  @__has_col = 0,
  'ALTER TABLE orders ADD COLUMN payment_method VARCHAR(32) NULL AFTER status',
  'SELECT 1'
);

PREPARE stmt FROM @__sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
