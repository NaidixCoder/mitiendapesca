-- Email Marketing (Etapa 3): Campañas (broadcast) + tracking de clicks + atribución a compras

-- Campañas de email (broadcast)
CREATE TABLE IF NOT EXISTS email_campaigns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  preheader VARCHAR(200) NULL,
  body_html MEDIUMTEXT NOT NULL,
  cta_label VARCHAR(80) NULL,
  cta_url TEXT NULL,
  audience VARCHAR(32) NOT NULL DEFAULT 'all',
  audience_params_json JSON NULL,
  status ENUM('draft','scheduled','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
  scheduled_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,

  KEY idx_email_campaigns_status_sched (status, scheduled_at),
  KEY idx_email_campaigns_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Token de tracking para clicks (resuelve token -> URL destino)
CREATE TABLE IF NOT EXISTS email_click_tokens (
  token CHAR(64) NOT NULL,
  campaign_id BIGINT UNSIGNED NULL,
  queue_id BIGINT UNSIGNED NULL,
  subscriber_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  destination_url TEXT NOT NULL,
  clicked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (token),
  KEY idx_email_click_campaign_clicked (campaign_id, clicked_at),
  KEY idx_email_click_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atribución a compras (sin romper si no existe la columna)
ALTER TABLE orders
  ADD COLUMN email_campaign_id BIGINT UNSIGNED NULL AFTER marketing_campaign_id,
  ADD INDEX idx_orders_email_campaign_created (email_campaign_id, created_at);

ALTER TABLE checkout_intents
  ADD COLUMN email_campaign_id BIGINT UNSIGNED NULL AFTER marketing_campaign_id,
  ADD INDEX idx_checkout_intents_email_campaign_created (email_campaign_id, created_at);
