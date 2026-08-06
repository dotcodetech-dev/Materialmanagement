-- MaterialFlow — consolidated MySQL 8 schema
-- Import via phpMyAdmin (or: mysql -u user -p materialflow < materialflow_mysql.sql)
-- Consolidates: db/init/001_schema.sql + scripts/add-batch-history.js + app_settings.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE app_users (
  id CHAR(36) NOT NULL PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_users_role CHECK (role IN ('ADMIN', 'STOREKEEPER', 'MANAGER', 'STAFF', 'VIEWER'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
  id CHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(255) NULL,
  address TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE items (
  id CHAR(36) NOT NULL PRIMARY KEY,
  barcode VARCHAR(64) COLLATE utf8mb4_bin NOT NULL UNIQUE,
  sku VARCHAR(64) NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  category VARCHAR(100) NOT NULL DEFAULT 'General',
  unit VARCHAR(20) NOT NULL,
  reorder_level DECIMAL(14, 3) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_items_reorder CHECK (reorder_level >= 0),
  INDEX idx_items_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
  id CHAR(36) NOT NULL PRIMARY KEY,
  item_id CHAR(36) NOT NULL,
  customer_id CHAR(36) NULL,
  movement_type ENUM('INWARD', 'OUTWARD', 'RETURN_IN', 'RETURN_OUT', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT') NOT NULL,
  quantity DECIMAL(14, 3) NOT NULL,
  reference_number VARCHAR(100) NULL,
  notes TEXT NULL,
  occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  recorded_by CHAR(36) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_movements_qty CHECK (quantity > 0),
  CONSTRAINT fk_movements_item FOREIGN KEY (item_id) REFERENCES items (id),
  CONSTRAINT fk_movements_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
  CONSTRAINT fk_movements_user FOREIGN KEY (recorded_by) REFERENCES app_users (id),
  INDEX idx_stock_movements_item_time (item_id, occurred_at DESC),
  INDEX idx_stock_movements_customer_time (customer_id, occurred_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE barcode_batches (
  id CHAR(36) NOT NULL PRIMARY KEY,
  item_id CHAR(36) NOT NULL,
  batch_reference VARCHAR(64) COLLATE utf8mb4_bin NOT NULL UNIQUE,
  quantity_total INT NOT NULL,
  quantity_generated INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
  status_detail VARCHAR(20) NULL,
  total_printed INT NOT NULL DEFAULT 0,
  last_printed_at DATETIME NULL,
  last_printed_by CHAR(36) NULL,
  barcode_prefix VARCHAR(64) NOT NULL,
  created_by CHAR(36) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_batches_qty CHECK (quantity_total > 0),
  CONSTRAINT chk_batches_status CHECK (status IN ('PENDING', 'COMPLETED', 'ARCHIVED')),
  CONSTRAINT chk_batches_status_detail CHECK (status_detail IN ('CREATED', 'PARTIALLY_PRINTED', 'FULLY_PRINTED', 'ARCHIVED')),
  CONSTRAINT fk_batches_item FOREIGN KEY (item_id) REFERENCES items (id),
  CONSTRAINT fk_batches_creator FOREIGN KEY (created_by) REFERENCES app_users (id),
  CONSTRAINT fk_batches_printer FOREIGN KEY (last_printed_by) REFERENCES app_users (id),
  INDEX idx_barcode_batches_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE batch_barcodes (
  id CHAR(36) NOT NULL PRIMARY KEY,
  batch_id CHAR(36) NOT NULL,
  barcode_code VARCHAR(64) COLLATE utf8mb4_bin NOT NULL UNIQUE,
  item_id CHAR(36) NOT NULL,
  unit_number INT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'UNSCANNED',
  scanned_at DATETIME NULL,
  scanned_by CHAR(36) NULL,
  movement_id CHAR(36) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_barcodes_status CHECK (status IN ('UNSCANNED', 'SCANNED')),
  CONSTRAINT fk_barcodes_batch FOREIGN KEY (batch_id) REFERENCES barcode_batches (id),
  CONSTRAINT fk_barcodes_item FOREIGN KEY (item_id) REFERENCES items (id),
  CONSTRAINT fk_barcodes_scanner FOREIGN KEY (scanned_by) REFERENCES app_users (id),
  CONSTRAINT fk_barcodes_movement FOREIGN KEY (movement_id) REFERENCES stock_movements (id),
  INDEX idx_batch_barcodes_batch (batch_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE batch_history (
  id CHAR(36) NOT NULL PRIMARY KEY,
  batch_id CHAR(36) NOT NULL,
  action VARCHAR(20) NOT NULL,
  printed_quantity INT NULL,
  printed_by CHAR(36) NULL,
  action_details JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_history_action CHECK (action IN ('GENERATED', 'PRINTED', 'PARTIAL_PRINT', 'EXPORTED')),
  CONSTRAINT fk_history_batch FOREIGN KEY (batch_id) REFERENCES barcode_batches (id),
  CONSTRAINT fk_history_user FOREIGN KEY (printed_by) REFERENCES app_users (id),
  INDEX idx_batch_history_batch (batch_id),
  INDEX idx_batch_history_action (action),
  INDEX idx_batch_history_date (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE batch_exports (
  id CHAR(36) NOT NULL PRIMARY KEY,
  batch_id CHAR(36) NOT NULL,
  export_format VARCHAR(10) NOT NULL,
  export_file_path VARCHAR(255) NULL,
  exported_by CHAR(36) NULL,
  record_count INT NULL,
  file_size INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_exports_format CHECK (export_format IN ('CSV', 'PDF', 'EXCEL')),
  CONSTRAINT fk_exports_batch FOREIGN KEY (batch_id) REFERENCES barcode_batches (id),
  CONSTRAINT fk_exports_user FOREIGN KEY (exported_by) REFERENCES app_users (id),
  INDEX idx_batch_exports_batch (batch_id),
  INDEX idx_batch_exports_date (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_key, setting_value) VALUES
  ('company_name', 'MaterialFlow'),
  ('tagline', 'BARCODE INVENTORY'),
  ('logo_icon', '▦'),
  ('address', ''),
  ('phone', ''),
  ('email', '');

-- Ledger-first stock balance: applications insert movements; they never update stock directly.
CREATE VIEW item_stock_balance AS
SELECT
  i.id AS item_id,
  i.barcode,
  i.sku,
  i.name,
  i.category,
  i.unit,
  i.reorder_level,
  COALESCE(SUM(CASE
    WHEN sm.movement_type IN ('INWARD', 'RETURN_IN', 'ADJUSTMENT_IN') THEN sm.quantity
    ELSE -sm.quantity
  END), 0) AS available_quantity
FROM items i
LEFT JOIN stock_movements sm ON sm.item_id = i.id
GROUP BY i.id;
