-- MaterialFlow — reset transactional data to a clean slate.
--
-- KEEPS:   items, app_users, app_settings   (master data + logins + branding)
-- CLEARS:  all stock movements, all batches, and (optionally) customers.
--
-- After running: every item remains but with stock 0; Ledger, Reports,
-- Dashboard counters, and Batch History are empty. item_stock_balance is a
-- VIEW and recomputes to 0 automatically.
--
-- BACK UP FIRST. This is irreversible. Import via phpMyAdmin or run through
-- the maintenance script. Order-independent thanks to FK checks off.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM batch_barcodes;
DELETE FROM batch_history;
DELETE FROM batch_exports;
DELETE FROM barcode_batches;
DELETE FROM stock_movements;

-- Comment the next line out if you want to KEEP customers.
DELETE FROM customers;

SET FOREIGN_KEY_CHECKS = 1;
