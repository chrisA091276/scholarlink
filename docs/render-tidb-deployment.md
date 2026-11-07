# Deploy ScholarLink with GitHub, Render, and TiDB Cloud

GitHub holds the source. A Render **Docker Web Service** runs Laravel, the landing page, and all three role dashboards. A **MySQL-compatible TiDB Cloud** instance holds accounts, applications, sessions, and other database records. Uploaded documents and avatars need separate persistent file storage.

The repository now includes a `Dockerfile`, `.dockerignore`, and `deploy/render/` startup/Apache/PHP configuration. The image uses PHP 8.3 and Node 22, installs dependencies, builds Vite assets, and serves only `public/`. Database migrations run at startup only when `RUN_MIGRATIONS=true`; they never run during the image build. The runtime supports the port Render supplies.

## 1. Push the project to GitHub

Use a private repository if the project is not intended to be public. With GitHub Desktop, add this project folder (or create a repository here if it is not one yet), review the Changes list, commit, and publish/push the repository.

The repository root on GitHub must contain `Dockerfile`, `artisan`, `composer.json`, `composer.lock`, `package.json`, and `package-lock.json`, plus the application directories and `deploy/`. Keep the local images, fonts, libraries, and video in `public/` because the pages use them.

Do not upload `.env`, database dumps, the local SQLite file named `adfc_scholarship`, document uploads, browser preview profiles, `vendor/`, or `node_modules/`. The ignore files cover these local artifacts. If any secrets were already committed, remove them from the repository and rotate them; adding an ignore rule does not remove tracked secrets. GitHub Pages cannot execute this PHP application.

## 2. Prepare TiDB Cloud

1. Open or create a **MySQL-compatible** TiDB Cloud instance. Prefer a location near your Render region when available.
2. In TiDB's SQL Editor, create an empty application database:

   ```sql
   CREATE DATABASE IF NOT EXISTS adfc_scholarship
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Open **Connect** and select a standard/public connection. Copy the exact host, port, username, and password shown for your instance. Port `4000` is common, but use the value shown by your console. Keep any prefix in the username.
4. Use a database account authorized for this database, including the table/index/foreign-key changes needed by Laravel migrations. Keep its password in Render's Environment settings.
5. If your TiDB instance uses an IP access list, add **all** outbound IP ranges shown by Render under your web service's **Connect > Outbound** tab. Include the ranges of any separately deployed worker or scheduler. Do not use your site's inbound IP or a permanent allow-all rule.

Use `DB_CONNECTION=mysql` in Laravel. Do not select Render PostgreSQL or change to `pgsql` for this guide. TiDB Cloud Starter/Essential standard connections require TLS. This Docker image includes the Linux CA bundle at `/etc/ssl/certs/ca-certificates.crt`; the existing database configuration reads `MYSQL_ATTR_SSL_CA`. Do not disable certificate verification. If a Dedicated instance's Connect screen supplies a different CA, upload it as a Render secret file and set the CA path to that file.

## 3. Create a Render Web Service

In Render, choose **New > Web Service**, connect GitHub, and select this repository.

| Render setting | Value |
| --- | --- |
| Name | Your chosen service name, e.g. `adfc-scholarlink` |
| Branch | The branch containing these changes, usually `main` |
| Runtime / Language | Docker |
| Root Directory | Leave blank when the project is at the repository root |
| Dockerfile Path | `./Dockerfile` |
| Docker Build Context | `.` |
| Docker Command | Leave blank; use the image's default startup command |
| Health Check Path | `/up` |
| Region | Prefer a region near TiDB and your users |

For durable local uploads, select a **paid web-service instance** and attach a persistent disk:

| Disk setting | Value |
| --- | --- |
| Mount path | `/var/www/html/storage/app` |
| Size | Start with enough space for your uploaded documents and avatars, e.g. 1 GB for a small trial |

Only files under the mount path survive redeploys. Documents remain under `private/`; `public/storage` exposes only the `public/` avatar directory. A disk allows one service instance and causes brief downtime during redeployment.

## 4. Set the Render environment variables

For a **new empty deployment**, run this locally and copy the complete output into Render's `APP_KEY` field:

```powershell
php artisan key:generate --show
```

`--show` prints a key without replacing the local one. If you are migrating an existing installation and its records, reuse that installation's existing `APP_KEY` instead. Keep the deployment key stable across deploys; do not run `key:generate` in the startup command.

Add these values in Render **Environment** before the first deployment. Replace every placeholder. Paste raw values into individual fields, without enclosing quotes. For passwords, use the individual field to preserve special characters.

```dotenv
APP_NAME=ScholarLink
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY
APP_URL=https://YOUR-SERVICE.onrender.com
APP_TIMEZONE=Asia/Manila
TRUST_RENDER_PROXY=true

