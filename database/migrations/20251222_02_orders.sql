-- 20251222_02_orders.sql
-- Pedidos (MVP) para Mi Cuenta.
-- No destructivo: crea tablas si no existen.

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  status ENUM('pending','paid','shipped','cancelled','refunded') NOT NULL DEFAULT 'pending',
  subtotal INT UNSIGNED NOT NULL,
  shipping INT UNSIGNED NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL,

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
  INDEX idx_orders_address (address_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  price INT UNSIGNED NOT NULL,

  -- snapshot del producto al momento de comprar
  product_sku VARCHAR(80) NULL,
  product_name VARCHAR(200) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order (order_id),
  INDEX idx_product (product_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
