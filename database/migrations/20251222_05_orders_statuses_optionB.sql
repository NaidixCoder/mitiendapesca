-- 20251222_05_orders_statuses_optionB.sql
-- Opción B: estados profesionales para cuando exista pasarela de pago.
-- Convención: valores internos (DB) en inglés, UI siempre en español.

START TRANSACTION;

-- 1) Migrar datos existentes
UPDATE orders SET status = 'pending_payment' WHERE status = 'pending';
UPDATE orders SET status = 'processing' WHERE status = 'confirmed';

-- 2) Ajustar ENUM
ALTER TABLE orders
  MODIFY status ENUM(
    'pending_payment','payment_failed','payment_expired',
    'paid','processing','packed','shipped','delivered',
    'cancelled','refunded','chargeback'
  ) NOT NULL DEFAULT 'pending_payment';

COMMIT;
