-- Global app settings (key-value)

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(80) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
