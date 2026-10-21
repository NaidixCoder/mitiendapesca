-- 2026-01-26
-- Soporte fiscal mínimo (listo para Monotributo y RI):
-- - Snapshot fiscal del vendedor/cliente en orders
-- - Descomposición neto/IVA por ítem en order_items
--
-- Nota: si tu DB ya tiene estas columnas, este script puede fallar con "Duplicate column".

START TRANSACTION;

-- Orders: snapshot fiscal + totales neto/IVA
ALTER TABLE orders
  ADD COLUMN seller_tax_regime VARCHAR(32) NULL AFTER total,
  ADD COLUMN seller_cuit VARCHAR(32) NULL AFTER seller_tax_regime,
  ADD COLUMN seller_business_name VARCHAR(160) NULL AFTER seller_cuit,
  ADD COLUMN seller_fiscal_address VARCHAR(200) NULL AFTER seller_business_name,
  ADD COLUMN customer_tax_condition VARCHAR(32) NULL AFTER seller_fiscal_address,
  ADD COLUMN customer_cuit VARCHAR(32) NULL AFTER customer_tax_condition,
  ADD COLUMN currency_code VARCHAR(16) NULL AFTER customer_cuit,
  ADD COLUMN total_net INT UNSIGNED NULL AFTER currency_code,
  ADD COLUMN total_vat INT UNSIGNED NULL AFTER total_net;

-- Order items: snapshot por ítem (unit y line)
ALTER TABLE order_items
  ADD COLUMN vat_rate DECIMAL(7,4) NULL AFTER unit_cost,
  ADD COLUMN unit_net INT UNSIGNED NULL AFTER vat_rate,
  ADD COLUMN unit_vat INT UNSIGNED NULL AFTER unit_net,
  ADD COLUMN line_net INT UNSIGNED NULL AFTER unit_vat,
  ADD COLUMN line_vat INT UNSIGNED NULL AFTER line_net;

COMMIT;
