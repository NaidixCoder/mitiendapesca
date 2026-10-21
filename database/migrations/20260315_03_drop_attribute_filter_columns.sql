SET @db_name := DATABASE();

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'attributes'
    AND COLUMN_NAME = 'is_filterable'
);
SET @sql := IF(@col_exists > 0, 'ALTER TABLE attributes DROP COLUMN is_filterable', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'attributes'
    AND INDEX_NAME = 'idx_attributes_filterable_sort'
);
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE attributes DROP INDEX idx_attributes_filterable_sort', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'attributes'
    AND COLUMN_NAME = 'is_public'
);
SET @sql := IF(@col_exists > 0, 'ALTER TABLE attributes DROP COLUMN is_public', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'attributes'
    AND COLUMN_NAME = 'facet_sort'
);
SET @sql := IF(@col_exists > 0, 'ALTER TABLE attributes DROP COLUMN facet_sort', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'attributes'
    AND COLUMN_NAME = 'group_name'
);
SET @sql := IF(@col_exists > 0, 'ALTER TABLE attributes DROP COLUMN group_name', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
