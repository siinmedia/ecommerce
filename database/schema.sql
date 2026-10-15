-- E-Commerce Single-Vendor MVP — schema (MySQL 8 / InnoDB / utf8mb4)
CREATE DATABASE IF NOT EXISTS ecommerce_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce_mvp;

-- ============ USERS & ADDRESSES ============
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin','developer') NOT NULL DEFAULT 'user',
  status ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE addresses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  label VARCHAR(50) NOT NULL DEFAULT 'Rumah',
  recipient VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  city_label VARCHAR(150) NOT NULL,
  destination_id VARCHAR(20) NOT NULL COMMENT 'RajaOngkir city id',
  postal_code VARCHAR(10) NULL,
  note VARCHAR(255) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_addr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_addr_user (user_id)
) ENGINE=InnoDB;

-- ============ CATALOG ============
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  parent_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cat_slug (slug),
  CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  sku VARCHAR(80) NOT NULL,
  description TEXT NULL,
  price INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'IDR, harga dasar',
  compare_price INT UNSIGNED NULL,
  weight_gram INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_products_slug (slug),
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_status (status),
  KEY idx_products_cat (category_id),
  FULLTEXT KEY ft_products (name, description),
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE product_variants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  sku VARCHAR(80) NOT NULL,
  price INT UNSIGNED NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  weight_gram INT UNSIGNED NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  UNIQUE KEY uq_variant_sku (sku),
  KEY idx_variant_product (product_id),
  CONSTRAINT fk_var_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE stock_ledger (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  variant_id INT UNSIGNED NOT NULL,
  delta INT NOT NULL,
  before_stock INT NOT NULL,
  after_stock INT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  actor_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ledger_variant (variant_id),
  CONSTRAINT fk_ledger_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ CART ============
CREATE TABLE carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_carts_user (user_id),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id INT UNSIGNED NOT NULL,
  variant_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  UNIQUE KEY uq_cart_variant (cart_id, variant_id),
  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ ORDERS ============
CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(30) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  address_id INT UNSIGNED NULL,
  bank_account_id INT UNSIGNED NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'PENDING_PAYMENT',
  subtotal INT UNSIGNED NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  shipping_cost INT UNSIGNED NOT NULL DEFAULT 0,
  grand_total INT UNSIGNED NOT NULL DEFAULT 0,
  courier VARCHAR(20) NOT NULL DEFAULT '',
  courier_service VARCHAR(80) NOT NULL DEFAULT '',
  shipping_etd VARCHAR(40) NOT NULL DEFAULT '',
  shipping_snapshot_json JSON NULL COMMENT 'locked rate snapshot',
  promo_code VARCHAR(40) NULL,
  note VARCHAR(255) NULL,
  idempotency_key VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orders_code (order_code),
  UNIQUE KEY uq_orders_idem (idempotency_key),
  KEY idx_orders_user (user_id),
  KEY idx_orders_status (status),
  KEY idx_orders_expiry (status, expires_at),
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  variant_id INT UNSIGNED NULL,
  product_name VARCHAR(200) NOT NULL COMMENT 'snapshot',
  variant_name VARCHAR(120) NOT NULL COMMENT 'snapshot',
  sku VARCHAR(80) NOT NULL COMMENT 'snapshot',
  price INT UNSIGNED NOT NULL COMMENT 'snapshot',
  qty INT UNSIGNED NOT NULL,
  line_total INT UNSIGNED NOT NULL,
  KEY idx_oi_order (order_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_addresses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  recipient VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  city_label VARCHAR(150) NOT NULL,
  postal_code VARCHAR(10) NULL,
  note VARCHAR(255) NULL,
  UNIQUE KEY uq_oa_order (order_id),
  CONSTRAINT fk_oa_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  from_status VARCHAR(20) NULL,
  to_status VARCHAR(20) NOT NULL,
  actor_id INT UNSIGNED NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_osh_order (order_id),
  CONSTRAINT fk_osh_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ PAYMENTS ============
CREATE TABLE bank_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_name VARCHAR(60) NOT NULL,
  account_number VARCHAR(40) NOT NULL,
  account_name VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE payment_proofs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  filename VARCHAR(190) NOT NULL COMMENT 'stored name in private storage',
  mime VARCHAR(60) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pp_order (order_id),
  CONSTRAINT fk_pp_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payment_reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NOT NULL,
  decision VARCHAR(20) NOT NULL COMMENT 'PAID | PAYMENT_REJECTED',
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pr_order (order_id),
  CONSTRAINT fk_pr_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ SHIPMENTS ============
CREATE TABLE shipments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  courier VARCHAR(20) NOT NULL,
  service VARCHAR(80) NOT NULL,
  cost INT UNSIGNED NOT NULL,
  etd VARCHAR(40) NOT NULL DEFAULT '',
  origin_id VARCHAR(20) NOT NULL DEFAULT '',
  destination_id VARCHAR(20) NOT NULL DEFAULT '',
  waybill VARCHAR(80) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  shipped_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ship_order (order_id),
  CONSTRAINT fk_ship_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tracking_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id INT UNSIGNED NOT NULL,
  status VARCHAR(60) NOT NULL,
  description VARCHAR(255) NOT NULL,
  event_time DATETIME NOT NULL,
  source VARCHAR(30) NOT NULL DEFAULT 'admin',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_te_ship (shipment_id),
  CONSTRAINT fk_te_ship FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ PROMOTIONS ============
CREATE TABLE promotions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  type ENUM('nominal','percent') NOT NULL,
  value INT UNSIGNED NOT NULL,
  max_discount INT UNSIGNED NULL,
  min_spend INT UNSIGNED NOT NULL DEFAULT 0,
  quota INT UNSIGNED NOT NULL DEFAULT 0,
  used_count INT UNSIGNED NOT NULL DEFAULT 0,
  per_user_limit INT UNSIGNED NOT NULL DEFAULT 1,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_promo_code (code)
) ENGINE=InnoDB;

CREATE TABLE promotion_redemptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  promotion_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pred_promo_user (promotion_id, user_id),
  CONSTRAINT fk_pred_promo FOREIGN KEY (promotion_id) REFERENCES promotions(id)
) ENGINE=InnoDB;

