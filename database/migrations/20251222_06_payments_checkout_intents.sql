-- 20251222_06_payments_checkout_intents.sql
-- Agrega tablas para flujo: checkout = pago (sin crear pedido hasta webhook)

CREATE TABLE IF NOT EXISTS checkout_intents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  address_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(64) NOT NULL,
  status ENUM('created','used','failed') NOT NULL DEFAULT 'created',
  fail_reason VARCHAR(255) NULL,
  amount INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'ARS',
  address_json JSON NOT NULL,
  items_json JSON NOT NULL,
  totals_json JSON NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_checkout_intents_user (user_id),
  UNIQUE KEY uk_checkout_intents_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(32) NOT NULL,
  provider_payment_id VARCHAR(64) NOT NULL,
  provider_preference_id VARCHAR(64) NULL,
  status VARCHAR(32) NULL,
  amount INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'ARS',
  intent_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  raw_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uk_payments_provider_pid (provider, provider_payment_id),
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_intent (intent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opcional: default status en orders a processing
-- (No destructivo; requiere recrear columna si es ENUM. Si tu schema ya tiene default processing, ignorar.)
