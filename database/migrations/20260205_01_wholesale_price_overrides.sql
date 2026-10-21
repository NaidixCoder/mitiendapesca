
-- 20260205_01_wholesale_price_overrides.sql
-- Agrega overrides mayoristas por producto y variante (FIX556 Etapa 1)

ALTER TABLE products
  ADD COLUMN price_wh1_override INT UNSIGNED NULL AFTER price,
  ADD COLUMN price_wh2_override INT UNSIGNED NULL AFTER price_wh1_override;

ALTER TABLE product_variants
  ADD COLUMN price_wh1_override INT UNSIGNED NULL AFTER price,
  ADD COLUMN price_wh2_override INT UNSIGNED NULL AFTER price_wh1_override;
