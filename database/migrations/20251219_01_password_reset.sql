-- 2025-12-19 Password reset + password_changed_at
-- Objetivo:
-- 1) Recuperación de contraseña via token (one-time)
-- 2) Invalidador lógico de sesiones previas via users.password_changed_at

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  email VARCHAR(190) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  ip VARCHAR(64) NULL,
  ua VARCHAR(200) NULL,
  INDEX idx_email (email),
  INDEX idx_user (user_id),
  INDEX idx_expires (expires_at),
  UNIQUE KEY uq_token_hash (token_hash),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campo para invalidar sesiones previas (si no existe, ejecutar una vez)
-- Hostinger puede tener MySQL/MariaDB sin "ADD COLUMN IF NOT EXISTS".
-- Hacemos el ALTER de forma segura si el campo no existe.
SET @__has_col := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'password_changed_at'
);

SET @__sql := IF(
  @__has_col = 0,
  'ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL AFTER password_hash',
  'SELECT 1'
);

PREPARE stmt FROM @__sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
