# MaterialFlow (PHP) — GoDaddy Shared Hosting Deployment

**Stack:** PHP 8.2+ · CodeIgniter 4.7 · MySQL 8 (works on MariaDB 10.4+)
**Hosting model:** cPanel shared hosting — no SSH and no server-side Composer needed.
Deployment is: create database → import two .sql files → upload two folders → edit `.env`.

---

## 1. What gets uploaded where

The project splits into a private part (above the web root) and a public part (the web root):

| Local folder | Server location | Contents |
|---|---|---|
| `app/`, `writable/`, `vendor/`, `db/`, `.env` | `/home/<user>/materialflow/` | Application code — **not** web-accessible |
| `public/` (its *contents*) | `/home/<user>/public_html/` | `index.php`, `.htaccess`, `css/`, `js/` |

`public/index.php` auto-detects this layout — it first looks for `../app/` (local dev)
and falls back to `../materialflow/app/` (server). No edits needed if you use the
folder name `materialflow`. If you choose a different folder name, change the
fallback path at the top of `public/index.php`.

> **Subdomain / addon domain?** Point its document root at a folder, put the
> contents of `public/` there, and keep `materialflow/` one level above it.

## 2. Create the database (cPanel)

1. cPanel → **MySQL® Databases**.
2. Create a database (GoDaddy prefixes it, e.g. `abc123_materialflow`).
3. Create a database user with a strong password.
4. Add the user to the database with **ALL PRIVILEGES**.
5. Note all three values for step 4.

## 3. Import the schema and admin user (phpMyAdmin)

1. cPanel → **phpMyAdmin** → select your database.
2. **Import** → `db/materialflow_mysql.sql` → Go. (Creates all 9 tables + the stock-balance view + default branding.)
3. **Import** → `db/seed_admin.sql` → Go. (Creates the first admin.)

Seeded login — **change this password immediately after first login**:
- Email: `admin@materialflow.com`
- Password: `ChangeMe@123`

## 4. Upload the files

1. On your machine, zip the project as two archives:
   - `materialflow.zip` — the folders `app/`, `writable/`, `vendor/`, `db/`, plus `.env.example`
   - `public.zip` — the **contents** of `public/`
2. cPanel → **File Manager**:
   - In `/home/<user>/`, create folder `materialflow`, upload + extract `materialflow.zip` into it.
   - In `/home/<user>/public_html/`, upload + extract `public.zip`.
3. In `/home/<user>/materialflow/`, rename `.env.example` to `.env` and edit it:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://yourdomain.com/'

database.default.hostname = localhost
database.default.database = abc123_materialflow
database.default.username = abc123_mfuser
database.default.password = your_db_password
database.default.DBDriver = MySQLi
```

4. Check permissions: `materialflow/writable/` and everything inside it must be
   writable (755 is fine on GoDaddy; File Manager → Permissions if needed).

## 5. PHP version and extensions

cPanel → **Select PHP Version**:
- PHP **8.2** or newer.
- Extensions: `mysqli`, `intl`, `mbstring`, `json` (usually on by default).

## 6. First run

1. Open `https://yourdomain.com/` → you should see the MaterialFlow login page.
2. Sign in with the seeded admin, then immediately:
   - **Settings → User Management**: change the admin password (edit your own user),
     create real users with proper roles.
   - **Settings → Profile & Branding**: set your company name, logo icon, address.
3. Delete `db/seed_admin.sql` from the server (File Manager) — it contains the
   bootstrap credentials.

## 7. Roles

| Role | Access |
|---|---|
| ADMIN | Everything, including Settings and user management |
| MANAGER / STOREKEEPER | Items, customers, scanning, movements, batches, labels |
| VIEWER | Read-only: dashboard, catalogues, ledger, reports, batch history |

Role checks are enforced server-side on every route — hiding a button is not the
only protection.

## 8. Troubleshooting

| Symptom | Fix |
|---|---|
| Blank page / 500 | Check `materialflow/writable/logs/`. Usually a wrong `.env` DB credential. |
| "Whoops!" generic error | `CI_ENVIRONMENT` is `production` (good) — read the log file for the real error. |
| Login page loops | `writable/session/` not writable, or `app.baseURL` doesn't match the real URL. |
| CSS/JS 404 | The *contents* of `public/` must sit directly in `public_html/` (not in a `public/` subfolder). |
| DB connection error | GoDaddy DB host is `localhost` for same-account databases; confirm the prefixed DB/user names. |
| Clock/timezone oddities | The app stores UTC and renders UTC; that is by design. |

## 9. Local development (Windows)

```
# once
composer install            # or use the committed vendor/
mysql -u root < db/materialflow_mysql.sql
mysql -u root < db/seed_admin.sql
copy .env.example .env      # set CI_ENVIRONMENT = development + local DB creds

# every day
php spark serve             # http://localhost:8080
```
