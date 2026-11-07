<?php

namespace App\Concerns;

trait NormalizesContactDetails
{
    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['contact_number', 'emergency_contact_number'] as $field) {
            if (is_string($this->input($field)) && $this->filled($field)) {
                $number = preg_replace('/[\s()\-]+/', '', (string) $this->input($field));
                if (preg_match('/^09\d{9}$/', $number)) $number = '+63'.substr($number, 1);
                if (preg_match('/^639\d{9}$/', $number)) $number = '+'.$number;
                $values[$field] = $number;
            }
        }
        if (is_string($this->input('email')) && $this->filled('email')) $values['email'] = strtolower(trim($this->input('email')));
        $this->merge($values);
    }
}
