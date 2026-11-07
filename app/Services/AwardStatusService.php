<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ScholarshipAward;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AwardStatusService
{
    public function update(ScholarshipAward $award, User $admin, array $data): ScholarshipAward
    {
        abort_unless($admin->isAdmin() && $admin->isActive(), 403);
        return DB::transaction(function () use ($award, $admin, $data) {
            $award = ScholarshipAward::lockForUpdate()->findOrFail($award->id);
            $status = $data['recipient_status'];
            if (! in_array($status, ScholarshipAward::STATUSES, true)) {
                throw ValidationException::withMessages(['recipient_status' => 'Choose a valid scholarship status.']);
            }
            $reason = $status === 'active' ? '' : trim($data['status_reason'] ?? '');
            if (in_array($status, ['removed', 'suspended'], true) && $reason === '') {
                throw ValidationException::withMessages(['status_reason' => 'Explain the removal or suspension. This reason will be shown to the student.']);
            }
            $start = array_key_exists('start_date', $data) ? $data['start_date'] : $award->start_date;
            $end = array_key_exists('end_date', $data) ? $data['end_date'] : $award->end_date;
            if ($start && $end && Carbon::parse($end)->lt(Carbon::parse($start))) {
                throw ValidationException::withMessages(['end_date' => 'The award end date cannot be before its start date.']);
            }
            $changed = $award->recipient_status !== $status;
            if ($changed && $status === 'active') {
                $program = $award->program()->lockForUpdate()->firstOrFail();
                if ($program->slots !== null && $program->awards()->where('recipient_status', 'active')->count() >= $program->slots) {
                    throw ValidationException::withMessages(['recipient_status' => 'No scholarship slots remain. Review the program capacity before reinstating this student.']);
                }
            }
            $before = $award->only(['recipient_status', 'status_reason', 'status_version', 'start_date', 'end_date', 'remarks']);
            $values = array_intersect_key($data, array_flip(['start_date', 'end_date', 'remarks']));
            $values += ['recipient_status' => $status, 'status_reason' => $reason !== '' ? $reason : null];
            if ($changed) {
                $values['status_version'] = $award->status_version + 1;
                $values['status_changed_at'] = now();
                foreach ($award->appeals()->whereIn('status', \App\Models\ScholarshipAppeal::PENDING_STATUSES)->lockForUpdate()->get() as $appeal) {
                    $appeal->update(['status' => 'closed', 'pending_slot' => null, 'decided_by' => $admin->id,
                        'decided_at' => now(), 'decision_reason' => "Closed because the scholarship status changed to {$status}."]);
                    AuditLog::record($admin, 'appeal_closed', $appeal, $appeal->decision_reason);
                }
            }
            $award->update($values);
            AuditLog::record($admin, 'award_update', $award, "Award status: {$status}", $before, $award->only(array_keys($before)));
            NotifyService::send($award->user_id, 'Scholarship status updated',
                "Your award {$award->award_no} is now {$status}.".($reason !== '' ? " Reason: {$reason}" : ''), route('my-awards'));
            return $award;
        });
    }
}
