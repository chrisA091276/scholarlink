<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipApplication extends Model
{
    use HasFactory;

    public const STATUSES = [
        'draft', 'submitted', 'under_review', 'incomplete_requirements',
        'for_evaluation', 'recommended_approval', 'recommended_rejection',
        'approved', 'rejected', 'withdrawn',
    ];

    public const REVIEW_STATUSES = ['submitted', 'under_review', 'incomplete_requirements', 'for_evaluation'];

    public const DECISION_STATUSES = ['recommended_approval', 'recommended_rejection'];

    public const ACTIVE_STATUSES = [...self::REVIEW_STATUSES, ...self::DECISION_STATUSES];

    // Allowed forward transitions (server-enforced)
    public const TRANSITIONS = [
        'draft' => ['submitted', 'withdrawn'],
        'submitted' => ['under_review', 'incomplete_requirements', 'withdrawn'],
        'under_review' => ['incomplete_requirements', 'for_evaluation', 'withdrawn'],
        'incomplete_requirements' => ['submitted', 'withdrawn'],
        'for_evaluation' => ['recommended_approval', 'recommended_rejection', 'incomplete_requirements'],
        'recommended_approval' => ['approved', 'rejected'],
        'recommended_rejection' => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
        'withdrawn' => ['draft'],
    ];

    protected $hidden = ['gpa'];

    public function getFormDataAttribute(?string $value): ?array
    {
        // Retain historical storage while excluding retired fields from every output.
        return $value === null ? null : \App\Support\RetiredAcademicFields::withoutAnswers($this->fromJson($value) ?? []);
    }

    protected $fillable = [
        'application_no', 'user_id', 'scholarship_program_id', 'assigned_to', 'academic_year',
        'status', 'enrollment_status', 'statement',
        'guardian_name', 'guardian_occupation', 'household_size', 'monthly_household_income',
        'form_data', 'form_version', 'adfc_form', 'form_review', 'last_saved_step', 'declaration_accepted_at',
        'submitted_at', 'decided_at', 'decided_by', 'decision_remarks',
    ];

    protected $casts = [
        'monthly_household_income' => 'decimal:2',
        'form_data' => 'array',
        'adfc_form' => 'array',
        'form_review' => 'array',
        'submitted_at' => 'datetime',
        'declaration_accepted_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(User::class, 'user_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function program() { return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id'); }
    public function decider() { return $this->belongsTo(User::class, 'decided_by'); }
    public function documents() { return $this->hasMany(ApplicationDocument::class); }
    public function eligibilityResults() { return $this->hasMany(EligibilityResult::class); }
    public function latestEligibilityResult() { return $this->hasOne(EligibilityResult::class)->latestOfMany(); }
    public function evaluations() { return $this->hasMany(ApplicationEvaluation::class); }
    public function statusHistories() { return $this->hasMany(ApplicationStatusHistory::class)->orderBy('created_at'); }
    public function award() { return $this->hasOne(ScholarshipAward::class); }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function applicantName(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->adfc_form['first_name'] ?? null,
            $this->adfc_form['last_name'] ?? null,
        ])));

        return $name !== '' ? $name : ($this->student?->name ?? 'Applicant');
    }

    public static function generateApplicationNo(): string
    {
        return 'APP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(8)));
    }
}
