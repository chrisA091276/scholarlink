<?php

namespace App\Services;

use App\Models\ScholarshipProgram;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApplicationTemplateService
{
    /**
     * Validate and keep only fields configured by the selected scholarship.
     * This prevents clients from injecting arbitrary JSON into an application.
     */
    public function validate(ScholarshipProgram $program, array $input, bool $requireRequired = true, string $templateKey = 'application_template'): array
    {
        $template = $this->fields($program, $templateKey);
        $allowedKeys = collect($template)->pluck('key')->all();
        $data = Arr::only($input, $allowedKeys);
        $rules = [];

        foreach ($template as $field) {
            $key = $field['key'];
            $type = $field['type'];
            $fieldRules = [$requireRequired && $field['required'] ? 'required' : 'nullable'];

            if ($type === 'multi_select') {
                $rules["form_data.{$key}"] = array_merge($fieldRules, ['array', 'max:20']);
                $rules["form_data.{$key}.*"] = ['string', Rule::in($field['options'])];
                continue;
            }

            $fieldRules[] = match ($type) {
                'number' => ['numeric', 'min:0', 'max:999999999.99'],
                'date' => ['date'],
                'checkbox' => ['boolean'],
                'email' => ['email', 'max:255'],
                'url' => ['url', 'max:2048'],
                'select' => ['string', Rule::in($field['options'])],
                default => ['string', 'max:5000'],
            };

            $rules["form_data.{$key}"] = collect($fieldRules)->flatten()->all();
        }

        $validated = Validator::make(['form_data' => $data], $rules, [
            'form_data.*.required' => 'This scholarship-specific field is required.',
        ])->validate();

        return $validated['form_data'] ?? [];
    }

    /** @return array<int, string> */
    public function missingRequiredFields(ScholarshipProgram $program, array $input, string $templateKey = 'application_template'): array
    {
        return collect($this->fields($program, $templateKey))
            ->filter(fn (array $field) => $field['required'] && blank($input[$field['key']] ?? null))
            ->pluck('label')
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function fields(ScholarshipProgram $program, string $templateKey = 'application_template'): array
    {
        return collect($program->{$templateKey} ?? [])
            ->filter(fn ($field) => is_array($field) && preg_match('/^[a-z][a-z0-9_]{1,60}$/', (string) ($field['key'] ?? '')))
            ->reject(fn ($field) => \App\Support\RetiredAcademicFields::matches($field['key']) || \App\Support\RetiredAcademicFields::matches($field['label'] ?? ''))
            ->map(function (array $field): array {
                $type = in_array($field['type'] ?? 'text', ['text', 'textarea', 'number', 'date', 'select', 'multi_select', 'checkbox', 'email', 'url'], true)
                    ? $field['type']
                    : 'text';

                return [
                    'key' => (string) $field['key'],
                    'label' => (string) ($field['label'] ?? str_replace('_', ' ', ucfirst($field['key']))),
                    'type' => $type,
                    'required' => (bool) ($field['required'] ?? false),
                    'help' => isset($field['help']) ? (string) $field['help'] : null,
                    'options' => array_values(array_filter((array) ($field['options'] ?? []), 'is_scalar')),
                ];
            })
            ->values()
            ->all();
    }
}
