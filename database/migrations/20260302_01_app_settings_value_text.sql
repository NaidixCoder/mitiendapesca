-- app_settings: permitir valores largos (JSON, etc.)
-- Motivo: home_sections_config y otros settings estructurados pueden superar 255 chars.

ALTER TABLE app_settings
  MODIFY setting_value TEXT NOT NULL;

