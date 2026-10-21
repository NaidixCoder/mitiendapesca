-- Fix de consistencia: variant_id no puede ser NULL (evita duplicados en UNIQUE con NULL)
-- y limpieza de duplicados existentes.
-- Seguro para MySQL/MariaDB.

START TRANSACTION;

-- Normalizar NULL -> 0
UPDATE cart_items SET variant_id = 0 WHERE variant_id IS NULL;

-- Eliminar duplicados dejando el menor id por (cart_id, product_id, variant_id)
DELETE ci FROM cart_items ci
JOIN cart_items ci2
  ON ci.cart_id = ci2.cart_id
 AND ci.product_id = ci2.product_id
 AND ci.variant_id = ci2.variant_id
 AND ci.id > ci2.id;

-- Asegurar NOT NULL + DEFAULT 0
ALTER TABLE cart_items
  MODIFY variant_id INT NOT NULL DEFAULT 0;

COMMIT;
