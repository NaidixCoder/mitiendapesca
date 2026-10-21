-- ========= SEO PRO (Lote 10A) =========
-- Bloques de contenido para rich snippets (Bullets / Specs / FAQ) por CATEGORÍA

CREATE TABLE IF NOT EXISTS category_seo_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,

  bullets_json JSON NULL,
  specs_json   JSON NULL,
  faq_json     JSON NULL,

  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_category_seo_blocks_category (category_id),
  CONSTRAINT fk_category_seo_blocks_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
