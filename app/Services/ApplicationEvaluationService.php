<?php

namespace App\Services;

use App\Models\ApplicationEvaluation;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApplicationEvaluationService
{
    public function record(ScholarshipApplication $application, User $actor, array $data): ApplicationEvaluation
    {
        return DB::transaction(function () use ($application, $actor, $data) {
            $application = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
            abort_unless($actor->isActive() && ($actor->isAdmin() || $actor->isStaff()), 403);
            Gate::forUser($actor)->authorize('view', $application);
            if ($application->status !== 'for_evaluation') {
                throw ValidationException::withMessages(['recommendation' => 'Move this application to evaluation before recording a recommendation.']);
            }
            $evaluation = $application->evaluations()->create([
                'evaluator_id' => $actor->id, 'score' => $data['score'] ?? null,
                'recommendation' => $data['recommendation'], 'evaluation_notes' => $data['evaluation_notes'],
                'evaluated_at' => now(),
            ]);
            if ($data['recommendation'] !== 'pending') {
                app(ApplicationTransitionService::class)->transition($application, $data['recommendation'], $actor, $data['evaluation_notes']);
            }
            AuditLog::record($actor, 'evaluation_create', $evaluation, 'Evaluation recorded');
            NotifyService::send($application->user_id, 'Evaluation update', 'Your application evaluation has been updated.', route('my-applications.show', $application));
            return $evaluation;
        });
    }
}
