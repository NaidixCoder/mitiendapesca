-- Etapa 2: permitir crear producto sin datos de venta (SKU/Precio) y definir modo de venta.
-- Ejecutar una sola vez en la base.

ALTER TABLE products
  ADD COLUMN sale_mode ENUM('simple','variants') NOT NULL DEFAULT 'simple' AFTER category_id;

ALTER TABLE products
  MODIFY COLUMN sku VARCHAR(80) NULL,
  MODIFY COLUMN price INT UNSIGNED NULL;
