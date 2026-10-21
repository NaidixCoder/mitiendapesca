-- 20260201_03_order_shipments_correo_fields.sql
-- Agrega campos mínimos para integración Correo Argentino (MiCorreo / PAQ.AR)
-- Charset/Collation: respetamos el default de la DB. No tocar si ya está en utf8mb4.

ALTER TABLE order_shipments
  ADD COLUMN external_shipment_id VARCHAR(64) NULL AFTER tracking_url,
  ADD COLUMN tracking_events_json JSON NULL AFTER external_shipment_id,
  ADD COLUMN last_tracked_at DATETIME NULL AFTER tracking_events_json,
  ADD COLUMN last_error VARCHAR(255) NULL AFTER last_tracked_at;

CREATE INDEX idx_order_shipments_external_shipment_id ON order_shipments (external_shipment_id);
