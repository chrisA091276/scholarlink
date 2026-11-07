<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id_no' => $this->student_id_no,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_number' => $this->emergency_contact_number,
            'course' => $this->course,
            'department' => $this->department,
            'year_level' => $this->year_level,
            'enrollment_status' => $this->enrollment_status,
            'academic_year' => $this->academic_year,
            'semester' => $this->semester,
            'parent_guardian_name' => $this->parent_guardian_name,
            'parent_guardian_occupation' => $this->parent_guardian_occupation,
            'household_size' => $this->household_size,
            'monthly_household_income' => $this->monthly_household_income,
            'profile_completed' => (bool) $this->profile_completed,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
