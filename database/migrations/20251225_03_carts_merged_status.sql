-- 20251225_03_carts_merged_status.sql
-- Extiende analítica de carritos: estado 'merged' + referencia al carrito destino.

ALTER TABLE carts
  ADD COLUMN merged_into_cart_id BIGINT UNSIGNED NULL AFTER converted_at,
  ADD COLUMN merged_at DATETIME NULL AFTER merged_into_cart_id;

-- ampliar enum de status
ALTER TABLE carts
  MODIFY status ENUM('active','converted','abandoned','merged') NOT NULL DEFAULT 'active';

ALTER TABLE carts
  ADD INDEX idx_carts_merged_into (merged_into_cart_id),
  ADD CONSTRAINT fk_carts_merged_into FOREIGN KEY (merged_into_cart_id) REFERENCES carts(id) ON DELETE SET NULL;
