-- Plantillas de valores para variantes (por categoría / marca)
-- No destructivo: sólo crea tablas si no existen.

CREATE TABLE IF NOT EXISTS variant_value_sets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category_id INT NOT NULL,
  brand_id INT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vvs_cat_brand (category_id, brand_id),
  CONSTRAINT fk_vvs_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  CONSTRAINT fk_vvs_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS variant_value_set_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  set_id INT NOT NULL,
  attribute_id INT NOT NULL,
  value_id INT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_set_attr_value (set_id, attribute_id, value_id),
  INDEX idx_vvsi_set (set_id),
  INDEX idx_vvsi_attr (attribute_id),
  INDEX idx_vvsi_value (value_id),
  CONSTRAINT fk_vvsi_set FOREIGN KEY (set_id) REFERENCES variant_value_sets(id) ON DELETE CASCADE,
  CONSTRAINT fk_vvsi_attr FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
  CONSTRAINT fk_vvsi_value FOREIGN KEY (value_id) REFERENCES attribute_values(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
