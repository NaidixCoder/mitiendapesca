-- 2026-01-04: SEO PRO (Lote 1) — Core DB
-- Incluye:
-- 1) Campos SEO básicos en categories/brands (meta_title/meta_description + seo_text)
-- 2) Tabla seo_settings (canonical/robots/social/schema override) por entidad
-- 3) Tablas de redirecciones y historial de slugs
--
-- Nota: este script es "seguro" para re-ejecutar (usa checks en information_schema).

/* ====== Helpers para ALTER idempotente ====== */
SET @db := DATABASE();

/* ====== categories: meta_title / meta_description / seo_text ====== */
SET @has_cat_meta_title := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='meta_title'
);
SET @sql := IF(@has_cat_meta_title=0,
  'ALTER TABLE categories ADD COLUMN meta_title VARCHAR(70) NULL AFTER slug',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_cat_meta_desc := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='meta_description'
);
SET @sql := IF(@has_cat_meta_desc=0,
  'ALTER TABLE categories ADD COLUMN meta_description VARCHAR(160) NULL AFTER meta_title',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_cat_seo_text := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='seo_text'
);
SET @sql := IF(@has_cat_seo_text=0,
  'ALTER TABLE categories ADD COLUMN seo_text MEDIUMTEXT NULL AFTER meta_description',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* ====== brands: meta_title / meta_description / seo_text ====== */
SET @has_brand_meta_title := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='brands' AND COLUMN_NAME='meta_title'
);
SET @sql := IF(@has_brand_meta_title=0,
  'ALTER TABLE brands ADD COLUMN meta_title VARCHAR(70) NULL AFTER slug',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_brand_meta_desc := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='brands' AND COLUMN_NAME='meta_description'
);
SET @sql := IF(@has_brand_meta_desc=0,
  'ALTER TABLE brands ADD COLUMN meta_description VARCHAR(160) NULL AFTER meta_title',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_brand_seo_text := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='brands' AND COLUMN_NAME='seo_text'
);
SET @sql := IF(@has_brand_seo_text=0,
  'ALTER TABLE brands ADD COLUMN seo_text MEDIUMTEXT NULL AFTER meta_description',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* ====== seo_settings (avanzado por entidad) ====== */
CREATE TABLE IF NOT EXISTS seo_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('product','category','brand','page') NOT NULL,
  entity_id INT UNSIGNED NOT NULL,

  canonical_url VARCHAR(255) NULL,
  robots_index TINYINT(1) NOT NULL DEFAULT 1,
  robots_follow TINYINT(1) NOT NULL DEFAULT 1,

  og_title VARCHAR(255) NULL,
  og_description VARCHAR(500) NULL,
  og_image_path VARCHAR(255) NULL,

  twitter_card ENUM('summary','summary_large_image') NOT NULL DEFAULT 'summary_large_image',
  twitter_title VARCHAR(255) NULL,
  twitter_description VARCHAR(500) NULL,
  twitter_image_path VARCHAR(255) NULL,

  schema_override JSON NULL,

  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_seo_settings_entity (entity_type, entity_id),
  INDEX idx_seo_settings_entity_type (entity_type),
  INDEX idx_seo_settings_entity_id (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ====== seo_redirects (301/302) ====== */
CREATE TABLE IF NOT EXISTS seo_redirects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_path VARCHAR(255) NOT NULL,
  to_path VARCHAR(255) NOT NULL,
  status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  hits INT UNSIGNED NOT NULL DEFAULT 0,
  last_hit_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_seo_redirects_from (from_path),
  INDEX idx_seo_redirects_to (to_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ====== seo_slug_history (histórico de slugs por entidad) ====== */
CREATE TABLE IF NOT EXISTS seo_slug_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('product','category','brand','page') NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  slug VARCHAR(200) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_seo_slug_history_unique (entity_type, slug),
  INDEX idx_seo_slug_history_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
