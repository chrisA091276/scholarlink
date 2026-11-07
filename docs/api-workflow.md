# API workflow and contracts

Base URL: `/api/v1`. Send `Accept: application/json`; JSON requests also use `Content-Type: application/json`. Public read endpoints expose published programs. Admin management pages can view drafts. Protected endpoints require a current Sanctum token and an active account. Use a dedicated API client's secure token store; the Blade website uses CSRF-protected session forms instead.

`docs/openapi.yaml` describes the required endpoint groups; the Postman collection supplies executable examples. Existing `/api` and shorter `/my-applications` aliases remain compatible. Do not use those aliases when creating a new integration.

## Response conventions

- A resource is under `data`; paginated resources include Laravel `links` and `meta` (or the paginator's equivalent pagination fields).
- Authentication responses contain `user`, `token`, and `token_type`. Tokens expire after the configured lifetime and logout revokes the current token.
- Mutations include a `message`; creates normally return `201`, reads and updates `200`.
- Validation returns `422` with `message` and an `errors` object keyed by field. Authentication failure is `401`; role/ownership failure `403`; missing or unpublished records `404`; throttling `429`. Browser forms additionally enforce CSRF (`419`).
- A failed create-and-submit request can return `422` with the **saved draft** in `data`. Resume its ID rather than creating another application. Transient server/storage failures never imply submission success.
- Unknown role/status request values never grant authority. Document paths and storage credentials are never public API fields; `download_url` is an authenticated controller endpoint.

## Registration and profile

`POST /register`:

```json
{
  "name": "Fictional Student",
  "email": "student@example.test",
  "contact_number": "09171234567",
  "password": "UseYourOwnPassword123!",
  "password_confirmation": "UseYourOwnPassword123!",
  "terms_accepted": true,
  "device_name": "postman"
}
```

Alternatively send `first_name` and `last_name`, with optional middle name/suffix. The server normalizes email and Philippine mobile numbers, checks uniqueness and confirmation, and requires 8–72 characters including uppercase and lowercase letters, a number and a symbol. Laravel hashes passwords. Public registration always assigns `student` regardless of client role input. The database also prevents duplicate accounts if concurrent requests pass initial validation.

Use `GET/PUT /student/profile`. Required profile values are `student_id_no`, `first_name`, `last_name`, `date_of_birth` (before today), `contact_number`, `address`, `course`, `year_level`, and `enrollment_status`. Optional fields include middle name, suffix, department, academic year, semester, emergency contact. Email belongs to the account; update it through `/account`, which clears verification. Financial information belongs to the program-specific application, not general profile updates. A profile used by an active submitted application/award is locked until an authorized correction workflow permits editing.

## Application lifecycle

1. Browse `GET /scholarships`, `GET /scholarships/{id}`, and `GET /scholarships/{id}/requirements`. Read `application_template` and configured eligibility rules. Only published programs within their application period accept a new draft.
2. `POST /student/applications` with `scholarship_program_id`, `academic_year`, `action: "draft"`, and `last_saved_step: 1`. The response returns a unique `id` and `application_no`. One application per student/program/academic-year cycle is enforced by a database constraint. A saved draft cannot switch its scholarship.
3. `PUT /student/applications/{id}` to save answers and the last step (1–7). Include `scholarship_program_id` and `action: "draft"`. Supported values are `academic_year`, `enrollment_status`, `statement`, permitted financial values (`guardian_name`, `guardian_occupation`, `household_size`, `monthly_household_income`) and template answers under `form_data`. Financial keys without configured program rules are discarded. Unknown template keys are discarded; configured types/options are validated.
4. `POST /student/applications/{id}/documents` as multipart form data: `document_name`, configured `scholarship_requirement_id`, and `file`. Optional `replace_document_id` must belong to this application and match the original requirement. PDF, JPG/JPEG and PNG are supported, limited by each configured requirement and the global ceiling. The endpoint checks real MIME content and file extension. Uploads can occur while a draft or an authorized correction is active.
5. `POST /student/applications/{id}/submit` with `{"declaration":true}`. The service checks active account, optional verified email, profile completeness, academic information, required financial answers/template values/documents, program availability and eligibility. It records submission time, declaration, history, screening and notifications transactionally. Retries return the existing submitted record without a second notification/history entry. The first timestamp is retained across corrections.
6. Track `GET /student/applications/{id}` and `/history`, and `GET /notifications`. Update fields/documents only in allowed states. A verified document cannot be replaced during a correction unless staff marks that document for correction.

## Review and decisions

Administrators assign using `POST /applications/{id}/assign` with `assigned_to`. Assigned staff use `GET /staff/applications` and `GET /staff/applications/{id}`. List filters include `search`, `status`, `scholarship_id`, `assigned_to`, `mine`, `page` and bounded `per_page` where supported.

`POST /staff/applications/{id}/verify-documents`:

```json
{"documents":[{"id":1,"verification_status":"requires_correction","verification_notes":"Upload a clear copy showing the enrollment date."}]}
```

Batch reviews are atomic. Every item must belong to the application. Rejection/correction needs remarks, records a document review, reopens the permitted correction state, and notifies the student. Finalized applications cannot be re-reviewed.

`POST /applications/{id}/status` with `to_status: "under_review"`, then `/staff/applications/{id}/eligibility` to screen, then `to_status: "for_evaluation"`. Required documents must be verified and a screening record must exist before evaluation. General status updates cannot impersonate a student submission.

`POST /staff/applications/{id}/evaluation` or `/recommendation`:

```json
{"score":90,"recommendation":"recommended_approval","evaluation_notes":"Configured criteria and supporting documents reviewed."}
```

Recommendations use the canonical stored names `recommended_approval` and `recommended_rejection` (the equivalents of “recommended for approval/rejection” in the brief). The pending recommendation value is `pending`. An evaluation requires `for_evaluation` status. Record creation and transition share one transaction.

Only administrators call `POST /admin/applications/{id}/decision` with `to_status: "approved"` or `"rejected"` and `remarks` (required for rejection). Approval checks required verified documents and available award slots, creates one award, and records the responsible administrator. Direct arbitrary transitions are rejected. Main transition map:

```text
draft → submitted → under_review → for_evaluation
  → recommended_approval | recommended_rejection → approved | rejected
submitted | under_review | for_evaluation → incomplete_requirements → submitted
```

The full permitted withdrawal/resumption map is in `ScholarshipApplication::TRANSITIONS`; it is enforced by `ApplicationTransitionService`. Status history has no edit/delete endpoint and model updates/deletes are refused.

## Scholarship status and reinstatement appeals

`GET /my-awards` includes `recipient_status`, `status_description`, `status_reason`, `status_changed_at`, `can_appeal`, and appeal history. Award status is distinct from account access and application approval.

Administrators update `POST /awards/{id}/status` with `recipient_status` (`active`, `completed`, `suspended`, `removed`, or `expired`), optional award dates/remarks, and `status_reason` (required for removal or suspension). The reason is visible to the student. Changes preserve an audit trail, notify the student, and close pending appeals when the status changes. Restoring active status checks available scholarship slots.

Students submit `POST /awards/{id}/appeals` with `statement` (20–5,000 characters). Only their own removed/suspended awards are eligible; one pending appeal per award is enforced transactionally and by a database constraint. `GET /my-appeals` returns only that student's appeals. Students may submit a new explanation after a rejection.

Administrators use `GET /appeals?status=submitted` (also accepts `under_review`, `approved`, `rejected`, or `closed`) and `POST /appeals/{id}/decide`:

```json
{"status":"approved","decision_reason":"The attendance explanation has been accepted."}
```

Decision status accepts `under_review`, `approved`, or `rejected`; `decision_reason` is required for final decisions. Approval and award reactivation are atomic. Rejection keeps the award's current status. Finalized/stale appeals cannot be decided again. Staff cannot decide appeals or change membership. Review and status changes create notifications and audit records.

## Programs, awards and operations

- Administrator program writes: `POST /admin/scholarships`, `PUT /admin/scholarships/{id}`, `/publish`; `PUT` accepts `draft`, `published`, `closed`, `archived`. `DELETE /scholarships/{id}` archives programs with applications instead of deleting their history. Unique codes have field validation and database protection.
- Configure documents via `POST /scholarships/{id}/requirements`; eligibility rules via `/rules`. Operators configure actual policy; examples in seeds are fictional.
- Student awards and renewals: `GET /my-awards`, `GET /my-renewals`, `POST /awards/{id}/renewals`. `action: "draft"` resumes the existing draft for that award and academic year. `action: "submit"` checks configured renewal template fields. Duplicate submitted cycles are rejected. Only active awards can be renewed.
- Administrator renewal decisions: `POST /renewals/{id}/decide` with `status` and `remarks`. Only submitted/under-review renewals can receive a decision. Rejection needs a reason. Configure award `start_date` and `end_date` using `/awards/{id}/status` for validity/reminder tracking; no institutional renewal duration is invented.
- `GET /admin/reports?from=YYYY-MM-DD&to=YYYY-MM-DD` uses persisted submission dates. Reports and CSV/PDF export pages use authorized queries. `GET /admin/audit-logs` is administrator-only.
- `POST /notifications/{id}/read` and `/notifications/read-all` only modify the current user's messages.

See `php artisan route:list --path=api/v1` for the full route inventory, including compatibility aliases. `docs/installation.md` describes SMTP, queues, scheduler, private storage and test configuration.