-- ============ BASIC (wishlist, reviews) ============
CREATE TABLE wishlists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wish (user_id, product_id),
  CONSTRAINT fk_wish_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wish_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  rating TINYINT UNSIGNED NOT NULL,
  body TEXT NULL,
  status ENUM('pending','published','hidden') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rev_product (product_id, status),
  CONSTRAINT fk_rev_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ============ TECH OPS ============
CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id INT UNSIGNED NULL,
  actor_role VARCHAR(20) NOT NULL,
  action VARCHAR(80) NOT NULL,
  entity VARCHAR(60) NOT NULL,
  entity_id INT UNSIGNED NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_entity (entity, entity_id),
  KEY idx_audit_actor (actor_id),
  KEY idx_audit_time (created_at)
) ENGINE=InnoDB;

CREATE TABLE integration_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(40) NOT NULL,
  endpoint VARCHAR(190) NOT NULL,
  correlation_id VARCHAR(32) NOT NULL,
  request_json JSON NULL COMMENT 'secrets redacted before insert',
  response_code INT NOT NULL DEFAULT 0,
  response_json TEXT NULL COMMENT 'truncated snippet',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ir_corr (correlation_id),
  KEY idx_ir_time (created_at)
) ENGINE=InnoDB;

CREATE TABLE background_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_type VARCHAR(80) NOT NULL,
  ref_id VARCHAR(80) NULL,
  payload_json JSON NULL,
  status ENUM('queued','running','succeeded','failed','dead') NOT NULL DEFAULT 'queued',
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  max_attempts INT UNSIGNED NOT NULL DEFAULT 5,
  last_error TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_job_status (status)
) ENGINE=InnoDB;
