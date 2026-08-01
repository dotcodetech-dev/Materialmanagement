const { Client } = require("pg");

const connectionString = process.env.MATERIALFLOW_DATABASE_URL || "postgresql://postgres:user@localhost:5432/postgres?sslmode=disable";

async function addBatchHistoryTables() {
  const client = new Client({
    connectionString,
    ssl: { rejectUnauthorized: false }
  });

  await client.connect();
  try {
    console.log("Creating batch_history table...");
    await client.query(`
      CREATE TABLE IF NOT EXISTS batch_history (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        batch_id UUID NOT NULL REFERENCES barcode_batches(id),
        action TEXT NOT NULL CHECK (action IN ('GENERATED', 'PRINTED', 'PARTIAL_PRINT', 'EXPORTED')),
        printed_quantity INTEGER,
        printed_by UUID REFERENCES app_users(id),
        action_details JSONB,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      )
    `);

    console.log("Creating batch_exports table...");
    await client.query(`
      CREATE TABLE IF NOT EXISTS batch_exports (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        batch_id UUID NOT NULL REFERENCES barcode_batches(id),
        export_format TEXT NOT NULL CHECK (export_format IN ('CSV', 'PDF', 'EXCEL')),
        export_file_path TEXT,
        exported_by UUID REFERENCES app_users(id),
        record_count INTEGER,
        file_size INTEGER,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      )
    `);

    console.log("Altering barcode_batches table to add status tracking...");
    await client.query(`
      ALTER TABLE barcode_batches
      ADD COLUMN IF NOT EXISTS status_detail TEXT CHECK (status_detail IN ('CREATED', 'PARTIALLY_PRINTED', 'FULLY_PRINTED', 'ARCHIVED')),
      ADD COLUMN IF NOT EXISTS total_printed INTEGER DEFAULT 0,
      ADD COLUMN IF NOT EXISTS last_printed_at TIMESTAMPTZ,
      ADD COLUMN IF NOT EXISTS last_printed_by UUID REFERENCES app_users(id)
    `);

    console.log("Creating indexes for batch_history...");
    await client.query(`CREATE INDEX IF NOT EXISTS idx_batch_history_batch ON batch_history(batch_id)`);
    await client.query(`CREATE INDEX IF NOT EXISTS idx_batch_history_action ON batch_history(action)`);
    await client.query(`CREATE INDEX IF NOT EXISTS idx_batch_history_date ON batch_history(created_at DESC)`);

    console.log("Creating indexes for batch_exports...");
    await client.query(`CREATE INDEX IF NOT EXISTS idx_batch_exports_batch ON batch_exports(batch_id)`);
    await client.query(`CREATE INDEX IF NOT EXISTS idx_batch_exports_date ON batch_exports(created_at DESC)`);

    console.log("✓ Batch history tables created successfully!");
  } catch (error) {
    console.error(`Error creating batch history tables: ${error.message}`);
    process.exit(1);
  } finally {
    await client.end();
  }
}

addBatchHistoryTables();
