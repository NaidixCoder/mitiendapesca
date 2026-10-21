-- Atribución de campañas internas a intents y pedidos (para medir ventas por /promo/slug)

-- 1) Guardar campaña en el intent (Mercado Pago) para que el webhook pueda crear el pedido con atribución.
ALTER TABLE checkout_intents
  ADD COLUMN marketing_campaign_id INT UNSIGNED NULL AFTER order_id,
  ADD INDEX idx_checkout_intents_campaign_created (marketing_campaign_id, created_at);

-- 2) Guardar campaña en el pedido (transferencia + MP) para reportes de marketing.
ALTER TABLE orders
  ADD COLUMN marketing_campaign_id INT UNSIGNED NULL AFTER payment_method,
  ADD INDEX idx_orders_campaign_created (marketing_campaign_id, created_at);
