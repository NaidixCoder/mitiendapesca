-- Analíticas de navegación / funnel (P2)
-- Tabla de eventos: page_view, product_view, add_to_cart, checkout_view, purchase

CREATE TABLE IF NOT EXISTS analytics_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_key VARCHAR(40) NOT NULL,
  path VARCHAR(255) NULL,
  referrer VARCHAR(255) NULL,
  marketing_campaign_id INT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  session_id VARCHAR(128) NULL,
  cart_token_hash CHAR(64) NULL,
  ip_hash CHAR(64) NULL,
  ua_hash CHAR(64) NULL,
  product_id INT UNSIGNED NULL,
  variant_id INT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_event_date (event_key, created_at),
  KEY idx_path_date (path, created_at),
  KEY idx_product_date (product_id, created_at),
  KEY idx_variant_date (variant_id, created_at),
  KEY idx_user_date (user_id, created_at),
  KEY idx_session_date (session_id, created_at),
  KEY idx_order_date (order_id, created_at),
  KEY idx_campaign_date (marketing_campaign_id, created_at),
  UNIQUE KEY uniq_event_order (event_key, order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
