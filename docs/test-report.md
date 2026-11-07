# Verification report — October 27, 2026

The selected runtime is Laravel 12 on PHP 8.2.12 with XAMPP MariaDB/MySQL. Existing runtime records were preserved. Test fixtures are fictional and run in isolated databases.

## Automated checks

| Check | Result |
|---|---|
| `php artisan test --no-coverage` | 74 tests passed, 352 assertions, isolated in-memory SQLite |
| `php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml --no-coverage` | 74 tests passed, 352 assertions; dedicated `scholarlink_qa_20261027_test` database only |
| `npm.cmd test` | 3 JavaScript tests passed |
| `npm.cmd run build` | Vite build passed; Node version warning noted below |
| `php artisan view:cache` | Blade templates compiled successfully |
| `php artisan migrate --force` | Runtime schema is current; nothing to migrate |
| `php tools/export-schema.php` | Reads schema metadata to produce the column/index/foreign-key dictionary |

Coverage includes registration, role escalation prevention, normalization, unique database constraints, password policy and hashing, password reset and token revocation, login throttling, actual CSRF middleware, role dashboards, profile validation, publication/deadlines, drafts and resumption, required document checks, private document access, spoofed upload rejection, repeated submission, database rollback, verification/corrections, eligibility, recommendations, administrator decisions, award creation, renewal drafts/submission, notifications, report filtering and unauthorized access.

Additional regression checks cover public requirements routing, JSON errors without an Accept header, sensitive login input not retained in session flash data, template JSON escaping, public browsing with verification enabled, and JSON draft persistence before uploading. JavaScript tests check that native form submissions retain the clicked action, cancelled validation/AJAX submissions remain usable, and repeated native submissions are blocked.

## Real-browser acceptance

Chrome headless, a separate browser profile, and the dedicated MySQL QA database were used. The test creates its own fictional student and scholarship. No production student records or external email delivery are involved.

Passed journey:

1. Browse the public scholarship directory without authentication.
2. Register through the real CSRF-protected browser form.
3. Complete and save the student profile.
4. Select a configured scholarship and complete its custom and financial fields.
5. Save a draft at step five and resume the same server-persisted application.
6. Upload a real fictional PDF through the browser's file input and progress handler.
7. Reload and confirm the file and saved step are retained.
8. Review at a 390-pixel mobile viewport without horizontal page overflow.
9. Accept the declaration and submit, displaying `submitted` and the unique reference number.

There were no unhandled page JavaScript exceptions on the passing journey. Desktop registration, directory, checklist, mobile review and submission screenshots are kept locally under `storage/app/browser-qa/`, which is excluded from source control.

The browser check found and resolved two application defects: double-escaped configuration attributes prevented the wizard from reading requirements, and a submit control named `action` shadowed the form URL during the pre-upload save. Native submit-button disabling was also corrected so draft/submit actions are included in requests.

## Verification limits and deployment requirements

- SMTP delivery, optional Supabase storage/PostgreSQL, public HTTPS, production backups and supervised workers require the operator's actual configuration. They were not exercised against credentialed external services. A log mailer is not proof of email delivery.
- The installed Node 20.18.0 completed the build but is below Vite's supported minimum. Use Node 20.19+ or 22.12+ for repeatable installation/builds; the package declares this requirement.
- These are functional/security regression checks, not a penetration test or multi-user load test. Parallel submission behavior is backed by database uniqueness and transactional locks; distributed load testing remains a deployment exercise.
- Institution-approved scholarship policy, privacy contact, lawful basis and retention periods must be configured before collecting real records. Demonstration programs are not official ADFC policy.
- Apache VirtualHost installation is documented in `installation.md`; the HTTP/browser check used Laravel's local development server with XAMPP's database.

## Reproduce the browser check

First run the MySQL tests, then create browser fixtures. Do not run the PHPUnit MySQL suite while the browser test is using the same QA database.

In a dedicated PowerShell terminal:

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='scholarlink_qa_20261027_test'
$env:EMAIL_NOTIFICATIONS='false'
$env:REQUIRE_EMAIL_VERIFICATION='false'
$env:APP_URL='http://127.0.0.1:8001'
php tools/prepare-browser-tests.php
php artisan serve --host=127.0.0.1 --port=8001
```

In a second terminal, run `node --experimental-websocket tools/browser-smoke.cjs`. The script uses installed Chrome; override `CHROME_PATH` if needed. It launches a hidden test browser on loopback port 9227 and closes it afterwards. Stop the QA server and close the dedicated terminal after testing so its database environment cannot be reused accidentally.

## Final MySQL run

The final MySQL run passed all 74 tests and 352 assertions in 24.878 seconds. No unresolved test failures remain. The final SQLite run passed the same 74 tests and 352 assertions in 13.90 seconds. The restricted execution environment occasionally blocks Node subprocess creation (`EPERM`); the successful build/test runs used approved execution outside that restriction.
