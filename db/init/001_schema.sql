-- MaterialFlow database schema. This file runs only when the Docker volume is first created.

CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TYPE movement_type AS ENUM ('INWARD', 'OUTWARD', 'RETURN_IN', 'RETURN_OUT', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT');

CREATE TABLE app_users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  full_name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL CHECK (role IN ('ADMIN', 'STOREKEEPER', 'MANAGER', 'VIEWER')),
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE customers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  phone TEXT,
  email TEXT,
  address TEXT,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  barcode TEXT NOT NULL UNIQUE,
  sku TEXT UNIQUE,
  name TEXT NOT NULL,
  category TEXT NOT NULL DEFAULT 'General',
  unit TEXT NOT NULL,
  reorder_level NUMERIC(14, 3) NOT NULL DEFAULT 0 CHECK (reorder_level >= 0),
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE stock_movements (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  item_id UUID NOT NULL REFERENCES items(id),
  customer_id UUID REFERENCES customers(id),
  movement_type movement_type NOT NULL,
  quantity NUMERIC(14, 3) NOT NULL CHECK (quantity > 0),
  reference_number TEXT,
  notes TEXT,
  occurred_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  recorded_by UUID REFERENCES app_users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_stock_movements_item_time ON stock_movements (item_id, occurred_at DESC);
CREATE INDEX idx_stock_movements_customer_time ON stock_movements (customer_id, occurred_at DESC) WHERE customer_id IS NOT NULL;
CREATE INDEX idx_items_name ON items (name);

-- A ledger-first stock balance. Applications insert movements; they never update stock directly.
-- Batch Barcode Management Tables
CREATE TABLE barcode_batches (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  item_id UUID NOT NULL REFERENCES items(id),
  batch_reference TEXT NOT NULL UNIQUE,
  quantity_total INTEGER NOT NULL CHECK (quantity_total > 0),
  quantity_generated INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'COMPLETED', 'ARCHIVED')),
  barcode_prefix TEXT NOT NULL,
  created_by UUID REFERENCES app_users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE batch_barcodes (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  batch_id UUID NOT NULL REFERENCES barcode_batches(id),
  barcode_code TEXT NOT NULL UNIQUE,
  item_id UUID NOT NULL REFERENCES items(id),
  unit_number INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'UNSCANNED' CHECK (status IN ('UNSCANNED', 'SCANNED')),
  scanned_at TIMESTAMPTZ,
  scanned_by UUID REFERENCES app_users(id),
  movement_id UUID REFERENCES stock_movements(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_batch_barcodes_code ON batch_barcodes(barcode_code);
CREATE INDEX idx_batch_barcodes_batch ON batch_barcodes(batch_id, status);
CREATE INDEX idx_barcode_batches_item ON barcode_batches(item_id);

-- A ledger-first stock balance. Applications insert movements; they never update stock directly.
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
