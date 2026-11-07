# Data dictionary

Generated from the migrated XAMPP schema. Contains schema metadata only, never student records. Laravel migrations remain the source of truth.

The `gpa` columns in `student_profiles`, `scholarship_applications`, and `renewal_applications` are retained only for historical storage. They are no longer collected, displayed, exported, or used for eligibility. Active GWA/GPA settings, template fields, and rules are removed by the retirement migration.

## `announcements`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `title` | `varchar(200)` | No |
| `content` | `text` | No |
| `audience` | `varchar(30)` | No |
| `is_published` | `tinyint(1)` | No |
| `published_at` | `timestamp` | Yes |
| `created_by` | `bigint(20) unsigned` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `announcements_audience_is_published_index`: audience, is_published
- `announcements_created_by_foreign`: created_by
- `primary`: id (unique)

Foreign keys:

- created_by → users(id); delete set null.

## `application_documents`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `scholarship_application_id` | `bigint(20) unsigned` | No |
| `scholarship_requirement_id` | `bigint(20) unsigned` | Yes |
| `document_name` | `varchar(200)` | No |
| `file_path` | `varchar(500)` | No |
| `file_type` | `varchar(20)` | Yes |
| `file_size` | `bigint(20) unsigned` | No |
| `verification_status` | `varchar(30)` | No |
| `verification_notes` | `text` | Yes |
| `verified_by` | `bigint(20) unsigned` | Yes |
| `verified_at` | `timestamp` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |
| `storage_disk` | `varchar(20)` | No |
| `deleted_at` | `timestamp` | Yes |
| `original_filename` | `varchar(255)` | Yes |

Indexes:

- `application_documents_scholarship_requirement_id_foreign`: scholarship_requirement_id
- `application_documents_verified_by_foreign`: verified_by
- `app_docs_appid_verifstatus_idx`: scholarship_application_id, verification_status
- `primary`: id (unique)

Foreign keys:

- scholarship_application_id → scholarship_applications(id); delete cascade.
- scholarship_requirement_id → scholarship_requirements(id); delete set null.
- verified_by → users(id); delete set null.

## `application_evaluations`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `scholarship_application_id` | `bigint(20) unsigned` | No |
| `evaluator_id` | `bigint(20) unsigned` | No |
| `score` | `tinyint(3) unsigned` | Yes |
| `recommendation` | `varchar(40)` | No |
| `evaluation_notes` | `text` | Yes |
| `evaluated_at` | `timestamp` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `application_evaluations_evaluator_id_foreign`: evaluator_id
- `application_evaluations_scholarship_application_id_index`: scholarship_application_id
- `primary`: id (unique)

Foreign keys:

- evaluator_id → users(id); delete cascade.
- scholarship_application_id → scholarship_applications(id); delete cascade.

## `application_status_histories`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `scholarship_application_id` | `bigint(20) unsigned` | No |
| `from_status` | `varchar(40)` | Yes |
| `to_status` | `varchar(40)` | No |
| `changed_by` | `bigint(20) unsigned` | Yes |
| `remarks` | `text` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `application_status_histories_changed_by_foreign`: changed_by
- `application_status_histories_scholarship_application_id_index`: scholarship_application_id
- `primary`: id (unique)

Foreign keys:

- changed_by → users(id); delete set null.
- scholarship_application_id → scholarship_applications(id); delete cascade.

## `audit_logs`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `user_id` | `bigint(20) unsigned` | Yes |
| `action` | `varchar(100)` | No |
| `auditable_type` | `varchar(150)` | Yes |
| `auditable_id` | `bigint(20) unsigned` | Yes |
| `description` | `text` | Yes |
| `old_values` | `text` | Yes |
| `new_values` | `text` | Yes |
| `ip_address` | `varchar(45)` | Yes |
| `user_agent` | `text` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `audit_logs_auditable_type_auditable_id_index`: auditable_type, auditable_id
- `audit_logs_user_id_created_at_index`: user_id, created_at
- `primary`: id (unique)

Foreign keys:

- user_id → users(id); delete set null.

## `cache`

| Column | Database type | Nullable |
|---|---|---|
| `key` | `varchar(255)` | No |
| `value` | `mediumtext` | No |
| `expiration` | `int(11)` | No |

Indexes:

- `cache_expiration_index`: expiration
- `primary`: key (unique)

## `cache_locks`

| Column | Database type | Nullable |
|---|---|---|
| `key` | `varchar(255)` | No |
| `owner` | `varchar(255)` | No |
| `expiration` | `int(11)` | No |

Indexes:

- `cache_locks_expiration_index`: expiration
- `primary`: key (unique)

