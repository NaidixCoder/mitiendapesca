-- FIX556 Hotfix — users.last_activity_at (estado Online/Offline en Admin)
-- Objetivo: registrar actividad reciente de usuarios autenticados para mostrar Online/Offline.
SET @has_col := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'last_activity_at'
);

SET @sql := IF(@has_col = 0,
  'ALTER TABLE users ADD COLUMN last_activity_at DATETIME NULL AFTER last_login_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'idx_users_last_activity_at'
);

SET @sql2 := IF(@has_idx = 0,
  'CREATE INDEX idx_users_last_activity_at ON users (last_activity_at)',
  'SELECT 1'
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;
