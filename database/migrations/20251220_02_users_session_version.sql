-- users.session_version
-- Revocación global de sesiones (portable) mediante versionado.

ALTER TABLE users
  ADD COLUMN session_version INT NOT NULL DEFAULT 1 AFTER last_login_at;
