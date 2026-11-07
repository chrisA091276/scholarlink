# ADFC ScholarLink

ADFC ScholarLink is a browser-based Laravel 12 scholarship management system for Asian Development Foundation College. It provides student applications, private document uploads, staff review, administrator decisions, awards, renewals, notifications, reports, audit logs, and a Sanctum REST API.

The supported local runtime is **XAMPP Apache + MariaDB/MySQL**. This project does not include an Android or native mobile client.

## Quick start with XAMPP

1. Install PHP 8.2+, Composer, Node.js/npm (Node 20.19+ recommended for Vite), and XAMPP.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Create the database in phpMyAdmin (`http://localhost/phpmyadmin`):

   ```sql
   CREATE DATABASE adfc_scholarship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. From the project directory:

   ```powershell
   copy .env.example .env # new installation only
   composer install
   php artisan key:generate
   php artisan migrate
   npm.cmd ci
   npm.cmd run build
   php artisan serve
   ```

5. Open `http://localhost:8080`. For Apache, point the VirtualHost or alias at the project `public/` directory and enable `mod_rewrite`.

The `.env.example` file is configured for XAMPP's default `root` user with an empty password. Change it if your XAMPP installation uses a password.

## Fictional local demonstration accounts

These accounts are created only by the optional local/testing seeder. Do not use them for a real deployment. Create the initial administrator with the interactive provisioning command instead. Never run demonstration seeding over production records.

| Role | Email | Password |
|---|---|---|
| Administrator | admin@adfc.edu.ph | Admin123! |
| Staff / Evaluator | staff@adfc.edu.ph | Staff123! |
| Student | student@adfc.edu.ph | Student123! |

Public registration creates a **Student** account only. Staff and administrator accounts must be provisioned by an administrator or:

```powershell
php artisan app:create-admin admin@adfc.edu.ph --name="ADFC Admin"
```

Browser login pages are separate: `/login/student`, `/login/staff`, and `/login/admin`. Use **Switch role** in the upper right to move between them. `/login` opens the student login. Each page accepts only active accounts for its role; registration is available from the student page.

Student browser login has two steps: enter email and password, then verify the School ID registered to that same account (`00-000000`). The dashboard and remember-me session become available only after both steps pass. Students can go back to change their email; unfinished verification expires after five minutes. Missing or outdated School IDs must be corrected by the scholarship office. Staff and administrator browser login and API login retain their existing flows.

Browser sign-up collects personal details, school details, and a password in three steps. It requires a unique School ID in the `00-000000` format so the new account can complete student login. Back preserves entries within the page; server errors retain non-password details and return to the relevant step. With JavaScript disabled, the same fields appear as one form. The API registration contract is unchanged.

New passwords require at least eight characters with uppercase and lowercase letters, a number and a symbol (maximum 72 characters). Initial administrator provisioning requires at least twelve characters with the same composition rules. Demo login hints are hidden by default; enable `SHOW_DEMO_CREDENTIALS=true` only for a seeded, local demonstration.

## Main workflows

The scholarship application follows `docs/reference/ADFC_Form_Professional.docx`: applicant information, school information, family background, other financial assistance, privacy/consent, a 2 × 2 photo, and the office-use review. New applications save a separate snapshot of these answers and can be downloaded as an A4 PDF. The online declaration is recorded at submission; the printed form retains a signature line.

All three dashboards follow this form. Students can jump to each section and see missing, uploaded, returned, or verified evidence. Staff see assigned reviews and required evidence; administrators see unassigned applications and recommendations awaiting a decision. Dashboard counts and linked queues use the same workflow stages. Existing submitted applications retain their saved records; editable drafts adopt the professional form when saved. Downloads for all applications use the reference layout, with available details mapped for older records and unrecorded answers clearly identified.

Run `php artisan migrate` on an existing installation to add the form and document-category columns. Existing accounts, applications, and uploads are retained. See [professional form verification](docs/professional-form-verification.md) for the reference mapping and checks.

- **Student:** create an account, complete a profile, browse open programs, save/resume a seven-step application, upload private requirements, submit, track status, correct documents, view scholarship membership and removal reasons, appeal for reinstatement, and save/resume renewals with program-specific questions.
- **Staff:** view assigned applications, verify documents, screen configured eligibility rules, evaluate, request corrections, and recommend approval/rejection. Staff cannot make final decisions.
- **Administrator:** manage users, programs, requirements, eligibility rules, assignments, final decisions, awards, reinstatement appeals, renewals, announcements, reports, audit logs, and settings.

### Scholarship status and reinstatement

Students see their current membership on the dashboard and **Scholarship Status** page. Administrators can mark an award active, completed, suspended, removed, or expired. Removal/suspension requires a student-visible reason (such as documented absences); it does not deactivate the student's login. Removal is recorded by an administrator rather than inferred automatically from attendance.

Students with removed or suspended awards can submit an explanation through **Appeal for reinstatement**. One appeal may await review per award. Administrators use **Scholarship Appeals** to mark it under review, reject it with an explanation, or approve and restore the existing award to active status. Approval respects program capacity. Students receive notifications and can read their appeal/decision history. A new appeal may be submitted after rejection; changing an award's status closes outstanding appeals to prevent outdated decisions from restoring the wrong status.

For existing installations, run `php artisan migrate` to add the appeal records and award status fields. Existing awards and accounts are retained.

