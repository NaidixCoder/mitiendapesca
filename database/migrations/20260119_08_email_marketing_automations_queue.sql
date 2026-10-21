-- Email Marketing (Etapa 2): Automatizaciones + Cola + Logs
-- No destructivo: crea tablas si no existen.

CREATE TABLE IF NOT EXISTS email_automations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uk_email_automations_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_automation_steps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  automation_id INT UNSIGNED NOT NULL,
  code VARCHAR(80) NOT NULL,
  name VARCHAR(140) NOT NULL,
  delay_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  subject VARCHAR(160) NOT NULL,
  template VARCHAR(120) NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uk_email_steps_code (automation_id, code),
  KEY idx_email_steps_enabled (is_enabled),
  CONSTRAINT fk_email_steps_automation FOREIGN KEY (automation_id)
    REFERENCES email_automations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dedupe_key CHAR(64) NOT NULL,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  template VARCHAR(120) NOT NULL,
  payload_json JSON NULL,
  unsubscribe_token CHAR(64) NULL,
  subscriber_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  automation_step_id INT UNSIGNED NULL,
  entity_type VARCHAR(20) NULL,
  entity_id BIGINT UNSIGNED NULL,
  status ENUM('queued','sending','sent','failed','cancelled') NOT NULL DEFAULT 'queued',
  scheduled_at DATETIME NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(255) NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,

  UNIQUE KEY uk_email_queue_dedupe (dedupe_key),
  KEY idx_email_queue_status_sched (status, scheduled_at),
  KEY idx_email_queue_to (to_email),
  KEY idx_email_queue_subscriber (subscriber_id),
  KEY idx_email_queue_user (user_id),
  KEY idx_email_queue_step (automation_step_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_send_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  queue_id BIGINT UNSIGNED NULL,
  to_email VARCHAR(190) NOT NULL,
  status ENUM('sent','failed','cancelled') NOT NULL,
  subject VARCHAR(160) NOT NULL,
  template VARCHAR(120) NOT NULL,
  subscriber_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  automation_step_id INT UNSIGNED NULL,
  entity_type VARCHAR(20) NULL,
  entity_id BIGINT UNSIGNED NULL,
  error_text VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY idx_email_logs_created (created_at),
  KEY idx_email_logs_to (to_email),
  KEY idx_email_logs_step (automation_step_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
