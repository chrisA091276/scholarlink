<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RenewalApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'renewal_no' => $this->renewal_no,
            'academic_year' => $this->academic_year,
            'statement' => $this->statement,
            'form_data' => $this->form_data,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'decided_at' => $this->decided_at?->toISOString(),
            'award' => new ScholarshipAwardResource($this->whenLoaded('award')),
            'student' => new UserResource($this->whenLoaded('student')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
