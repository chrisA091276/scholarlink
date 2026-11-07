<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PortalLoginRequest extends LoginRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['login_method'] = ['nullable', Rule::in(['email'])];
        $rules['student_id_no'] = ['exclude'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'login_method.in' => 'Start with your email address and password.',
        ];
    }
}
