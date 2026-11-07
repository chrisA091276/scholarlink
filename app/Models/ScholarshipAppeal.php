<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarshipAppeal extends Model
{
    public const PENDING_STATUSES = ['submitted', 'under_review'];
    public const STATUSES = ['submitted', 'under_review', 'approved', 'rejected', 'closed'];

    protected $fillable = [
        'appeal_no', 'scholarship_award_id', 'user_id', 'award_status_version',
        'award_status', 'status_reason', 'statement', 'status', 'pending_slot',
        'decision_reason', 'decided_by', 'decided_at',
    ];

    protected $casts = ['award_status_version' => 'integer', 'decided_at' => 'datetime'];

    public function award() { return $this->belongsTo(ScholarshipAward::class, 'scholarship_award_id'); }
    public function student() { return $this->belongsTo(User::class, 'user_id'); }
    public function decider() { return $this->belongsTo(User::class, 'decided_by'); }
    public function isPending(): bool { return in_array($this->status, self::PENDING_STATUSES, true); }
}
