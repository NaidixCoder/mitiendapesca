-- 20251222_04_orders_statuses.sql
-- Extiende estados de pedidos (no destructivo)

ALTER TABLE orders
  MODIFY status ENUM(
    'pending',
    'confirmed',
    'paid',
    'packed',
    'shipped',
    'delivered',
    'cancelled',
    'refunded'
  ) NOT NULL DEFAULT 'pending';

-- Nota: si tu MySQL no permite modificar ENUM con registros existentes,
-- revisa primero qué valores hay en orders.status.
