<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentProfileRequest extends FormRequest
{
    use \App\Concerns\NormalizesContactDetails;
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'student_id_no' => ['required', 'string', 'max:50', Rule::unique('student_profiles', 'student_id_no')->ignore($this->user()?->studentProfile?->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'contact_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'address' => ['required', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_number' => ['nullable', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'course' => ['required', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:150'],
            'year_level' => ['required', 'string', 'max:30'],
            'enrollment_status' => ['required', 'in:enrolled,continuing,new,freshman,transferee,returning'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->user()->applications()->whereNotIn('status', ['draft', 'incomplete_requirements', 'withdrawn', 'rejected'])->exists()) {
                $validator->errors()->add('profile', 'Your profile is locked while a submitted application or award relies on it. Ask the scholarship office to authorize a correction.');
            }
        }];
    }
}
