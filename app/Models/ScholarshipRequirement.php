<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_program_id', 'name', 'description',
        'is_required', 'allowed_types', 'max_size_kb',
    ];

    protected $casts = ['is_required' => 'boolean'];

    public function program() { return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id'); }
    public function documents() { return $this->hasMany(ApplicationDocument::class, 'scholarship_requirement_id'); }
}
