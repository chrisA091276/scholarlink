<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    public const STATUSES = ['pending', 'verified', 'rejected', 'requires_correction'];

    protected $hidden = ['file_path', 'storage_disk'];

    protected $fillable = [
        'scholarship_application_id', 'scholarship_requirement_id',
        'storage_disk', 'document_name', 'document_category', 'original_filename', 'file_path', 'file_type', 'file_size',
        'verification_status', 'verification_notes', 'verified_by', 'verified_at',
    ];

    protected $casts = ['verified_at' => 'datetime'];

    public function reviews() { return $this->hasMany(DocumentReview::class); }

    public function application() { return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id'); }
    public function requirement() { return $this->belongsTo(ScholarshipRequirement::class, 'scholarship_requirement_id'); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
}
