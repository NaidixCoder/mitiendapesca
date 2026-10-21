-- ========= SEO PRO (Lote 7) =========
-- Bloques de contenido para rich snippets (Bullets / Specs / FAQ) por producto

CREATE TABLE IF NOT EXISTS product_seo_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,

  bullets_json JSON NULL,
  specs_json   JSON NULL,
  faq_json     JSON NULL,

  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_product_seo_blocks_product (product_id),
  CONSTRAINT fk_product_seo_blocks_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
