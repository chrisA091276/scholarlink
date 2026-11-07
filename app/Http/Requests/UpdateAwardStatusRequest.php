<?php

namespace App\Http\Requests;

use App\Models\ScholarshipAward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAwardStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isAdmin() && $this->user()->isActive(); }

    public function rules(): array
    {
        return [
            'recipient_status' => ['required', Rule::in(ScholarshipAward::STATUSES)],
            'status_reason' => ['required_if:recipient_status,removed,suspended', 'nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
