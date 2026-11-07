<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'scholarship_requirement_id' => ['nullable', 'exists:scholarship_requirements,id'],
            'document_name' => ['required', 'string', 'max:200'],
            'document_category' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(config('adfc_form.documents')))],
            'replace_document_id' => ['nullable', 'integer'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('scholarlink.max_upload_kb', 5120)],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Only PDF, JPG, JPEG, and PNG files are allowed.',
            'file.max' => 'File must not exceed '.config('scholarlink.max_upload_kb').' KB.',
        ];
    }
}
