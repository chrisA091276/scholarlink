<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\RenewalApplication;
use App\Models\ScholarshipAward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenewalService
{
    public function save(ScholarshipAward $award, User $student, array $data): RenewalApplication
    {
        return DB::transaction(function () use ($award, $student, $data) {
            $award = ScholarshipAward::lockForUpdate()->findOrFail($award->id);
            abort_unless($student->isActive() && $student->isStudent() && $award->user_id === $student->id, 403);
            abort_unless($award->recipient_status === 'active', 403, 'Only active awards can be renewed.');
            $renewal = $award->renewals()->where('academic_year', $data['academic_year'])->lockForUpdate()->first();
            if ($renewal && $renewal->status !== 'draft') {
                throw ValidationException::withMessages(['academic_year' => 'A renewal has already been submitted for this academic year.']);
            }
            $submitting = $data['action'] === 'submit';
            if ($submitting && config('scholarlink.verify_email') && ! $student->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['email' => 'Verify your email before submitting.']);
            }
            $values = [
                'academic_year' => $data['academic_year'],
                'statement' => $data['statement'] ?? null,
                'form_data' => app(ApplicationTemplateService::class)->validate($award->program, $data['form_data'] ?? [], $submitting, 'renewal_template'),
                'status' => $submitting ? 'submitted' : 'draft', 'submitted_at' => $submitting ? now() : null,
            ];
            if ($renewal) $renewal->update($values);
            else $renewal = $award->renewals()->create($values + ['user_id' => $student->id, 'renewal_no' => 'RNW-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(8)))]);
            AuditLog::record($student, 'renewal_save', $renewal, 'Renewal '.$renewal->status);
            if ($submitting) NotifyService::send($student, 'Renewal submitted', 'Renewal '.$renewal->renewal_no.' is ready for review.', route('my-renewals.index'));
            return $renewal;
        });
    }

    public function decide(RenewalApplication $renewal, User $admin, array $data): void
    {
        DB::transaction(function () use ($renewal, $admin, $data) {
            abort_unless($admin->isAdmin() && $admin->isActive(), 403);
            $renewal = RenewalApplication::lockForUpdate()->findOrFail($renewal->id);
            if (! in_array($renewal->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['status' => 'Only submitted renewals awaiting a decision can be reviewed.']);
            }
            if ($data['status'] === 'rejected' && trim($data['remarks'] ?? '') === '') {
                throw ValidationException::withMessages(['remarks' => 'Provide a reason for rejection.']);
            }
            $renewal->update(['status' => $data['status'], 'remarks' => $data['remarks'] ?? null,
                'decided_by' => $data['status'] === 'under_review' ? null : $admin->id,
                'decided_at' => $data['status'] === 'under_review' ? null : now()]);
            AuditLog::record($admin, 'renewal_decide', $renewal, 'Renewal '.$data['status']);
            NotifyService::send($renewal->user_id, 'Renewal '.$data['status'], 'Your renewal '.$renewal->renewal_no.' was updated. '.($data['remarks'] ?? ''), route('my-renewals.index'));
        });
    }
}
