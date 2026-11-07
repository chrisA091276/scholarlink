<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibilityResult extends Model
{
    use HasFactory;

    protected $fillable = ['scholarship_application_id', 'checked_by', 'result', 'reasons', 'checked_at'];
    protected $casts = ['checked_at' => 'datetime'];

    public function application() { return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id'); }
    public function checker() { return $this->belongsTo(User::class, 'checked_by'); }
}
