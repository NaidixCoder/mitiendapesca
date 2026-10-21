-- Agrega request_id para correlacionar eventos disparados en el mismo request
-- y permitir agrupación en /admin/audit sin perder granularidad.

ALTER TABLE admin_audit_log
  ADD COLUMN request_id CHAR(32) NULL AFTER user_id,
  ADD INDEX idx_request (request_id);
