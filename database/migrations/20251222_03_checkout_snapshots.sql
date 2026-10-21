-- 20251222_03_checkout_snapshots.sql
-- Checkout técnico: snapshots de dirección y de items.
-- No destructivo (ALTER ADD COLUMN). Ejecutar una sola vez.

ALTER TABLE orders
  ADD COLUMN address_id BIGINT UNSIGNED NULL AFTER total,
  ADD COLUMN shipping_full_name VARCHAR(120) NULL AFTER address_id,
  ADD COLUMN shipping_phone VARCHAR(40) NULL AFTER shipping_full_name,
  ADD COLUMN shipping_street VARCHAR(160) NULL AFTER shipping_phone,
  ADD COLUMN shipping_number VARCHAR(20) NULL AFTER shipping_street,
  ADD COLUMN shipping_floor VARCHAR(20) NULL AFTER shipping_number,
  ADD COLUMN shipping_apartment VARCHAR(20) NULL AFTER shipping_floor,
  ADD COLUMN shipping_city VARCHAR(80) NULL AFTER shipping_apartment,
  ADD COLUMN shipping_state VARCHAR(80) NULL AFTER shipping_city,
  ADD COLUMN shipping_zip VARCHAR(20) NULL AFTER shipping_state,
  ADD COLUMN shipping_notes VARCHAR(255) NULL AFTER shipping_zip,
  ADD COLUMN shipping_address_json JSON NULL AFTER shipping_notes;

ALTER TABLE order_items
  ADD COLUMN product_sku VARCHAR(80) NULL AFTER price,
  ADD COLUMN product_name VARCHAR(200) NULL AFTER product_sku;

CREATE INDEX idx_orders_address ON orders (address_id);