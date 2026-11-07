<?php

namespace App\Rules;

use App\Support\RetiredAcademicFields;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportedScholarshipField implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && RetiredAcademicFields::matches($value)) {
            $fail('This field is no longer supported. Choose another scholarship criterion.');
        }
    }
}
