-- 2026-01-01: Soporte de imágenes por variante
-- Objetivo: permitir imágenes "generales" (variant_id NULL) y por variante (variant_id = product_variants.id)
-- Nota: si tu DB no soporta IF NOT EXISTS, ejecutá solo si la columna no existe.

ALTER TABLE product_images
  ADD COLUMN variant_id BIGINT UNSIGNED NULL AFTER product_id;

-- Índices para listar/ordenar y resolver portada por scope
CREATE INDEX idx_product_images_scope_sort  ON product_images (product_id, variant_id, sort, id);
CREATE INDEX idx_product_images_scope_cover ON product_images (product_id, variant_id, is_cover, id);

-- Integridad: si se elimina una variante, se eliminan sus imágenes.
ALTER TABLE product_images
  ADD CONSTRAINT fk_product_images_variant
  FOREIGN KEY (variant_id) REFERENCES product_variants(id)
  ON DELETE CASCADE;
