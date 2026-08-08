-- MaterialFlow — barcode v2 migration.
-- Adds the compact-barcode columns + constraints. Safe to run on an install
-- where batch_barcodes / barcode_batches are EMPTY (no data backfill needed).
-- If those tables still hold OLD-scheme rows, clear them first
-- (db/reset_transactions.sql) — this migration does not backfill unit_serial.

ALTER TABLE barcode_batches
  ADD COLUMN verified_at DATETIME NULL AFTER barcode_prefix,
  ADD COLUMN verified_by CHAR(36) NULL AFTER verified_at;

ALTER TABLE batch_barcodes
  ADD COLUMN unit_serial BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER barcode_code,
  ADD COLUMN item_code VARCHAR(64) NULL AFTER item_id,
  ADD COLUMN batch_reference VARCHAR(64) NULL AFTER item_code;

-- Drop the default now that the column exists (new rows always set it explicitly).
ALTER TABLE batch_barcodes ALTER COLUMN unit_serial DROP DEFAULT;

ALTER TABLE batch_barcodes
  ADD CONSTRAINT uq_barcodes_serial UNIQUE (unit_serial),
  ADD CONSTRAINT uq_barcodes_batch_unit UNIQUE (batch_id, unit_number);
