<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipRequirementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_required' => (bool) $this->is_required,
            'allowed_types' => $this->allowed_types,
            'max_size_kb' => $this->max_size_kb,
        ];
    }
}
