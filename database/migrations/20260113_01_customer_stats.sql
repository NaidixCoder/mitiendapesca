-- ===== CUSTOMER STATS (agregados por usuario para performance + fidelidad) =====
CREATE TABLE IF NOT EXISTS customer_stats (
  user_id BIGINT UNSIGNED NOT NULL,

  paid_orders_count INT UNSIGNED NOT NULL DEFAULT 0,
  paid_total INT UNSIGNED NOT NULL DEFAULT 0,
  first_paid_at DATETIME NULL,
  last_paid_at DATETIME NULL,
  avg_paid_total INT UNSIGNED NOT NULL DEFAULT 0,

  loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
  loyalty_tier VARCHAR(20) NOT NULL DEFAULT 'Bronce',

  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id),
  INDEX idx_customer_stats_tier (loyalty_tier),
  INDEX idx_customer_stats_paid_total (paid_total),
  CONSTRAINT fk_customer_stats_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
