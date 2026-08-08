-- MaterialFlow — barcode v2 migration (non-destructive, backfills existing rows).
-- Adds the compact-barcode columns + constraints and backfills any existing
-- batch_barcodes so the UNIQUE constraints can be applied without data loss.
-- Existing rows keep their old barcode_code (already printed); NEW batches use
-- the compact MFU scheme, with global serials continuing past the backfilled max.

ALTER TABLE barcode_batches
  ADD COLUMN verified_at DATETIME NULL AFTER barcode_prefix,
  ADD COLUMN verified_by CHAR(36) NULL AFTER verified_at;

ALTER TABLE batch_barcodes
  ADD COLUMN unit_serial BIGINT UNSIGNED NULL AFTER barcode_code,
  ADD COLUMN item_code VARCHAR(64) NULL AFTER item_id,
  ADD COLUMN batch_reference VARCHAR(64) NULL AFTER item_code;

-- Backfill the snapshot columns from the linked rows.
UPDATE batch_barcodes bb JOIN items i ON i.id = bb.item_id
  SET bb.item_code = i.barcode
  WHERE bb.item_code IS NULL;
UPDATE batch_barcodes bb JOIN barcode_batches b ON b.id = bb.batch_id
  SET bb.batch_reference = b.batch_reference
  WHERE bb.batch_reference IS NULL;

-- Backfill a unique global serial for every existing row.
SET @s := 0;
UPDATE batch_barcodes SET unit_serial = (@s := @s + 1) ORDER BY created_at, unit_number;

-- Now the column can be NOT NULL and uniquely constrained.
ALTER TABLE batch_barcodes MODIFY unit_serial BIGINT UNSIGNED NOT NULL;
ALTER TABLE batch_barcodes
  ADD CONSTRAINT uq_barcodes_serial UNIQUE (unit_serial),
  ADD CONSTRAINT uq_barcodes_batch_unit UNIQUE (batch_id, unit_number);
