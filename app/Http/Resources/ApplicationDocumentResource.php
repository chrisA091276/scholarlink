<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->scholarship_application_id,
            'document_name' => $this->document_name,
            'document_category' => $this->document_category,
            'original_filename' => $this->original_filename,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'verification_status' => $this->verification_status,
            'verification_notes' => $this->verification_notes,
            'verified_at' => $this->verified_at?->toISOString(),
            'requirement' => new ScholarshipRequirementResource($this->whenLoaded('requirement')),
            'verifier' => new UserResource($this->whenLoaded('verifier')),
            'download_url' => route('api.documents.download', [
                'application' => $this->scholarship_application_id,
                'document' => $this->id,
            ]),
            'uploaded_at' => $this->created_at?->toISOString(),
        ];
    }
}
