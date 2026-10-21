-- 20260127_01_user_fiscal_profiles.sql
-- Perfil fiscal por usuario + estado de validación padrón

CREATE TABLE IF NOT EXISTS user_fiscal_profiles (
  user_id BIGINT UNSIGNED NOT NULL,
  tax_condition VARCHAR(32) NOT NULL DEFAULT 'consumidor_final',
  cuit VARCHAR(16) NOT NULL DEFAULT '',
  legal_name VARCHAR(160) NOT NULL DEFAULT '',
  validation_status ENUM('unverified','valid','invalid','pending') NOT NULL DEFAULT 'unverified',
  validation_error VARCHAR(255) NULL,
  validated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id),
  KEY idx_user_fiscal_status (validation_status),
  KEY idx_user_fiscal_updated (updated_at),
  CONSTRAINT fk_user_fiscal_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
