# MaterialFlow (PHP) — Hostinger Deployment (with GitHub auto-deploy)

**Stack:** PHP 8.2+ · CodeIgniter 4.7 · MySQL 8 / MariaDB 10.4+
**Hosting model:** Hostinger shared hosting (Premium / Business / Cloud) via hPanel.
**Options:** GitHub auto-deploy (recommended) OR manual ZIP upload.

---

## Prerequisites

- Hostinger plan with **Git integration** (Premium plan and above).
- A domain or subdomain pointing at your Hostinger account.
- The GitHub repo containing `materialflow-php/` (this folder, or a subtree).
- **`vendor/` must be committed** in the repo (Hostinger shared hosting has no server-side Composer). It already is.
- SSH access enabled in hPanel (Advanced → SSH Access). Needed only for the first-time symlink step and for troubleshooting.

---

## Directory layout on Hostinger

Hostinger's public web root for a domain is `~/domains/YOUR-DOMAIN.com/public_html/`. The app splits into an above-webroot part and a public part — same as the GoDaddy layout, just different absolute paths:

```
~/                                              (your Hostinger home)
├── repo/                                       ← GitHub clone lands here (or wherever you point Git)
│   └── materialflow-php/
│       ├── app/
│       ├── vendor/
│       ├── writable/
│       ├── db/
│       ├── public/
│       └── .env                                ← you create this on the server
└── domains/YOUR-DOMAIN.com/public_html/        ← web root
    ├── index.php                               → symlink to ../../../repo/materialflow-php/public/index.php
    ├── .htaccess                               → symlink to ../../../repo/materialflow-php/public/.htaccess
    ├── css/                                    → symlink
    ├── js/                                     → symlink
    └── favicon.ico                             → symlink
```

