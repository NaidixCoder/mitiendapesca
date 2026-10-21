-- =====================================================
-- Email Marketing (Etapa 1): Suscriptores + preferencias + baja
-- =====================================================

CREATE TABLE IF NOT EXISTS email_subscribers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  status ENUM('pending','subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  confirm_token CHAR(64) DEFAULT NULL,
  unsubscribe_token CHAR(64) NOT NULL,
  confirmed_at DATETIME DEFAULT NULL,
  unsubscribed_at DATETIME DEFAULT NULL,
  preferences_json JSON DEFAULT NULL,
  source VARCHAR(40) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_email_subscribers_email (email),
  UNIQUE KEY uk_email_subscribers_unsub_token (unsubscribe_token),
  KEY idx_email_subscribers_status (status),
  KEY idx_email_subscribers_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
