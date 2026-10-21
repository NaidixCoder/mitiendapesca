-- 20251225_01_order_shipments.sql
-- Envíos / tracking (MVP pre-API)
CREATE TABLE IF NOT EXISTS order_shipments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  carrier VARCHAR(80) NOT NULL DEFAULT 'Correo Argentino',
  service VARCHAR(80) NULL,
  tracking_number VARCHAR(80) NULL,
  tracking_url VARCHAR(255) NULL,
  status ENUM('preparando','despachado','en_transito','en_sucursal','entregado','fallido') NOT NULL DEFAULT 'preparando',
  shipped_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_order_shipments_order (order_id),
  INDEX idx_order_shipments_status (status),
  CONSTRAINT fk_order_shipments_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
