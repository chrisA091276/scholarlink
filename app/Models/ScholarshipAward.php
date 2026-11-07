<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipAward extends Model
{
    use HasFactory;

    public const STATUSES = ['active', 'completed', 'suspended', 'removed', 'expired'];

    protected $fillable = [
        'award_no', 'scholarship_application_id', 'user_id', 'scholarship_program_id',
        'benefits', 'start_date', 'end_date', 'recipient_status', 'remarks', 'awarded_by',
        'status_reason', 'status_changed_at', 'status_version',
    ];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'status_changed_at' => 'datetime', 'status_version' => 'integer'];

    public function canAppeal(): bool { return in_array($this->recipient_status, ['removed', 'suspended'], true); }
    public function statusDescription(): string
    {
        return match ($this->recipient_status) {
            'active' => 'You are currently included in this scholarship.',
            'removed' => 'You are no longer included in this scholarship. You may appeal for reinstatement.',
            'suspended' => 'Your scholarship participation is suspended. You may appeal for reinstatement.',
            'completed' => 'You have completed this scholarship.',
            'expired' => 'This scholarship award has expired.',
            default => 'Contact the scholarship office for details about your status.',
        };
    }

    public function application() { return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id'); }
    public function student() { return $this->belongsTo(User::class, 'user_id'); }
    public function program() { return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id'); }
    public function awarder() { return $this->belongsTo(User::class, 'awarded_by'); }
    public function renewals() { return $this->hasMany(RenewalApplication::class); }
    public function appeals() { return $this->hasMany(ScholarshipAppeal::class)->latest('id'); }
}
