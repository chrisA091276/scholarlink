<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScholarshipProgramRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        foreach (['application_template', 'renewal_template'] as $templateKey) {
            if (! is_string($this->{$templateKey})) continue;
            $json = trim($this->{$templateKey});
            if ($json === '') { $this->merge([$templateKey => null]); continue; }
            try {
                $this->merge([$templateKey => json_decode($json, true, 512, JSON_THROW_ON_ERROR)]);
            } catch (\JsonException) {
                $this->merge([$templateKey => '__invalid_template_json__']);
            }
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', \Illuminate\Validation\Rule::unique('scholarship_programs', 'code')->ignore($this->route('scholarship')?->id)],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'benefits' => ['nullable', 'string'],
            'eligibility_criteria' => ['nullable', 'string'],
            'opening_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:opening_date'],
            'slots' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'application_template' => ['nullable', 'array', 'max:20'],
            'application_template.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,60}$/', 'distinct', new \App\Rules\SupportedScholarshipField],
            'application_template.*.label' => ['required', 'string', 'max:150', new \App\Rules\SupportedScholarshipField],
            'application_template.*.type' => ['required', 'in:text,textarea,number,date,select,multi_select,checkbox,email,url'],
            'application_template.*.required' => ['sometimes', 'boolean'],
            'application_template.*.help' => ['nullable', 'string', 'max:500'],
            'application_template.*.options' => ['nullable', 'array', 'max:20'],
            'application_template.*.options.*' => ['string', 'max:100'],
            'renewal_template' => ['nullable', 'array', 'max:20'],
            'renewal_template.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,60}$/', 'distinct', new \App\Rules\SupportedScholarshipField],
            'renewal_template.*.label' => ['required', 'string', 'max:150', new \App\Rules\SupportedScholarshipField],
            'renewal_template.*.type' => ['required', 'in:text,textarea,number,date,select,multi_select,checkbox,email,url'],
            'renewal_template.*.required' => ['sometimes', 'boolean'],
            'renewal_template.*.help' => ['nullable', 'string', 'max:500'],
            'renewal_template.*.options' => ['nullable', 'array', 'max:20'],
            'renewal_template.*.options.*' => ['string', 'max:100'],
            'status' => ['required', 'in:draft,published,closed,archived'],
        ];
    }
}
