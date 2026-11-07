<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScholarshipAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStudent() && $this->user()->isActive()
            && (int) $this->route('award')->user_id === (int) $this->user()->id;
    }

    public function rules(): array { return ['statement' => ['required', 'string', 'min:20', 'max:5000']]; }
}
