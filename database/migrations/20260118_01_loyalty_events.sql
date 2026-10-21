-- ===== LOYALTY EVENTS (ledger / cuenta corriente de puntos) =====
-- Motivo: poder mostrar "movimientos de puntos" en /cuenta y tener base para beneficios reales.

CREATE TABLE IF NOT EXISTS loyalty_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NULL,

  -- Clave de deduplicación para evitar duplicados por reintentos.
  -- Ej: snapshot:user:1 | order_paid:order:123 | adjustment:manual:abc
  dedupe_key VARCHAR(80) NOT NULL,

  -- Tipo de evento (semántico)
  event_key VARCHAR(40) NOT NULL,

  -- Delta de puntos (puede ser negativo)
  points_delta INT NOT NULL,

  -- Referencia opcional al total del pedido (para auditoría/UX)
  amount_total INT UNSIGNED NULL,

  note VARCHAR(255) NULL,
  meta TEXT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uniq_loyalty_events_dedupe (dedupe_key),
  KEY idx_loyalty_events_user_date (user_id, created_at),
  KEY idx_loyalty_events_order (order_id),
  CONSTRAINT fk_loyalty_events_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
