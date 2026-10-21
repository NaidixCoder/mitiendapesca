-- 20251225_04_variants_core.sql
-- Variantes V1 (catálogo de atributos + variantes por producto + stock por variante)
-- Objetivo: preparar la base sin tocar UI ni checkout.

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS attributes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  type ENUM('select','text','number') NOT NULL DEFAULT 'select',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_attributes_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attribute_values (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attribute_id INT UNSIGNED NOT NULL,
  value VARCHAR(120) NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_attr_value (attribute_id, value),
  KEY idx_attr_values_attr (attribute_id),
  CONSTRAINT fk_attr_values_attr FOREIGN KEY (attribute_id)
    REFERENCES attributes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_attributes (
  product_id INT UNSIGNED NOT NULL,
  attribute_id INT UNSIGNED NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id, attribute_id),
  KEY idx_pa_attr (attribute_id),
  CONSTRAINT fk_pa_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_attr FOREIGN KEY (attribute_id)
    REFERENCES attributes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  sku VARCHAR(80) NULL,
  barcode VARCHAR(80) NULL,
  title VARCHAR(255) NULL,
  price INT UNSIGNED NULL, -- si NULL usa products.price
  cost INT UNSIGNED NULL,  -- si NULL usa products.cost (si existe)
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_pv_product (product_id),
  KEY idx_pv_active (product_id, is_active),
  UNIQUE KEY uk_pv_product_sku (product_id, sku),
  CONSTRAINT fk_pv_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variant_values (
  variant_id INT UNSIGNED NOT NULL,
  attribute_id INT UNSIGNED NOT NULL,
  value_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (variant_id, attribute_id),
  KEY idx_pvv_value (value_id),
  CONSTRAINT fk_pvv_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE,
  CONSTRAINT fk_pvv_attr FOREIGN KEY (attribute_id)
    REFERENCES attributes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_pvv_value FOREIGN KEY (value_id)
    REFERENCES attribute_values(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_variants (
  variant_id INT UNSIGNED PRIMARY KEY,
  stock INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 0,
  updated_at DATETIME NULL,
  CONSTRAINT fk_inv_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
