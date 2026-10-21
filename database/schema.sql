-- FIXED: variant_value_sets FKs use UNSIGNED columns to match referenced tables
-- Tienda Pesca — CORE schema (completo)
SET NAMES utf8mb4;
SET time_zone = "+00:00";

-- ===== BRANDS =====
CREATE TABLE IF NOT EXISTS brands (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  meta_title VARCHAR(70) NULL,
  meta_description VARCHAR(160) NULL,
  seo_text MEDIUMTEXT NULL,
  sort INT NOT NULL DEFAULT 0,
  cover_image VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_brands_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== CATEGORIES =====
CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  meta_title VARCHAR(70) NULL,
  meta_description VARCHAR(160) NULL,
  seo_text MEDIUMTEXT NULL,
  cover_image VARCHAR(255) NULL,
  sort INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id)
    REFERENCES categories(id) ON DELETE SET NULL,
  KEY idx_categories_created_at (created_at),
  KEY idx_categories_parent_id (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCTS =====
CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  internal_code VARCHAR(32) NULL UNIQUE,
  sku VARCHAR(80) NULL UNIQUE,
  slug VARCHAR(160) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  brand_id BIGINT UNSIGNED NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  sale_mode ENUM('simple','variants') NOT NULL DEFAULT 'simple',
  short_desc VARCHAR(500) NULL,
  long_desc MEDIUMTEXT NULL,
  highlight_tokens_json TEXT NULL,
  iva_rate DECIMAL(6,4) NULL,
  price INT UNSIGNED NULL,
  list_price INT UNSIGNED NULL,
  price_updated_at DATETIME NULL,
  cost INT UNSIGNED NULL,
  ship_weight INT UNSIGNED NULL,
  ship_height INT UNSIGNED NULL,
  ship_width INT UNSIGNED NULL,
  ship_length INT UNSIGNED NULL,
  cost_currency_id BIGINT UNSIGNED NULL,
  margin_pct DECIMAL(7,4) NULL,
  price_from_cache INT UNSIGNED NULL,
  list_price_from_cache INT UNSIGNED NULL,
  barcode VARCHAR(64) NULL,
  rating DECIMAL(2,1) DEFAULT 0.0,
  reviews INT UNSIGNED DEFAULT 0,
  is_new TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  meta_title VARCHAR(70) NULL,
  meta_description VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_cat_price (category_id, price),
  INDEX idx_cat_price_from (category_id, price_from_cache),
  INDEX idx_brand (brand_id),
  INDEX idx_products_cost_currency (cost_currency_id),
  INDEX idx_created (created_at),
  INDEX idx_products_active (is_active),
  FULLTEXT KEY ft_name_desc (name, short_desc, long_desc),

  CONSTRAINT fk_prod_brand FOREIGN KEY (brand_id)
    REFERENCES brands(id) ON DELETE SET NULL,
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCT SPECS =====
CREATE TABLE IF NOT EXISTS product_specs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  internal_code VARCHAR(32) NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  spec_key VARCHAR(120) NOT NULL,
  spec_value VARCHAR(255) NOT NULL,
  sort INT DEFAULT 0,
  CONSTRAINT fk_spec_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_spec_prod (product_id, sort),
  INDEX idx_spec_key (spec_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== INVENTORY =====
CREATE TABLE IF NOT EXISTS inventory (
  product_id BIGINT UNSIGNED PRIMARY KEY,
  stock INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 3,
  CONSTRAINT fk_inv_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  KEY idx_inventory_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_inventory_stock ON inventory (stock, low_stock_threshold);

-- ===== USERS =====
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NULL,
  password_changed_at DATETIME NULL,
  oauth_provider VARCHAR(30)  NULL,
  oauth_sub      VARCHAR(64)  NULL,
  email_verified TINYINT(1)   NOT NULL DEFAULT 0,
  email_verified_at DATETIME NULL,

  -- Legal (términos y privacidad)
  terms_accepted_at DATETIME NULL,
  terms_version     VARCHAR(32) NULL,
  privacy_version   VARCHAR(32) NULL,

  marketing_campaign_id BIGINT UNSIGNED NULL,
  avatar_url     VARCHAR(255) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL,
  last_login_at  DATETIME NULL,
  last_activity_at DATETIME NULL,
  session_version INT NOT NULL DEFAULT 1,
  role ENUM('admin','customer','mayorista1','mayorista2') NOT NULL DEFAULT 'customer',
  UNIQUE KEY uq_email (email),
  UNIQUE KEY uq_oauth (oauth_provider, oauth_sub),
  INDEX idx_login (email, oauth_provider, oauth_sub),
  INDEX idx_users_role (role),
  INDEX idx_users_created (created_at),
  INDEX idx_users_marketing_campaign_created (marketing_campaign_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_product_favorites (
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  KEY idx_user_product_favorites_product_id (product_id),
  KEY idx_user_product_favorites_created_at (created_at),
  CONSTRAINT fk_user_product_favorites_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_product_favorites_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== USER PROFILE (datos opcionales) =====
-- Separamos datos personales (teléfono, nacimiento, opt-ins) del auth.
CREATE TABLE IF NOT EXISTS user_profiles (
  user_id BIGINT UNSIGNED NOT NULL,
  phone VARCHAR(30) NULL,
  birth_date DATE NULL,
  marketing_email_opt_in TINYINT(1) NOT NULL DEFAULT 0,
  marketing_whatsapp_opt_in TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,

  PRIMARY KEY (user_id),
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_user_profiles_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== USER FISCAL PROFILES (CUIT + condición IVA, con validación padrón) =====
CREATE TABLE IF NOT EXISTS user_fiscal_profiles (
  user_id BIGINT UNSIGNED NOT NULL,
  tax_condition VARCHAR(32) NOT NULL DEFAULT 'consumidor_final',
  cuit VARCHAR(16) NOT NULL DEFAULT '',
  legal_name VARCHAR(160) NOT NULL DEFAULT '',
  validation_status ENUM('unverified','valid','invalid','pending') NOT NULL DEFAULT 'unverified',
  validation_error VARCHAR(255) NULL,
  validated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id),
  CONSTRAINT fk_user_fiscal_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_user_fiscal_status (validation_status),
  KEY idx_user_fiscal_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== USER ADDRESSES =====
CREATE TABLE IF NOT EXISTS user_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  label      VARCHAR(60)  NULL,
  full_name  VARCHAR(120) NOT NULL,
  phone      VARCHAR(40)  NULL,
  street     VARCHAR(160) NOT NULL,
  number     VARCHAR(20)  NOT NULL,
  floor      VARCHAR(20)  NULL,
  apartment  VARCHAR(20)  NULL,
  city       VARCHAR(80)  NOT NULL,
  state      VARCHAR(80)  NOT NULL,
  zip        VARCHAR(20)  NOT NULL,
  notes      VARCHAR(255) NULL,
  is_default TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,

  INDEX idx_addr_user (user_id),
  INDEX idx_addr_user_default (user_id, is_default),
  CONSTRAINT fk_addr_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_user_addresses_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== EMAIL VERIFICATIONS =====
CREATE TABLE IF NOT EXISTS email_verifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,

  UNIQUE KEY uq_ev_token (token),
  UNIQUE KEY uq_ev_user (user_id),

  INDEX idx_ev_expires (expires_at),
  INDEX idx_ev_used (used_at),

  CONSTRAINT fk_ev_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  KEY idx_email_verifications_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== USER TOKENS (remember me) =====
CREATE TABLE IF NOT EXISTS user_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  selector CHAR(22) NOT NULL UNIQUE,
  token_hash CHAR(44) NOT NULL,
  expires_at DATETIME NOT NULL,
  last_used_at DATETIME DEFAULT NULL,
  ip VARCHAR(45) NULL,
  ua VARCHAR(200) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_expires (user_id, expires_at),
  CONSTRAINT fk_ut_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_user_tokens_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Admin Audit Log =====


-- ===== CUSTOMER STATS (agregados por usuario para performance + fidelidad) =====
-- ===== CUSTOMER STATS (agregados por usuario para performance + fidelidad) =====
CREATE TABLE IF NOT EXISTS customer_stats (
  user_id BIGINT UNSIGNED NOT NULL,

  paid_orders_count INT UNSIGNED NOT NULL DEFAULT 0,
  paid_total INT UNSIGNED NOT NULL DEFAULT 0,
  first_paid_at DATETIME NULL,
  last_paid_at DATETIME NULL,
  avg_paid_total INT UNSIGNED NOT NULL DEFAULT 0,

  loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
  loyalty_tier VARCHAR(20) NOT NULL DEFAULT 'Bronce',

  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id),
  INDEX idx_customer_stats_tier (loyalty_tier),
  INDEX idx_customer_stats_paid_total (paid_total),
  CONSTRAINT fk_customer_stats_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  request_id CHAR(32) NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  metadata JSON NULL,
  ip VARBINARY(16) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_request (request_id),
  INDEX idx_action (action),
  INDEX idx_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_admin_audit_log_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Auth Login Metrics =====
-- Métricas de login (éxitos/fallos) sin guardar email en claro.
CREATE TABLE IF NOT EXISTS auth_login_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event VARCHAR(32) NOT NULL,     -- login_success | login_failed | logout
  method VARCHAR(32) NOT NULL,    -- password | google | magic_link (futuro)
  reason VARCHAR(64) NULL,        -- invalid_credentials | rate_limited | ...
  user_id BIGINT UNSIGNED NULL,
  email_hash CHAR(64) NULL,       -- sha256(email_normalizado)
  ip VARCHAR(64) NULL,
  ua_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_created_at (created_at),
  INDEX idx_event_method (event, method),
  INDEX idx_user_created (user_id, created_at),
  INDEX idx_email_hash_created (email_hash, created_at),
  INDEX idx_ip_created (ip, created_at),
  CONSTRAINT fk_auth_login_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== Import jobs =====
CREATE TABLE IF NOT EXISTS import_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_type   VARCHAR(50) NOT NULL,
  status     VARCHAR(20) NOT NULL DEFAULT 'queued',
  revert_status VARCHAR(32) NOT NULL DEFAULT 'not_reverted',
  file_name  VARCHAR(255) NULL,
  file_path  VARCHAR(255) NULL,
  total_rows INT NULL,
  ok_rows    INT NULL,
  err_rows   INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  reverted_at_utc DATETIME NULL,
  reverted_by_user_id BIGINT UNSIGNED NULL,
  revert_summary_json LONGTEXT NULL,
  KEY idx_import_jobs_created_at (created_at),
  KEY idx_import_jobs_reverted_at (reverted_at_utc),
  KEY idx_import_jobs_revert_status (revert_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Orders =====
CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  status ENUM('pending_payment','payment_failed','payment_expired','paid','processing','packed','shipped','delivered','cancelled','refunded','chargeback') NOT NULL DEFAULT 'processing',
  payment_method VARCHAR(32) NULL,
  marketing_campaign_id BIGINT UNSIGNED NULL,
  email_campaign_id BIGINT UNSIGNED NULL,
  checkout_token VARCHAR(64) NULL,
  subtotal INT UNSIGNED NOT NULL,
  shipping INT UNSIGNED NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL,

  -- snapshot fiscal (listo para Monotributo y RI)
  seller_tax_regime VARCHAR(32) NULL,
  seller_cuit VARCHAR(32) NULL,
  seller_business_name VARCHAR(160) NULL,
  seller_fiscal_address VARCHAR(200) NULL,
  customer_tax_condition VARCHAR(32) NULL,
  customer_cuit VARCHAR(32) NULL,
  currency_code VARCHAR(16) NULL,
  total_net INT UNSIGNED NULL,
  total_vat INT UNSIGNED NULL,

  -- snapshot de dirección usada en checkout
  address_id BIGINT UNSIGNED NULL,
  shipping_full_name VARCHAR(120) NULL,
  shipping_phone VARCHAR(40) NULL,
  shipping_street VARCHAR(160) NULL,
  shipping_number VARCHAR(20) NULL,
  shipping_floor VARCHAR(20) NULL,
  shipping_apartment VARCHAR(20) NULL,
  shipping_city VARCHAR(80) NULL,
  shipping_state VARCHAR(80) NULL,
  shipping_zip VARCHAR(20) NULL,
  shipping_notes VARCHAR(255) NULL,
  shipping_address_json JSON NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status_created (status, created_at),
  INDEX idx_orders_campaign_created (marketing_campaign_id, created_at),
  INDEX idx_orders_email_campaign_created (email_campaign_id, created_at),
  INDEX idx_orders_address (address_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_orders_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== CHECKOUT INTENTS (pago sin crear pedido) =====
CREATE TABLE IF NOT EXISTS checkout_intents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  address_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(64) NOT NULL,
  cart_hash VARCHAR(64) NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  marketing_campaign_id BIGINT UNSIGNED NULL,
  email_campaign_id BIGINT UNSIGNED NULL,
  status ENUM('created','used','failed') NOT NULL DEFAULT 'created',
  fail_reason VARCHAR(255) NULL,
  amount INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'ARS',
  seller_tax_regime VARCHAR(32) NULL,
  seller_cuit VARCHAR(32) NULL,
  seller_business_name VARCHAR(160) NULL,
  seller_fiscal_address VARCHAR(200) NULL,
  customer_tax_condition VARCHAR(32) NULL,
  customer_cuit VARCHAR(32) NULL,
  currency_code VARCHAR(16) NULL,
  total_net INT UNSIGNED NULL,
  total_vat INT UNSIGNED NULL,
  address_json JSON NOT NULL,
  items_json JSON NOT NULL,
  totals_json JSON NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_checkout_intents_user (user_id),
  INDEX idx_checkout_intents_reuse (user_id, address_id, status, cart_hash, created_at),
  INDEX idx_checkout_intents_order (order_id),
  INDEX idx_checkout_intents_campaign_created (marketing_campaign_id, created_at),
  INDEX idx_checkout_intents_email_campaign_created (email_campaign_id, created_at),
  UNIQUE KEY uk_checkout_intents_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PAYMENTS =====
CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(32) NOT NULL,
  provider_payment_id VARCHAR(64) NOT NULL,
  provider_preference_id VARCHAR(64) NULL,
  status VARCHAR(32) NULL,
  amount INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'ARS',
  intent_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  raw_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uk_payments_provider_pid (provider, provider_payment_id),
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_intent (intent_id),
  KEY idx_payments_created_at (created_at),
  KEY idx_payments_provider_preference_id (provider_preference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ===== ORDER FISCAL REQUESTS (Solicitud Factura A post-compra) =====
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

-- ===== COMPROBANTES FISCALES (ARCA/AFIP) =====
CREATE TABLE IF NOT EXISTS comprobantes_fiscales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  kind ENUM('invoice','credit_note') NOT NULL DEFAULT 'invoice',
  related_comprobante_id BIGINT UNSIGNED NULL,
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

  email_to VARCHAR(255) NULL,
  email_sent_at DATETIME NULL,
  email_error VARCHAR(255) NULL,

  issued_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  KEY idx_comprobantes_fiscales_order (order_id),
  UNIQUE KEY uk_cf_order_kind (order_id, kind),
  KEY idx_cf_related_comprobante (related_comprobante_id),
  KEY idx_comprobantes_fiscales_status_created (status, created_at),
  KEY idx_comprobantes_fiscales_cbte (pto_vta, cbte_tipo, cbte_nro),
  CONSTRAINT fk_comprobantes_fiscales_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================
-- ENVÍOS
-- ==========================

CREATE TABLE IF NOT EXISTS order_shipments (
  order_id BIGINT UNSIGNED PRIMARY KEY,
  carrier VARCHAR(80) NULL,
  service VARCHAR(80) NULL,
  tracking_number VARCHAR(120) NULL,
  tracking_url VARCHAR(255) NULL,

  -- Correo Argentino
  external_shipment_id VARCHAR(64) NULL,
  tracking_events_json JSON NULL,
  last_tracked_at DATETIME NULL,
  last_error VARCHAR(255) NULL,

  -- Estado operativo (admin) vs estado tracking (API)
  status VARCHAR(50) NOT NULL DEFAULT 'preparando',
  tracking_status VARCHAR(32) NULL,

  shipped_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_ship_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE,

  KEY idx_order_shipments_order_id (order_id),
  KEY idx_order_shipments_external_shipment_id (external_shipment_id),
  KEY idx_order_shipments_tracking_status (tracking_status),
  KEY idx_order_shipments_last_tracked_at (last_tracked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ==========================
-- CARRITO
-- ==========================

CREATE TABLE IF NOT EXISTS carts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  token VARCHAR(64) NULL,
  status ENUM('active','merged','abandoned') NOT NULL DEFAULT 'active',
  merged_into_cart_id BIGINT UNSIGNED NULL,
  merged_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_cart_token (token),
  INDEX idx_cart_user (user_id),
  INDEX idx_cart_status (status),

  CONSTRAINT fk_cart_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_cart_merged_into FOREIGN KEY (merged_into_cart_id)
    REFERENCES carts(id) ON DELETE SET NULL,
  KEY idx_carts_created_at (created_at),
  KEY idx_carts_merged_into_cart_id (merged_into_cart_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================
-- ATRIBUTOS (variantes)
-- =====================
CREATE TABLE IF NOT EXISTS catalog_tag_groups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_catalog_tag_groups_slug (slug),
  UNIQUE KEY uq_catalog_tag_groups_name (name),
  KEY idx_catalog_tag_groups_active_sort (is_active, sort, name),
  KEY idx_catalog_tag_groups_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_catalog_tags_group_slug (group_id, slug),
  UNIQUE KEY uq_catalog_tags_group_name (group_id, name),
  KEY idx_catalog_tags_group_sort (group_id, sort, name),
  KEY idx_catalog_tags_active_sort (is_active, sort, name),
  CONSTRAINT fk_catalog_tags_group FOREIGN KEY (group_id)
    REFERENCES catalog_tag_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_catalog_tags (
  product_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id, tag_id),
  KEY idx_product_catalog_tags_tag_id (tag_id),
  KEY idx_product_catalog_tags_created_at (created_at),
  CONSTRAINT fk_product_catalog_tags_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_product_catalog_tags_tag FOREIGN KEY (tag_id)
    REFERENCES catalog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attributes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  code VARCHAR(120) NULL,
  slug VARCHAR(120) NOT NULL,
  type ENUM('select','text','number') NOT NULL DEFAULT 'select',
  is_variant_axis TINYINT(1) NOT NULL DEFAULT 0,
  unit VARCHAR(32) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attr_code (code),
  UNIQUE KEY uq_attr_slug (slug),
  UNIQUE KEY uq_attr_name (name),
  KEY idx_attributes_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attribute_values (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attribute_id BIGINT UNSIGNED NOT NULL,
  value VARCHAR(80) NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attr_val (attribute_id, value),
  INDEX idx_attr (attribute_id),
  CONSTRAINT fk_attr_vals_attr
    FOREIGN KEY (attribute_id) REFERENCES attributes(id)
    ON DELETE CASCADE,
  KEY idx_attribute_values_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atributos habilitados para un producto
CREATE TABLE IF NOT EXISTS product_attributes (
  product_id BIGINT UNSIGNED NOT NULL,
  attribute_id BIGINT UNSIGNED NOT NULL,
  sort INT UNSIGNED NOT NULL DEFAULT 0,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (product_id, attribute_id),
  INDEX idx_pa_attr (attribute_id),
  CONSTRAINT fk_pa_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_attr FOREIGN KEY (attribute_id)
    REFERENCES attributes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores seleccionados por producto (para generar combinaciones)
CREATE TABLE IF NOT EXISTS product_attribute_values (
  product_id BIGINT UNSIGNED NOT NULL,
  attribute_id BIGINT UNSIGNED NOT NULL,
  value_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, attribute_id, value_id),
  INDEX idx_pav_attr (attribute_id),
  INDEX idx_pav_val (value_id),
  CONSTRAINT fk_pav_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pav_attr FOREIGN KEY (attribute_id)
    REFERENCES attributes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pav_val FOREIGN KEY (value_id)
    REFERENCES attribute_values(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Variantes por producto (cada variante tiene SKU propio)
CREATE TABLE IF NOT EXISTS product_variants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  internal_code VARCHAR(32) NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  -- SKU real del proveedor. Puede quedar NULL hasta que el admin lo complete.
  sku VARCHAR(80) NULL,
  title VARCHAR(200) NULL,
  price INT UNSIGNED NULL,
  list_price INT UNSIGNED NULL,
  price_updated_at DATETIME NULL,
  cost INT UNSIGNED NULL,
  cost_currency_id BIGINT UNSIGNED NULL,
  margin_pct DECIMAL(7,4) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_variant_sku (sku),
  UNIQUE KEY uq_product_variants_internal_code (internal_code),
  INDEX idx_variant_product (product_id),
  INDEX idx_pv_cost_currency (cost_currency_id),

  CONSTRAINT fk_pv_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  KEY idx_product_variants_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCT IMAGES =====
-- Importante: debe crearse DESPUÉS de product_variants para poder armar la FK (variant_id)
CREATE TABLE IF NOT EXISTS product_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  internal_code VARCHAR(32) NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  -- NULL = imagen general del producto / no asociada a una variante
  variant_id BIGINT UNSIGNED NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(200) NULL,
  is_cover TINYINT(1) NOT NULL DEFAULT 0,
  sort INT DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_img_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_img_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE,
  INDEX idx_prod_variant_sort (product_id, variant_id, sort),
  INDEX idx_variant_cover (product_id, variant_id, is_cover),
  KEY idx_product_images_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variant_id BIGINT UNSIGNED NULL,
  qty INT UNSIGNED NOT NULL,
  price INT UNSIGNED NULL,
  unit_list_price INT UNSIGNED NULL,
  unit_cost INT UNSIGNED NULL,

  -- snapshot fiscal por ítem (IVA incluido en price)
  vat_rate DECIMAL(7,4) NULL,
  unit_net INT UNSIGNED NULL,
  unit_vat INT UNSIGNED NULL,
  line_net INT UNSIGNED NULL,
  line_vat INT UNSIGNED NULL,

  -- snapshot del producto al momento de comprar
  product_sku VARCHAR(80) NULL,
  product_name VARCHAR(200) NULL,

  -- snapshot de variante (si aplica)
  variant_sku VARCHAR(80) NULL,
  variant_title VARCHAR(200) NULL,
  variant_attributes_json JSON NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_order (order_id),
  INDEX idx_product (product_id),
  INDEX idx_variant (variant_id),

  CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)
    REFERENCES orders(id)    ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE RESTRICT,
  CONSTRAINT fk_oi_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE SET NULL,
  KEY idx_order_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS cart_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variant_id BIGINT UNSIGNED NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_cart_item (cart_id, product_id, variant_id),
  INDEX idx_ci_cart (cart_id),
  INDEX idx_ci_product (product_id),
  INDEX idx_ci_variant (variant_id),

  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id)
    REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ci_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE SET NULL,
  KEY idx_cart_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Valores (atributo/valor) asociados a una variante
CREATE TABLE IF NOT EXISTS product_variant_values (
  variant_id BIGINT UNSIGNED NOT NULL,
  attribute_id BIGINT UNSIGNED NOT NULL,
  value_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (variant_id, attribute_id),
  INDEX idx_pvv_val (value_id),
  CONSTRAINT fk_pvv_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE,
  CONSTRAINT fk_pvv_attr FOREIGN KEY (attribute_id)
    REFERENCES attributes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pvv_value FOREIGN KEY (value_id)
    REFERENCES attribute_values(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS variant_catalog_tags (
  variant_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (variant_id, tag_id),
  KEY idx_variant_catalog_tags_tag_id (tag_id),
  KEY idx_variant_catalog_tags_created_at (created_at),
  CONSTRAINT fk_variant_catalog_tags_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE,
  CONSTRAINT fk_variant_catalog_tags_tag FOREIGN KEY (tag_id)
    REFERENCES catalog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventario por variante
CREATE TABLE IF NOT EXISTS inventory_variants (
  variant_id BIGINT UNSIGNED PRIMARY KEY,
  stock INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 3,
  CONSTRAINT fk_inv_var FOREIGN KEY (variant_id)
    REFERENCES product_variants(id) ON DELETE CASCADE,
  KEY idx_inventory_variants_variant_id (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plantillas de valores para variantes (por categoría / marca)
-- No destructivo: sólo crea tablas si no existen.

CREATE TABLE IF NOT EXISTS variant_value_sets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  brand_id BIGINT UNSIGNED NULL,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vvs_cat_brand (category_id, brand_id),
  CONSTRAINT fk_vvs_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  CONSTRAINT fk_vvs_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
  KEY idx_variant_value_sets_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS variant_value_set_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  set_id BIGINT UNSIGNED NOT NULL,
  attribute_id BIGINT UNSIGNED NOT NULL,
  value_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_set_attr_value (set_id, attribute_id, value_id),
  INDEX idx_vvsi_set (set_id),
  INDEX idx_vvsi_attr (attribute_id),
  INDEX idx_vvsi_value (value_id),
  CONSTRAINT fk_vvsi_set FOREIGN KEY (set_id) REFERENCES variant_value_sets(id) ON DELETE CASCADE,
  CONSTRAINT fk_vvsi_attr FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
  CONSTRAINT fk_vvsi_value FOREIGN KEY (value_id) REFERENCES attribute_values(id) ON DELETE CASCADE,
  KEY idx_variant_value_set_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ========= SEO PRO (Lote 1) =========

CREATE TABLE IF NOT EXISTS seo_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('product','category','brand','page') NOT NULL,
  entity_id INT UNSIGNED NOT NULL,

  canonical_url VARCHAR(255) NULL,
  robots_index TINYINT(1) NOT NULL DEFAULT 1,
  robots_follow TINYINT(1) NOT NULL DEFAULT 1,

  og_title VARCHAR(255) NULL,
  og_description VARCHAR(500) NULL,
  og_image_path VARCHAR(255) NULL,

  twitter_card ENUM('summary','summary_large_image') NOT NULL DEFAULT 'summary_large_image',
  twitter_title VARCHAR(255) NULL,
  twitter_description VARCHAR(500) NULL,
  twitter_image_path VARCHAR(255) NULL,

  schema_override JSON NULL,

  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_seo_settings_entity (entity_type, entity_id),
  INDEX idx_seo_settings_entity_type (entity_type),
  INDEX idx_seo_settings_entity_id (entity_id),
  KEY idx_seo_settings_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seo_redirects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_path VARCHAR(255) NOT NULL,
  to_path VARCHAR(255) NOT NULL,
  status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  hits INT UNSIGNED NOT NULL DEFAULT 0,
  last_hit_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_seo_redirects_from (from_path),
  INDEX idx_seo_redirects_to (to_path),
  KEY idx_seo_redirects_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seo_slug_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('product','category','brand','page') NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  slug VARCHAR(200) NOT NULL,
  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_seo_slug_history_unique (entity_type, slug),
  INDEX idx_seo_slug_history_entity (entity_type, entity_id),
  KEY idx_seo_slug_history_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_seo_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  internal_code VARCHAR(32) NULL,
  product_id BIGINT UNSIGNED NOT NULL,

  bullets_json JSON NULL,
  specs_json   JSON NULL,
  faq_json     JSON NULL,

  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_product_seo_blocks_product (product_id),
  CONSTRAINT fk_product_seo_blocks_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  KEY idx_product_seo_blocks_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS category_seo_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,

  bullets_json JSON NULL,
  specs_json   JSON NULL,
  faq_json     JSON NULL,

  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_category_seo_blocks_category (category_id),
  CONSTRAINT fk_category_seo_blocks_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  KEY idx_category_seo_blocks_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brand_seo_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand_id BIGINT UNSIGNED NOT NULL,

  bullets_json JSON NULL,
  specs_json   JSON NULL,
  faq_json     JSON NULL,

  created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_brand_seo_blocks_brand (brand_id),
  CONSTRAINT fk_brand_seo_blocks_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
  KEY idx_brand_seo_blocks_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Lealtad (ledger de puntos) =====
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


-- ===== Analíticas de navegación (P2) =====
-- Analíticas de navegación / funnel (P2)
-- Tabla de eventos: page_view, product_view, add_to_cart, checkout_view, purchase

CREATE TABLE IF NOT EXISTS analytics_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_key VARCHAR(40) NOT NULL,
  path VARCHAR(255) NULL,
  referrer VARCHAR(255) NULL,
  marketing_campaign_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  session_id VARCHAR(128) NULL,
  cart_token_hash CHAR(64) NULL,
  ip_hash CHAR(64) NULL,
  ua_hash CHAR(64) NULL,
  product_id INT UNSIGNED NULL,
  variant_id INT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_event_date (event_key, created_at),
  KEY idx_path_date (path, created_at),
  KEY idx_product_date (product_id, created_at),
  KEY idx_variant_date (variant_id, created_at),
  KEY idx_user_date (user_id, created_at),
  KEY idx_session_date (session_id, created_at),
  KEY idx_order_date (order_id, created_at),
  KEY idx_campaign_date (marketing_campaign_id, created_at),
  UNIQUE KEY uniq_event_order (event_key, order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Marketing (campañas internas / links cortos)
-- =====================================================

CREATE TABLE IF NOT EXISTS marketing_campaigns (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  destination_path VARCHAR(255) NOT NULL,
  utm_source VARCHAR(80) DEFAULT NULL,
  utm_medium VARCHAR(80) DEFAULT NULL,
  utm_campaign VARCHAR(120) DEFAULT NULL,
  utm_content VARCHAR(120) DEFAULT NULL,
  utm_term VARCHAR(120) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_marketing_campaigns_slug (slug),
  KEY idx_marketing_campaigns_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK (post-create) para evitar error por orden de tablas
ALTER TABLE users
  ADD CONSTRAINT fk_users_marketing_campaign
  FOREIGN KEY (marketing_campaign_id) REFERENCES marketing_campaigns(id)
  ON DELETE SET NULL;


CREATE TABLE IF NOT EXISTS marketing_campaign_hits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  session_id VARCHAR(128) DEFAULT NULL,
  referrer VARCHAR(255) DEFAULT NULL,
  ip_hash CHAR(64) DEFAULT NULL,
  ua_hash CHAR(64) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_marketing_hits_campaign_created (campaign_id, created_at),
  KEY idx_marketing_hits_session (session_id),
  CONSTRAINT fk_marketing_hits_campaign FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Settings globales (key-value)
-- =====================================================

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Home Hero Slides
-- =====================================================
CREATE TABLE IF NOT EXISTS home_hero_slides (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  title VARCHAR(120) NOT NULL DEFAULT '',
  alt_text VARCHAR(255) NOT NULL DEFAULT '',
  image_desktop VARCHAR(255) NOT NULL,
  image_mobile VARCHAR(255) NOT NULL DEFAULT '',
  target_url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  starts_at DATETIME NULL DEFAULT NULL,
  ends_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_home_hero_active_sort (is_active, sort_order, id),
  KEY idx_home_hero_schedule (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Email Marketing (Etapa 1): Suscriptores + preferencias + baja
-- =====================================================

CREATE TABLE IF NOT EXISTS email_subscribers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  status ENUM('pending','subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  confirm_token CHAR(64) DEFAULT NULL,
  unsubscribe_token CHAR(64) NOT NULL,
  confirmed_at DATETIME DEFAULT NULL,
  unsubscribed_at DATETIME DEFAULT NULL,
  preferences_json JSON DEFAULT NULL,
  source VARCHAR(40) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_email_subscribers_email (email),
  UNIQUE KEY uk_email_subscribers_unsub_token (unsubscribe_token),
  KEY idx_email_subscribers_status (status),
  KEY idx_email_subscribers_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Marketing (Etapa 2): Automatizaciones + Cola + Logs
-- No destructivo: crea tablas si no existen.

CREATE TABLE IF NOT EXISTS email_automations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uk_email_automations_code (code),
  KEY idx_email_automations_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_automation_steps (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  automation_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(80) NOT NULL,
  name VARCHAR(140) NOT NULL,
  delay_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  subject VARCHAR(160) NOT NULL,
  template VARCHAR(120) NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uk_email_steps_code (automation_id, code),
  KEY idx_email_steps_enabled (is_enabled),
  CONSTRAINT fk_email_steps_automation FOREIGN KEY (automation_id)
    REFERENCES email_automations(id) ON DELETE CASCADE,
  KEY idx_email_automation_steps_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dedupe_key CHAR(64) NOT NULL,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  template VARCHAR(120) NOT NULL,
  payload_json JSON NULL,
  unsubscribe_token CHAR(64) NULL,
  subscriber_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  automation_step_id INT UNSIGNED NULL,
  entity_type VARCHAR(20) NULL,
  entity_id BIGINT UNSIGNED NULL,
  status ENUM('queued','sending','sent','failed','cancelled') NOT NULL DEFAULT 'queued',
  scheduled_at DATETIME NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(255) NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,

  UNIQUE KEY uk_email_queue_dedupe (dedupe_key),
  KEY idx_email_queue_status_sched (status, scheduled_at),
  KEY idx_email_queue_to (to_email),
  KEY idx_email_queue_subscriber (subscriber_id),
  KEY idx_email_queue_user (user_id),
  KEY idx_email_queue_step (automation_step_id),
  KEY idx_email_queue_created_at (created_at),
  KEY idx_email_queue_entity_id (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_send_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  queue_id BIGINT UNSIGNED NULL,
  to_email VARCHAR(190) NOT NULL,
  status ENUM('sent','failed','cancelled') NOT NULL,
  subject VARCHAR(160) NOT NULL,
  template VARCHAR(120) NOT NULL,
  subscriber_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  automation_step_id INT UNSIGNED NULL,
  entity_type VARCHAR(20) NULL,
  entity_id BIGINT UNSIGNED NULL,
  error_text VARCHAR(255) NULL,
  open_token CHAR(64) NULL,
  opened_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY idx_email_logs_created (created_at),
  KEY idx_email_logs_to (to_email),
  KEY idx_email_logs_step (automation_step_id),
  UNIQUE KEY uq_email_send_logs_open_token (open_token),
  KEY idx_email_send_logs_opened_at (opened_at),
  KEY idx_email_send_logs_entity_id (entity_id),
  KEY idx_email_send_logs_queue_id (queue_id),
  KEY idx_email_send_logs_subscriber_id (subscriber_id),
  KEY idx_email_send_logs_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Email Marketing (Etapa 3): Campañas broadcast + tracking de clicks
-- =====================================================

CREATE TABLE IF NOT EXISTS email_campaigns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  preheader VARCHAR(200) NULL,
  body_html MEDIUMTEXT NOT NULL,
  cta_label VARCHAR(80) NULL,
  cta_url TEXT NULL,
  audience VARCHAR(32) NOT NULL DEFAULT 'all',
  audience_params_json JSON NULL,
  status ENUM('draft','scheduled','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
  scheduled_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  KEY idx_email_campaigns_status_sched (status, scheduled_at),
  KEY idx_email_campaigns_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== EMAIL TEMPLATES (Etapa 4) =====

CREATE TABLE IF NOT EXISTS email_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  subject_default VARCHAR(190) NULL,
  preheader_default VARCHAR(190) NULL,
  editor_mode ENUM('visual','html') NOT NULL DEFAULT 'visual',
  body_html MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_click_tokens (
  token CHAR(64) NOT NULL,
  campaign_id BIGINT UNSIGNED NULL,
  queue_id BIGINT UNSIGNED NULL,
  subscriber_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  destination_url TEXT NOT NULL,
  clicked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (token),
  KEY idx_email_click_campaign_clicked (campaign_id, clicked_at),
  KEY idx_email_click_created (created_at),
  KEY idx_email_click_tokens_queue_id (queue_id),
  KEY idx_email_click_tokens_subscriber_id (subscriber_id),
  KEY idx_email_click_tokens_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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

-- FKs for currencies
ALTER TABLE products ADD CONSTRAINT fk_products_cost_currency FOREIGN KEY (cost_currency_id) REFERENCES currencies(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE product_variants ADD CONSTRAINT fk_pv_cost_currency FOREIGN KEY (cost_currency_id) REFERENCES currencies(id) ON UPDATE CASCADE ON DELETE RESTRICT;


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

-- 20260131_01_suppliers.sql
-- Proveedores (base operativa para compras / umbrales / gestiones)
-- Reglas de Oro DB: utf8mb4 + collation consistente

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(190) NOT NULL,
  `cuit` VARCHAR(11) NULL,
  `email` VARCHAR(190) NOT NULL DEFAULT '',
  `telefono` VARCHAR(64) NOT NULL DEFAULT '',
  `condicion_iva` VARCHAR(32) NOT NULL DEFAULT '',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_suppliers_cuit` (`cuit`),
  KEY `idx_suppliers_nombre` (`nombre`),
  KEY `idx_suppliers_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- purchase_invoices
-- Compras (IVA compras) + integración con Proveedores
-- Importes guardados en pesos (ARS) como INT.

CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` BIGINT UNSIGNED NOT NULL,
  `kind` VARCHAR(20) NOT NULL DEFAULT 'invoice', -- invoice | credit_note
  `cbte_tipo` SMALLINT UNSIGNED NOT NULL, -- 1,6,11,3,8,13 (AFIP)
  `pto_vta` INT UNSIGNED NOT NULL DEFAULT 0,
  `cbte_nro` INT UNSIGNED NOT NULL DEFAULT 0,
  `issued_at` DATE NOT NULL,
  `imp_neto` INT NOT NULL DEFAULT 0,
  `imp_iva` INT NOT NULL DEFAULT 0,
  `perc_iva` INT NOT NULL DEFAULT 0,
  `perc_iibb` INT NOT NULL DEFAULT 0,
  `ret_ganancias` INT NOT NULL DEFAULT 0,
  `imp_total` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'loaded', -- loaded | validated | void
  `notes` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `voided_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_invoices_supplier` (`supplier_id`),
  KEY `idx_purchase_invoices_issued_at` (`issued_at`),
  KEY `idx_purchase_invoices_kind` (`kind`),
  KEY `idx_purchase_invoices_status` (`status`),
  UNIQUE KEY `uniq_purchase_invoice_doc` (`supplier_id`,`cbte_tipo`,`pto_vta`,`cbte_nro`),
  CONSTRAINT `fk_purchase_invoices_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- accounting_closures
-- Snapshot contable mensual (cierre) - IVA Ventas/Compras y saldo
-- Importes guardados en pesos (ARS) como INT.
CREATE TABLE IF NOT EXISTS `accounting_closures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `period` VARCHAR(7) NOT NULL, -- YYYY-MM
  `iva_ventas` INT NOT NULL DEFAULT 0,
  `iva_compras` INT NOT NULL DEFAULT 0,
  `saldo_iva` INT NOT NULL DEFAULT 0,
  `ventas_json` JSON NULL,
  `compras_json` JSON NULL,
  `closed_by_user_id` BIGINT UNSIGNED NOT NULL,
  `closed_at` DATETIME NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_accounting_closures_period` (`period`),
  KEY `idx_accounting_closures_closed_at` (`closed_at`),
  KEY `idx_accounting_closures_closed_by` (`closed_by_user_id`),
  CONSTRAINT `fk_accounting_closures_closed_by` FOREIGN KEY (`closed_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
