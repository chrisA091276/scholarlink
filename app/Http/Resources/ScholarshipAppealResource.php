<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipAppealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'appeal_no' => $this->appeal_no,
            'scholarship_award_id' => $this->scholarship_award_id,
            'award_status' => $this->award_status, 'status_reason' => $this->status_reason,
            'statement' => $this->statement, 'status' => $this->status,
            'decision_reason' => $this->decision_reason,
            'submitted_at' => $this->created_at?->toISOString(),
            'decided_at' => $this->decided_at?->toISOString(),
            'award' => new ScholarshipAwardResource($this->whenLoaded('award')),
            'student' => new UserResource($this->whenLoaded('student')),
        ];
    }
}
