-- 20251226_01_product_attribute_values
-- Selección de valores por producto+atributo (para generar variantes con subset)
CREATE TABLE IF NOT EXISTS product_attribute_values (
  product_id INT UNSIGNED NOT NULL,
  attribute_id INT UNSIGNED NOT NULL,
  value_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id, attribute_id, value_id),
  KEY idx_pav_product (product_id),
  KEY idx_pav_attr (attribute_id),
  KEY idx_pav_value (value_id),
  CONSTRAINT fk_pav_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pav_attr FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pav_value FOREIGN KEY (value_id) REFERENCES attribute_values(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
