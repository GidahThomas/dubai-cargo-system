# Dubai Computer Fast Cargo — Retail and Cargo Management System

Web system for an electronics importer and retailer (trading as **Dubai Tech Plaza**): catalogue and online orders, stock per branch, payments (including mobile money), invoices and quotations, nine-stage cargo tracking, deliveries, customer notifications (in-app, WhatsApp, SMS), reports and an audit trail.

PHP 8.2 · MariaDB/MySQL · Bootstrap 5.3 · runs on XAMPP (Apache) — no framework, no build step.

---

## Roles

| Role | What they do |
| --- | --- |
| Visitor | Browse the catalogue and Instagram gallery, request a quotation or invoice, track a shipment, chat on WhatsApp, register |
| Customer | Cart and checkout, pay (mobile money or reference), orders, tracking timeline, notifications, profile |
| Manager | Products and stock, orders, payments, invoices, quotations, shipments, deliveries, counter sales, reports, message log, Instagram import |
| Admin (owner) | Everything a manager does, plus users, settings and Audit Review |

---

## Setup (new machine)

1. Copy the project into `C:\xampp\htdocs\dubai-cargo-system` and start Apache and MySQL in XAMPP.
2. Create the database: import `database/schema.sql` (or `database/schema_hosting.sql` on shared hosting).
3. Configure: copy `.env.example` to `.env` and fill in the values you need (see below).
4. Apply migrations: `C:\xampp\php\php.exe tools\migrate.php`
5. Schedule background jobs (once): `powershell -ExecutionPolicy Bypass -File tools\schedule_tasks.ps1`
6. Set real passwords for the seeded accounts:
   `C:\xampp\php\php.exe tools\set_password.php admin@dubai-fast-cargo.test` (prints a strong password).
   The seed data ships with a well-known demo password — never leave it in place.
7. Open `http://localhost/dubai-cargo-system/`.

The root `.htaccess` only lets the web reach `public/`. On a real server, point the site's document root at `public/` instead.

---

## Configuration (`.env`)

| Setting | Purpose |
| --- | --- |
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | Database connection |
| `APP_DEBUG` | `true` shows technical error details; keep `false` anywhere others can reach |
| `SMTP_*` | Outgoing email (falls back to PHP `mail()`) |
| `ERROR_ALERT_EMAIL` | Emails the owner when an error happens (max once per 30 min per error) |
| `FORCE_HTTPS`, `TRUST_PROXY` | Redirect to HTTPS and send HSTS once an SSL certificate is installed |
| `WHATSAPP_NUMBER`, `WHATSAPP_COUNTRY_CODE` | Chat buttons and links (default: company phone, +255) |
| `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_TEMPLATE` | Automatic WhatsApp updates (Meta Cloud API) |
| `SMS_PROVIDER`, `SMS_*` | Automatic SMS updates via Africa's Talking or Beem |
| `AZAMPAY_*` | Mobile-money payments (M-Pesa, Tigo Pesa, Airtel Money, HaloPesa) |
| `BACKUP_DIR`, `BACKUP_KEEP`, `BACKUP_COPY_DIR`, `MYSQL_BIN_DIR` | Backups and their off-machine copy |
| `DB_TEST_NAME` | Database the tests rebuild (default `<DB_NAME>_test`) |
| `DB_PORT`, `DB_SSL_CA` | Cloud databases (TiDB Cloud: port 4000, encrypted connection) |
| `CLOUDINARY_URL`, `CLOUDINARY_FOLDER` | Store uploaded photos on Cloudinary instead of `public/uploads` |
| `SESSION_DRIVER=database` | Keep sign-in sessions in the database (hosts without a permanent disk) |
| `APP_BASE_PATH` | Public path of the site when every request runs through one script (`/` on Vercel) |
| `CRON_SECRET` | Enables the scheduled-jobs web address `index.php?url=cron/messages` / `cron/daily` |
| `LOG_DIR` | Where error logs are written (default `storage/logs`) |

Every external service is optional and switched off until its keys are set.

Hosting: [docs/DEPLOY_CPANEL.md](docs/DEPLOY_CPANEL.md) (shared PHP hosting) or [docs/DEPLOY_VERCEL.md](docs/DEPLOY_VERCEL.md) (Vercel free + TiDB Cloud + Cloudinary).

---

## Background jobs (Windows Task Scheduler)

| Task | When | Does |
| --- | --- | --- |
| `DubaiCargo\SendMessages` | every minute | Sends queued WhatsApp/SMS messages, retrying failures (1, 5, 15, 60, 240 min) |
| `DubaiCargo\DailyBackup` | 02:00 daily | Database dump + uploads archive, keeps 14, optional copy to `BACKUP_COPY_DIR` |
| `DubaiCargo\Thumbnails` | every 30 min | Small WebP copies of product and gallery photos |

---

## Command-line tools (`tools/`)

```
C:\xampp\php\php.exe tools\migrate.php [status]          apply / list database migrations
C:\xampp\php\php.exe tools\backup.php [verify]           back up now / test-restore the newest backup
C:\xampp\php\php.exe tools\set_password.php <email> [pw] reset a password (generates one if omitted)
C:\xampp\php\php.exe tools\send_messages.php             send queued messages now
C:\xampp\php\php.exe -d extension=gd tools\make_thumbnails.php
C:\xampp\php\php.exe tools\import_instagram.php [folder] import an Instagram data export
C:\xampp\php\php.exe tools\import_sql.php <file.sql>     load a database dump (e.g. into TiDB Cloud)
C:\xampp\php\php.exe tools\upload_images_to_cloudinary.php [--dry-run]
powershell -ExecutionPolicy Bypass -File tools\export_database.ps1   dump for a cloud database
```

Add `--env=.env.tidb` to any PHP tool to run it against other settings (e.g. the cloud database). These scripts refuse to run from a browser.

---

## Database changes

`database/schema.sql` is the base. Every later change is a numbered file in `database/migrations/`, applied by `tools/migrate.php` and recorded in the `schema_migrations` table. To change the database, add a new file (`YYYY_MM_DD_NN_description.sql`, no `USE` statement, safe to re-run) — never edit one that has already been applied. `database/legacy/` holds old scripts already included in `schema.sql`.

---

## Tests

```
C:\xampp\php\php.exe tests\run.php
```

Each run drops and rebuilds a separate test database from `schema.sql` plus all migrations, so real data is never touched. GitHub Actions (`.github/workflows/ci.yml`) runs a syntax check and the tests on MariaDB 10.4 for every push.

---

## Code layout

```
app/core/         App (router), Controller, Model, Migrator
app/controllers/  one controller per area; URLs are index.php?url=<controller>/<action>/<id>
app/models/       database access and business rules (transactions live here)
app/services/     WhatsApp, Sms, AzamPay, StockAlert, Thumbnail, ErrorReporter, Instagram importer, Mailer
app/views/        PHP templates (layouts/, partials/, one folder per area)
app/helpers/      Auth, formatting and URL helpers, class autoloader
public/           the only web-visible folder: index.php, assets/, uploads/
storage/          backups, logs, Instagram export (not web-visible, not in git)
tools/            command-line scripts
tests/            test runner and *Test.php files
```

Classes load from `app/` automatically (`app/helpers/autoload.php`; Composer's classmap is used too when `vendor/` exists).
