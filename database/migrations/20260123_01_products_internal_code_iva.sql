-- 20260123_01_products_internal_code_iva.sql
-- Tienda Pesca — Preparación para Import CSV v3 + IVA futuro + Código interno
-- No destructivo. Agrega columnas nullable y UNIQUE.

ALTER TABLE products
  ADD COLUMN internal_code VARCHAR(32) NULL AFTER id,
  ADD COLUMN iva_rate DECIMAL(5,2) NULL AFTER long_desc,
  ADD UNIQUE KEY uq_products_internal_code (internal_code);

ALTER TABLE product_variants
  ADD COLUMN internal_code VARCHAR(32) NULL AFTER id,
  ADD UNIQUE KEY uq_product_variants_internal_code (internal_code);
