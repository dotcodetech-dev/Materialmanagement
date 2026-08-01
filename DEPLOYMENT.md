# 🚀 DEPLOYMENT GUIDE
## MaterialFlow - Barcode Inventory Management System

**Product Name:** MaterialFlow  
**Version:** 1.0.0  
**Author:** Ranjith Kumar  
**Organization:** Aveon Infotech Private Limited  
**Last Updated:** August 1, 2026  

---

## 📋 TABLE OF CONTENTS

1. [System Requirements](#system-requirements)
2. [Prerequisite Software](#prerequisite-software)
3. [Deployment Package](#deployment-package)
4. [Installation Steps](#installation-steps)
5. [Configuration](#configuration)
6. [Database Setup](#database-setup)
7. [Running the Application](#running-the-application)
8. [Verification & Testing](#verification--testing)
9. [Troubleshooting](#troubleshooting)
10. [Production Checklist](#production-checklist)

---

## 🖥️ SYSTEM REQUIREMENTS

### **Server Requirements**
```
CPU: Minimum 2 cores (4 cores recommended)
RAM: Minimum 4GB (8GB recommended)
Storage: Minimum 20GB (100GB recommended for database growth)
OS: Linux (Ubuntu 20.04+) or Windows Server 2019+
Network: Stable internet connection, port 3000 accessible
```

### **Client Requirements**
```
Browser: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
Display: Minimum 1024×768 (1280×720+ recommended)
Network: Broadband internet connection
Screen: Tablet-friendly (768px minimum width)
```

### **Barcode Hardware (Optional)**
```
Barcode Scanner: USB or Bluetooth
Label Printer: 30mm thermal printer
Print Format: Standard Avery 30×30mm labels
```

---

## 💾 PREREQUISITE SOFTWARE

### **Required Software**

#### **1. Node.js & npm**
```bash
Version: Node.js 18.0.0 or higher
npm: 9.0.0 or higher

Installation (Linux - Ubuntu/Debian):
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

Installation (Windows):
Download from https://nodejs.org/en/download/
Run installer (includes npm)
Verify: node -v && npm -v
```

#### **2. PostgreSQL Database**
```bash
Version: PostgreSQL 12 or higher (18+ recommended)
Port: 5432 (default)
User: postgres
Password: Set during installation

Installation (Linux - Ubuntu/Debian):
sudo apt-get update
sudo apt-get install -y postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql

Installation (Windows):
Download from https://www.postgresql.org/download/windows/
Run installer and follow wizard
Note: Remember the postgres password during setup!

Verify: psql --version
```

#### **3. Git (for version control)**
```bash
Version: 2.30 or higher

Installation (Linux):
sudo apt-get install -y git

Installation (Windows):
Download from https://git-scm.com/download/win
Run installer

Verify: git --version
```

#### **4. PM2 (Process Manager - Optional but Recommended)**
```bash
For production servers:
npm install -g pm2

Usage:
pm2 start npm --name "materialflow" -- start
pm2 save
pm2 startup

Verify: pm2 --version
```

### **Optional Software**

#### **5. Docker (for containerized deployment)**
```bash
Version: Docker 20.10+
Download: https://www.docker.com/products/docker-desktop

Benefits:
- Consistent environment across systems
- Easy scaling and deployment
- Simplified database management
```

#### **6. Nginx (Reverse Proxy - for production)**
```bash
Installation (Linux):
sudo apt-get install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx

Purpose:
- Load balancing
- SSL/TLS termination
- Static file serving
- Request compression
```

---

## 📦 DEPLOYMENT PACKAGE

### **Package Contents**

```
materialflow/
├── app/                          # Next.js application
│   ├── api/                      # API routes (backend)
│   ├── page.tsx                  # Main application
│   ├── login/page.tsx            # Login page
│   ├── layout.tsx                # Layout wrapper
│   └── styles.css                # Global styles
├── lib/                          # Utilities
│   ├── db.ts                     # Database connection
│   └── auth.ts                   # Authentication
├── db/                           # Database scripts
│   ├── init/001_schema.sql       # Database schema
│   └── migrations/               # Future migrations
├── scripts/                      # Setup scripts
│   ├── create-batch-tables.js
│   ├── add-batch-history.js
│   └── deploy-schema.js
├── public/                       # Static assets
├── package.json                  # Dependencies
├── .env.example                  # Environment template
├── next.config.js                # Next.js config
├── tsconfig.json                 # TypeScript config
├── middleware.ts                 # Route protection
├── README.md                      # Quick start
├── PRODUCT_DOCUMENT.md           # Product spec
├── PROJECT_STRUCTURE.md          # File organization
├── DEPLOYMENT.md                 # This file
└── LICENSE.md                    # License terms
```

### **Package Size**
```
Source Code: ~50MB
Dependencies: ~500MB (with node_modules)
Database: ~100MB (initial, grows with data)
Total: ~650MB
```

### **Download/Clone Package**
```bash
# Option 1: Clone from GitHub
git clone https://github.com/dotcodetech-dev/Materialmanagement.git
cd Materialmanagement

# Option 2: Download ZIP
wget https://github.com/dotcodetech-dev/Materialmanagement/archive/main.zip
unzip main.zip
cd Materialmanagement-main
```

---

## 🚀 INSTALLATION STEPS

### **Step 1: Verify Prerequisites**

```bash
# Check Node.js version
node -v          # Should be v18.0.0 or higher
npm -v            # Should be 9.0.0 or higher

# Check PostgreSQL
psql --version    # Should be 12 or higher

# Check Git
git --version     # Should be 2.30 or higher
```

### **Step 2: Clone Repository**

```bash
git clone https://github.com/dotcodetech-dev/Materialmanagement.git
cd Materialmanagement
```

### **Step 3: Install Dependencies**

```bash
# Install all Node.js packages
npm install

# Verify installation
npm list | grep -E "next|react|pg|jsbarcode"

# Should show recent versions
```

### **Step 4: Create Environment File**

```bash
# Copy example configuration
cp .env.example .env

# Edit with your settings
nano .env  # or use your editor
```

**Sample .env configuration:**
```bash
# Database Connection
DATABASE_URL="postgresql://postgres:password@localhost:5432/materialflow_db"
MATERIALFLOW_DATABASE_URL="postgresql://postgres:password@localhost:5432/materialflow_db"

# Application Settings
NODE_ENV="production"
PORT=3000
NEXT_PUBLIC_API_URL="http://localhost:3000"

# Session Configuration
SESSION_SECRET="your-very-secret-key-change-in-production-12345"
```

### **Step 5: Create Database**

```bash
# Connect to PostgreSQL
psql -U postgres

# Create database
CREATE DATABASE materialflow_db;

# Create user (optional, for better security)
CREATE USER materialflow_user WITH PASSWORD 'secure_password_here';
ALTER ROLE materialflow_user CREATEDB;

# Grant privileges
GRANT ALL PRIVILEGES ON DATABASE materialflow_db TO materialflow_user;

# Exit
\q
```

### **Step 6: Initialize Database Schema**

```bash
# Load main schema
psql -U postgres -d materialflow_db -f db/init/001_schema.sql

# Run deployment script
node scripts/deploy-schema.js

# Create batch tables
node scripts/create-batch-tables.js

# Create history tables
node scripts/add-batch-history.js
```

**Verify schema creation:**
```bash
psql -U postgres -d materialflow_db -c "\dt"

# You should see these tables:
# - items
# - stock_movements
# - customers
# - app_users
# - barcode_batches
# - batch_barcodes
# - batch_history
# - batch_exports
# - movement_lines
```

### **Step 7: Build Application**

```bash
# Create optimized production build
npm run build

# Verify build succeeded
ls -la .next

# Check for build errors
npm run lint
```

### **Step 8: Test Application (Development)**

```bash
# Start development server
npm run dev

# Open browser
# Navigate to http://localhost:3000

# Test login functionality
# Default email: admin@materialflow.com
```

---

## ⚙️ CONFIGURATION

### **Environment Variables (.env)**

#### **Database Configuration**
```bash
# PostgreSQL Connection String
DATABASE_URL="postgresql://postgres:password@localhost:5432/materialflow_db"

# Alternative if using separate user
MATERIALFLOW_DATABASE_URL="postgresql://materialflow_user:password@localhost:5432/materialflow_db"
```

#### **Application Settings**
```bash
# Node environment
NODE_ENV="production"  # or "development"

# Application port
PORT=3000

# API base URL (for frontend requests)
NEXT_PUBLIC_API_URL="http://localhost:3000"

# For production with domain:
NEXT_PUBLIC_API_URL="https://inventory.yourdomain.com"
```

#### **Security Settings**
```bash
# Session secret key (MUST change in production!)
SESSION_SECRET="generate-a-long-random-string-here"

# To generate a secure key:
# node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

#### **Optional Email Configuration**
```bash
# Email settings (if needed in future)
SMTP_HOST="smtp.gmail.com"
SMTP_PORT=587
SMTP_USER="your-email@gmail.com"
SMTP_PASSWORD="your-app-password"
```

### **Database Connection Settings**

#### **Local Development**
```bash
DATABASE_URL="postgresql://postgres:password@localhost:5432/materialflow_db"
NODE_ENV="development"
```

#### **Remote Database (Railway, Heroku, etc.)**
```bash
# Example: Railway database
DATABASE_URL="postgresql://user:password@postgres-omg7-production.up.railway.app:5432/materialflow_db"
NODE_ENV="production"
```

#### **Connection Pooling**
```bash
# For production with many users, add to DATABASE_URL:
DATABASE_URL="postgresql://user:password@host:5432/db?sslmode=require&statement_timeout=30000"
```

### **Next.js Configuration (next.config.js)**

**Already configured for:**
- React 19 support
- API routes enabled
- Image optimization
- Compression enabled
- TypeScript support

No changes needed unless customizing behavior.

### **TypeScript Configuration**

Edit `tsconfig.json` if needed:
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "lib": ["ES2020", "DOM"],
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true
  }
}
```

---

## 🗄️ DATABASE SETUP

### **Initial Database Creation**

```bash
# Step 1: Connect to PostgreSQL as admin
sudo -u postgres psql

# Step 2: Create dedicated user (recommended)
CREATE USER materialflow_user WITH PASSWORD 'secure_password_123';

# Step 3: Alter role to create databases
ALTER ROLE materialflow_user CREATEDB;

# Step 4: Create database
CREATE DATABASE materialflow_db OWNER materialflow_user;

# Step 5: Grant privileges
GRANT ALL PRIVILEGES ON DATABASE materialflow_db TO materialflow_user;

# Exit
\q
```

### **Load Database Schema**

```bash
# Method 1: Using psql directly
psql -U materialflow_user -d materialflow_db -f db/init/001_schema.sql

# Method 2: Using Node.js script
node scripts/deploy-schema.js
```

### **Create Extended Schema**

```bash
# Create batch history tables
node scripts/create-batch-tables.js

# Create export tracking tables
node scripts/add-batch-history.js

# Verify all tables
psql -U materialflow_user -d materialflow_db -c "\dt"
```

### **Seed Initial Data**

```bash
# Add admin user (optional)
psql -U materialflow_user -d materialflow_db <<EOF
INSERT INTO app_users (full_name, email, password_hash, role, status)
VALUES ('Admin User', 'admin@materialflow.com', 'hashed_password_here', 'ADMIN', 'ACTIVE');

INSERT INTO items (name, barcode, category, unit, reorder_level)
VALUES ('Sample Item', '9789390166268', 'Equipment', 'Nos', 5);
EOF

# Or use the seed endpoint (if implemented)
curl -X POST http://localhost:3000/api/auth/seed
```

### **Database Backup**

```bash
# Create backup
pg_dump -U materialflow_user -d materialflow_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Compress backup
gzip backup_20260801_120000.sql

# Restore from backup
psql -U materialflow_user -d materialflow_db < backup_20260801_120000.sql
```

### **Backup Automation (Linux)**

```bash
# Create backup script: /usr/local/bin/backup-materialflow.sh
#!/bin/bash
BACKUP_DIR="/backups/materialflow"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

pg_dump -U materialflow_user -d materialflow_db | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete

# Make executable
chmod +x /usr/local/bin/backup-materialflow.sh

# Add to crontab (daily at 2 AM)
crontab -e
# Add line: 0 2 * * * /usr/local/bin/backup-materialflow.sh
```

---

## ▶️ RUNNING THE APPLICATION

### **Development Mode**

```bash
# Start development server with hot reload
npm run dev

# Server starts at http://localhost:3000
# Auto-reload on code changes
# Debug mode active
# Connect database for testing

# Stop with Ctrl+C
```

### **Production Mode - Option 1: Direct Node**

```bash
# Build first
npm run build

# Start production server
NODE_ENV=production npm start

# Server runs at http://localhost:3000
# Single instance
# Manual restart required if it crashes
```

### **Production Mode - Option 2: Using PM2 (Recommended)**

```bash
# Install PM2 globally
npm install -g pm2

# Start application with PM2
pm2 start npm --name "materialflow" -- start

# Start on system boot
pm2 startup
pm2 save

# Monitor
pm2 monit

# View logs
pm2 logs materialflow

# Restart
pm2 restart materialflow

# Stop
pm2 stop materialflow

# Remove from PM2
pm2 delete materialflow
```

### **Production Mode - Option 3: Using Docker**

```bash
# Build Docker image
docker build -t materialflow .

# Run container
docker run -d \
  --name materialflow \
  -p 3000:3000 \
  --env-file .env \
  -v materialflow_data:/app/data \
  materialflow

# Stop container
docker stop materialflow

# View logs
docker logs materialflow
```

### **Using Nginx Reverse Proxy**

```bash
# Install Nginx
sudo apt-get install -y nginx

# Create config: /etc/nginx/sites-available/materialflow
server {
    listen 80;
    server_name inventory.yourdomain.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}

# Enable site
sudo ln -s /etc/nginx/sites-available/materialflow /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### **Access Application**

```
Development:  http://localhost:3000
Production:   http://your-server-ip:3000
Domain:       https://inventory.yourdomain.com

Default Credentials:
Email:    admin@materialflow.com
Password: (Set during first login or via seed)
```

---

## ✅ VERIFICATION & TESTING

### **Health Checks**

```bash
# 1. Server Running
curl http://localhost:3000
# Expected: HTML response

# 2. API Working
curl http://localhost:3000/api/items
# Expected: JSON response or error if not authenticated

# 3. Database Connected
psql -U materialflow_user -d materialflow_db -c "SELECT COUNT(*) FROM items;"
# Expected: count of items (0 initially)

# 4. Authentication
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email":"admin@materialflow.com",
    "password":"your_password"
  }'
# Expected: Session cookie in response
```

### **Functional Testing**

```
FEATURE 1: Create Item
□ Login to application
□ Navigate to Items section
□ Click "Add Item" button
□ Fill item details: name, barcode, category, unit
□ Submit form
□ Verify: Item appears in list

FEATURE 2: Generate Batch
□ Navigate to Items
□ Click "Generate batch" on an item
□ Enter batch reference (e.g., "BATCH-001")
□ Enter quantity (e.g., 100)
□ Optional: Custom barcode prefix
□ Submit
□ Verify: Batch appears in Batch History with status "COMPLETED"

FEATURE 3: Print Labels
□ Navigate to Batch History
□ Click "Reprint batch" button
□ Verify: Print dialog opens
□ Verify: 63 labels displayed on page (7×9 grid)
□ Verify: Each label is 30×30mm square
□ Verify: Labels contain: Item name, unit number, barcode, barcode code
□ Print to printer or PDF

FEATURE 4: Scan Test
□ Navigate to Inward Movement
□ Scan or type a barcode
□ Verify: Item added to "Recent Scans"
□ Verify: Stock level updated
□ Try scanning same barcode again
□ Verify: System shows "Already scanned" error

FEATURE 5: Export Test
□ Navigate to Batch History
□ Click "Export as CSV" on a batch
□ Verify: CSV file downloads
□ Open file in Excel/Google Sheets
□ Verify: Data includes unit number, barcode, scan status, etc.

FEATURE 6: User Management (Admin only)
□ Navigate to Users section
□ Add new user with role "STOREKEEPER"
□ Verify: User can log in
□ Test: User can only scan, not generate batches
□ Verify: Role-based access control works
```

### **Performance Testing**

```bash
# Using Apache Bench (ab)
sudo apt-get install -y apache2-utils

# Test homepage
ab -n 100 -c 10 http://localhost:3000/

# Test API endpoint
ab -n 100 -c 10 http://localhost:3000/api/items

# Expected results:
# Response time: < 500ms per request
# Throughput: 20+ requests/second
# Failed requests: 0
```

### **Load Testing**

```bash
# Using wrk (modern load testing)
wrk -t4 -c100 -d30s http://localhost:3000/

# Test results should show:
# Requests/sec: 50+
# Latency: p50 < 100ms, p99 < 500ms
# Errors: None
```

---

## 🔧 TROUBLESHOOTING

### **Common Issues & Solutions**

#### **Issue 1: Port 3000 Already in Use**

```bash
# Solution 1: Find and kill process on port 3000
lsof -ti :3000 | xargs kill -9

# Solution 2: Use different port
PORT=3001 npm start

# Solution 3: Check what's using the port
netstat -tlnp | grep 3000
# or on macOS:
lsof -i :3000
```

#### **Issue 2: Database Connection Failed**

```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Start PostgreSQL if stopped
sudo systemctl start postgresql

# Verify connection string in .env
echo $DATABASE_URL

# Test connection directly
psql -U postgres -d materialflow_db -c "SELECT version();"

# Check if database exists
psql -U postgres -l | grep materialflow
```

#### **Issue 3: Build Fails**

```bash
# Clear cache and rebuild
rm -rf .next node_modules package-lock.json
npm install
npm run build

# Check for TypeScript errors
npm run lint

# Check build output
npm run build 2>&1 | tail -20
```

#### **Issue 4: Out of Memory During Build**

```bash
# Increase Node memory limit
NODE_OPTIONS=--max-old-space-size=4096 npm run build

# For production server, add to .env:
NODE_OPTIONS=--max-old-space-size=4096
```

#### **Issue 5: Barcode Labels Not Printing**

```bash
# Check if barcodes exist in database
psql -U materialflow_user -d materialflow_db \
  -c "SELECT COUNT(*) FROM batch_barcodes;"

# Get sample barcode data
psql -U materialflow_user -d materialflow_db \
  -c "SELECT barcode_code, unit_number FROM batch_barcodes LIMIT 5;"

# Verify batch exists
psql -U materialflow_user -d materialflow_db \
  -c "SELECT * FROM barcode_batches WHERE id = 1;"
```

#### **Issue 6: Authentication Fails**

```bash
# Check if users table has data
psql -U materialflow_user -d materialflow_db \
  -c "SELECT id, email, role FROM app_users;"

# Seed initial admin user
psql -U materialflow_user -d materialflow_db <<EOF
INSERT INTO app_users (full_name, email, password_hash, role, status)
VALUES ('Admin', 'admin@materialflow.com', 'hashed_pass', 'ADMIN', 'ACTIVE');
EOF

# Check session table exists
psql -U materialflow_user -d materialflow_db -c "\dt" | grep session
```

#### **Issue 7: Slow Database Queries**

```bash
# Enable query logging
psql -U materialflow_user -d materialflow_db <<EOF
ALTER SYSTEM SET log_min_duration_statement = 1000;
SELECT pg_reload_conf();
EOF

# Check slow query log
tail -f /var/log/postgresql/postgresql.log

# Optimize slow queries
EXPLAIN ANALYZE SELECT * FROM batch_barcodes WHERE batch_id = 1;
```

### **Log Files Location**

```bash
# Application logs (if using PM2)
pm2 logs materialflow

# Or check PM2 log directory
~/.pm2/logs/materialflow-out.log
~/.pm2/logs/materialflow-error.log

# Database logs (PostgreSQL)
tail -f /var/log/postgresql/postgresql.log

# System logs (if using systemd)
journalctl -u materialflow -n 100

# Nginx reverse proxy logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### **Debug Mode**

```bash
# Run with debug output
DEBUG=* npm run dev

# Or set environment variable
NODE_DEBUG=* npm start

# Check database queries
psql -U materialflow_user -d materialflow_db -c "SET log_statement = 'all';"
```

---

## ✨ PRODUCTION CHECKLIST

### **Pre-Deployment**

- [ ] All code tested locally
- [ ] Dependencies updated and compatible
- [ ] No console errors or warnings
- [ ] Database backups created and tested
- [ ] Environment variables configured correctly
- [ ] SSL certificates obtained (for HTTPS)
- [ ] Firewall rules configured to allow port 3000
- [ ] Email notifications setup (if needed)
- [ ] Monitoring tools installed (PM2, Nginx)
- [ ] Load balancer configured (if needed)

### **Deployment**

- [ ] Build completes without errors: `npm run build`
- [ ] Database schema deployed: `node scripts/deploy-schema.js`
- [ ] Batch tables created: `node scripts/create-batch-tables.js`
- [ ] History tables created: `node scripts/add-batch-history.js`
- [ ] Application starts: `npm start` or `pm2 start`
- [ ] Database connection successful
- [ ] All API endpoints responding (tested with curl)
- [ ] Login functionality working
- [ ] Barcode generation tested
- [ ] Print functionality tested

### **Post-Deployment**

- [ ] Verify all features working in production
- [ ] Monitor logs for errors: `pm2 logs`
- [ ] Test barcode scanning with real scanner
- [ ] Test label printing with real printer
- [ ] Verify database backups running
- [ ] Monitor server resources (CPU, RAM, Disk)
- [ ] Setup monitoring alerts
- [ ] Document deployment details
- [ ] Create runbook for common tasks
- [ ] Train users on system

### **Security Verification**

- [ ] PASSWORD: Session secret changed from default
- [ ] PASSWORD: Database user password is strong
- [ ] HTTPS: SSL certificate installed and valid
- [ ] FIREWALL: Only necessary ports open (80, 443, 5432)
- [ ] ACCESS: Role-based access control tested
- [ ] LOGS: Audit logs being recorded
- [ ] BACKUPS: Daily backups scheduled and tested
- [ ] UPDATES: Security updates applied

### **Monitoring Setup**

```bash
# Monitor server resources
watch -n 5 'free -h && df -h'

# Monitor application (if using PM2)
pm2 monit

# Monitor database
psql -U materialflow_user -d materialflow_db -c "SELECT datname, usename, count(*) FROM pg_stat_activity GROUP BY datname, usename;"

# Setup log rotation
sudo apt-get install -y logrotate
# Edit /etc/logrotate.conf as needed
```

---

## 📊 SYSTEM ARCHITECTURE

### **High-Level Architecture**

```
┌──────────────────────────────────────────────────┐
│         Client (Web Browser)                     │
│  Chrome | Firefox | Safari | Edge | Mobile      │
└─────────────────────┬──────────────────────────┘
                      │ HTTP/HTTPS:80,443
                      │
┌─────────────────────▼──────────────────────────┐
│      Nginx Reverse Proxy (Optional)            │
│  - SSL/TLS termination                         │
│  - Load balancing                              │
│  - Static file caching                         │
└─────────────────────┬──────────────────────────┘
                      │ HTTP:3000 (local)
                      │
┌─────────────────────▼──────────────────────────┐
│   Next.js Application Server (Node.js)         │
│  - React 19 Frontend                           │
│  - API Routes (Business Logic)                 │
│  - Session Management                          │
│  - Authentication                              │
│  - File export (CSV, PDF)                      │
└─────────────────────┬──────────────────────────┘
                      │ TCP:5432
                      │
┌─────────────────────▼──────────────────────────┐
│     PostgreSQL Database Server                 │
│  - Items & Stock                               │
│  - Batches & Barcodes                          │
│  - History & Audit Logs                        │
│  - Users & Sessions                            │
│  - Daily Backups                               │
└──────────────────────────────────────────────┘
```

---

## 📈 SCALING GUIDE

### **For 1-50 Users (Current Capacity)**

```
Single instance deployment:
- 1x Node.js server (4+ cores, 8GB RAM)
- 1x PostgreSQL server (2+ cores, 4GB RAM)
- 100GB storage for database
- Suitable for small to medium warehouses
```

### **For 50-200 Users**

```
Load balancing needed:
- 2-3x Node.js servers behind load balancer
- 1x PostgreSQL with connection pooling (PgBouncer)
- Shared session storage (Redis or database)
- Separate backup server
- 500GB+ storage
```

### **For 200+ Users**

```
Distributed architecture:
- 4-8x Node.js servers with horizontal scaling
- PostgreSQL with read replicas
- Redis for caching and sessions
- Separate analytics database
- Message queue for batch processing
- CDN for static assets
- 1TB+ storage with archival
```

---

## 🔐 SECURITY HARDENING

### **HTTPS/SSL Setup**

```bash
# Using Let's Encrypt (free SSL)
sudo apt-get install -y certbot python3-certbot-nginx

# Get certificate
sudo certbot certonly --nginx -d inventory.yourdomain.com

# Auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### **PostgreSQL Security**

```bash
# Create non-root user
CREATE USER materialflow_user WITH PASSWORD 'strong_password_here';

# Restrict privileges
GRANT CONNECT ON DATABASE materialflow_db TO materialflow_user;
GRANT USAGE ON SCHEMA public TO materialflow_user;
GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO materialflow_user;

# Enable password authentication in pg_hba.conf
sudo nano /etc/postgresql/12/main/pg_hba.conf
# Change: local   all             all                                     peer
# To:     local   all             all                                     md5
```

### **Firewall Configuration**

```bash
# Enable UFW (Ubuntu)
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow PostgreSQL (internal only)
sudo ufw allow from 127.0.0.1 to any port 5432

# Check rules
sudo ufw status
```

---

## 🎯 PERFORMANCE OPTIMIZATION

### **Database Optimization**

```bash
# Create indexes (already done in schema)
psql -U materialflow_user -d materialflow_db <<EOF
CREATE INDEX idx_batch_barcodes_batch_id ON batch_barcodes(batch_id);
CREATE INDEX idx_batch_barcodes_status ON batch_barcodes(status);
CREATE INDEX idx_batches_item_id ON barcode_batches(item_id);
EOF

# Vacuum and analyze
psql -U materialflow_user -d materialflow_db <<EOF
VACUUM ANALYZE;
EOF

# Monitor query performance
psql -U materialflow_user -d materialflow_db <<EOF
SELECT query, calls, total_time, mean_time 
FROM pg_stat_statements 
ORDER BY mean_time DESC LIMIT 10;
EOF
```

### **Application Caching**

```bash
# Use Redis for session caching (future implementation)
npm install redis

# In .env:
REDIS_URL=redis://localhost:6379
```

### **Node.js Optimization**

```bash
# In production, use cluster mode with PM2
pm2 start npm --name "materialflow" -i max -- start

# Monitor CPU cores
pm2 status
```

---

## 📞 SUPPORT & MAINTENANCE

### **Support Channels**
- Email: contact@aveoninfotech.com
- Documentation: See README.md and this guide
- Issue Tracking: GitHub Issues
- GitHub Repository: https://github.com/dotcodetech-dev/Materialmanagement

### **Regular Maintenance Tasks**

```bash
# Daily
- Check error logs
- Monitor disk usage

# Weekly
- Verify database backups
- Review user activity logs
- Check application logs

# Monthly
- Security updates: npm update
- Dependency updates: npm audit fix
- Database optimization: VACUUM ANALYZE
- Performance analysis

# Quarterly
- Full security audit
- Database size analysis
- Archive old data
- Test disaster recovery
```

### **Update Procedure**

```bash
# 1. Backup
pg_dump -U materialflow_user -d materialflow_db | gzip > backup_pre_update.sql.gz

# 2. Pull latest code
git pull origin main

# 3. Install new dependencies
npm install

# 4. Run migrations (if any)
node scripts/deploy-schema.js

# 5. Build
npm run build

# 6. Restart application
pm2 restart materialflow

# 7. Verify
curl http://localhost:3000/api/items
```

---

## ✅ FINAL VERIFICATION

Before going live, verify:

```bash
# 1. Build succeeds
npm run build

# 2. Database connected
psql -U materialflow_user -d materialflow_db -c "SELECT version();"

# 3. Server starts
npm start

# 4. API responds
curl http://localhost:3000/api/items

# 5. Login works
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@materialflow.com","password":"password"}'

# 6. Database queries work
psql -U materialflow_user -d materialflow_db -c "SELECT COUNT(*) FROM items;"
```

---

**Status:** ✅ Ready for Deployment  
**Last Updated:** August 1, 2026  
**Version:** 1.0.0  

For questions or support, contact:  
📧 contact@aveoninfotech.com  
🌐 https://www.aveoninfotech.com  
**Author:** Ranjith Kumar  
**Organization:** Aveon Infotech Private Limited