## `document_reviews`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `application_document_id` | `bigint(20) unsigned` | No |
| `reviewed_by` | `bigint(20) unsigned` | No |
| `status` | `varchar(30)` | No |
| `remarks` | `text` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `document_reviews_application_document_id_foreign`: application_document_id
- `document_reviews_reviewed_by_foreign`: reviewed_by
- `primary`: id (unique)

Foreign keys:

- application_document_id → application_documents(id); delete restrict.
- reviewed_by → users(id); delete restrict.

## `eligibility_results`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `scholarship_application_id` | `bigint(20) unsigned` | No |
| `checked_by` | `bigint(20) unsigned` | Yes |
| `result` | `varchar(30)` | No |
| `reasons` | `text` | Yes |
| `checked_at` | `timestamp` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `eligibility_results_checked_by_foreign`: checked_by
- `eligibility_results_scholarship_application_id_index`: scholarship_application_id
- `primary`: id (unique)

Foreign keys:

- checked_by → users(id); delete set null.
- scholarship_application_id → scholarship_applications(id); delete cascade.

## `eligibility_rules`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `scholarship_program_id` | `bigint(20) unsigned` | No |
| `rule_key` | `varchar(60)` | No |
| `rule_label` | `varchar(200)` | No |
| `rule_type` | `varchar(30)` | No |
| `operator` | `varchar(20)` | No |
| `rule_value` | `text` | No |
| `failure_mode` | `varchar(20)` | No |
| `is_active` | `tinyint(1)` | No |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `eligibility_rules_scholarship_program_id_is_active_index`: scholarship_program_id, is_active
- `primary`: id (unique)

Foreign keys:

- scholarship_program_id → scholarship_programs(id); delete cascade.

## `failed_jobs`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `uuid` | `varchar(255)` | No |
| `connection` | `text` | No |
| `queue` | `text` | No |
| `payload` | `longtext` | No |
| `exception` | `longtext` | No |
| `failed_at` | `timestamp` | No |

Indexes:

- `failed_jobs_uuid_unique`: uuid (unique)
- `primary`: id (unique)

## `jobs`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `queue` | `varchar(255)` | No |
| `payload` | `longtext` | No |
| `attempts` | `tinyint(3) unsigned` | No |
| `reserved_at` | `int(10) unsigned` | Yes |
| `available_at` | `int(10) unsigned` | No |
| `created_at` | `int(10) unsigned` | No |

Indexes:

- `jobs_queue_index`: queue
- `primary`: id (unique)

## `job_batches`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `varchar(255)` | No |
| `name` | `varchar(255)` | No |
| `total_jobs` | `int(11)` | No |
| `pending_jobs` | `int(11)` | No |
| `failed_jobs` | `int(11)` | No |
| `failed_job_ids` | `longtext` | No |
| `options` | `mediumtext` | Yes |
| `cancelled_at` | `int(11)` | Yes |
| `created_at` | `int(11)` | No |
| `finished_at` | `int(11)` | Yes |

Indexes:

- `primary`: id (unique)

## `migrations`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `int(10) unsigned` | No |
| `migration` | `varchar(255)` | No |
| `batch` | `int(11)` | No |

Indexes:

- `primary`: id (unique)

## `notifications`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `user_id` | `bigint(20) unsigned` | No |
| `title` | `varchar(200)` | No |
| `message` | `text` | No |
| `link` | `varchar(500)` | Yes |
| `is_read` | `tinyint(1)` | No |
| `read_at` | `timestamp` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `notifications_user_id_is_read_index`: user_id, is_read
- `primary`: id (unique)

Foreign keys:

- user_id → users(id); delete cascade.

## `password_reset_tokens`

| Column | Database type | Nullable |
|---|---|---|
| `email` | `varchar(255)` | No |
| `token` | `varchar(255)` | No |
| `created_at` | `timestamp` | Yes |

Indexes:

- `primary`: email (unique)

## `personal_access_tokens`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `tokenable_type` | `varchar(255)` | No |
| `tokenable_id` | `bigint(20) unsigned` | No |
| `name` | `text` | No |
| `token` | `varchar(64)` | No |
| `abilities` | `text` | Yes |
| `last_used_at` | `timestamp` | Yes |
| `expires_at` | `timestamp` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `personal_access_tokens_expires_at_index`: expires_at
- `personal_access_tokens_tokenable_type_tokenable_id_index`: tokenable_type, tokenable_id
- `personal_access_tokens_token_unique`: token (unique)
- `primary`: id (unique)

