<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'scholarship_program_id' => ['required', 'exists:scholarship_programs,id'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'enrollment_status' => ['nullable', 'in:enrolled,continuing,new,freshman,transferee,returning'],
            'statement' => ['nullable', 'string', 'max:5000'],
            'guardian_name' => ['nullable', 'string', 'max:200'],
            'guardian_occupation' => ['nullable', 'string', 'max:150'],
            'household_size' => ['nullable', 'integer', 'min:1', 'max:30'],
            'monthly_household_income' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'form_data' => ['nullable', 'array'],
            'adfc_form' => ['sometimes', 'array'],
            'last_saved_step' => ['nullable', 'integer', 'between:1,7'],
            'declaration' => ['nullable', 'boolean'],
            'action' => ['required', 'in:draft,submit'],
        ];
    }
}
