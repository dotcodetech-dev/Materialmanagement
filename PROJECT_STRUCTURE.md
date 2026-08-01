# 📁 PROJECT STRUCTURE
## MaterialFlow Barcode Inventory Management System

**Author:** Ranjith Kumar  
**Organization:** Aveon Infotech Private Limited  
**Last Updated:** August 1, 2026  

---

## 📊 DIRECTORY TREE

```
materialflow/
├── app/                                # Next.js application root
│   ├── api/                           # API routes (backend)
│   │   ├── auth/                      # Authentication endpoints
│   │   │   ├── login/route.ts        # User login
│   │   │   ├── logout/route.ts       # User logout
│   │   │   ├── me/route.ts           # Current user info
│   │   │   └── seed/route.ts         # Database seeding
│   │   ├── items/                     # Item management
│   │   │   ├── route.ts              # Create/read items
│   │   │   └── [id]/route.ts         # Update/delete item
│   │   ├── customers/                # Customer management
│   │   │   ├── route.ts              # Create/read customers
│   │   │   └── [id]/route.ts         # Update/delete customer
│   │   ├── batches/                   # Batch barcode operations
│   │   │   └── route.ts              # Generate & list batches
│   │   ├── batch-details/            # Batch information
│   │   │   └── route.ts              # Get batch with barcodes
│   │   ├── batch-status/             # Batch status tracking
│   │   │   └── route.ts              # Get batch status
│   │   ├── batch-history/            # Batch audit trail
│   │   │   └── route.ts              # Log & retrieve history
│   │   ├── batch-export-csv/         # CSV export
│   │   │   └── route.ts              # Generate CSV download
│   │   ├── batch-exports/            # Export tracking
│   │   │   └── route.ts              # Log exports
│   │   ├── barcodes/                  # Barcode operations
│   │   │   ├── validate/route.ts     # Pre-scan validation
│   │   │   └── mark-scanned/route.ts # Mark scanned
│   │   ├── movements/                 # Stock movements
│   │   │   └── route.ts              # Record movements
│   │   ├── users/                     # User management
│   │   │   ├── route.ts              # Create/read users
│   │   │   └── [id]/route.ts         # Update/delete user
│   │   └── settings/                  # Application settings
│   │       └── route.ts              # Get/update settings
│   ├── login/                         # Login page
│   │   └── page.tsx                  # Login component
│   ├── page.tsx                       # Main application (dashboard)
│   ├── layout.tsx                     # Root layout wrapper
│   ├── styles.css                     # Global styles
│   ├── robots.ts                      # SEO robots.txt
│   └── sitemap.ts                     # SEO sitemap
│
├── lib/                               # Utility libraries
│   ├── db.ts                         # PostgreSQL connection pool
│   └── auth.ts                       # Authentication utilities
│
├── db/                               # Database files
│   ├── init/                         # Database initialization
│   │   └── 001_schema.sql            # Main schema creation
│   └── migrations/                   # Database migrations (future)
│
├── scripts/                          # Utility scripts
│   ├── create-batch-tables.js        # Create batch tables
│   ├── add-batch-history.js          # Create history tables
│   └── deploy-schema.js              # Deploy schema to DB
│
├── public/                           # Static assets
│   └── fonts/                        # Web fonts
│       └── MaterialSymbolsOutlined.ttf  # Material Symbols font
│
├── .claude/                          # Claude Code configuration
│   └── launch.json                   # Dev server config
│
├── .env.example                      # Environment variables template
├── .env.local                        # Local environment variables
├── .gitignore                        # Git ignore rules
├── .git/                             # Git repository
├── package.json                      # Node.js dependencies
├── package-lock.json                 # Locked dependency versions
├── tsconfig.json                     # TypeScript configuration
├── next.config.js                    # Next.js configuration
├── compose.yaml                      # Docker Compose config (optional)
├── middleware.ts                     # Next.js middleware
├── next-env.d.ts                     # TypeScript definitions
│
├── README.md                         # Project overview & quick start
├── DEPLOYMENT.md                     # Deployment guide (this file)
├── PRODUCT_DOCUMENT.md               # Product specification
├── PROJECT_STRUCTURE.md              # This file
├── LICENSE.md                        # License information
└── MEMORY.md                         # Development memory (Claude Code)

```