## `renewal_applications`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `renewal_no` | `varchar(30)` | No |
| `scholarship_award_id` | `bigint(20) unsigned` | No |
| `user_id` | `bigint(20) unsigned` | No |
| `academic_year` | `varchar(20)` | No |
| `gpa` | `decimal(4,2)` | Yes |
| `statement` | `text` | Yes |
| `form_data` | `longtext` | Yes |
| `status` | `varchar(30)` | No |
| `remarks` | `text` | Yes |
| `decided_by` | `bigint(20) unsigned` | Yes |
| `decided_at` | `timestamp` | Yes |
| `submitted_at` | `timestamp` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `primary`: id (unique)
- `renewals_award_cycle_unique`: scholarship_award_id, academic_year (unique)
- `renewal_applications_decided_by_foreign`: decided_by
- `renewal_applications_renewal_no_unique`: renewal_no (unique)
- `renewal_applications_scholarship_award_id_status_index`: scholarship_award_id, status
- `renewal_applications_user_id_foreign`: user_id

Foreign keys:

- decided_by → users(id); delete set null.
- scholarship_award_id → scholarship_awards(id); delete cascade.
- user_id → users(id); delete cascade.

## `scholarship_appeals`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `appeal_no` | `varchar(40)` | No |
| `scholarship_award_id` | `bigint(20) unsigned` | No |
| `user_id` | `bigint(20) unsigned` | No |
| `award_status_version` | `int(10) unsigned` | No |
| `award_status` | `varchar(30)` | No |
| `status_reason` | `text` | Yes |
| `statement` | `text` | No |
| `status` | `varchar(30)` | No |
| `pending_slot` | `tinyint(3) unsigned` | Yes |
| `decision_reason` | `text` | Yes |
| `decided_by` | `bigint(20) unsigned` | Yes |
| `decided_at` | `timestamp` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `appeals_one_pending_per_award`: scholarship_award_id, pending_slot (unique)
- `primary`: id (unique)
- `scholarship_appeals_appeal_no_unique`: appeal_no (unique)
- `scholarship_appeals_decided_by_foreign`: decided_by
- `scholarship_appeals_user_id_status_index`: user_id, status

Foreign keys:

- decided_by → users(id); delete set null.
- scholarship_award_id → scholarship_awards(id); delete cascade.
- user_id → users(id); delete cascade.

## `scholarship_applications`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `application_no` | `varchar(30)` | No |
| `user_id` | `bigint(20) unsigned` | No |
| `scholarship_program_id` | `bigint(20) unsigned` | No |
| `assigned_to` | `bigint(20) unsigned` | Yes |
| `academic_year` | `varchar(20)` | No |
| `status` | `varchar(40)` | No |
| `enrollment_status` | `varchar(30)` | Yes |
| `gpa` | `decimal(4,2)` | Yes |
| `statement` | `text` | Yes |
| `form_data` | `longtext` | Yes |
| `guardian_name` | `varchar(200)` | Yes |
| `guardian_occupation` | `varchar(150)` | Yes |
| `household_size` | `smallint(5) unsigned` | Yes |
| `monthly_household_income` | `decimal(12,2)` | Yes |
| `submitted_at` | `timestamp` | Yes |
| `decided_at` | `timestamp` | Yes |
| `decided_by` | `bigint(20) unsigned` | Yes |
| `decision_remarks` | `text` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |
| `last_saved_step` | `tinyint(3) unsigned` | No |
| `declaration_accepted_at` | `timestamp` | Yes |

Indexes:

- `primary`: id (unique)
- `scholarship_applications_application_no_unique`: application_no (unique)
- `scholarship_applications_decided_by_foreign`: decided_by
- `scholarship_applications_scholarship_program_id_foreign`: scholarship_program_id
- `scholarship_applications_status_scholarship_program_id_index`: status, scholarship_program_id
- `scholarship_applications_user_id_index`: user_id
- `sch_apps_assigned_to_idx`: assigned_to
- `uniq_app_user_schol_year`: user_id, scholarship_program_id, academic_year (unique)

Foreign keys:

- assigned_to → users(id); delete set null.
- decided_by → users(id); delete set null.
- scholarship_program_id → scholarship_programs(id); delete cascade.
- user_id → users(id); delete cascade.

## `scholarship_awards`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `award_no` | `varchar(30)` | No |
| `scholarship_application_id` | `bigint(20) unsigned` | No |
| `user_id` | `bigint(20) unsigned` | No |
| `scholarship_program_id` | `bigint(20) unsigned` | No |
| `benefits` | `text` | Yes |
| `start_date` | `date` | Yes |
| `end_date` | `date` | Yes |
| `recipient_status` | `varchar(30)` | No |
| `remarks` | `text` | Yes |
| `awarded_by` | `bigint(20) unsigned` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |
| `status_reason` | `text` | Yes |
| `status_changed_at` | `timestamp` | Yes |
| `status_version` | `int(10) unsigned` | No |

Indexes:

- `primary`: id (unique)
- `scholarship_awards_awarded_by_foreign`: awarded_by
- `scholarship_awards_award_no_unique`: award_no (unique)
- `scholarship_awards_scholarship_application_id_unique`: scholarship_application_id (unique)
- `scholarship_awards_scholarship_program_id_foreign`: scholarship_program_id
- `scholarship_awards_user_id_recipient_status_index`: user_id, recipient_status

