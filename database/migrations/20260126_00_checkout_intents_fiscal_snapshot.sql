-- 2026-01-26
-- Snapshot fiscal/contable en checkout_intents (para que webhook no dependa de sesión)
-- - vendedor: régimen, CUIT, razón social, domicilio fiscal
-- - cliente: condición IVA, CUIT
-- - moneda: currency_code
-- - totales neto/IVA
--
-- Nota: si tu DB ya tiene estas columnas, este script puede fallar con "Duplicate column".

START TRANSACTION;

ALTER TABLE checkout_intents
  ADD COLUMN seller_tax_regime VARCHAR(32) NULL AFTER currency,
  ADD COLUMN seller_cuit VARCHAR(32) NULL AFTER seller_tax_regime,
  ADD COLUMN seller_business_name VARCHAR(160) NULL AFTER seller_cuit,
  ADD COLUMN seller_fiscal_address VARCHAR(200) NULL AFTER seller_business_name,
  ADD COLUMN customer_tax_condition VARCHAR(32) NULL AFTER seller_fiscal_address,
  ADD COLUMN customer_cuit VARCHAR(32) NULL AFTER customer_tax_condition,
  ADD COLUMN currency_code VARCHAR(16) NULL AFTER customer_cuit,
  ADD COLUMN total_net INT UNSIGNED NULL AFTER currency_code,
  ADD COLUMN total_vat INT UNSIGNED NULL AFTER total_net;

COMMIT;
