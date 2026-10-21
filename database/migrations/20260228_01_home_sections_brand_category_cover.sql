-- 2026-02-28: Home sections dynamic data
-- - brands: add sort + cover_image
-- - categories: add cover_image
-- Idempotent migration.

SET @db := DATABASE();

/* ===== brands.sort ===== */
SET @has_brand_sort := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='brands' AND COLUMN_NAME='sort'
);
SET @sql := IF(@has_brand_sort=0,
  'ALTER TABLE brands ADD COLUMN sort INT NOT NULL DEFAULT 0 AFTER seo_text',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* ===== brands.cover_image ===== */
SET @has_brand_cover := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='brands' AND COLUMN_NAME='cover_image'
);
SET @sql := IF(@has_brand_cover=0,
  'ALTER TABLE brands ADD COLUMN cover_image VARCHAR(255) NULL AFTER sort',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* ===== categories.cover_image ===== */
SET @has_cat_cover := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='cover_image'
);
SET @sql := IF(@has_cat_cover=0,
  'ALTER TABLE categories ADD COLUMN cover_image VARCHAR(255) NULL AFTER seo_text',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

