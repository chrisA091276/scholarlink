<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'benefits' => $this->benefits,
            'eligibility_criteria' => $this->eligibility_criteria,
            'application_template' => $this->application_template,
            'renewal_template' => $this->renewal_template,
            'opening_date' => $this->opening_date?->toDateString(),
            'deadline' => $this->deadline?->toDateString(),
            'slots' => $this->slots,
            'status' => $this->status,
            'is_open_for_application' => $this->isOpenForApplication(),
            'requirements' => ScholarshipRequirementResource::collection($this->whenLoaded('requirements')),
            'eligibility_rules' => EligibilityRuleResource::collection($this->whenLoaded('eligibilityRules')),
            'applications_count' => $this->whenCounted('applications'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
