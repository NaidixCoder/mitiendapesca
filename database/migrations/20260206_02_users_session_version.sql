-- FIX556 — users.session_version (para forzar cierre de sesión)
-- Regla: al incrementar session_version, cualquier sesión previa queda inválida.
ALTER TABLE users
  ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER role;