DB_CONNECTION=mysql
DB_HOST=YOUR_TIDB_HOST
DB_PORT=4000
DB_DATABASE=adfc_scholarship
DB_USERNAME=YOUR_EXACT_TIDB_USERNAME
DB_PASSWORD=YOUR_TIDB_PASSWORD
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
DOCUMENT_STORAGE=local
MAX_UPLOAD_KB=5120

LOG_CHANNEL=stderr
LOG_LEVEL=info
RUN_MIGRATIONS=true
SHOW_DEMO_CREDENTIALS=false
MAIL_MAILER=log
EMAIL_NOTIFICATIONS=false
REQUIRE_EMAIL_VERIFICATION=false
```

Leave `DB_URL`, `DATABASE_URL`, `SESSION_DOMAIN`, and `ASSET_URL` unset unless you deliberately need them. `DB_URL` can override the separate database settings. Use the actual assigned Render URL for `APP_URL`; update it when adding a custom domain. Only enable `TRUST_RENDER_PROXY` when incoming traffic goes through Render's proxy.

`QUEUE_CONNECTION=sync` runs jobs during the request and needs no separate queue worker. In-app notifications still work. The log mailer does **not** deliver reset or verification emails; configure SMTP before relying on those features.

## 5. Deploy and initialize accounts

Click **Create Web Service** or **Manual Deploy > Deploy latest commit**. The Docker build installs dependencies. At runtime, the startup script creates storage directories, runs pending migrations, caches configuration/views, links avatars, and starts Apache. No demonstration accounts are seeded.

Once the service is live, open Render **Shell** on the paid web service:

```sh
php artisan migrate:status
php artisan app:create-admin your-email@example.com --name="Scholarship Administrator"
```

The second command asks for a hidden password of 12–72 characters containing uppercase, lowercase, a number, and a symbol. It refuses to overwrite an existing account. Sign in at `/login/admin`, then use **User Management** to create Staff accounts. Students use `/register` and `/login/student`; Staff use `/login/staff`.

Never run `migrate:fresh` on your deployed database. `migrate` creates/updates tables; it does not copy local accounts or existing uploaded documents. Moving existing data requires a database export/import and a separate private-file transfer, with a backup first.

You can keep `RUN_MIGRATIONS=true` for this single-instance setup: only pending migrations execute. For a managed release workflow, switch it to `false` and run `php artisan migrate --force` in a controlled deployment step. Workers and cron mode ignore this flag and never migrate.

## 6. Email and scheduled reminders

For email, add your provider's real values:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=YOUR_SMTP_HOST
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=YOUR_SMTP_USERNAME
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_FROM_ADDRESS=YOUR_VERIFIED_SENDER
MAIL_FROM_NAME=ScholarLink
EMAIL_NOTIFICATIONS=true
```

Use `MAIL_SCHEME=smtps` and port `465` only if your provider specifies implicit TLS. Enable `REQUIRE_EMAIL_VERIFICATION=true` after testing delivery. Render blocks port 25 for all services; free web services also block ports 465 and 587.

To run deadline/renewal reminders and expired-token pruning, add a Render **Cron Job** from the same repository, Docker runtime, and application/database environment. Use schedule `* * * * *` and Docker Command:

```sh
/bin/sh /var/www/html/deploy/render/start.sh schedule
```

Set `RUN_MIGRATIONS=false` there and create it after the web service's first migration succeeds. Render cron schedules use UTC, but Laravel's own daily tasks use the configured `APP_TIMEZONE`. This job only needs database access, not the upload disk. Cron jobs are a separately billed service.

If email volume grows, change `QUEUE_CONNECTION=database` consistently and add a Render **Background Worker** using the same repository and environment, with Docker Command:

```sh
/bin/sh /var/www/html/deploy/render/start.sh worker
```

The worker runs the project's email jobs; they do not need the web service's disk. Keep `APP_KEY` and database credentials consistent across services. Use `php artisan queue:failed` to check failures. Switching to the database queue without a running worker leaves email jobs waiting.

## Free Render option

A free Docker web service can demonstrate the app with TiDB, using the same environment values. It sleeps after inactivity, has no persistent disk, no Render Shell, and blocks standard SMTP ports. TiDB keeps database rows, but **local documents and avatars are lost when the service restarts, redeploys, or sleeps**.

For a free demo's first administrator, use this project's interactive command from a local terminal connected to the cloud database. Create an ignored `.env.tidb-admin` locally with the deployment `APP_KEY`, TiDB database values, `APP_ENV=production`, `APP_DEBUG=false`, and a Windows CA file. Download the ISRG Root X1 certificate linked in TiDB's TLS documentation and set `MYSQL_ATTR_SSL_CA` to its absolute local path; do not use the Linux path on Windows. If TiDB uses an access list, temporarily add your current public IP. After confirming the remote schema exists, run:

```powershell
php artisan app:create-admin your-email@example.com --name="Scholarship Administrator" --env=tidb-admin
```

This creates an account in the remote database. Do not run tests or demo seeding against it. Remove the temporary environment file when finished. If Laravel has cached configuration locally, clear it before using the alternate environment file.

For persistent scholarship documents on a free web service, the app already supports an optional **private Supabase Storage bucket** (`DOCUMENT_STORAGE=supabase` plus its server-side credentials). That adds another service and does not yet move public avatars off local storage. With only GitHub + Render + TiDB, the paid Render disk is the straightforward option for preserving all uploads.

## Verify the live deployment

- Open `/up`, `/`, and `/scholarships`. `/up` only checks framework startup; the latter two also exercise the database.
- Register a temporary student, log in with each role, and confirm URLs/assets remain HTTPS.
- Create a test program and application, upload a small document, and download it as the authorized user.
- Verify the application PDF and avatar display.
- Redeploy and confirm the uploaded files remain available.
- Test password-reset mail if SMTP is enabled.

| Symptom | Check |
| --- | --- |
| Connection timeout / refused | Exact TiDB host and port, public endpoint, instance state, and Render outbound IP access |
| `Access denied` | Full TiDB username (including prefix), password, and database privileges |
| TLS / certificate failure | `MYSQL_ATTR_SSL_CA` exists in that environment; keep verification enabled |
| `could not find driver` | Runtime must be Docker using this Dockerfile, which installs `pdo_mysql` |
| Missing table | Startup migration logs and database permissions |
| HTTP assets or forms under an HTTPS page | `TRUST_RENDER_PROXY=true`, correct `APP_URL`, and redeploy to refresh cached settings |
| Login expires / 419 | Stable `APP_KEY`, database sessions, HTTPS, correct hostname, and no stale `SESSION_DOMAIN` |
| Uploaded files disappear | Disk mount must be exactly `/var/www/html/storage/app`, or use supported external storage |
| Build cannot find `artisan` / `composer.json` | Repository root and Docker build context |

Prepared locally on November 6, 2026. The 21 selected PHP tests passed (217 assertions), including proxy handling, authentication, landing, and dashboards; the Vite production build also passed. The local Node 20.18 installation emitted Vite's version warning; the Docker build uses supported Node 22. Docker is not installed in this workspace, and no TiDB/Render credentials were supplied, so the Docker image build and live TiDB migration still need validation during your first deployment.

Official references: [Render's Laravel/Docker guide](https://render.com/docs/deploy-php-laravel-docker), [persistent disks](https://render.com/docs/disks), [free-service limitations](https://render.com/docs/free), [Render outbound IP ranges](https://render.com/docs/outbound-ip-addresses), [cron jobs](https://render.com/docs/cronjobs), [TiDB connections](https://docs.pingcap.com/tidbcloud/connect-to-tidb-cluster-serverless/), [TiDB TLS and CA files](https://docs.pingcap.com/tidbcloud/secure-connections-to-serverless-clusters/), [TiDB MySQL compatibility](https://docs.pingcap.com/tidbcloud/mysql-compatibility/), and [Laravel trusted proxies](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies).
