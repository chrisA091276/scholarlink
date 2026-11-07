<?php

namespace App\Services;

use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationTransitionService
{
    public function transition(ScholarshipApplication $application, string $to, User $user, ?string $remarks): void
    {
        DB::transaction(function () use ($application, $to, $user, $remarks) {
            $locked = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
            abort_unless($user->isActive(), 403);
            if ($to === 'submitted') {
                throw ValidationException::withMessages(['to_status' => 'Students must submit through the validated submission workflow.']);
            }
            if (in_array($to, ['incomplete_requirements', 'recommended_rejection', 'rejected'], true) && trim($remarks ?? '') === '') {
                throw ValidationException::withMessages(['remarks' => 'Explain the correction request or rejection.']);
            }
            if (in_array($to, ['approved', 'rejected'], true)) abort_unless($user->isAdmin(), 403);
            elseif ($user->isStudent()) abort_unless($locked->user_id === $user->id && $to === 'withdrawn', 403);
            elseif ($user->isStaff()) abort_unless((int) $locked->assigned_to === $user->id, 403);
            if (! $locked->canTransitionTo($to)) throw ValidationException::withMessages(['to_status' => 'This status transition is no longer available. Reload the application.']);
            if ($to === 'for_evaluation') {
                if (! app(AdfcApplicationForm::class)->reviewed($locked) || app(AdfcApplicationForm::class)->missingDocuments($locked, true)) {
                    throw ValidationException::withMessages(['to_status' => 'Complete the ADFC form review and verify its required documents before evaluation.']);
                }
                $required = $locked->program->requirements()->where('is_required', true)->pluck('id');
                $verified = $locked->documents()->where('verification_status', 'verified')->pluck('scholarship_requirement_id');
                if ($required->diff($verified)->isNotEmpty() || ! $locked->eligibilityResults()->exists()) {
                    throw ValidationException::withMessages(['to_status' => 'Verify the required documents and record eligibility screening before evaluation.']);
                }
            }
            if (in_array($to, ['recommended_approval', 'recommended_rejection'], true)
                && ! $locked->evaluations()->where('recommendation', $to)->exists()) {
                throw ValidationException::withMessages(['to_status' => 'Record an evaluation and recommendation first.']);
            }
            if ($to === 'approved') {
                if ($locked->form_version === AdfcApplicationForm::VERSION &&
                    (! app(AdfcApplicationForm::class)->reviewed($locked) || ($locked->form_review['qualification'] ?? null) !== 'meets'
                        || app(AdfcApplicationForm::class)->missingDocuments($locked, true))) {
                    throw ValidationException::withMessages(['documents' => 'Approval requires a completed ADFC review, confirmed qualifications, and verified documents.']);
                }
                $program = $locked->program()->lockForUpdate()->firstOrFail();
                if ($program->slots !== null && $program->awards()->where('recipient_status', 'active')->count() >= $program->slots) {
                    throw ValidationException::withMessages(['to_status' => 'No award slots remain for this scholarship.']);
                }
                $required = $program->requirements()->where('is_required', true)->pluck('id');
                $verified = $locked->documents()->where('verification_status', 'verified')->pluck('scholarship_requirement_id');
                if ($required->diff($verified)->isNotEmpty()) throw ValidationException::withMessages(['documents' => 'Verify all required documents before approval.']);
            }
            $from = $locked->status;
            $locked->update(['status' => $to]);
            $locked->statusHistories()->create(['from_status' => $from, 'to_status' => $to, 'changed_by' => $user->id, 'remarks' => $remarks]);
            $application->setRawAttributes($locked->getAttributes(), true);
        });
    }
}
