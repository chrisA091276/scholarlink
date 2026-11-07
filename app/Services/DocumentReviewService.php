<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DocumentReviewService
{
    /** All review entry points use the same locks, history and correction rules. */
    public function review(ScholarshipApplication $application, User $actor, array $items): void
    {
        DB::transaction(function () use ($application, $actor, $items) {
            $application = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
            abort_unless($actor->isActive() && ($actor->isAdmin() || $actor->isStaff()), 403);
            Gate::forUser($actor)->authorize('view', $application);
            abort_unless(in_array($application->status, ['submitted', 'under_review', 'incomplete_requirements', 'for_evaluation'], true), 403, 'This application is not open for document review.');

            foreach ($items as $item) {
                $document = $application->documents()->lockForUpdate()->findOrFail($item['id']);
                $status = $item['verification_status'];
                $remarks = trim($item['verification_notes'] ?? '');
                $correction = in_array($status, ['rejected', 'requires_correction'], true);
                if (! in_array($status, ['pending', 'verified', 'rejected', 'requires_correction'], true)
                    || ($correction && $remarks === '')) {
                    throw ValidationException::withMessages(['verification_notes' => 'Provide a valid status and clear remarks for rejected documents or corrections.']);
                }
                $document->reviews()->create(['reviewed_by' => $actor->id, 'status' => $status, 'remarks' => $remarks ?: null]);
                $document->update(['verification_status' => $status, 'verification_notes' => $remarks ?: null, 'verified_by' => $actor->id, 'verified_at' => now()]);
                AuditLog::record($actor, 'document_verify', $document, 'Document review: '.$status);
                if ($correction) {
                    if ($application->canTransitionTo('incomplete_requirements')) {
                        app(ApplicationTransitionService::class)->transition($application, 'incomplete_requirements', $actor, $remarks);
                    }
                    NotifyService::send($application->user_id, 'Document needs attention', $document->document_name.': '.$remarks, route('my-applications.show', $application));
                }
            }

            $required = $application->program->requirements()->where('is_required', true)->pluck('id');
            $verified = $application->documents()->where('verification_status', 'verified')->pluck('scholarship_requirement_id');
            if ($required->isNotEmpty() && $required->diff($verified)->isEmpty()) {
                NotifyService::send($application->user_id, 'Documents verified', 'All required documents have been verified. Your application will continue through review.', route('my-applications.show', $application));
            }
        });
    }
}
