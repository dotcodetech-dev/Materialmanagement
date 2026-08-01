const fs = require("fs");
const path = require("path");
const { Client } = require("pg");

const connectionString = process.env.MATERIALFLOW_DATABASE_URL;

if (!connectionString) {
  console.error("Set MATERIALFLOW_DATABASE_URL before running this command.");
  process.exit(1);
}

async function deploy() {
  const client = new Client({
    connectionString,
    ssl: { rejectUnauthorized: false }
  });

  await client.connect();
  try {
    const existing = await client.query(`
      SELECT count(*)::int AS count
      FROM information_schema.tables
      WHERE table_schema = 'public'
        AND table_name IN ('app_users', 'customers', 'items', 'stock_movements')
    `);

    if (existing.rows[0].count > 0) {
      throw new Error("MaterialFlow tables already exist. Stopped to avoid changing existing data.");
    }

    const schema = fs.readFileSync(path.join(__dirname, "..", "db", "init", "001_schema.sql"), "utf8");
    await client.query("BEGIN");
    await client.query(schema);
    await client.query("COMMIT");
    console.log("MaterialFlow schema deployed successfully.");
  } catch (error) {
    try {
      await client.query("ROLLBACK");
    } catch {
      // The transaction may not have started yet.
    }
    throw error;
  } finally {
    await client.end();
  }
}

deploy().catch((error) => {
  console.error(`Database deployment failed: ${error.message}`);
  process.exit(1);
});
