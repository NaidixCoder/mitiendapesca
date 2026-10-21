-- ===== EMAIL TEMPLATES (Etapa 4) =====

CREATE TABLE IF NOT EXISTS email_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  subject_default VARCHAR(190) NULL,
  preheader_default VARCHAR(190) NULL,
  editor_mode ENUM('visual','html') NOT NULL DEFAULT 'visual',
  body_html MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