---

## 📂 DETAILED DIRECTORY DESCRIPTIONS

### `/app` - Next.js Application
The main Next.js 16 application directory containing both frontend and API routes.

**Key Files:**
- `page.tsx` - Main dashboard/application interface (~2000+ lines, comprehensive)
- `login/page.tsx` - Authentication login page
- `layout.tsx` - Root layout with global styling
- `styles.css` - Global CSS with Material Design 3 tokens

### `/app/api` - API Routes (Backend)

#### `/app/api/auth` - Authentication
Handles user login, logout, session management, and database seeding.

**Files:**
- `login/route.ts` - POST endpoint for user authentication
- `logout/route.ts` - POST endpoint to clear session
- `me/route.ts` - GET endpoint for current user info
- `seed/route.ts` - POST endpoint for initial data setup

#### `/app/api/items` - Item Management
CRUD operations for inventory items.

**Endpoints:**
- `GET /api/items` - Fetch all items
- `POST /api/items` - Create new item
- `PUT /api/items/[id]` - Update item
- `DELETE /api/items/[id]` - Delete item

#### `/app/api/batches` - Batch Barcode Generation
Core batch generation functionality.

**Endpoints:**
- `POST /api/batches` - Generate unique barcodes
- `GET /api/batches` - Fetch all batches with status

**Request Body (POST):**
```json
{
  "item_id": 1,
  "batch_reference": "PUMP-BATCH-001",
  "quantity": 100,
  "barcode_prefix": "MF-PUMP-"
}
```

#### `/app/api/batch-details` - Batch Information
Retrieves complete batch data including all barcodes.

**Endpoints:**
- `GET /api/batch-details?batch_id=1` - Get batch with barcodes array

#### `/app/api/batch-history` - Audit Trail
Logs and retrieves batch operation history.

**Endpoints:**
- `POST /api/batch-history` - Log batch action
- `GET /api/batch-history?batch_id=1` - Fetch history

#### `/app/api/batch-export-csv` - CSV Export
Generates downloadable CSV files from batch data.

**Endpoints:**
- `GET /api/batch-export-csv?batch_id=1` - Download CSV file

#### `/app/api/barcodes` - Barcode Operations

**Endpoints:**
- `POST /api/barcodes/validate` - Validate barcode before scan
- `POST /api/barcodes/mark-scanned` - Mark barcode as scanned

#### `/app/api/movements` - Stock Movements
Records inward and outward stock movements.

**Endpoints:**
- `GET /api/movements` - Fetch all movements
- `POST /api/movements` - Record new movement

#### `/app/api/users` - User Management
CRUD operations for application users.

**Endpoints:**
- `GET /api/users` - Fetch all users
- `POST /api/users` - Create new user
- `PUT /api/users/[id]` - Update user
- `DELETE /api/users/[id]` - Delete user

#### `/app/api/customers` - Customer Management
CRUD operations for customers/suppliers.

**Endpoints:**
- `GET /api/customers` - Fetch all customers
- `POST /api/customers` - Create new customer
- `PUT /api/customers/[id]` - Update customer
- `DELETE /api/customers/[id]` - Delete customer

### `/lib` - Utilities

#### `db.ts`
PostgreSQL connection pool configuration.

**Exports:**
- `pool` - Connection pool instance
- Database connection settings
- Error handling

**Usage:**
```typescript
import pool from "@/lib/db";
const result = await pool.query("SELECT * FROM items");
```

#### `auth.ts`
Authentication utilities and helpers.

**Functions:**
- Session management
- Password hashing
- User validation
- Role checking

### `/db` - Database

#### `db/init/001_schema.sql`
Main database schema definition.

**Creates Tables:**
1. `items` - Inventory items
2. `stock_movements` - Inward/outward tracking
3. `customers` - Customer/supplier info
4. `app_users` - Application users
5. `barcode_batches` - Batch metadata
6. `batch_barcodes` - Individual barcode records
7. `batch_history` - Audit trail
8. `batch_exports` - Export tracking

