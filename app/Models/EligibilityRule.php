<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibilityRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_program_id', 'rule_key', 'rule_label',
        'rule_type', 'operator', 'rule_value', 'failure_mode', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function program() { return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id'); }
}
