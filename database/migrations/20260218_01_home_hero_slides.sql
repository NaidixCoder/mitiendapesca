CREATE TABLE IF NOT EXISTS home_hero_slides (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  title VARCHAR(120) NOT NULL DEFAULT '',
  alt_text VARCHAR(255) NOT NULL DEFAULT '',
  image_desktop VARCHAR(255) NOT NULL,
  image_mobile VARCHAR(255) NOT NULL DEFAULT '',
  target_url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  starts_at DATETIME NULL DEFAULT NULL,
  ends_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_home_hero_active_sort (is_active, sort_order, id),
  KEY idx_home_hero_schedule (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
