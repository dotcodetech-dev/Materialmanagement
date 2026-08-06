# MaterialFlow v1.0 — Release Notes

**Release date:** 2026-08-06
**Repo:** [dotcodetech-dev/Materialmanagement](https://github.com/dotcodetech-dev/Materialmanagement)
**Stack:** PHP 8.2+ · CodeIgniter 4.7 · MySQL 8 / MariaDB 10.4+

MaterialFlow v1.0 is the first production-ready release — a barcode inventory + batch management system, rewritten in PHP for shared hosting (GoDaddy, Hostinger, cPanel) with full auto-deploy from GitHub.

---

## Highlights

- **5 user roles** with strict server-side permission enforcement — Admin, Manager, Storekeeper, Staff, Viewer
- **Barcode scanning** for inward/outward stock movements — one atomic transaction per scan, race-condition safe
- **Batch generation** — up to 10,000 barcodes per batch, printable as an A4 label sheet (63 labels per page)
- **Ledger-first stock accounting** — balance is always derived from movements, never stored, so it can never drift
- **Manual movements + customer tracking** — for returns, adjustments, and non-barcoded stock
- **Reports & CSV export** — inward, outward, and stock reports with date/user/category filters
- **Full SEO + AI-crawler support** — sitemap, robots, llms.txt, JSON-LD, Open Graph, Twitter cards
- **Hostinger + GoDaddy deploy runbooks** — including GitHub auto-deploy on Hostinger

---

## Features by module

### Authentication & Roles

- Session-based login with bcrypt (cost 12), CSRF protection, session regeneration
- 5 roles with route-level enforcement (no VIEWER-can-write bugs):
  | Role | Can do |
  |------|--------|
  | **Admin** | Everything, including user management + settings |
  | **Manager / Storekeeper** | Items/customers/movements CRUD, batches, labels, reports |
  | **Staff** | Inward + Outward scans, view stock only |
  | **Viewer** | Read-only across the whole app (no scan, no edit) |
- Login throttle: 5 attempts / IP / minute
- Post-login redirect: Staff → `/inward`, everyone else → dashboard

### Items & Stock

- CRUD with `MF-#####` auto-generated barcodes
- Category, unit (Nos/Kg/Meters/Liters/Boxes/Pairs), reorder level
- Real-time available quantity via `item_stock_balance` MySQL view
- Duplicate barcode/SKU protection at DB level (errno 1062 detection)
- Search/filter on the items table

### Customers

- CRUD (name, phone, email, address, active flag)
- Linked from manual outward movements

### Scan flow (Inward / Outward)

- Single transactional endpoint `POST /api/scan/commit` — replaces the old validate → move → mark-scanned triple round-trip
- `SELECT ... FOR UPDATE` on barcode + item rows, so two parallel scans of the same code produce exactly one success + one 409
- Outward scan rejects when balance would go negative — inside the transaction, no TOCTOU window
- Supports both batch barcodes (one-time-scan enforced) and item barcodes (fallback)
- Recent-scans list on the page with success/failure indicators

### Manual movements

- Item + customer + qty + reference + notes form
- Same balance checks as the scan flow

### Batch barcode generation

- Generate up to 10,000 barcodes per batch atomically (transaction with chunked insertBatch)
- Default prefix `{item.barcode}-{batch_reference}-` guarantees no collisions across batches
- Batch history page with status filters, details modal, print history, export history
- Print any range (`?from=&to=`) as an A4 label sheet (7×9 grid, 30×30mm labels, CODE128 via vendored JsBarcode)
- Cumulative print counter with PRINTED / PARTIAL_PRINT / FULLY_PRINTED status detail
- CSV export with proper quoting via `fputcsv`

### Ledger & Reports

- Full stock ledger with CSV export
- Three report types — Inward, Outward, Stock — with date range / user / category filters
- Print view (browser-native)
- CSV export per report

### Settings (Admin only)

- Branding: company name, tagline, logo icon
- User management: create / edit role / deactivate
- Self-deactivation guard

### SEO & Discoverability

- Full AVEON INFOTECH SEO on every page (hidden from users, visible to crawlers):
  - `<meta description>`, `<meta keywords>`
  - Open Graph tags (Facebook, LinkedIn, WhatsApp previews)
  - Twitter card tags
  - Schema.org `Organization` JSON-LD with `makesOffer[]` for all 8 services
  - Canonical, sitemap, and llms.txt link tags
- **`/sitemap.xml`** — dynamic, adapts to production baseURL
- **`/robots.txt`** — allows public pages, blocks 12 behind-auth routes, with explicit `Allow` for 17 AI/LLM crawlers:
  GPTBot · ChatGPT-User · OAI-SearchBot · ClaudeBot · Claude-Web · anthropic-ai · PerplexityBot · Perplexity-User · Google-Extended · Applebot-Extended · Bytespider · CCBot · cohere-ai · DuckAssistBot · MistralAI-User · YouBot · meta-externalagent
- **`/llms.txt`** — llmstxt.org convention: Markdown site summary for LLM citations
- Browser tab shows "MaterialFlow" (not the company brand)

---

## Correctness fixes shipped in v1.0

These are bugs the original Next.js reference had — now fixed:

1. **Race on double-scan** — the old validate → move → mark-scanned split had a window where the same barcode could be scanned twice. Fixed with a single `POST /api/scan/commit` transaction + `FOR UPDATE` locks.
2. **Print counter double-count** — MySQL evaluates SET clauses left-to-right, so `total_printed = total_printed + ...` in a naive port from Postgres would double. Now computed in PHP first.
3. **Print range bug** — old UI read `batch.barcodes` from the wrong path; range prints were always empty.
4. **Duplicate-scan toast "Invalid Date"** — error details were being dropped; scan.js now surfaces `scanned_by` + `scanned_at`.
5. **VIEWER could write** — the old app checked permissions only in the UI; v1.0 enforces on the server via `RoleFilter`.
6. **Timezone drift** — MySQL was writing `CURRENT_TIMESTAMP` in server timezone while PHP was writing UTC, breaking date-range filters near midnight. Now every request runs `SET time_zone = '+00:00'` on connect.
7. **Modal never closed** — `.overlay { display: grid }` overrode the `[hidden]` attribute, so `closeModal()` had no visual effect. Fixed with `.overlay[hidden] { display: none !important }` — one line, all 3 modals repaired.
8. **XSS via API response fields** — flash messages concatenated `item_name`, `scanned_by`, `batch_reference` directly. Now length-clamped + string-coerced before display.
9. **Info disclosure via error message** — `POST /api/scan/commit` returned `"Barcode not found: <value>"`, letting attackers probe for valid barcodes. Now returns a generic message and logs details server-side.
10. **Missing controller methods** — `/items/new` and `/customers/new` GET routes referenced undefined `create()` methods; now defined.

---

## Security posture

**Passed (verified):**
- SQL injection: all queries use parameterized binds
- CSRF: session-based tokens on all POSTs + `X-CSRF-TOKEN` header on all JSON APIs
- Passwords: bcrypt cost 12
- Sessions: regenerated on login, 8-hour expiration
- Authentication: server-side filter on every non-public route
- Role enforcement: server-side via `RoleFilter` (no more client-only VIEWER holes)
- FK constraints, CHECK constraints, UNIQUE indexes at DB level
- Duplicate detection via errno 1062 (not fragile error-string matching)

**Hardened in v1.0:**
- XSS in flash messages (see #8 above)
- Information disclosure in error responses (see #9)
- Timezone consistency between MySQL and PHP (see #6)

---

## Deployment

Two turnkey runbooks in the repo:

- **[DEPLOYMENT.md](DEPLOYMENT.md)** — GoDaddy shared hosting (cPanel, no SSH, ZIP upload)
- **[DEPLOY_HOSTINGER.md](DEPLOY_HOSTINGER.md)** — Hostinger shared hosting with **GitHub auto-deploy via webhook**

Both use the same layout: `vendor/` is committed, no server-side Composer needed. First-time setup is:
1. Create DB → import `db/materialflow_mysql.sql` + `db/seed_admin.sql`.
2. Upload files (or connect Git).
3. Create `.env` with DB creds + `baseURL`.
4. Log in as `admin@materialflow.com` / `ChangeMe@123` and **immediately change the password**.

---

## Tech reference

- **Framework:** CodeIgniter 4.7.4
- **PHP:** 8.2+ (tested on 8.3.33)
- **Database:** MySQL 8 (works on MariaDB 10.4+); InnoDB, utf8mb4, UUID v4 PKs generated in PHP
- **Frontend:** server-rendered views + small vanilla-JS modules (no build step, no npm)
- **Bundled libraries:** JsBarcode CODE128 (vendored locally in `public/js/vendor/`)
- **Session:** file-based, `writable/session`

---

## What's not in v1.0 (deferred)

- **Bulk Labels screen** — the route and controller exist but the sidebar entry was removed; if you need it, restore the nav entry in `app/Views/layouts/main.php`.
- **Pending-intake column on the stock report** — unscanned batch units aren't surfaced as "in-flight" stock; they only show in Batch History.
- **API rate limiting** beyond the login throttle.
- **User audit log page** — every action is already stamped with `recorded_by` / `created_by`, but there's no admin UI to browse it yet.
- **Real-time updates** — pages don't auto-refresh; the dashboard shows a snapshot at page load.

---

## Credits

- **AVEON INFOTECH** — product owner
- Rewrite from Next.js/PostgreSQL reference to PHP/MySQL for shared-hosting deployability

---

**License / support:** Contact AVEON INFOTECH for licensing and commercial support.
