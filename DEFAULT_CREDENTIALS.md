# 🔐 DEFAULT LOGIN & CREDENTIAL SETUP
## MaterialFlow - Barcode Inventory Management System

**Author:** Ranjith Kumar  
**Organization:** Aveon Infotech Private Limited  
**Version:** 1.0.0  

---

## ℹ️ Important Note

**MaterialFlow does NOT have hardcoded default credentials.**

Security best practice: The first user must be created manually during initial setup. This prevents unauthorized access and ensures secure password configuration.

---

## 📝 Initial Setup - Create First User

### Option 1: Using the Seed Endpoint (Easiest)

After starting the application for the first time:

```bash
# POST request to create the first admin user
curl -X POST http://localhost:3000/api/auth/seed \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Administrator",
    "email": "admin@materialflow.com",
    "password": "YourSecurePassword123!",
    "role": "ADMIN"
  }'
```

**Expected Response:**
```json
{
  "user": {
    "id": 1,
    "full_name": "Administrator",
    "email": "admin@materialflow.com",
    "role": "ADMIN"
  }
}
```

### Option 2: Using SQL Insert (Direct Database)

```bash
# Connect to database
psql -U postgres -d materialflow_db

# Insert admin user
INSERT INTO app_users (full_name, email, password_hash, role, is_active) 
VALUES (
  'Administrator',
  'admin@materialflow.com',
  '$2a$12$...hashed_password_here...',  -- Use bcrypt hash
  'ADMIN',
  true
);
```

**To generate a bcrypt hash:**
```bash
# Using Node.js
node -e "const bcrypt = require('bcryptjs'); bcrypt.hash('YourSecurePassword123!', 12).then(h => console.log(h));"
```

### Option 3: Using the Application UI (After First User Created)

Once admin user is created:

1. Login with admin credentials
2. Navigate to Users section
3. Click "Add User"
4. Fill in user details:
   - Full Name
   - Email
   - Password
   - Role (Admin, Manager, Storekeeper)
5. Click "Create"

---

## 👤 User Roles & Permissions

### ADMIN Role
```
Full system access:
✅ Create, read, update, delete items
✅ Create, read, update, delete customers
✅ Generate batch barcodes
✅ Print labels
✅ Record inward/outward movements
✅ Export data as CSV
✅ View complete batch history
✅ View all user activity
✅ Manage all users (create, edit, delete)
✅ Access settings and configuration
✅ View audit logs
```

### MANAGER Role
```
Limited management access:
✅ Create, read, update items
✅ Create, read, update customers
✅ Generate batch barcodes
✅ Print labels
✅ Record inward/outward movements
✅ Export data as CSV
✅ View batch history
✅ View own activity
❌ Cannot manage users
❌ Cannot delete items
❌ Cannot view other users' actions
```

### STOREKEEPER Role
```
Warehouse operations only:
✅ View items
✅ Scan barcodes (inward/outward)
✅ Record stock movements
✅ View inventory status
❌ Cannot create batches
❌ Cannot print labels
❌ Cannot export data
❌ Cannot manage users
❌ Cannot view history
❌ Limited to own actions only
```

---

## 🔑 Recommended Credentials Setup

### For Development Environment

```
Email:    admin@materialflow.com
Password: Admin@123

Email:    manager@materialflow.com
Password: Manager@123

Email:    store@materialflow.com
Password: Store@123
```

**Setup Script:**
```bash
curl -X POST http://localhost:3000/api/auth/seed \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Admin User",
    "email": "admin@materialflow.com",
    "password": "Admin@123",
    "role": "ADMIN"
  }'
```

### For Production Environment

**IMPORTANT: Use strong, unique passwords!**

```
Email:    admin@yourdomain.com
Password: GenerateStrongRandomPassword123!@#$%

Email:    manager@yourdomain.com
Password: AnotherStrongRandomPassword456!@#$%

Email:    store@yourdomain.com
Password: YetAnotherStrongPassword789!@#$%
```

