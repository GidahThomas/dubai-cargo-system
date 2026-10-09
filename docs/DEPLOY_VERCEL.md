# Hosting on Vercel (free) with TiDB Cloud and Cloudinary

Vercel runs the PHP code but has **no database and no permanent disk**. The app therefore uses three free services together:

| Service | What it holds | Sign up |
| --- | --- | --- |
| **Vercel** (Hobby plan) | The website and its PHP code, from your GitHub repository | https://vercel.com (sign in with GitHub) |
| **TiDB Cloud** (Starter / Serverless, free) | The database (MySQL-compatible) | https://tidbcloud.com |
| **Cloudinary** (Free plan) | Product photos, gallery photos, the invoice logo | https://cloudinary.com |
| **cron-job.org** (free) | Sends queued WhatsApp/SMS messages every few minutes | https://cron-job.org |

Time needed: about 1 hour. Nothing changes for XAMPP or cPanel: the Vercel behaviour only switches on through the settings below.

> **Read first: what is different from the shop computer or cPanel**
>
> - **Vercel's free Hobby plan is for personal, non-commercial use** (Vercel's terms). A shop website is commercial use, so Vercel may ask you to move to the Pro plan (about US$20/month). cPanel hosting (`docs/DEPLOY_CPANEL.md`) has no such rule.
> - Uploads larger than **4.5 MB** are refused by Vercel. Resize big photos before uploading.
> - The built-in backups (`tools/backup.php`) and the automatic thumbnails do not run on Vercel. Cloudinary makes the small versions of photos itself, and you take database copies as described in step 8.
> - Vercel's free cron runs **once a day**. WhatsApp/SMS messages are sent every few minutes only if you set up cron-job.org (step 7).
> - The Instagram import reads an export folder on the computer, so run it on the shop computer against the cloud database, then move its photos:
>   `C:\xampp\php\php.exe tools\import_instagram.php --env=.env.tidb` followed by `C:\xampp\php\php.exe tools\upload_images_to_cloudinary.php --env=.env.tidb`.
> - After the move, **the cloud database is the real one**. The XAMPP copy on the shop computer becomes an old copy for testing. Do not keep entering orders in both places.

---

## 1. Put the code on GitHub

Vercel deploys from a GitHub repository. If the project is already on GitHub (`git remote -v` shows it), skip this step. Otherwise create a **private** repository on github.com and push:

```
git remote add origin https://github.com/YOUR-NAME/dubai-tech-plaza.git
git push -u origin main
```

`.env`, backups and `deploy/` are git-ignored, so passwords and customer data are never uploaded.

## 2. Create the TiDB Cloud database

1. Sign up at https://tidbcloud.com and create a cluster on the free plan (Starter/Serverless). Pick the region closest to Tanzania (e.g. Frankfurt `eu-central-1`).
2. Open the cluster > **SQL Editor** and run:
   ```sql
   CREATE DATABASE dubai_tech_plaza;
   ```
3. Click **Connect**. Choose "Public" connection and generate a password. Note the **host** (`gateway01....tidbcloud.com`), **port** (`4000`), **user** (looks like `2abcDEF.root`) and **password**.

## 3. Create the Cloudinary account

Sign up at https://cloudinary.com. On the **Dashboard**, copy the **API environment variable**. It looks like `cloudinary://123456789012345:AbCdEf...@dxyz123`.

## 4. Prepare the settings

1. Copy `.env.vercel.example` to a new file **`.env.tidb`** in the project folder (it is git-ignored).
2. Fill in every value marked `CHANGE`:
   - `DB_HOST`, `DB_USER`, `DB_PASS` from step 2, `DB_NAME=dubai_tech_plaza`.
   - `CLOUDINARY_URL` from step 3.
   - `CRON_SECRET`: a long random text, e.g. from `C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(20));"`.
   - SMTP and the optional services, copied from your current `.env`.
3. **Only in `.env.tidb`** (the shop computer), change the certificate line to:
   ```
   DB_SSL_CA=C:\xampp\apache\bin\curl-ca-bundle.crt
   ```
   (On Vercel it stays `/etc/pki/tls/certs/ca-bundle.crt`.)

## 5. Copy the database and photos to the cloud

Run these on the shop computer, in the project folder (`C:\xampp\htdocs\dubai-cargo-system`), with XAMPP's MySQL running:

```
powershell -ExecutionPolicy Bypass -File tools\export_database.ps1
C:\xampp\php\php.exe tools\import_sql.php deploy\database-cloud.sql --env=.env.tidb
C:\xampp\php\php.exe tools\migrate.php --env=.env.tidb
C:\xampp\php\php.exe tools\upload_images_to_cloudinary.php --env=.env.tidb --dry-run
C:\xampp\php\php.exe tools\upload_images_to_cloudinary.php --env=.env.tidb
```