Using symlinks means `git pull` (or Hostinger's auto-deploy) refreshes the site instantly — no copy step. `public/index.php` already auto-detects the app path, so nothing to edit in code.

---

## 1. Create the database (hPanel)

1. hPanel → **Databases → Management**.
2. **Create a new MySQL database**. Note the database name, user, and password.
3. Hostinger prefixes DB name and user with your account ID (e.g. `u123456789_materialflow`).
4. Open **phpMyAdmin** for that database.
5. **Import** → select `materialflow-php/db/materialflow_mysql.sql` → Go. Creates 9 tables + `item_stock_balance` view + default branding.
6. **Import** → select `materialflow-php/db/seed_admin.sql` → Go. Creates the first admin.

Default seeded login — **change immediately after first sign-in**:
- Email: `admin@materialflow.com`
- Password: `ChangeMe@123`

---

## 2. Connect the GitHub repository (auto-deploy)

1. hPanel → **Advanced → Git**.
2. **Create a new repository**:
   - **Repository address:** paste your GitHub HTTPS URL, e.g. `https://github.com/your-user/your-repo.git`.
     - If the repo is private, use SSH (`git@github.com:user/repo.git`) and add Hostinger's SSH key to the repo's Deploy Keys.
   - **Branch:** `main` (or whichever you deploy from).
   - **Directory:** `repo` (the app files land in `~/repo/`).
3. Click **Create**. Hostinger clones the repo.
4. **Enable Auto-Deployment** on the same page. Hostinger shows you a webhook URL like `https://webhooks.hostinger.com/deploy/xxxxxxxx`.
5. In GitHub: repo → **Settings → Webhooks → Add webhook**:
   - **Payload URL:** paste the Hostinger webhook URL.
   - **Content type:** `application/json`.
   - **Which events?** Just the **push** event.
   - **Save.**
6. From now on, every `git push` to that branch triggers a deploy automatically.

---

## 3. Wire the public folder to your domain (one-time)

SSH into Hostinger (hPanel → Advanced → SSH Access → copy the command), then run:

```bash
DOMAIN=YOUR-DOMAIN.com                     # change this
REPO=~/repo/materialflow-php               # match Hostinger Git directory + subfolder
WEB=~/domains/$DOMAIN/public_html

# Back up anything already in public_html, then clear it
mkdir -p ~/backup && mv $WEB/* $WEB/.htaccess ~/backup/ 2>/dev/null

# Symlink every file/dir from the app's public/ into the web root
ln -sfn $REPO/public/index.php    $WEB/index.php
ln -sfn $REPO/public/.htaccess    $WEB/.htaccess
ln -sfn $REPO/public/css          $WEB/css
ln -sfn $REPO/public/js           $WEB/js
ln -sfn $REPO/public/favicon.ico  $WEB/favicon.ico

# App needs a writable directory
chmod -R 775 $REPO/writable
```

Confirm: `ls -la $WEB` should show arrows (`->`) on every entry.

---

## 4. Create `.env` on the server (one-time)

The repo does **not** include `.env` (it's git-ignored). Create it once via SSH or the hPanel File Manager:

```bash
nano ~/repo/materialflow-php/.env
```

Paste this and fill in your values:

```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://YOUR-DOMAIN.com/'

database.default.hostname = localhost
database.default.database = u123456789_materialflow
database.default.username = u123456789_mfuser
database.default.password = YOUR_DB_PASSWORD
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

# Force MySQL to UTC (matches the app's timezone)
# Handled automatically by app/Config/Events.php; no extra flag needed.

# Optional but recommended
app.forceGlobalSecureRequests = true
security.tokenName = 'csrf_test_name'
security.headerName = 'X-CSRF-TOKEN'
```

Save (Ctrl+O, Enter, Ctrl+X).

---

## 5. PHP version + extensions (hPanel)

1. hPanel → **Advanced → PHP Configuration**.
2. Set **PHP version** to **8.2** or **8.3**.
3. Under **PHP Extensions**, ensure these are enabled (all shipped by default on Hostinger; verify only):
   - `mysqli`, `intl`, `mbstring`, `curl`, `zip`, `json`, `openssl`, `session`.

---

## 6. Enable HTTPS (free)

hPanel → **Security → SSL** → **Install** the free Let's Encrypt certificate for your domain. Wait 5-10 minutes for issuance.

Then edit `.env` and confirm `app.baseURL` starts with `https://` and `app.forceGlobalSecureRequests = true`.

---

## 7. Smoke test

Open `https://YOUR-DOMAIN.com/`:
- You should see the login page.
- Sign in with `admin@materialflow.com` / `ChangeMe@123`.
- **Immediately change the password** in Settings → Users.
- Create an item, generate a batch, scan a barcode. Confirm the dashboard's "Today" counter increments.
- Visit `https://YOUR-DOMAIN.com/robots.txt` and `/sitemap.xml` — both should return content.

If anything 500s, check the log: `tail -50 ~/repo/materialflow-php/writable/logs/log-*.log`.

---

## 8. Deploying updates

Once step 2 is set up, any push to the tracked branch triggers Hostinger to `git pull` on the server. No further action needed. Because we symlinked `public_html` to the repo's `public/` folder, changes appear instantly.

For a manual pull (skipping the webhook), SSH in and:
```bash
cd ~/repo && git pull
```

---

## Alternative: no Git, manual ZIP upload

If you can't or don't want to use Git:

1. On your laptop: zip the `materialflow-php/` folder.
2. hPanel → **Files → File Manager** → upload the zip to `~/` and extract.
3. Do steps 3, 4, 5, 6, 7 above (symlink, `.env`, PHP version, SSL, smoke test).
4. For future updates: re-upload the zip, extract, overwrite.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| **500 on every page** | Check `writable/logs/log-YYYY-MM-DD.log`. Usually: wrong DB creds in `.env`, missing `writable/` permissions (`chmod -R 775 writable`), or PHP version < 8.2. |
| **"Unable to connect to the database"** | `.env` DB creds wrong, or DB is on a different host. On Hostinger `localhost` is correct for the shared MySQL server. |
| **"The action you requested is not allowed" on POST** | CSRF cookie/session mismatch — usually caused by inconsistent baseURL. Make sure `app.baseURL` in `.env` exactly matches the browser URL (including `https://` and trailing `/`). |
| **CSS / JS 404s** | The symlinks in `public_html` are missing or broken. Re-run step 3. |
| **After push, changes don't appear** | Check the Git tab in hPanel for the webhook's last delivery. Confirm the GitHub webhook secret and Content type match. |
| **Blank white page** | PHP fatal — enable error display temporarily by setting `CI_ENVIRONMENT = development` in `.env`, refresh, read the error, then set it back to `production`. |

---

## Security checklist before you go live

- [ ] Changed admin password from `ChangeMe@123`.
- [ ] `.env` has `CI_ENVIRONMENT = production` (never `development` on the live site).
- [ ] `.env` has `app.forceGlobalSecureRequests = true` and HTTPS is issued.
- [ ] `db/seed_admin.sql` is **not** publicly accessible (it's above the web root — verify by trying `https://YOUR-DOMAIN.com/db/seed_admin.sql` → should 404).
- [ ] `writable/` is `775`, not `777`.
- [ ] hPanel → SSH → set a strong password or key-only auth.
