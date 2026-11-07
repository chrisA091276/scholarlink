<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationStatusHistory extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Status history is append-only.'));
        static::deleting(fn () => throw new \LogicException('Status history is append-only.'));
    }

    protected $fillable = [
        'scholarship_application_id', 'from_status', 'to_status', 'changed_by', 'remarks',
    ];

    public function application() { return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id'); }
    public function changer() { return $this->belongsTo(User::class, 'changed_by'); }
}