1. **export_database** saves the shop database to `deploy\database-cloud.sql`.
2. **import_sql** loads it into TiDB. It should end with `Done: N statements.`
3. **migrate** should say there is nothing new to apply.
4. **upload_images_to_cloudinary** with `--dry-run` lists the photos it will move. Without `--dry-run` it uploads them and writes their Cloudinary addresses into the **cloud** database. The shop computer's database and files are not changed.

Then **delete `deploy\database-cloud.sql`**: it contains customer data and password hashes.

## 6. Create the Vercel project

1. On https://vercel.com: **Add New… > Project**, then import your GitHub repository.
2. **Framework Preset: Other**. Leave Build Command, Output Directory and Install Command as they are; `vercel.json` in the project sets them.
3. Open **Environment Variables**. Click in the first field and **paste the whole content of `.env.tidb`**: Vercel splits it into separate variables. Then change one value back:
   - `DB_SSL_CA` = `/etc/pki/tls/certs/ca-bundle.crt`
4. Click **Deploy**. When it finishes, open the address Vercel gives you (e.g. `https://dubai-tech-plaza.vercel.app`).

Check:
- The home page, Products and Gallery show the photos (from `res.cloudinary.com`).
- Sign in works, and you stay signed in when you open other pages.
- Add a test product with a photo: the photo appears, and in Cloudinary under **Media Library > dubai-tech-plaza**.
- Download an invoice PDF: the logo and product photos appear.

From now on, every `git push` to `main` updates the website automatically.

### Your own domain (optional)

Vercel > Project > **Settings > Domains** > add `www.yourdomain.com` and follow the DNS instructions. HTTPS is automatic.

## 7. Scheduled jobs (cron-job.org)

`vercel.json` already asks Vercel to call `index.php?url=cron/daily` once a day (sends any waiting messages and clears old sign-in sessions). Vercel adds `CRON_SECRET` to that call automatically.

To send WhatsApp/SMS messages within minutes, also add a job at https://cron-job.org:

- **URL:** `https://YOUR-SITE.vercel.app/index.php?url=cron/messages&token=YOUR_CRON_SECRET`
- **Schedule:** every 5 minutes.

Test it: open that address in a browser. You should see `{"ok":true,...}`. With a wrong or missing token, the page says "Page not found" (on purpose).

## 8. Backups

The free TiDB plan keeps its own automatic backups (cluster > **Backup**). Also keep your own copy now and then, for example monthly:

```
C:\xampp\mysql\bin\mysqldump.exe --host=HOST --port=4000 --user=USER -p --ssl --single-transaction --skip-lock-tables dubai_tech_plaza > tidb-backup.sql
```

Store it somewhere safe (it contains customer data). Photos are kept by Cloudinary.

---

## Troubleshooting

| Problem | Where to look / fix |
| --- | --- |
| "Something went wrong" page | Vercel > Project > **Logs**: the PHP error is shown there. |
| Database connection error in the logs | Check `DB_HOST`, `DB_PORT=4000`, `DB_USER` (with the `.root` part), `DB_PASS`, and that `DB_SSL_CA` on Vercel is `/etc/pki/tls/certs/ca-bundle.crt`. |
| Signed out on every page | `SESSION_DRIVER` must be `database` and the `sessions` table must exist (`tools\migrate.php --env=.env.tidb`). |
| Links go to `/api/...` or styles are missing | `APP_BASE_PATH` must be `/`. |
| Photo upload fails | `CLOUDINARY_URL` wrong, or the photo is over 4.5 MB. The logs show Cloudinary's message. |
| Cron address says "Page not found" | `CRON_SECRET` is not set on Vercel, is shorter than 16 characters, or the token in the address is different. |
| Changed a setting but nothing happened | Settings apply to new deployments: Vercel > Deployments > latest > **Redeploy**. |

## Settings used only by these hosts

| Setting | Vercel value | Meaning |
| --- | --- | --- |
| `APP_BASE_PATH` | `/` | Links point at the domain root (the code runs from `/api/index.php`) |
| `SESSION_DRIVER` | `database` | Sign-in sessions in the `sessions` table |
| `TRUST_PROXY` | `true` | Recognise HTTPS behind Vercel's servers |
| `DB_PORT`, `DB_SSL_CA` | `4000`, CA bundle path | Encrypted connection to TiDB |
| `CLOUDINARY_URL`, `CLOUDINARY_FOLDER` | from Cloudinary | New uploads go to Cloudinary |
| `CRON_SECRET` | long random text | Protects `cron/messages` and `cron/daily` |

Files involved: `vercel.json` (routing, PHP runtime, daily cron), `api/index.php` (entry point), `.vercelignore` (files never uploaded to Vercel).
