-- 20260204_01_import_jobs_revert_v2.sql
-- FIX551: Import Revert v2 (persistir estado de revert en import_jobs)
-- Idempotente (usa INFORMATION_SCHEMA) y compatible MySQL/MariaDB.

-- 1) revert_status
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'import_jobs'
    AND COLUMN_NAME = 'revert_status'
);
SET @sql := IF(@col = 0,
  "ALTER TABLE import_jobs ADD COLUMN revert_status VARCHAR(32) NOT NULL DEFAULT 'not_reverted' AFTER status",
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) reverted_at_utc
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'import_jobs'
    AND COLUMN_NAME = 'reverted_at_utc'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE import_jobs ADD COLUMN reverted_at_utc DATETIME NULL AFTER finished_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3) reverted_by_user_id
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'import_jobs'
    AND COLUMN_NAME = 'reverted_by_user_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE import_jobs ADD COLUMN reverted_by_user_id BIGINT UNSIGNED NULL AFTER reverted_at_utc',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) revert_summary_json (LONGTEXT para compatibilidad)
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'import_jobs'
    AND COLUMN_NAME = 'revert_summary_json'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE import_jobs ADD COLUMN revert_summary_json LONGTEXT NULL AFTER reverted_by_user_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5) Índices (idempotentes)
SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'import_jobs'
    AND INDEX_NAME = 'idx_import_jobs_reverted_at'
);
SET @sql := IF(@idx = 0,
  'CREATE INDEX idx_import_jobs_reverted_at ON import_jobs (reverted_at_utc)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'import_jobs'
    AND INDEX_NAME = 'idx_import_jobs_revert_status'
);
SET @sql := IF(@idx = 0,
  'CREATE INDEX idx_import_jobs_revert_status ON import_jobs (revert_status)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
