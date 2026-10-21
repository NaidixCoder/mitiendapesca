-- 20260202_04_order_shipment_tracking_status_events.sql
-- FIX518: tracking operativo completo
-- - Agrega tracking_status (estado alimentado por API)
-- - Crea tabla order_shipment_events para persistir eventos normalizados (sin depender de parsear JSON en views)
-- Reglas DB: utf8mb4 + utf8mb4_unicode_ci, índices en FKs.

ALTER TABLE order_shipments
  ADD COLUMN tracking_status VARCHAR(32) NULL AFTER status;

CREATE TABLE IF NOT EXISTS order_shipment_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  external_shipment_id VARCHAR(64) NULL,
  tracking_number VARCHAR(64) NULL,

  event_code VARCHAR(64) NOT NULL,
  event_label VARCHAR(140) NULL,
  event_date_raw VARCHAR(64) NULL,
  event_at DATETIME NULL,
  location VARCHAR(140) NULL,

  raw_json JSON NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_order_shipment_events_dedup (order_id, event_code, event_date_raw),
  KEY idx_order_shipment_events_order_id (order_id),
  KEY idx_order_shipment_events_event_at (event_at),
  KEY idx_order_shipment_events_external_shipment_id (external_shipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
