-- Marketing: campañas internas + links cortos (/promo/{slug})

CREATE TABLE IF NOT EXISTS marketing_campaigns (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  destination_path VARCHAR(255) NOT NULL,
  utm_source VARCHAR(80) DEFAULT NULL,
  utm_medium VARCHAR(80) DEFAULT NULL,
  utm_campaign VARCHAR(120) DEFAULT NULL,
  utm_content VARCHAR(120) DEFAULT NULL,
  utm_term VARCHAR(120) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_marketing_campaigns_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketing_campaign_hits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  campaign_id INT UNSIGNED NOT NULL,
  session_id VARCHAR(128) DEFAULT NULL,
  referrer VARCHAR(255) DEFAULT NULL,
  ip_hash CHAR(64) DEFAULT NULL,
  ua_hash CHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_marketing_hits_campaign_created (campaign_id, created_at),
  KEY idx_marketing_hits_session (session_id),
  CONSTRAINT fk_marketing_hits_campaign FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