**Creates Indexes:**
- `batch_barcodes(barcode_code)` - Fast barcode lookup
- `batch_barcodes(batch_id, status)` - Scan status queries
- `barcode_batches(item_id)` - Item batch queries
- And others for performance

### `/scripts` - Utility Scripts

#### `scripts/create-batch-tables.js`
Creates batch-related tables if they don't exist.

**Run with:**
```bash
node scripts/create-batch-tables.js
```

#### `scripts/add-batch-history.js`
Creates history tracking tables.

**Run with:**
```bash
node scripts/add-batch-history.js
```

#### `scripts/deploy-schema.js`
Deploys the entire database schema.

**Run with:**
```bash
node scripts/deploy-schema.js
```

### `/public` - Static Assets

#### `public/fonts/`
Web fonts for Material Design.

**Contents:**
- `MaterialSymbolsOutlined.ttf` - Material Symbols Outlined font

---

## 🔧 CONFIGURATION FILES

### `.env.example`
Template for environment variables. Copy to `.env` and configure.

**Variables:**
```bash
# Database
DATABASE_URL=postgresql://user:password@host:5432/database_name

# Application
NODE_ENV=development
PORT=3000
NEXT_PUBLIC_API_URL=http://localhost:3000

# Session
SESSION_SECRET=your-secret-key-here
```

### `package.json`
Node.js project configuration and dependencies.

**Key Scripts:**
```json
{
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start",
    "lint": "next lint"
  }
}
```

**Main Dependencies:**
- `next` - React framework
- `react` - UI library
- `pg` - PostgreSQL driver
- `jsbarcode` - Barcode generation

### `tsconfig.json`
TypeScript configuration.

**Settings:**
- Target: ES2020
- Module: ESNext
- Strict mode enabled
- Path aliases for imports

### `next.config.js`
Next.js configuration.

**Settings:**
- React version 19
- API routes enabled
- Image optimization
- Compression enabled

### `.gitignore`
Git ignore patterns.

**Ignored:**
- `node_modules/`
- `.next/`
- `.env.local`
- `*.log`
- `.DS_Store`

### `compose.yaml`
Docker Compose configuration (optional).

**Services:**
- PostgreSQL database
- Next.js application
- Volume management
- Network configuration

### `middleware.ts`
Next.js middleware for route protection.

**Functionality:**
- Session validation
- Redirect unauthenticated users
- Route protection

---

## 📝 DOCUMENTATION FILES

### `README.md`
Quick start guide and project overview.

**Contents:**
- Installation steps
- Running the application
- Basic usage
- Troubleshooting

### `DEPLOYMENT.md`
Complete deployment guide.

**Contents:**
- System requirements
- Installation steps
- Configuration
- Database setup
- Running in production
- Troubleshooting guide
- Production checklist

### `PRODUCT_DOCUMENT.md`
Product specification and features.

**Contents:**
- Executive summary
- Key features
- Architecture
- Technical specs
- Security features
- Roadmap

### `PROJECT_STRUCTURE.md`
This file - explains directory organization.

### `LICENSE.md`
License information and terms.

---

## 🔄 FILE RELATIONSHIPS

### Data Flow

```
Client (React)
    ↓
Next.js Pages (page.tsx, login/page.tsx)
    ↓
API Routes (/api/*)
    ↓
Database Layer (PostgreSQL)
    ↓
SQL Queries (db/init/001_schema.sql)
```

### Authentication Flow

```
Login Page (login/page.tsx)
    ↓
POST /api/auth/login
    ↓
Validate credentials
    ↓
Create session cookie
    ↓
Redirect to Dashboard
    ↓
Middleware validates session
    ↓
Protected routes accessible
```

### Batch Generation Flow

```
Dashboard (app/page.tsx)
    ↓
Generate Batch Form
    ↓
POST /api/batches
    ↓
Validate item exists
    ↓
Create barcode_batches record
    ↓
Bulk insert batch_barcodes
    ↓
Log to batch_history
    ↓
Return success response
    ↓
Update UI with batch data
```

