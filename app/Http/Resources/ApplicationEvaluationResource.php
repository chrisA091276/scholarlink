<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->scholarship_application_id,
            'score' => $this->score,
            'recommendation' => $this->recommendation,
            'evaluation_notes' => $this->evaluation_notes,
            'evaluated_at' => $this->evaluated_at?->toISOString(),
            'evaluator' => new UserResource($this->whenLoaded('evaluator')),
        ];
    }
}
