-- Order fiscal requests (Factura A solicitada post-compra)
-- Charset/Collation enforced: utf8mb4 / utf8mb4_unicode_ci

CREATE TABLE IF NOT EXISTS order_fiscal_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  requested_type VARCHAR(2) NOT NULL DEFAULT 'A',
  status VARCHAR(16) NOT NULL DEFAULT 'pending', -- pending|invalid|approved|rejected|done
  customer_tax_condition VARCHAR(32) NOT NULL DEFAULT '',
  customer_cuit VARCHAR(16) NOT NULL DEFAULT '',
  customer_legal_name VARCHAR(160) NOT NULL DEFAULT '',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_order_fiscal_request_order (order_id),
  KEY idx_order_fiscal_requests_status_created (status, created_at),
  CONSTRAINT fk_order_fiscal_requests_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
