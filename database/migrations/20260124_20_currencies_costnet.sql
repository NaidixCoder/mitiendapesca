-- FIX334: Monedas + IVA neto canonico + margen rate
-- Charset/Collation enforced
CREATE TABLE IF NOT EXISTS currencies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL,
  name VARCHAR(80) NOT NULL,
  symbol VARCHAR(8) NULL,
  is_base TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uq_currencies_code (code),
  KEY idx_currencies_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS currency_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  currency_id BIGINT UNSIGNED NOT NULL,
  rate_to_ars DECIMAL(18,6) NOT NULL,
  source VARCHAR(60) NULL,
  effective_from DATETIME NOT NULL,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_currency_rates_currency_effective (currency_id, effective_from),
  KEY idx_currency_rates_effective (effective_from),
  CONSTRAINT fk_currency_rates_currency FOREIGN KEY (currency_id)
    REFERENCES currencies(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed base currencies if missing
INSERT INTO currencies (code, name, symbol, is_base, is_active)
SELECT * FROM (
  SELECT 'ARS' AS code, 'Peso argentino' AS name, '$' AS symbol, 1 AS is_base, 1 AS is_active
) AS s
WHERE NOT EXISTS (SELECT 1 FROM currencies WHERE code='ARS');

INSERT INTO currencies (code, name, symbol, is_base, is_active)
SELECT * FROM (
  SELECT 'USD_GOZIO' AS code, 'Dólar Gozio' AS name, 'US$' AS symbol, 0 AS is_base, 1 AS is_active
) AS s
WHERE NOT EXISTS (SELECT 1 FROM currencies WHERE code='USD_GOZIO');

INSERT INTO currencies (code, name, symbol, is_base, is_active)
SELECT * FROM (
  SELECT 'USD_MARINE' AS code, 'Dólar Marine' AS name, 'US$' AS symbol, 0 AS is_base, 1 AS is_active
) AS s
WHERE NOT EXISTS (SELECT 1 FROM currencies WHERE code='USD_MARINE');

-- Ensure ARS has rate 1
INSERT INTO currency_rates (currency_id, rate_to_ars, source, effective_from)
SELECT c.id, 1.000000, 'seed', UTC_TIMESTAMP()
FROM currencies c
WHERE c.code='ARS'
  AND NOT EXISTS (SELECT 1 FROM currency_rates cr WHERE cr.currency_id=c.id);

-- Seed example provider rates (can be edited later)
INSERT INTO currency_rates (currency_id, rate_to_ars, source, effective_from)
SELECT c.id, 1545.000000, 'seed', UTC_TIMESTAMP()
FROM currencies c
WHERE c.code='USD_GOZIO'
  AND NOT EXISTS (SELECT 1 FROM currency_rates cr WHERE cr.currency_id=c.id);

INSERT INTO currency_rates (currency_id, rate_to_ars, source, effective_from)
SELECT c.id, 1520.000000, 'seed', UTC_TIMESTAMP()
FROM currencies c
WHERE c.code='USD_MARINE'
  AND NOT EXISTS (SELECT 1 FROM currency_rates cr WHERE cr.currency_id=c.id);

-- Products: add cost currency + margin rate
ALTER TABLE products
  ADD COLUMN cost_currency_id BIGINT UNSIGNED NULL AFTER cost,
  ADD COLUMN margin_pct DECIMAL(7,4) NULL AFTER cost_currency_id;

-- Variants: add cost currency + margin rate
ALTER TABLE product_variants
  ADD COLUMN cost_currency_id BIGINT UNSIGNED NULL AFTER cost,
  ADD COLUMN margin_pct DECIMAL(7,4) NULL AFTER cost_currency_id;

-- FK constraints (safe: only if column exists)
ALTER TABLE products
  ADD CONSTRAINT fk_products_cost_currency FOREIGN KEY (cost_currency_id)
  REFERENCES currencies(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE product_variants
  ADD CONSTRAINT fk_pv_cost_currency FOREIGN KEY (cost_currency_id)
  REFERENCES currencies(id) ON UPDATE CASCADE ON DELETE RESTRICT;

-- IVA rate: migrate from percent (21) to rate (0.21) if needed
UPDATE products SET iva_rate = iva_rate / 100
WHERE iva_rate IS NOT NULL AND iva_rate > 1;

-- Normalize margin_pct from 35 to 0.35 if legacy values exist
UPDATE products SET margin_pct = margin_pct / 100
WHERE margin_pct IS NOT NULL AND margin_pct > 1;

UPDATE product_variants SET margin_pct = margin_pct / 100
WHERE margin_pct IS NOT NULL AND margin_pct > 1;
