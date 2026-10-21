-- FIX519: Campos físicos para cálculo de envíos (con fallback a Ajustes)
-- No es excluyente para checkout: si faltan, se usan valores por defecto de Ajustes.

ALTER TABLE products
  ADD COLUMN ship_weight INT UNSIGNED NULL AFTER cost,
  ADD COLUMN ship_height INT UNSIGNED NULL AFTER ship_weight,
  ADD COLUMN ship_width  INT UNSIGNED NULL AFTER ship_height,
  ADD COLUMN ship_length INT UNSIGNED NULL AFTER ship_width;

ALTER TABLE product_variants
  ADD COLUMN ship_weight INT UNSIGNED NULL AFTER margin_pct,
  ADD COLUMN ship_height INT UNSIGNED NULL AFTER ship_weight,
  ADD COLUMN ship_width  INT UNSIGNED NULL AFTER ship_height,
  ADD COLUMN ship_length INT UNSIGNED NULL AFTER ship_width;
