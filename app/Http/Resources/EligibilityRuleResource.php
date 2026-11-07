<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EligibilityRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rule_key' => $this->rule_key,
            'rule_label' => $this->rule_label,
            'operator' => $this->operator,
            'rule_value' => $this->rule_value,
            'rule_type' => $this->rule_type,
            'failure_mode' => $this->failure_mode,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
