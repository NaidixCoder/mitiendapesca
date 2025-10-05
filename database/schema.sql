-- Tienda Pesca — CORE schema (completo)
SET NAMES utf8mb4;
SET time_zone = "+00:00";

-- ===== BRANDS =====
CREATE TABLE IF NOT EXISTS brands (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== CATEGORIES =====
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  sort INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id)
    REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCTS =====
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(160) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  brand_id INT UNSIGNED NULL,
  category_id INT UNSIGNED NOT NULL,
  -- Contenido
  short_desc VARCHAR(500) NULL,
  long_desc MEDIUMTEXT NULL,
  -- Precios
  price INT UNSIGNED NOT NULL,
  list_price INT UNSIGNED NULL,
  cost INT UNSIGNED NULL,
  -- Identificación comercial
  barcode VARCHAR(64) NULL,
  -- Flags/UI
  rating DECIMAL(2,1) DEFAULT 0.0,
  reviews INT UNSIGNED DEFAULT 0,
  is_new TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  -- SEO
  meta_title VARCHAR(70) NULL,
  meta_description VARCHAR(160) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_cat_price (category_id, price),
  INDEX idx_brand (brand_id),
  INDEX idx_created (created_at),
  INDEX idx_products_active (is_active),
  FULLTEXT KEY ft_name_desc (name, short_desc, long_desc),

  CONSTRAINT fk_prod_brand FOREIGN KEY (brand_id)
    REFERENCES brands(id) ON DELETE SET NULL,
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCT IMAGES =====
CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(200) NULL,
  is_cover TINYINT(1) NOT NULL DEFAULT 0,
  sort INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_img_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_prod_sort (product_id, sort),
  INDEX idx_cover (product_id, is_cover)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PRODUCT SPECS =====
CREATE TABLE IF NOT EXISTS product_specs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
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
  product_id INT UNSIGNED PRIMARY KEY,
  stock INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 3,
  CONSTRAINT fk_inv_prod FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice útil para consultas de stock bajo
CREATE INDEX IF NOT EXISTS idx_inventory_stock ON inventory (stock, low_stock_threshold);

-- ===== USERS =====
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NULL,
  oauth_provider VARCHAR(30)  NULL,
  oauth_sub      VARCHAR(64)  NULL,
  email_verified TINYINT(1)   NOT NULL DEFAULT 0,
  role           ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  avatar_url     VARCHAR(255) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL,
  last_login_at  DATETIME NULL,
  UNIQUE KEY uq_email (email),
  UNIQUE KEY uq_oauth (oauth_provider, oauth_sub),
  INDEX idx_login (email, oauth_provider, oauth_sub),
  INDEX idx_users_role (role),
  INDEX idx_users_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  CONSTRAINT fk_ut_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Admin Audit Log =====
CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  metadata JSON NULL,
  ip VARBINARY(16) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_action (action),
  INDEX idx_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Import jobs =====
CREATE TABLE IF NOT EXISTS import_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_type   VARCHAR(50) NOT NULL,
  status     VARCHAR(20) NOT NULL DEFAULT 'queued',
  file_name  VARCHAR(255) NULL,
  file_path  VARCHAR(255) NULL,
  total_rows INT NULL,
  ok_rows    INT NULL,
  err_rows   INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finished_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Orders (para KPIs) =====
CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  status ENUM('pending','paid','shipped','cancelled','refunded') NOT NULL DEFAULT 'pending',
  subtotal INT UNSIGNED NOT NULL,
  shipping INT UNSIGNED NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status_created (status, created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  price INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order (order_id),
  INDEX idx_product (product_id),
  CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)  REFERENCES orders(id)    ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
