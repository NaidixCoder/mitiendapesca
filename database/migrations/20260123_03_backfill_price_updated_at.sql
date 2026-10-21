-- Backfill de price_updated_at (solo donde esté NULL y haya precio/lista).
-- Usa created_at como aproximación (no existe updated_at en estas tablas).
UPDATE products
SET price_updated_at = created_at
WHERE price_updated_at IS NULL AND ((price IS NOT NULL AND price > 0) OR (list_price IS NOT NULL AND list_price > 0));

UPDATE product_variants
SET price_updated_at = created_at
WHERE price_updated_at IS NULL AND ((price IS NOT NULL AND price > 0) OR (list_price IS NOT NULL AND list_price > 0));
