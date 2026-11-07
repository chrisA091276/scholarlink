<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipProgram extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'published', 'closed', 'archived'];

    protected $fillable = [
        'code', 'name', 'description', 'category', 'benefits',
        'eligibility_criteria', 'opening_date', 'deadline', 'slots',
        'application_template', 'renewal_template', 'status', 'created_by',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'deadline' => 'date',
        'application_template' => 'array',
        'renewal_template' => 'array',
    ];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function requirements() { return $this->hasMany(ScholarshipRequirement::class); }
    public function eligibilityRules() { return $this->hasMany(EligibilityRule::class); }
    public function applications() { return $this->hasMany(ScholarshipApplication::class); }
    public function awards() { return $this->hasMany(ScholarshipAward::class); }

    public function financialFields(): array
    {
        $possible = ['guardian_name', 'guardian_occupation', 'household_size', 'monthly_household_income'];
        $keys = $this->eligibilityRules()->where('is_active', true)->pluck('rule_key')->map(fn ($key) => str_replace('application.', '', $key))->all();
        return array_values(array_intersect($possible, $keys));
    }

    public function isOpenForApplication(): bool
    {
        if ($this->status !== 'published') return false;
        $today = now()->toDateString();
        if ($this->opening_date && $today < $this->opening_date->toDateString()) return false;
        if ($this->deadline && $today > $this->deadline->toDateString()) return false;
        return true;
    }
}