### Barcode Scanning Flow

```
Inward/Outward Tab (app/page.tsx)
    ↓
Scan barcode input
    ↓
POST /api/barcodes/validate
    ↓
Check batch_barcodes table
    ↓
Check one-time scan status
    ↓
Return validation result
    ↓
If valid, POST /api/movements
    ↓
POST /api/barcodes/mark-scanned
    ↓
Update stock levels
    ↓
Display in recent scans
```

### Label Printing Flow

```
Batch History (app/page.tsx)
    ↓
Click "Reprint batch"
    ↓
GET /api/batch-details?batch_id=X
    ↓
Load barcodes array
    ↓
Render print dialog
    ↓
Generate barcode images (JsBarcode)
    ↓
Display 30×30mm labels (7×9 grid)
    ↓
Browser print dialog
    ↓
Send to printer
```

---

## 💾 DATABASE SCHEMA OVERVIEW

### Core Tables (10 total)

1. **items** - Inventory items
   - Primary key: id
   - Unique: name

2. **barcode_batches** - Batch metadata
   - Primary key: id
   - Foreign key: item_id
   - Unique: batch_reference
   - Index: item_id

3. **batch_barcodes** - Individual barcodes
   - Primary key: id
   - Foreign keys: batch_id, item_id
   - Unique: barcode_code
   - Index: (batch_id, status), barcode_code

4. **stock_movements** - Inward/outward tracking
   - Primary key: id
   - Foreign key: item_id

5. **batch_history** - Audit trail
   - Primary key: id
   - Foreign key: batch_id

6. **batch_exports** - Export tracking
   - Primary key: id
   - Foreign key: batch_id

7. **customers** - Suppliers/customers
   - Primary key: id

8. **app_users** - Application users
   - Primary key: id
   - Unique: email

9. **settings** - Application configuration
   - Primary key: id

10. **movement_lines** - Movement details
    - Primary key: id
    - Foreign key: movement_id

---

## 🚀 BUILD & DEPLOYMENT

### Development

```bash
npm install          # Install dependencies
npm run dev          # Start dev server
# Open http://localhost:3000
```

### Production Build

```bash
npm run build        # Create optimized build
npm start            # Run production server
```

### Database Setup

```bash
# Initialize schema
psql -U user -d database -f db/init/001_schema.sql

# Run setup scripts
node scripts/deploy-schema.js
node scripts/create-batch-tables.js
node scripts/add-batch-history.js
```

---

## 📊 FILE SIZE STATISTICS

| File/Directory | Size | Lines | Purpose |
|---|---|---|---|
| app/page.tsx | 200KB | 2000+ | Main app (all features) |
| db/init/001_schema.sql | 15KB | 300+ | Database schema |
| package.json | 5KB | 50 | Dependencies |
| .env.example | 1KB | 20 | Configuration |
| Total Source | ~300KB | 4000+ | Production code |
| With node_modules | ~500MB | - | All dependencies |

---

## 🔐 Security Structure

**Authentication:** `lib/auth.ts`, `/api/auth/*`  
**Authorization:** `middleware.ts`, Role checks in API  
**Database:** Parameterized queries, connection pooling  
**Frontend:** XSS protection through React, CSRF tokens  
**Audit:** `batch_history`, `batch_exports` tables  

---

## 📌 IMPORTANT NOTES

1. **Main Application:** All UI logic is in `app/page.tsx` (2000+ lines)
2. **API Routes:** RESTful endpoints in `/api/*` directories
3. **Database:** PostgreSQL 18+ required, schema in `db/init/001_schema.sql`
4. **Authentication:** Cookie-based sessions with role-based access
5. **Batch System:** Unique barcode generation with one-time scan enforcement
6. **Label Printing:** 30×30mm square format, 63 per A4 page
7. **Export:** CSV format with batch summaries
8. **Audit Trail:** Complete logging of all operations

---

**Last Updated:** August 1, 2026  
**Version:** 1.0  
**Author:** Ranjith Kumar  
**Organization:** Aveon Infotech Private Limited