**Generate Secure Password:**
```bash
# Using OpenSSL (Linux/Mac)
openssl rand -base64 32

# Using Python
python3 -c "import secrets; print(secrets.token_urlsafe(32))"

# Using Node.js
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

---

## 🚀 Quick Start - Full Setup

### Step 1: Start Application

```bash
npm install
npm run dev
```

Application runs at: http://localhost:3000

### Step 2: Create Database

```bash
createdb materialflow_db
psql -U postgres -d materialflow_db -f db/init/001_schema.sql
node scripts/deploy-schema.js
node scripts/create-batch-tables.js
```

### Step 3: Create First Admin User

```bash
curl -X POST http://localhost:3000/api/auth/seed \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Admin",
    "email": "admin@materialflow.com",
    "password": "Admin@123",
    "role": "ADMIN"
  }'
```

### Step 4: Login

Open browser: http://localhost:3000

**Credentials:**
- Email: `admin@materialflow.com`
- Password: `Admin@123`

### Step 5: Create Additional Users

After login as admin:
1. Navigate to Users section
2. Click "Add User"
3. Create Manager and Storekeeper accounts
4. Distribute credentials securely

---

## 🔒 Password Security Best Practices

### Requirements for Secure Passwords

✅ Minimum 12 characters  
✅ Mix of uppercase (A-Z) and lowercase (a-z)  
✅ Numbers (0-9)  
✅ Special characters (!@#$%^&*)  
✅ Avoid dictionary words  
✅ Avoid sequential numbers (123, abc)  
✅ Avoid personal information  
✅ Unique for each user  

### Recommended Format

```
Pattern: [Uppercase][Lowercase][Numbers][Special]

Examples:
✅ AdminFlow@2026Secure
✅ Manager$MaterialFlow2026!
✅ Store#Keeper@Barcode2026
```

### Password Complexity Checker

```
Weak:     password, admin123, 123456
Medium:   Admin@123, Manager2026
Strong:   AdminFlow@2026Secure!, Manager$Material#2026
```

---

## 📋 User Creation Template

### For Admin Users
```json
{
  "fullName": "Administrator Name",
  "email": "admin@yourdomain.com",
  "password": "SecureAdminPassword123!@#$",
  "role": "ADMIN"
}
```

### For Manager Users
```json
{
  "fullName": "Manager Name",
  "email": "manager@yourdomain.com",
  "password": "SecureManagerPassword456!@#",
  "role": "MANAGER"
}
```

### For Storekeeper Users
```json
{
  "fullName": "Storekeeper Name",
  "email": "store@yourdomain.com",
  "password": "SecureStorePassword789!@#",
  "role": "STOREKEEPER"
}
```

---

## 🔄 Session Management

### Session Duration
- **Default:** Session persists while browser is open
- **Inactivity Timeout:** Configurable (set via SESSION_SECRET)
- **Logout:** Manual logout or browser close

### Session Cookie Details
```
Name:       session
HttpOnly:   true (prevents XSS access)
Secure:     true (HTTPS only in production)
SameSite:   Strict (CSRF protection)
MaxAge:     Browser session lifetime
```

### Logout Endpoint

```bash
curl -X POST http://localhost:3000/api/auth/logout \
  -H "Content-Type: application/json"
```

---

## 🔐 Changing Passwords

### Current Limitation
Current version doesn't have built-in password change UI.

### Method 1: Admin Reset via SQL
```bash
psql -U postgres -d materialflow_db

-- First, generate new password hash using Node.js:
-- node -e "const bcrypt = require('bcryptjs'); bcrypt.hash('NewPassword123!', 12).then(h => console.log(h));"

UPDATE app_users 
SET password_hash = '$2a$12$...new_hash_here...'
WHERE email = 'user@example.com';
```

### Method 2: Delete and Recreate User
```bash
# Delete user
DELETE FROM app_users WHERE email = 'user@example.com';

# Create new user with new password via seed endpoint
curl -X POST http://localhost:3000/api/auth/seed \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "User Name",
    "email": "user@example.com",
    "password": "NewPassword123!",
    "role": "STOREKEEPER"
  }'
```

---

## 📊 User Management Operations

### Create User via API
```bash
curl -X POST http://localhost:3000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "John Doe",
    "email": "john@example.com",
    "password": "SecurePassword123!",
    "role": "MANAGER"
  }'
