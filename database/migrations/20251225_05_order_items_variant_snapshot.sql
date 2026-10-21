-- 20251225_05_order_items_variant_snapshot.sql
-- Agrega soporte de variante a order_items (snapshot).
-- Objetivo: congelar variante elegida en el pedido sin afectar el flujo actual.

ALTER TABLE order_items
  ADD COLUMN variant_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER product_id,
  ADD COLUMN variant_sku VARCHAR(80) NULL AFTER product_sku,
  ADD COLUMN variant_title VARCHAR(255) NULL AFTER product_name,
  ADD COLUMN variant_attributes_json TEXT NULL AFTER variant_title,
  ADD INDEX idx_variant (variant_id);
