# MaterialFlow

Barcode-ready material management MVP built with Next.js and TypeScript.

## Included

- Items with barcode/SKU, category, unit, opening stock and reorder level
- Customers
- Inward and outward stock movement records
- Barcode/SKU entry field (USB barcode scanners work as keyboard input)
- Stock ledger, dashboard and low-stock alerts
- Demo data is saved in browser local storage

## Run locally

```powershell
npm.cmd install
npm.cmd run dev
```

Open `http://localhost:3000`.

## PostgreSQL with Docker

1. Install and start Docker Desktop.
2. Copy `.env.example` to `.env`, then replace `POSTGRES_PASSWORD` with a strong password.
3. Start the database:

```powershell
docker compose up -d db
```

4. Verify it is ready:

```powershell
docker compose ps
```

PostgreSQL is available on `localhost:5432` by default. The first startup creates the tables and the `item_stock_balance` stock-ledger view from `db/init/001_schema.sql`. Data persists in the named Docker volume `materialflow_postgres_data`.

To stop the database without deleting data:

```powershell
docker compose stop db
```

## Deploy schema to Railway PostgreSQL

Keep your Railway URL private. In PowerShell, set it only for the current terminal session, then run:

```powershell
$env:MATERIALFLOW_DATABASE_URL = 'your Railway public PostgreSQL URL'
npm.cmd run db:deploy
```

The command uses TLS, deploys the schema in one transaction, and stops if MaterialFlow tables already exist.

## Production next step

Connect this interface to a FastAPI + PostgreSQL service, then add user login, audit logs, label-printing integration and server backups. The database schema is now included, but the current MVP interface still uses browser local storage until that API is built.
