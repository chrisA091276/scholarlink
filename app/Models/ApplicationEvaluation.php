<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_application_id', 'evaluator_id', 'score',
        'recommendation', 'evaluation_notes', 'evaluated_at',
    ];

    protected $casts = ['evaluated_at' => 'datetime'];

    public function application() { return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id'); }
    public function evaluator() { return $this->belongsTo(User::class, 'evaluator_id'); }
}
