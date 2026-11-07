<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $hidden = ['gpa'];

    protected $fillable = [
        'user_id', 'student_id_no', 'first_name', 'middle_name', 'last_name', 'suffix',
        'date_of_birth', 'contact_number', 'address', 'emergency_contact_name', 'emergency_contact_number',
        'course', 'department', 'year_level', 'enrollment_status', 'academic_year', 'semester',
        'parent_guardian_name', 'parent_guardian_occupation', 'household_size', 'monthly_household_income',
        'profile_completed',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'monthly_household_income' => 'decimal:2',
        'profile_completed' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} " . ($this->middle_name ? $this->middle_name . ' ' : '') . "{$this->last_name}");
    }
}
