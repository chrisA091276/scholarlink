<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScholarshipRequirementRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allowed_types' => strtolower(preg_replace('/\s+/', '', $this->input('allowed_types') ?: 'pdf,jpg,jpeg,png')),
            'max_size_kb' => $this->input('max_size_kb') ?: config('scholarlink.max_upload_kb'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_required' => ['sometimes', 'boolean'],
            'allowed_types' => ['required', 'regex:/^(pdf|jpg|jpeg|png)(,(pdf|jpg|jpeg|png))*$/'],
            'max_size_kb' => ['required', 'integer', 'min:1', 'max:'.config('scholarlink.max_upload_kb')],
        ];
    }
}
