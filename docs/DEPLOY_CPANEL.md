# Putting Dubai Tech Plaza online (cPanel hosting)

This guide takes the system from the shop computer to a live website at your own domain, with HTTPS, email, background jobs and backups. Allow about one hour. You need the cPanel login from your hosting company.

## What the hosting plan must have

| Need | Why | Where to check |
| --- | --- | --- |
| PHP 8.2 or newer | The system is written for PHP 8.2 | cPanel > Select PHP Version |
| MySQL or MariaDB database | Stores products, orders, invoices | cPanel > MySQL Databases |
| Free SSL (AutoSSL / Let's Encrypt) | HTTPS: secure logins and the installable phone app | cPanel > SSL/TLS Status |
| Cron jobs | WhatsApp/SMS sending, backups, thumbnails | cPanel > Cron Jobs |
| 2 GB disk or more | Product photos and backups | Hosting plan details |

Most cPanel plans (Hostinger, Namecheap, local Tanzanian hosts) include all of these.

## Step 1: Build the upload package (on the shop computer)

1. Make sure XAMPP's MySQL is running.
2. Open PowerShell in the project folder and run:

   ```
   powershell -ExecutionPolicy Bypass -File tools\package_for_hosting.ps1
   ```

3. It creates `deploy\dubai-tech-plaza-<date>.zip`: the application, your product photos, and `database.sql` (a copy of all your data).

The zip contains customer details and password hashes. Do not email it or share it; delete it once the site is live.

## Step 2: Create the database

1. cPanel > **MySQL Database Wizard**.
2. Database name: e.g. `dubaitech`. cPanel adds your account prefix, so it becomes something like `abcd_dubaitech`. **Write down the full name.**
3. Create a user (e.g. `dtpuser`, becomes `abcd_dtpuser`) with a strong password. **Write both down.**
4. Give the user **ALL PRIVILEGES** on the database.

## Step 3: Import your data

1. cPanel > **phpMyAdmin**, click your new database on the left.
2. **Import** tab > choose `database.sql` from the zip (extract it on your computer first) > **Go**.
3. You should see 27 tables, including `products`, `orders` and `schema_migrations`.

If the file is too large for phpMyAdmin, ask your host to import it, or use cPanel > Terminal: `mysql -u abcd_dtpuser -p abcd_dubaitech < database.sql`.

## Step 4: Upload the application

1. cPanel > **File Manager** > open `public_html` (or the folder for your domain).
2. **Upload** the zip, then right-click it > **Extract**.
3. You should now see `app`, `public`, `tools`, `.htaccess`, `index.php` and others directly inside `public_html`.
4. Delete the zip and `database.sql` from `public_html`.

The included `.htaccess` lets visitors reach only the `public` folder; settings, code and backups are blocked. If your host lets you set the domain's **document root**, pointing it at `public_html/public` is even cleaner (then `/public/` disappears from addresses).

## Step 5: Enter the settings

1. In File Manager, enable **Settings > Show Hidden Files**.
2. Copy `.env.production.example` to **`.env`** in the same folder, then edit `.env`.
3. Fill in every line marked `CHANGE`:
   - `DB_NAME`, `DB_USER`, `DB_PASS`: from Step 2. `DB_HOST` stays `localhost`.
   - Email (`SMTP_...`): create a mailbox in cPanel > **Email Accounts** (e.g. `info@yourdomain.com`) and use its address and password. cPanel shows the mail server name under "Connect Devices".
   - `ERROR_ALERT_EMAIL`: where error alerts should go.
4. Save.

## Step 6: Set the PHP version

cPanel > **Select PHP Version** (or MultiPHP Manager):

- Version **8.2** (or 8.3).
- Extensions ticked: `pdo_mysql`, `mbstring`, `curl`, `gd`, `fileinfo`, `zlib`, `openssl`.

## Step 7: Turn on HTTPS

1. cPanel > **SSL/TLS Status** > select your domain > **Run AutoSSL**. Wait until it shows a green padlock.
2. `.env` already has `FORCE_HTTPS=true`, so every visit now uses `https://`.

HTTPS is what makes the phone app installable (Android "Install app", offline screen).

## Step 8: Schedule the background jobs

cPanel > **Cron Jobs**. Find your PHP path first: it is shown in cPanel > Select PHP Version, or ask your host. It is usually `/usr/local/bin/php` or `/opt/cpanel/ea-php82/root/usr/bin/php`. Replace `USER` with your cPanel username.

| How often | Command | Does |
| --- | --- | --- |
| Every minute (`* * * * *`) | `/usr/local/bin/php /home/USER/public_html/tools/send_messages.php` | Sends WhatsApp/SMS updates |
| Every 30 minutes (`*/30 * * * *`) | `/usr/local/bin/php /home/USER/public_html/tools/make_thumbnails.php` | Small product photos |
| Daily 02:00 (`0 2 * * *`) | `/usr/local/bin/php /home/USER/public_html/tools/backup.php` | Database and photo backup |

Some shared hosts block the backup job's database export. If the backup email or log shows an error, rely on cPanel's own **Backup** feature (download a full backup weekly) instead.

## Step 9: Check that everything works

Open `https://yourdomain.com` and go through this list:

- [ ] Home page loads with a padlock in the address bar
- [ ] `https://yourdomain.com/.env` shows **403 Forbidden** (settings are protected)
- [ ] Products and product photos show
- [ ] Sign in with the owner account; the dashboard shows your real numbers
- [ ] Invoice Settings shows your company details and logo
- [ ] Send yourself a test from the Contact page; it arrives by email
- [ ] On an Android phone, the website offers **Install app**

Then update **Invoice Settings** if anything changed (phone, address, logo), and ask staff to sign in and change their passwords.

## Updating the website later

1. On the shop computer, commit the changes and run the packaging script again.
2. Upload the new zip and extract it over the old files. The zip has no `.env`, so your server settings stay as they are, and photos uploaded on the live site are kept (the zip only adds the shop computer's photos). Delete the new `database.sql` without importing it: importing would replace live orders with the shop computer's older copy.
3. Apply any database changes: cPanel > **Terminal**, then:

   ```
   cd public_html && php tools/migrate.php
   ```

   (No Terminal? Add a one-time cron job with the same command, then delete it.)

## If something goes wrong

| What you see | Fix |
| --- | --- |
| **500 Internal Server Error** right after upload | Your host may not allow `Options` in `.htaccess`. Delete the `Options -Indexes` line in `.htaccess`, `public/.htaccess` and `public/uploads/.htaccess`. |
| "Something went wrong" page | Check `storage/logs/app-errors.log`. For a few minutes only, set `APP_DEBUG=true` in `.env` to see details, then set it back to `false`. |
| Database connection error | Re-check `DB_NAME`, `DB_USER`, `DB_PASS` (with the cPanel prefix) and that the user has ALL PRIVILEGES. |
| Product photos missing | `public/uploads` was not uploaded or extracted; upload that folder again. |
| Emails not sending | Re-check the `SMTP_` settings against cPanel > Email Accounts > Connect Devices. |
| Redirect loop after turning on HTTPS | Your host terminates SSL in front of the server: set `TRUST_PROXY=true` in `.env`. |
| Future database update fails with "IF NOT EXISTS" error | Your host uses MySQL instead of MariaDB. Ask your developer to adjust that migration file. |
