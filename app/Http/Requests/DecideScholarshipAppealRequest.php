<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideScholarshipAppealRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isAdmin() && $this->user()->isActive(); }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:under_review,approved,rejected'],
            'decision_reason' => ['required_if:status,approved,rejected', 'nullable', 'string', 'max:2000'],
        ];
    }
}
