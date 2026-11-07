<?php

namespace App\Services;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationDraftService
{
    public function save(User $user, array $data, ?ScholarshipApplication $application = null): ScholarshipApplication
    {
        return DB::transaction(function () use ($user, $data, $application) {
            // Serialize creation for a student; the cycle unique constraint is the final backstop.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($application) {
                $application = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
                abort_unless($application->user_id === $user->id, 403);
                abort_unless(in_array($application->status, ['draft', 'incomplete_requirements'], true), 403);
                $program = $application->program;
                if ((int) $data['scholarship_program_id'] !== $program->id) {
                    throw ValidationException::withMessages(['scholarship_program_id' => 'A saved draft belongs to its original scholarship. Create another draft to choose a different program.']);
                }
            } else {
                $program = ScholarshipProgram::findOrFail($data['scholarship_program_id']);
                if (! $program->isOpenForApplication()) throw ValidationException::withMessages(['scholarship_program_id' => 'This scholarship is not currently accepting applications.']);
            }
            $cycle = $data['academic_year'] ?? $application?->academic_year ?? now()->year.'-'.(now()->year+1);
            $duplicate = ScholarshipApplication::where('user_id', $user->id)->where('scholarship_program_id', $program->id)
                ->where('academic_year', $cycle)->when($application, fn ($q) => $q->whereKeyNot($application->id))->exists();
            if ($duplicate) throw ValidationException::withMessages(['scholarship_program_id' => 'An application already exists for this scholarship cycle. Resume the existing application.']);
            $data['form_data'] = app(ApplicationTemplateService::class)->validate($program, $data['form_data'] ?? $application?->form_data ?? [], false);
            $allowed = ['academic_year', 'enrollment_status', 'statement', 'form_data', 'last_saved_step'];
            // Financial values are accepted only where an administrator configured their use.
            $allowed = array_merge($allowed, $program->financialFields());
            $values = \Illuminate\Support\Arr::only($data, $allowed);
            $values['academic_year'] = $cycle;
            if (! $application || array_key_exists('adfc_form', $data)) {
                $values['adfc_form'] = app(AdfcApplicationForm::class)->validate($data['adfc_form'] ?? app(AdfcApplicationForm::class)->defaults($user));
                $values['form_version'] = AdfcApplicationForm::VERSION;
                // A changed answer needs a new office review, including corrected submissions.
                if ($application && $application->adfc_form !== $values['adfc_form']) $values['form_review'] = null;
            }
            if ($application) $application->update($values);
            else {
                $application = ScholarshipApplication::create($values + [
                    'user_id' => $user->id, 'scholarship_program_id' => $program->id,
                    'application_no' => ScholarshipApplication::generateApplicationNo(), 'status' => 'draft',
                ]);
                $application->statusHistories()->create(['from_status' => null, 'to_status' => 'draft', 'changed_by' => $user->id, 'remarks' => 'Draft created']);
            }
            AuditLog::record($user, 'application_save', $application, 'Draft saved.');
            return $application;
        });
    }
}