```

### Fetch All Users
```bash
curl http://localhost:3000/api/users
```

### Update User
```bash
curl -X PUT http://localhost:3000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "John Updated",
    "role": "ADMIN"
  }'
```

### Delete User
```bash
curl -X DELETE http://localhost:3000/api/users/1
```

### Get Current User Info
```bash
curl http://localhost:3000/api/auth/me
```

---

## 🛡️ Seed Endpoint Security Notes

### Important Limitations

1. **One-Time Use:** The seed endpoint only works when NO users exist
   - After first user created, it returns 409 error
   - Prevents accidental user creation

2. **No Authentication Required:** First call doesn't need login
   - By design (no users to authenticate against)
   - This is why it's critical to create admin first

3. **Call Once:** Only call seed endpoint once per fresh database
   - Multiple calls will fail with "Users already exist" error

### Secure Seed Workflow

```bash
# 1. Deploy fresh database
psql -U postgres -d materialflow_db -f db/init/001_schema.sql

# 2. Verify no users exist
psql -U postgres -d materialflow_db -c "SELECT COUNT(*) FROM app_users;"
# Output: count = 0

# 3. Create admin user (only call seed once!)
curl -X POST http://localhost:3000/api/auth/seed \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Administrator",
    "email": "admin@yourdomain.com",
    "password": "VerySecurePassword123!@#$",
    "role": "ADMIN"
  }'

# 4. Verify user created
psql -U postgres -d materialflow_db -c "SELECT id, email, role FROM app_users;"

# 5. Login and create other users via UI or API
```

---

## 🚨 Troubleshooting Login Issues

### "Invalid email or password"
```
Solution:
1. Verify email address is correct
2. Verify password is correct (case-sensitive)
3. Check if user exists in database:
   psql -U postgres -d materialflow_db \
     -c "SELECT id, email FROM app_users WHERE email='user@example.com';"
```

### "Account is deactivated"
```
Solution:
1. Contact admin to reactivate account
2. Or update via SQL:
   UPDATE app_users SET is_active = true 
   WHERE email = 'user@example.com';
```

### "Users already exist" (on seed endpoint)
```
Solution:
This is expected after first user creation.
To create more users:
1. Login with existing admin account
2. Navigate to Users section
3. Click "Add User"
4. Fill details and submit
```

### Cannot Connect to Database
```
Solution:
1. Verify PostgreSQL is running:
   psql -U postgres -c "SELECT version();"
2. Verify DATABASE_URL in .env is correct
3. Verify database exists:
   psql -U postgres -l | grep materialflow_db
4. Check credentials in connection string
```

---

## 📝 Credential Storage Best Practices

### DO ✅
- [ ] Store credentials in password manager (1Password, LastPass, etc.)
- [ ] Use unique passwords for each user
- [ ] Rotate passwords every 90 days
- [ ] Store admin credentials securely offline
- [ ] Use strong, random passwords
- [ ] Log all user creation/deletion
- [ ] Enable audit trail
- [ ] Use HTTPS in production

### DON'T ❌
- [ ] Share passwords via email or chat
- [ ] Use default/example passwords in production
- [ ] Write passwords in code or config files
- [ ] Use same password for multiple accounts
- [ ] Allow users to have weak passwords
- [ ] Share admin credentials
- [ ] Log passwords in application logs
- [ ] Use HTTP in production

---

## 🔗 Related Documentation

For more information, see:
- [README.md](README.md) - Quick start guide
- [DEPLOYMENT.md](DEPLOYMENT.md) - Full deployment guide
- [PRODUCT_DOCUMENT.md](PRODUCT_DOCUMENT.md) - Product specifications
- [LICENSE.md](LICENSE.md) - License agreement

---

## 📞 Support

For login issues or credential management questions:

📧 **Email:** contact@aveoninfotech.com  
🌐 **Website:** www.aveoninfotech.com  
📚 **Documentation:** See related files above

---

**Last Updated:** August 1, 2026  
**Version:** 1.0.0  
**Author:** Ranjith Kumar  
**Organization:** Aveon Infotech Private Limited
