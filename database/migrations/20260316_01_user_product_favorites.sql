CREATE TABLE IF NOT EXISTS user_product_favorites (
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  KEY idx_user_product_favorites_product_id (product_id),
  KEY idx_user_product_favorites_created_at (created_at),
  CONSTRAINT fk_user_product_favorites_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_product_favorites_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
