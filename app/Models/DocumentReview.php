<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentReview extends Model
{
    protected $fillable = ['application_document_id', 'reviewed_by', 'status', 'remarks'];
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Document reviews are append-only.'));
        static::deleting(fn () => throw new \LogicException('Document reviews are append-only.'));
    }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
