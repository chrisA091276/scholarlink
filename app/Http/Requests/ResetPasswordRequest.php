<?php

namespace App\Http\Requests;

use App\Concerns\NormalizesContactDetails;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use NormalizesContactDetails;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => PasswordRules::rules(),
        ];
    }
}
