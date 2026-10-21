-- 2025-12-20 Terms/Privacy acceptance on registration
-- Objetivo: persistir aceptación legal en users.

ALTER TABLE users
  ADD COLUMN terms_accepted_at DATETIME NULL AFTER email_verified_at,
  ADD COLUMN terms_version     VARCHAR(32) NULL AFTER terms_accepted_at,
  ADD COLUMN privacy_version   VARCHAR(32) NULL AFTER terms_version;
