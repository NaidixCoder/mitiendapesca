-- 2025-12-20 Auth login metrics
-- Objetivo: registrar métricas de login (éxitos/fallos) sin guardar PII sensible.

CREATE TABLE IF NOT EXISTS auth_login_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event VARCHAR(32) NOT NULL,
  method VARCHAR(32) NOT NULL,
  reason VARCHAR(64) NULL,
  user_id BIGINT UNSIGNED NULL,
  email_hash CHAR(64) NULL,
  ip VARCHAR(64) NULL,
  ua_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_created_at (created_at),
  INDEX idx_event_method (event, method),
  INDEX idx_user_created (user_id, created_at),
  INDEX idx_email_hash_created (email_hash, created_at),
  INDEX idx_ip_created (ip, created_at),
  CONSTRAINT fk_auth_login_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
