# Installation and deployment

ScholarLink is a Laravel 12 web application. The selected local database is **XAMPP MariaDB/MySQL**, following the final project requirement. PostgreSQL and hosted Supabase are optional alternatives, not requirements for running this installation.

## Local XAMPP setup

1. Install XAMPP with PHP 8.2 or newer, Composer 2, and Node 20.19+ or 22.12+ with npm. Enable `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `curl`, `xml`, `dom` and `zip` in the PHP configuration used by both CLI and Apache. SQLite tests additionally need `pdo_sqlite`. Use `php --ini` and `php -m` to inspect the CLI runtime.
2. Start Apache and MySQL in XAMPP. Create a database through phpMyAdmin:

   ```sql
   CREATE DATABASE adfc_scholarship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Open a terminal in the project root. On a new installation only:

   ```powershell
   Copy-Item .env.example .env
   composer install
   php artisan key:generate
   npm.cmd ci
   npm.cmd run build
   ```

   Keep an existing `.env` and `APP_KEY` during upgrades. Never commit them. This repository already is the Laravel project; do not run `create-project` over it.
4. Configure `DB_CONNECTION=mysql`, host `127.0.0.1`, port `3306`, database `adfc_scholarship`, and your XAMPP credentials. The example uses local root with an empty password; use a dedicated password-protected account for deployment.
5. Apply migrations:

   ```powershell
   php artisan config:clear
   php artisan migrate
   php artisan migrate:status
   ```

   Migrations preserve existing records. The renewal uniqueness migration stops if legacy duplicate cycles need review. Never use `migrate:fresh` against an application database.
6. Choose how to initialize accounts. For an empty local demonstration database, `php artisan db:seed` creates clearly labeled fictional examples. The seeder refuses production. For real installation, skip demonstration seeding and run:

   ```powershell
   php artisan app:create-admin administrator@example.edu --name="Scholarship Administrator"
   ```

   The command asks for a hidden password of 12–72 characters with uppercase and lowercase letters, a number and a symbol. It never overwrites an existing account. Use administrator User Management to create staff. There is no public privileged registration route. Local demo credential hints are disabled by default (`SHOW_DEMO_CREDENTIALS=false`).
7. For development, `php artisan serve --host=127.0.0.1 --port=8080` serves the web application at `http://127.0.0.1:8080`; XAMPP MySQL supplies persistence. Alternatively configure Apache with the example below. Set `APP_URL` to the origin actually used. Public profile photos optionally use `php artisan storage:link`; application documents always remain on the separate private disk.

## Apache web hosting

Use `docs/xampp-vhost.conf` as a template in XAMPP's `apache/conf/extra/httpd-vhosts.conf`, with the actual project path. Ensure that file is included by `httpd.conf`, and `mod_rewrite` is enabled. Add `127.0.0.1 scholarlink.test` to the Windows hosts file using an authorized administrator account, set `APP_URL=http://scholarlink.test`, then restart Apache.

The document root must be `public`, never the project root. Do not expose `.env`, `vendor`, `database`, `storage/app/private`, or backup files. Allow the web account to write only Laravel's `storage` and `bootstrap/cache` directories. Keep phpMyAdmin restricted to local/administrative access.

PHP `upload_max_filesize` must be at least the configured `MAX_UPLOAD_KB`; `post_max_size` must be larger to accommodate multipart overhead. Restart Apache after PHP configuration changes. Supported document types are PDF, JPG/JPEG and PNG; each requirement selects a subset and a limit at or below the global maximum. Server validation checks file content, extension and size.

## Authentication and email

The Blade web application uses Laravel session cookies and CSRF forms. It does not save bearer tokens in browser storage. `/api/v1/login` and `/api/v1/register` issue Sanctum bearer tokens for API clients, with a configurable default lifetime of 1,440 minutes. API clients send `Accept: application/json` and `Authorization: Bearer …`. Browser sessions expire according to `SESSION_LIFETIME`. The current frontend does not require a separate SPA or stateful Sanctum middleware.