Required documents and application-specific fields are configured per scholarship. Eligibility rules can target standard profile/application values or any field in a scholarship's application template, using comparisons such as numeric thresholds, dates, lists, text matching, presence checks, and document completion. Each failed rule can either mark an applicant not eligible or send the application to manual review. A student may save a draft, but submission is blocked until all required documents have been uploaded. Every status change is recorded in append-only history and approval creates an award inside a database transaction.

GWA/GPA is retired from profiles, applications, renewals, reports, downloads, and eligibility screening. Run `php artisan migrate` and `php artisan view:clear` when updating an existing installation to remove the old configuration and refresh compiled pages. Historical numeric columns remain private and unused; new requests do not collect these values.

## Private uploads and notifications

Documents are stored under `storage/app/private/documents` on the local disk. They are never exposed through a public storage link; downloads pass through an authorized controller route. Uploads are validated for extension/MIME, configured requirement type, and size limits.

In-app notifications are persisted in the database. The default mailer is `log`; configure SMTP in `.env` before expecting real email delivery. A log entry is not treated as a successful external delivery.

## Local photos, website assets, and database copies

The website serves its photos, Bootstrap 5.3.3, Bootstrap Icons 1.11.3, Chart.js 4.4.1, and Instrument Sans fonts from this project. These assets no longer require Unsplash, jsDelivr, or Bunny Fonts at page load.

- `public/images/dashboard/` contains the downloaded dashboard and scholarship photos, including the student dashboard video poster. Existing branding and login photos remain in `public/images/`.
- `public/videos/dashboard/campus-walk.mp4` is the student dashboard's locally served campus video. It is muted, supports play/pause, pauses offscreen, and uses a static poster when reduced motion or data saving is enabled. See [student dashboard media and verification](docs/student-dashboard.md).
- `public/vendor/` contains the pinned libraries, icon fonts, web fonts, and license notices.
- `docs/local-assets.json` records the original download URLs, file sizes, and SHA-256 checksums. To refresh these copies with internet access, run `node tools/download-local-assets.cjs`.
- `storage/app/private/backups/mysql/` contains private, dated SQL snapshots and file-audit reports. Run `php tools/backup-local-database.php` while MySQL is running to create a fresh snapshot. The command reads the configured database and does not modify it; set `MYSQLDUMP_PATH` if the export utility is outside PATH and the default XAMPP location.

Keep the entire `storage/app/private/` and `storage/app/public/` folders when copying the project, along with its `.env`. SQL snapshots are excluded from Git and are not served from `public/`. To restore on another computer, import a chosen SQL snapshot into that computer's MySQL database and configure `.env` for that database. A snapshot captures the data at export time; later changes require a new export. PHP and MySQL/XAMPP still need to be installed on the destination computer. Recreate the public upload link there with `php artisan storage:link`.

## REST API

The canonical API is versioned at `/api/v1` and uses Laravel Sanctum bearer tokens. `/api` remains as a backward-compatible local alias for existing collections.

```powershell
# Login
curl -X POST http://localhost:8080/api/v1/login `
  -H "Accept: application/json" -H "Content-Type: application/json" `
  -d '{"email":"student@adfc.edu.ph","password":"Student123!"}'

# Authenticated request
curl http://localhost:8080/api/v1/me `
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

Import `postman/ScholarLink-API.postman_collection.json` and set its `base` variable to `http://localhost:8080/api/v1`. A ready local environment is provided at `postman/ScholarLink-API.postman_environment.json`.

The API includes authentication/password reset, student profile/dashboard/applications/documents/history, public scholarships and requirements, staff review actions, administrator dashboard/users/reports/audit logs, notifications, awards, renewals, and CRUD/configuration endpoints.

## Testing

Tests intentionally use an isolated in-memory SQLite database through `phpunit.xml`; this does not change the XAMPP runtime database.

```powershell
php artisan test --no-coverage
npm.cmd test
```

To test against an isolated XAMPP database (never the runtime database):

```powershell
php tools/prepare-mysql-tests.php
php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml --no-coverage
```

## Project documentation

- [Deploy with GitHub, Render, and TiDB Cloud](docs/render-tidb-deployment.md), including the Docker runtime, TLS database settings, persistent uploads, and first administrator.

- [Complete installation and deployment guide](docs/installation.md), including Apache, queues, scheduler, SMTP, and optional Supabase.
- [API workflow guide](docs/api-workflow.md), with payloads, permissions and response conventions.
- [Column-level data dictionary](docs/data-dictionary.md), generated from the migrated XAMPP schema.
- [Privacy operations](docs/privacy-operations.md), covering approved retention, access and correction procedures.
- [Test report](docs/test-report.md), including verification limits.
- [Mobile layouts and verification](docs/mobile-responsiveness.md), including responsive breakpoints and browser checks.

- `docs/openapi.yaml` — API contract for `/api/v1`.
- `docs/database.md` — relationship diagram, data dictionary, and environment variables.
- `postman/ScholarLink-API.postman_collection.json` — API requests.
- `postman/ScholarLink-API.postman_environment.json` — local API environment.

## Apache/XAMPP production checklist

- Set `APP_DEBUG=false` and use a strong `APP_KEY`.
- Keep `storage/app/private` outside the public web root and writable by Apache.
- Configure HTTPS, SMTP, queue/scheduler workers, backups, log rotation, and least-privilege database credentials.
- Do not commit `.env`, tokens, uploaded documents, or production passwords.