Foreign keys:

- awarded_by → users(id); delete set null.
- scholarship_application_id → scholarship_applications(id); delete cascade.
- scholarship_program_id → scholarship_programs(id); delete cascade.
- user_id → users(id); delete cascade.

## `scholarship_programs`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `code` | `varchar(30)` | No |
| `name` | `varchar(200)` | No |
| `description` | `text` | Yes |
| `category` | `varchar(100)` | No |
| `benefits` | `text` | Yes |
| `eligibility_criteria` | `text` | Yes |
| `application_template` | `longtext` | Yes |
| `renewal_template` | `longtext` | Yes |
| `opening_date` | `date` | Yes |
| `deadline` | `date` | Yes |
| `slots` | `int(10) unsigned` | Yes |
| `status` | `varchar(20)` | No |
| `created_by` | `bigint(20) unsigned` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `primary`: id (unique)
- `scholarship_programs_category_index`: category
- `scholarship_programs_code_unique`: code (unique)
- `scholarship_programs_created_by_foreign`: created_by
- `scholarship_programs_status_deadline_index`: status, deadline

Foreign keys:

- created_by → users(id); delete set null.

## `scholarship_requirements`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `scholarship_program_id` | `bigint(20) unsigned` | No |
| `name` | `varchar(200)` | No |
| `description` | `text` | Yes |
| `is_required` | `tinyint(1)` | No |
| `allowed_types` | `varchar(100)` | No |
| `max_size_kb` | `int(10) unsigned` | No |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `primary`: id (unique)
- `scholarship_requirements_scholarship_program_id_index`: scholarship_program_id

Foreign keys:

- scholarship_program_id → scholarship_programs(id); delete cascade.

## `sessions`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `varchar(255)` | No |
| `user_id` | `bigint(20) unsigned` | Yes |
| `ip_address` | `varchar(45)` | Yes |
| `user_agent` | `text` | Yes |
| `payload` | `longtext` | No |
| `last_activity` | `int(11)` | No |

Indexes:

- `primary`: id (unique)
- `sessions_last_activity_index`: last_activity
- `sessions_user_id_index`: user_id

## `settings`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `key` | `varchar(100)` | No |
| `value` | `text` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `primary`: id (unique)
- `settings_key_unique`: key (unique)

## `student_profiles`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `user_id` | `bigint(20) unsigned` | No |
| `student_id_no` | `varchar(50)` | Yes |
| `first_name` | `varchar(100)` | No |
| `middle_name` | `varchar(100)` | Yes |
| `last_name` | `varchar(100)` | No |
| `suffix` | `varchar(30)` | Yes |
| `date_of_birth` | `date` | Yes |
| `contact_number` | `varchar(30)` | Yes |
| `address` | `varchar(255)` | Yes |
| `emergency_contact_name` | `varchar(150)` | Yes |
| `emergency_contact_number` | `varchar(30)` | Yes |
| `course` | `varchar(150)` | Yes |
| `department` | `varchar(150)` | Yes |
| `year_level` | `varchar(30)` | Yes |
| `enrollment_status` | `varchar(30)` | No |
| `academic_year` | `varchar(20)` | Yes |
| `semester` | `varchar(30)` | Yes |
| `parent_guardian_name` | `varchar(200)` | Yes |
| `parent_guardian_occupation` | `varchar(150)` | Yes |
| `household_size` | `smallint(5) unsigned` | Yes |
| `monthly_household_income` | `decimal(12,2)` | Yes |
| `gpa` | `decimal(4,2)` | Yes |
| `profile_completed` | `tinyint(1)` | No |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `primary`: id (unique)
- `student_profiles_course_year_level_index`: course, year_level
- `student_profiles_student_id_no_unique`: student_id_no (unique)
- `student_profiles_user_id_unique`: user_id (unique)

Foreign keys:

- user_id → users(id); delete cascade.

## `users`

| Column | Database type | Nullable |
|---|---|---|
| `id` | `bigint(20) unsigned` | No |
| `name` | `varchar(255)` | No |
| `email` | `varchar(255)` | No |
| `email_verified_at` | `timestamp` | Yes |
| `password` | `varchar(255)` | No |
| `role` | `varchar(20)` | No |
| `status` | `varchar(20)` | No |
| `avatar_path` | `varchar(255)` | Yes |
| `remember_token` | `varchar(100)` | Yes |
| `created_at` | `timestamp` | Yes |
| `updated_at` | `timestamp` | Yes |

Indexes:

- `primary`: id (unique)
- `users_email_unique`: email (unique)
- `users_role_status_index`: role, status

