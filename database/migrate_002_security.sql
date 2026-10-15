-- migrate_002_security.sql — security hardening (login_attempts, password_resets,
-- TOTP 2FA columns/tables, session_version). Idempotent-ish: uses ADD COLUMN IF NOT EXISTS
-- style guards where MySQL 8 supports them via information_schema checks below.
USE ecommerce_mvp;

-- ============ users: session_version + TOTP ============
ALTER TABLE users
  ADD COLUMN session_version INT NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN totp_secret VARCHAR(64) NULL AFTER session_version,
  ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret;

-- ============ DB-backed login rate limiting ============
CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_login_ip_time (ip, attempted_at),
  KEY idx_login_email_time (email, attempted_at)
) ENGINE=InnoDB;

-- ============ password reset (selector + hashed token, single-use) ============
CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  selector VARCHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL COMMENT 'sha256 hex of token shown in URL',
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pr_selector (selector),
  KEY idx_pr_user (user_id),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ TOTP backup codes (single-use) ============
CREATE TABLE IF NOT EXISTS totp_backup_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code_hash CHAR(64) NOT NULL COMMENT 'sha256 hex of backup code',
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tbc_user (user_id),
  CONSTRAINT fk_tbc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- promotion_redemptions: lookup index for backfilling order_id (already present in
-- schema.sql as idx_pred_promo_user, kept here for DBs created before it existed).
-- MySQL has no IF NOT EXISTS for indexes; guard via procedure.
DELIMITER //
CREATE PROCEDURE migrate_002_add_pred_index()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'promotion_redemptions'
      AND index_name = 'idx_pred_promo_user'
  ) THEN
    ALTER TABLE promotion_redemptions ADD KEY idx_pred_promo_user (promotion_id, user_id);
  END IF;
END//
DELIMITER ;
CALL migrate_002_add_pred_index();
DROP PROCEDURE migrate_002_add_pred_index;
