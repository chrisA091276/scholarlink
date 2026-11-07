<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ScholarshipAppeal;
use App\Models\ScholarshipAward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScholarshipAppealService
{
    public function submit(ScholarshipAward $award, User $student, array $data): ScholarshipAppeal
    {
        return DB::transaction(function () use ($award, $student, $data) {
            $award = ScholarshipAward::lockForUpdate()->findOrFail($award->id);
            abort_unless($student->isStudent() && $student->isActive() && (int) $award->user_id === (int) $student->id, 403);
            if (! $award->canAppeal()) throw ValidationException::withMessages(['statement' => 'Only removed or suspended awards can be appealed.']);
            if ($award->appeals()->whereIn('status', ScholarshipAppeal::PENDING_STATUSES)->exists()) {
                throw ValidationException::withMessages(['statement' => 'An appeal is already awaiting a decision for this scholarship.']);
            }
            $appeal = $award->appeals()->create([
                'appeal_no' => 'APL-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(8))),
                'user_id' => $student->id, 'award_status_version' => $award->status_version,
                'award_status' => $award->recipient_status, 'status_reason' => $award->status_reason ?: $award->remarks,
                'statement' => $data['statement'], 'status' => 'submitted', 'pending_slot' => 1,
            ]);
            AuditLog::record($student, 'appeal_submitted', $appeal, 'Scholarship reinstatement requested.');
            NotifyService::send($student, 'Appeal submitted', "Your appeal {$appeal->appeal_no} is awaiting review. Your scholarship status stays unchanged until a decision is made.", route('my-awards'));
            User::where('role', 'admin')->where('status', 'active')->each(function ($admin) use ($appeal) {
                NotifyService::send($admin, 'Scholarship appeal awaiting review', "Appeal {$appeal->appeal_no} requests reinstatement.", route('appeals.show', $appeal));
            });
            return $appeal;
        });
    }

    public function decide(ScholarshipAppeal $appeal, User $admin, array $data): ScholarshipAppeal
    {
        abort_unless($admin->isAdmin() && $admin->isActive(), 403);
        return DB::transaction(function () use ($appeal, $admin, $data) {
            // Every appeal/status write locks the award first, preventing stale reinstatements.
            $award = ScholarshipAward::lockForUpdate()->findOrFail($appeal->scholarship_award_id);
            $appeal = ScholarshipAppeal::lockForUpdate()->findOrFail($appeal->id);
            if (! $appeal->isPending() || ! $award->canAppeal()
                || $award->status_version !== $appeal->award_status_version || $award->recipient_status !== $appeal->award_status) {
                throw ValidationException::withMessages(['status' => 'This appeal can no longer be decided. Reload to see the current scholarship status.']);
            }
            $status = $data['status'];
            if (! in_array($status, ['under_review', 'approved', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'Choose a valid appeal decision.']);
            }
            $reason = trim($data['decision_reason'] ?? '');
            $final = $status !== 'under_review';
            if ($final && $reason === '') throw ValidationException::withMessages(['decision_reason' => 'Explain the decision for the student.']);
            $appeal->update(['status' => $status, 'decision_reason' => $reason ?: null, 'pending_slot' => $final ? null : 1,
                'decided_by' => $final ? $admin->id : null, 'decided_at' => $final ? now() : null]);
            if ($status === 'approved') {
                app(AwardStatusService::class)->update($award, $admin, ['recipient_status' => 'active', 'status_reason' => null]);
            }
            AuditLog::record($admin, 'appeal_decided', $appeal, "Appeal {$status}: {$reason}");
            NotifyService::send($appeal->user_id, 'Scholarship appeal '.str_replace('_', ' ', $status),
                "Your appeal {$appeal->appeal_no} is ".str_replace('_', ' ', $status).'. '
                .($status === 'approved' ? 'You have been reinstated in the scholarship. ' : '').$reason, route('my-awards'));
            return $appeal;
        });
    }
}
