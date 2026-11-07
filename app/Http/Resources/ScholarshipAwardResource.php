<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipAwardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'award_no' => $this->award_no,
            'benefits' => $this->benefits,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'recipient_status' => $this->recipient_status,
            'status_description' => $this->statusDescription(),
            'status_reason' => $this->status_reason ?: ($this->canAppeal() ? $this->remarks : null),
            'status_changed_at' => $this->status_changed_at?->toISOString(),
            'can_appeal' => $this->canAppeal() && ! ($this->relationLoaded('appeals')
                ? $this->appeals->contains(fn ($appeal) => $appeal->isPending())
                : $this->appeals()->whereIn('status', \App\Models\ScholarshipAppeal::PENDING_STATUSES)->exists()),
            'remarks' => $this->remarks,
            'student' => new UserResource($this->whenLoaded('student')),
            'program' => new ScholarshipProgramResource($this->whenLoaded('program')),
            'application' => new ScholarshipApplicationResource($this->whenLoaded('application')),
            'renewals' => RenewalApplicationResource::collection($this->whenLoaded('renewals')),
            'appeals' => ScholarshipAppealResource::collection($this->whenLoaded('appeals')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
