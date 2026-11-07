<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalApplication extends Model
{
    use HasFactory;

    protected $hidden = ['gpa'];

    public function getFormDataAttribute(?string $value): ?array
    {
        // Retain historical storage while excluding retired fields from every output.
        return $value === null ? null : \App\Support\RetiredAcademicFields::withoutAnswers($this->fromJson($value) ?? []);
    }

    protected $fillable = [
        'renewal_no', 'scholarship_award_id', 'user_id', 'academic_year',
        'statement', 'form_data', 'status', 'remarks', 'decided_by', 'decided_at', 'submitted_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'decided_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function award() { return $this->belongsTo(ScholarshipAward::class, 'scholarship_award_id'); }
    public function student() { return $this->belongsTo(User::class, 'user_id'); }
    public function decider() { return $this->belongsTo(User::class, 'decided_by'); }
}
