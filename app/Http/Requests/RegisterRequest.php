<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use \App\Concerns\NormalizesContactDetails;
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $schoolIdRules = ! $this->is('api/*')
            ? ['required', 'string', 'regex:/\A[0-9]{2}-[0-9]{6}\z/', 'unique:student_profiles,student_id_no']
            : ['nullable', 'string', 'max:50', 'unique:student_profiles,student_id_no'];

        return [
            // Public registration is intentionally student-only. The role
            // field is never accepted from the browser or API client.
            'name' => ['nullable', 'string', 'max:255', 'required_without_all:first_name,last_name'],
            'first_name' => ['nullable', 'string', 'max:100', 'required_without:name'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100', 'required_without:name'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'student_id_no' => $schoolIdRules,
            'contact_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => \App\Support\PasswordRules::rules(),
            'device_name' => ['nullable', 'string', 'max:100'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Password must be at least 8 characters.',
            'contact_number.regex' => 'Use a valid Philippine mobile number, such as 09171234567 or +639171234567.',
            'terms_accepted.accepted' => 'You must accept the privacy notice and terms to create an account.',
            'student_id_no.required' => 'Enter your School ID. You will need it to sign in.',
            'student_id_no.regex' => 'Use the School ID format 00-000000.',
            'student_id_no.unique' => 'This School ID is already registered. Log in or contact the scholarship office for help.',
        ];
    }
}
