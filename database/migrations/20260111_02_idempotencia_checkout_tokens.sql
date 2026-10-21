-- 20260111_02_idempotencia_checkout_tokens
-- Objetivo: blindaje idempotencia (checkout_intents + orders)
-- - checkout_intents: cart_hash + order_id para reutilización y vínculo
-- - orders: checkout_token (unique) para evitar pedidos duplicados (submit/webhook)

ALTER TABLE checkout_intents
  ADD COLUMN cart_hash VARCHAR(64) NOT NULL AFTER token,
  ADD COLUMN order_id BIGINT UNSIGNED NULL AFTER cart_hash,
  ADD INDEX idx_checkout_intents_reuse (user_id, address_id, status, cart_hash, created_at),
  ADD INDEX idx_checkout_intents_order (order_id);

ALTER TABLE orders
  ADD COLUMN checkout_token VARCHAR(64) NULL AFTER payment_method,
  ADD UNIQUE KEY uk_orders_checkout_token (checkout_token);
