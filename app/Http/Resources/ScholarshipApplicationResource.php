<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_no' => $this->application_no,
            'status' => $this->status,
            'last_saved_step' => $this->last_saved_step,
            'declaration_accepted_at' => $this->declaration_accepted_at?->toISOString(),
            'academic_year' => $this->academic_year,
            'enrollment_status' => $this->enrollment_status,
            'statement' => $this->statement,
            'form_data' => $this->form_data,
            'form_version' => $this->form_version,
            'adfc_form' => $this->adfc_form,
            'form_review' => $this->when($request->user()?->isAdmin() || $request->user()?->isStaff(), $this->form_review),
            'guardian_name' => $this->guardian_name,
            'guardian_occupation' => $this->guardian_occupation,
            'household_size' => $this->household_size,
            'monthly_household_income' => $this->monthly_household_income,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'decided_at' => $this->decided_at?->toISOString(),
            'decision_remarks' => $this->decision_remarks,
            'assigned_to' => $this->assigned_to,
            'program' => new ScholarshipProgramResource($this->whenLoaded('program')),
            'student' => new UserResource($this->whenLoaded('student')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'documents' => ApplicationDocumentResource::collection($this->whenLoaded('documents')),
            'eligibility_results' => EligibilityResultResource::collection($this->whenLoaded('eligibilityResults')),
            'latest_eligibility_result' => new EligibilityResultResource($this->whenLoaded('latestEligibilityResult')),
            'evaluations' => ApplicationEvaluationResource::collection($this->whenLoaded('evaluations')),
            'award' => new ScholarshipAwardResource($this->whenLoaded('award')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
