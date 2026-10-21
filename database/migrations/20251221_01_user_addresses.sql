-- 20251221_01_user_addresses.sql
-- Crea tabla de direcciones del usuario (no destructiva)

CREATE TABLE IF NOT EXISTS user_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  label      VARCHAR(60)  NULL,
  full_name  VARCHAR(120) NOT NULL,
  phone      VARCHAR(40)  NULL,
  street     VARCHAR(160) NOT NULL,
  number     VARCHAR(20)  NOT NULL,
  floor      VARCHAR(20)  NULL,
  apartment  VARCHAR(20)  NULL,
  city       VARCHAR(80)  NOT NULL,
  state      VARCHAR(80)  NOT NULL,
  zip        VARCHAR(20)  NOT NULL,
  notes      VARCHAR(255) NULL,
  is_default TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,

  INDEX idx_addr_user (user_id),
  INDEX idx_addr_user_default (user_id, is_default),
  CONSTRAINT fk_addr_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
