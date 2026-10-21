-- ========= SEO PRO (Lote 10A) =========
-- Bloques de contenido para rich snippets (Bullets / Specs / FAQ) por MARCA

CREATE TABLE IF NOT EXISTS brand_seo_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand_id INT UNSIGNED NOT NULL,

  bullets_json JSON NULL,
  specs_json   JSON NULL,
  faq_json     JSON NULL,

  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_brand_seo_blocks_brand (brand_id),
  CONSTRAINT fk_brand_seo_blocks_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
