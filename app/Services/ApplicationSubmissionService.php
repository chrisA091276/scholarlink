<?php

namespace App\Services;

use App\Models\ScholarshipApplication;

class ApplicationSubmissionService
{
    public function submit(ScholarshipApplication $application, \App\Models\User $user, bool $declaration): ScholarshipApplication
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($application, $user, $declaration) {
            $application = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
            abort_unless($application->user_id === $user->id && $user->isStudent() && $user->isActive(), 403);
            // A retried request returns the existing submission without duplicate history or notifications.
            if ($application->submitted_at && ! in_array($application->status, ['draft', 'incomplete_requirements', 'withdrawn'], true)) return $application;
            abort_unless(in_array($application->status, ['draft', 'incomplete_requirements'], true), 403);
            $errors = [];
            if (! $declaration) $errors['declaration'] = 'Accept the declaration and privacy notice before submitting.';
            if (config('scholarlink.verify_email') && ! $user->hasVerifiedEmail()) $errors['email'] = 'Verify your email before submitting.';
            if (! $application->program->isOpenForApplication()) $errors['scholarship_program_id'] = 'This scholarship is not accepting applications.';
            $profile = $user->studentProfile;
            $professional = $application->form_version === AdfcApplicationForm::VERSION;
            if (! $professional && (! $profile || ! $profile->profile_completed)) $errors['profile'] = 'Complete your student profile before submitting.';
            if (! $professional && $profile) {
                foreach (['student_id_no', 'first_name', 'last_name', 'date_of_birth', 'contact_number', 'address', 'course', 'year_level'] as $field) {
                    if (blank($profile->{$field})) $errors['profile.'.$field] = 'Complete '.str_replace('_', ' ', $field).' in your profile.';
                }
            }
            if (! $application->academic_year || ! $application->enrollment_status) $errors['academic_year'] = 'Complete the academic information.';
            foreach ($application->program->financialFields() as $field) {
                if ($application->{$field} === null || $application->{$field} === '') $errors[$field] = 'Complete this program-required field.';
            }
            $missing = $this->missingRequirements($application);
            if ($missing) $errors['documents'] = 'Upload required documents: '.implode(', ', $missing).'.';
            foreach ([
                fn () => $professional ? app(AdfcApplicationForm::class)->validate($application->adfc_form ?? [], true) : null,
                fn () => app(ApplicationTemplateService::class)->validate($application->program, $application->form_data ?? [], true),
            ] as $validate) {
                try { $validate(); } catch (\Illuminate\Validation\ValidationException $error) {
                    $errors = array_merge($errors, $error->errors());
                }
            }
            if ($errors) throw \Illuminate\Validation\ValidationException::withMessages($errors);
            app(ApplicationTemplateService::class)->validate($application->program, $application->form_data ?? [], true);
            $eligibility = app(EligibilityService::class)->screen($application);
            if ($eligibility['result'] === 'not_eligible') {
                throw \Illuminate\Validation\ValidationException::withMessages(['eligibility' => 'The application does not meet configured eligibility criteria. Review the program requirements.']);
            }
            $previous = $application->status;
            $application->update(['status' => 'submitted', 'submitted_at' => $application->submitted_at ?? now(), 'declaration_accepted_at' => now(), 'last_saved_step' => 7]);
            $application->statusHistories()->create(['from_status' => $previous, 'to_status' => 'submitted', 'changed_by' => $user->id, 'remarks' => 'Student submitted and accepted the declaration.']);
            app(EligibilityService::class)->runAndStore($application, $user->id);
            \App\Models\AuditLog::record($user, 'application_submit', $application, 'Application submitted: '.$application->application_no);
            NotifyService::send($user, 'Application submitted', 'Your application '.$application->application_no.' was submitted.', route('my-applications.show', $application));
            $reviewers = \App\Models\User::where('status', 'active')->where(function ($query) use ($application) {
                $query->where('role', 'admin');
                if ($application->assigned_to) $query->orWhere('id', $application->assigned_to);
            })->get();
            foreach ($reviewers as $reviewer) NotifyService::send($reviewer, 'Application ready for review', 'Application '.$application->application_no.' is ready.', route('applications.show', $application));
            return $application;
        });
    }

    /**
     * Return the configured requirements that do not yet have an uploaded
     * document. Verification is a staff step and is intentionally separate
     * from initial student submission.
     */
    public function missingRequirements(ScholarshipApplication $application): array
    {
        $application->loadMissing(['program.requirements', 'documents']);

        $uploadedRequirementIds = $application->documents
            ->whereIn('verification_status', ['pending', 'verified'])
            ->pluck('scholarship_requirement_id')
            ->filter()
            ->unique()
            ->all();

        $configured = $application->program->requirements
            ->where('is_required', true)
            ->reject(fn ($requirement) => in_array($requirement->id, $uploadedRequirementIds, true))
            ->pluck('name')
            ->values()
            ->all();
        return array_values(array_unique(array_merge($configured, app(AdfcApplicationForm::class)->missingDocuments($application))));
    }

    public function canSubmit(ScholarshipApplication $application): bool
    {
        return $this->missingRequirements($application) === []
            && $application->program?->isOpenForApplication();
    }
}
