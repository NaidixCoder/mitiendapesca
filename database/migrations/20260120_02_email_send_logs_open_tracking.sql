-- =====================================================
-- Email Marketing (Etapa 4): Open tracking
-- - Open tracking: guardamos token y opened_at en email_send_logs
-- =====================================================

ALTER TABLE email_send_logs
  ADD COLUMN open_token CHAR(64) NULL AFTER error_text,
  ADD COLUMN opened_at DATETIME NULL AFTER open_token,
  ADD UNIQUE KEY uq_email_send_logs_open_token (open_token),
  ADD KEY idx_email_send_logs_opened_at (opened_at);
