-- ==========================================================
-- Comprobantes fiscales (ARCA/AFIP)
--
-- Nota:
-- - Se crea como tabla separada para no “ensuciar” orders.
-- - La emisión puede ser manual (admin) o automática (post pago) más adelante.
-- - Guardamos request/response para auditoría y debugging.
-- ==========================================================

CREATE TABLE IF NOT EXISTS comprobantes_fiscales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','issued','error') NOT NULL DEFAULT 'pending',

  -- ARCA/WSFE
  arca_env ENUM('homologacion','produccion') NOT NULL DEFAULT 'homologacion',
  pto_vta INT UNSIGNED NOT NULL,
  cbte_tipo INT UNSIGNED NOT NULL,
  cbte_nro INT UNSIGNED NULL,
  concepto INT UNSIGNED NOT NULL DEFAULT 1,
  doc_tipo INT UNSIGNED NOT NULL,
  doc_nro BIGINT UNSIGNED NOT NULL,
  imp_total INT UNSIGNED NOT NULL,
  imp_neto INT UNSIGNED NOT NULL,
  imp_iva INT UNSIGNED NOT NULL,
  moneda_id VARCHAR(3) NOT NULL DEFAULT 'PES',
  moneda_ctz DECIMAL(10,4) NOT NULL DEFAULT 1.0000,

  cae VARCHAR(32) NULL,
  cae_vto VARCHAR(16) NULL,
  resultado VARCHAR(4) NULL,
  observaciones_json JSON NULL,

  request_json JSON NULL,
  response_json JSON NULL,
  error_code VARCHAR(32) NULL,
  error_message VARCHAR(255) NULL,

  issued_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_comprobantes_fiscales_order (order_id),
  KEY idx_comprobantes_fiscales_status_created (status, created_at),
  KEY idx_comprobantes_fiscales_cbte (pto_vta, cbte_tipo, cbte_nro),
  CONSTRAINT fk_comprobantes_fiscales_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
