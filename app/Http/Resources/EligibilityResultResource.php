<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EligibilityResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'result' => $this->result,
            'reasons' => $this->reasons
                ? preg_split('/\r\n|\r|\n/', $this->reasons)
                : [],
            'checked_at' => $this->checked_at?->toISOString(),
            'checked_by' => new UserResource($this->whenLoaded('checker')),
        ];
    }
}