Configure a real SMTP provider using `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and an authorized sender address. Laravel 12 supports `MAIL_SCHEME=smtp` for STARTTLS or `smtps` for implicit TLS, according to the provider. Set `EMAIL_NOTIFICATIONS=true` for queued notification mail and `REQUIRE_EMAIL_VERIFICATION=true` to require verification before application or renewal submission. The default log mailer does not deliver external email. Password-reset responses intentionally do not disclose whether an account exists.

Run a worker in its own terminal:

```powershell
php artisan queue:work --tries=3 --timeout=90
php artisan schedule:work
```

These are two separate long-running processes. On Windows production use supervised services for the queue and a Task Scheduler job invoking `php artisan schedule:run` every minute with the project as its working directory. Linux uses a supervised worker and a one-minute cron entry. Scheduled tasks create deadline reminders, configured award-expiry reminders, and prune expired tokens. Scholarship emails retry three times; inspect `php artisan queue:failed` and application logs before retrying failures. In-app messages persist independently of email delivery.

## Optional Supabase integration

XAMPP requires neither a Supabase project nor cloud credentials. For optional private file hosting, create a **private** bucket, set `DOCUMENT_STORAGE=supabase`, `SUPABASE_URL=https://<project>.supabase.co`, `SUPABASE_STORAGE_BUCKET`, and a server-only `SUPABASE_SERVICE_ROLE_KEY`. Do not give browser clients the service role key or create anonymous/public read policies. Laravel authorizes the owner, assigned staff, or administrator, checks that the bucket is private, and proxies downloads. Storage failure returns an error; it does not claim an upload succeeded. Existing documents remember their storage disk.

If choosing Supabase PostgreSQL instead of XAMPP, enable `pdo_pgsql`, use `DB_CONNECTION=pgsql`, and copy the host, port, database, username, and password from the project's database connection settings. Use an appropriate server/pooler connection and require TLS (`DB_SSLMODE=require`). Run migrations on a new database and run the workflow tests on a separate PostgreSQL testing installation before deployment; this delivery was verified on MariaDB and SQLite, not a credentialed Supabase instance.

## Tests and schema documentation

```powershell
php artisan config:clear
php artisan test --no-coverage
npm.cmd test
php tools/prepare-mysql-tests.php
php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml --no-coverage
php tools/export-schema.php
```

The first suite uses in-memory SQLite. The second uses only `scholarlink_qa_20261027_test`, a dedicated local MySQL database. `Tests/TestCase.php` refuses other database targets. The preparation helper needs local database-creation permission; tests need DDL permissions on the test database only. Never point the runtime `.env` at this test database. Do not run both test suites concurrently because they share framework storage fixtures. The schema exporter reads metadata only, not record contents.

## Production deployment

1. Back up the database and private files and test restoration in an isolated environment. Encrypt backups, restrict access and retain them under the institution's approved schedule. Keep the application key separately and securely.
2. Deploy the source and lockfiles. Run `composer install --no-dev --optimize-autoloader` and `npm ci && npm run build` in a build environment with a supported Node version.
3. Configure production secrets outside source control: `APP_ENV=production`, `APP_DEBUG=false`, canonical HTTPS `APP_URL`, dedicated database credentials, SMTP, private storage, `SESSION_SECURE_COOKIE=true`, and appropriate logging. Serve only `public/` behind HTTPS.
4. Run `php artisan migrate --force`, `php artisan config:cache`, and `php artisan view:cache`. This application includes closure routes; do not assume route caching is enabled. Restart workers with `php artisan queue:restart`.
5. Supervise workers and scheduler, rotate logs, monitor failed jobs and storage capacity, and verify `/up` plus a database-backed page such as `/scholarships`. `/up` alone checks framework startup, not external service health.
6. Configure real scholarship requirements and grading rules, award validity periods, the official privacy contact, and approved retention procedures before onboarding real students. Demonstration eligibility thresholds and documents are not official ADFC policy.

Official references: [Laravel 12 deployment](https://laravel.com/docs/12.x/deployment), [Sanctum](https://laravel.com/docs/12.x/sanctum), [task scheduling](https://laravel.com/docs/12.x/scheduling). See `privacy-operations.md` for privacy procedures and `test-report.md` for verified results and limitations.
