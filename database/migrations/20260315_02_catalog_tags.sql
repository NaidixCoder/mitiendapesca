CREATE TABLE IF NOT EXISTS catalog_tag_groups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_catalog_tag_groups_slug (slug),
  UNIQUE KEY uq_catalog_tag_groups_name (name),
  KEY idx_catalog_tag_groups_active_sort (is_active, sort, name),
  KEY idx_catalog_tag_groups_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_catalog_tags_group_slug (group_id, slug),
  UNIQUE KEY uq_catalog_tags_group_name (group_id, name),
  KEY idx_catalog_tags_group_sort (group_id, sort, name),
  KEY idx_catalog_tags_active_sort (is_active, sort, name),
  CONSTRAINT fk_catalog_tags_group FOREIGN KEY (group_id)
    REFERENCES catalog_tag_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_catalog_tags (
  product_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id, tag_id),
  KEY idx_product_catalog_tags_tag_id (tag_id),
  KEY idx_product_catalog_tags_created_at (created_at),
  CONSTRAINT fk_product_catalog_tags_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_product_catalog_tags_tag FOREIGN KEY (tag_id)
    REFERENCES catalog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS variant_catalog_tags (
  variant_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (variant_id, tag_id),
  KEY idx_variant_catalog_tags_tag_id (tag_id),
  KEY idx_variant_catalog_tags_created_at (created_at),
  CONSTRAINT fk_variant_catalog_tags_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE,
  CONSTRAINT fk_variant_catalog_tags_tag FOREIGN KEY (tag_id)
    REFERENCES catalog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
