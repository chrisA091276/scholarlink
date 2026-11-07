<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use \App\Concerns\NormalizesContactDetails;
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'login_role' => ['nullable', 'in:admin,staff,student'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
