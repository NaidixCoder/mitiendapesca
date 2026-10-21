-- FIX309: registrar fecha de modificación de precio (products + product_variants)
-- Nota: DB en UTC (SET time_zone = '+00:00')

ALTER TABLE products
  ADD COLUMN price_updated_at DATETIME NULL AFTER list_price;

ALTER TABLE product_variants
  ADD COLUMN price_updated_at DATETIME NULL AFTER list_price;

CREATE INDEX idx_products_price_updated_at ON products (price_updated_at);
CREATE INDEX idx_product_variants_price_updated_at ON product_variants (price_updated_at);
