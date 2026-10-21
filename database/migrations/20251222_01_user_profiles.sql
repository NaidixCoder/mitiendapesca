-- Crear tabla de perfil (datos opcionales del usuario)
-- No destructiva.

CREATE TABLE IF NOT EXISTS user_profiles (
  user_id BIGINT UNSIGNED NOT NULL,
  phone VARCHAR(30) NULL,
  birth_date DATE NULL,
  marketing_email_opt_in TINYINT(1) NOT NULL DEFAULT 0,
  marketing_whatsapp_opt_in TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,

  PRIMARY KEY (user_id),
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
