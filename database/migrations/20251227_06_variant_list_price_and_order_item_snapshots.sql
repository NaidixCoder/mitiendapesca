-- 2025-12-27
-- Variantes: agrega list_price (precio lista) por variante.
-- Pedidos: prepara snapshot para métricas (unit_list_price / unit_cost) en order_items.
--
-- Nota: si tu DB ya tiene estas columnas, este script puede fallar con "Duplicate column".

START TRANSACTION;

-- 1) Variantes
ALTER TABLE product_variants
  ADD COLUMN list_price INT UNSIGNED NULL AFTER price;

-- 2) Pedidos (snapshot para métricas)
ALTER TABLE order_items
  ADD COLUMN unit_list_price INT UNSIGNED NULL AFTER price,
  ADD COLUMN unit_cost INT UNSIGNED NULL AFTER unit_list_price;

COMMIT;
