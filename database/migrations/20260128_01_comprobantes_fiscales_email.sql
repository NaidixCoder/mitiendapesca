-- 20260128_01_comprobantes_fiscales_email.sql
-- Tracking de envío por email (PDF adjunto) para comprobantes.
-- Seguro para correr más de una vez (checks en information_schema).

-- email_to
SET @col_to := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND column_name = 'email_to'
);
SET @sql1 := IF(@col_to = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN email_to VARCHAR(255) NULL AFTER response_json",
  "SELECT 1"
);
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

-- email_sent_at
SET @col_sent := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND column_name = 'email_sent_at'
);
SET @sql2 := IF(@col_sent = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN email_sent_at DATETIME NULL AFTER email_to",
  "SELECT 1"
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- email_error
SET @col_err := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'comprobantes_fiscales'
    AND column_name = 'email_error'
);
SET @sql3 := IF(@col_err = 0,
  "ALTER TABLE comprobantes_fiscales ADD COLUMN email_error VARCHAR(255) NULL AFTER email_sent_at",
  "SELECT 1"
);
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;
