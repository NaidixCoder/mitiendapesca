-- Añade cache de precios "Desde" para productos con variantes (evita MIN() en listados)
-- SAFE: solo ADD COLUMN/INDEX
ALTER TABLE products
  ADD COLUMN price_from_cache INT UNSIGNED NULL AFTER cost,
  ADD COLUMN list_price_from_cache INT UNSIGNED NULL AFTER price_from_cache;

CREATE INDEX idx_cat_price_from ON products (category_id, price_from_cache);
