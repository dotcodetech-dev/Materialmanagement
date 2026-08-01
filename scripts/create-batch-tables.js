const { Client } = require("pg");

const connectionString = process.env.MATERIALFLOW_DATABASE_URL || "postgresql://postgres:user@localhost:5432/postgres?sslmode=disable";

async function createBatchTables() {
  const client = new Client({
    connectionString,
    ssl: { rejectUnauthorized: false }
  });

  await client.connect();
  try {
    console.log("Creating barcode_batches table...");
    await client.query(`
      CREATE TABLE IF NOT EXISTS barcode_batches (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        item_id UUID NOT NULL REFERENCES items(id),
        batch_reference TEXT NOT NULL UNIQUE,
        quantity_total INTEGER NOT NULL CHECK (quantity_total > 0),
        quantity_generated INTEGER NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'COMPLETED', 'ARCHIVED')),
        barcode_prefix TEXT NOT NULL,
        created_by UUID REFERENCES app_users(id),
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      )
    `);

    console.log("Creating batch_barcodes table...");
    await client.query(`
      CREATE TABLE IF NOT EXISTS batch_barcodes (
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
      )
    `);

    console.log("Creating indexes...");
    await client.query(`CREATE INDEX IF NOT EXISTS idx_batch_barcodes_code ON batch_barcodes(barcode_code)`);
    await client.query(`CREATE INDEX IF NOT EXISTS idx_batch_barcodes_batch ON batch_barcodes(batch_id, status)`);
    await client.query(`CREATE INDEX IF NOT EXISTS idx_barcode_batches_item ON barcode_batches(item_id)`);

    console.log("✓ Batch tables created successfully!");
  } catch (error) {
    console.error(`Error creating batch tables: ${error.message}`);
    process.exit(1);
  } finally {
    await client.end();
  }
}

createBatchTables();
