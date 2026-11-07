<?php

namespace Tests\Feature;

use App\Models\{ApplicationDocument, AppNotification, ScholarshipApplication, ScholarshipAward, ScholarshipProgram, User};
use App\Services\{ApplicationDraftService, ApplicationSubmissionService, EligibilityService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $status = 'draft'): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'TEST-'.$student->id, 'first_name' => 'Fictional', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2', 'enrollment_status' => 'enrolled', 'profile_completed' => true]);
        $program = ScholarshipProgram::create(['code' => 'TEST-'.$student->id, 'name' => 'Fictional test grant', 'category' => 'Test', 'status' => 'published', 'opening_date' => today()->subDay(), 'deadline' => today()->addDays(30), 'created_by' => $admin->id]);
        $application = ScholarshipApplication::create(['application_no' => ScholarshipApplication::generateApplicationNo(), 'user_id' => $student->id, 'scholarship_program_id' => $program->id, 'assigned_to' => $staff->id, 'academic_year' => '2026-2027', 'enrollment_status' => 'enrolled', 'status' => $status]);
        return compact('admin', 'staff', 'student', 'program', 'application');
    }

    private function document(ScholarshipApplication $application): ApplicationDocument
    {
        $requirement = $application->program->requirements()->create(['name' => 'Fictional enrollment record', 'is_required' => true, 'allowed_types' => 'pdf', 'max_size_kb' => 1024]);
        return $application->documents()->create(['scholarship_requirement_id' => $requirement->id, 'document_name' => 'Enrollment record', 'file_path' => 'documents/private-test.pdf', 'file_type' => 'pdf', 'file_size' => 25, 'storage_disk' => 'local', 'verification_status' => 'pending']);
    }

    public function test_registration_normalizes_phone_email_and_rejects_duplicates(): void
    {
        $payload = ['name' => 'Fictional Person', 'email' => 'PERSON@EXAMPLE.TEST', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'contact_number' => '(0917) 123-4567', 'terms_accepted' => true, 'role' => 'admin'];
        $this->postJson('/api/v1/register', $payload)->assertCreated()->assertJsonPath('user.role', 'student');
        $this->assertDatabaseHas('users', ['email' => 'person@example.test']);
        $this->assertDatabaseHas('student_profiles', ['contact_number' => '+639171234567']);
        $this->postJson('/api/v1/register', $payload)->assertUnprocessable()->assertJsonValidationErrors('email');
        $payload['email'] = 'second@example.test'; $payload['contact_number'] = '123'; $payload['terms_accepted'] = false; $payload['password_confirmation'] = 'mismatch';
        $this->postJson('/api/v1/register', $payload)->assertUnprocessable()->assertJsonValidationErrors(['contact_number', 'terms_accepted', 'password']);
    }

    public function test_verification_and_consent_block_submission_without_losing_draft(): void
    {
        extract($this->fixture());
        config(['scholarlink.verify_email' => true]);
        $student->forceFill(['email_verified_at' => null])->save();
        Sanctum::actingAs($student);
        $this->postJson('/api/v1/student/applications/'.$application->id.'/submit')->assertUnprocessable()->assertJsonValidationErrors(['email', 'declaration']);
        $this->assertSame('draft', $application->fresh()->status);
        $student->markEmailAsVerified();
        $this->postJson('/api/v1/student/applications/'.$application->id.'/submit', ['declaration' => true])->assertOk();
    }

    public function test_submission_retry_is_idempotent_after_review_begins(): void
    {
        extract($this->fixture());
        Sanctum::actingAs($student);
        $url='/api/v1/student/applications/'.$application->id.'/submit';
        $this->postJson($url, ['declaration' => true])->assertOk();
        $count = AppNotification::count();
        $application->refresh()->update(['status' => 'under_review']);
        $this->postJson($url, ['declaration' => true])->assertOk()->assertJsonPath('data.status', 'under_review');
        $this->assertSame($count, AppNotification::count());
        $this->assertSame(1, $application->statusHistories()->count());
    }

    public function test_submission_rechecks_deadline_and_financial_requirements(): void
    {
        extract($this->fixture());
        $program->eligibilityRules()->create(['rule_key' => 'monthly_household_income', 'rule_label' => 'Income review', 'operator' => '<=', 'rule_value' => '20000', 'rule_type' => 'number', 'is_active' => true]);
        $program->update(['deadline' => today()->subDay()]);
        Sanctum::actingAs($student);
        $this->postJson('/api/v1/student/applications/'.$application->id.'/submit', ['declaration' => true])->assertUnprocessable()->assertJsonValidationErrors(['scholarship_program_id', 'monthly_household_income']);
    }

    public function test_status_endpoint_cannot_bypass_student_submission(): void
    {
        extract($this->fixture());
        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/applications/'.$application->id.'/status', ['to_status' => 'submitted'])->assertUnprocessable();
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_bulk_review_requires_remarks_and_records_correction_history(): void
    {
        extract($this->fixture('submitted'));
        $document=$this->document($application);
        Sanctum::actingAs($staff);
        $url='/api/v1/staff/applications/'.$application->id.'/verify-documents';
        $this->postJson($url, ['documents' => [['id' => $document->id, 'verification_status' => 'requires_correction']]])->assertUnprocessable();
        $response=$this->postJson($url, ['documents' => [['id' => $document->id, 'verification_status' => 'requires_correction', 'verification_notes' => 'Please upload a readable copy.']]])->assertOk();
        $response->assertJsonMissingPath('data.0.file_path')->assertJsonMissingPath('data.0.storage_disk');
        $this->assertSame('incomplete_requirements', $application->fresh()->status);
        $this->assertDatabaseHas('document_reviews', ['application_document_id' => $document->id, 'reviewed_by' => $staff->id]);
        $this->assertDatabaseHas('application_status_histories', ['scholarship_application_id' => $application->id, 'to_status' => 'incomplete_requirements']);
    }

    public function test_bulk_review_rolls_back_when_a_document_belongs_to_another_application(): void
    {
        extract($this->fixture('submitted'));
        $document=$this->document($application);
        Sanctum::actingAs($staff);
        $this->postJson('/api/v1/staff/applications/'.$application->id.'/verify-documents', ['documents' => [
            ['id' => $document->id, 'verification_status' => 'verified'], ['id' => 999999, 'verification_status' => 'verified'],
        ]])->assertNotFound();
        $this->assertSame('pending', $document->fresh()->verification_status);
        $this->assertDatabaseCount('document_reviews', 0);
    }

    public function test_finalized_documents_and_evaluations_are_locked(): void
    {
        extract($this->fixture('approved'));
        $document=$this->document($application);
        Sanctum::actingAs($staff);
        $this->postJson('/api/v1/staff/applications/'.$application->id.'/verify-documents', ['documents' => [['id' => $document->id, 'verification_status' => 'verified']]])->assertForbidden();
        $this->postJson('/api/v1/staff/applications/'.$application->id.'/evaluation', ['recommendation' => 'recommended_approval', 'evaluation_notes' => 'Test evaluation'])->assertUnprocessable();
        $this->assertDatabaseCount('application_evaluations', 0);
    }

    public function test_full_correction_evaluation_decision_and_award_workflow(): void
    {
        extract($this->fixture());
        Storage::fake('local');
        $document=$this->document($application);
        Sanctum::actingAs($student);
        $this->postJson('/api/v1/student/applications/'.$application->id.'/submit', ['declaration' => true])->assertOk();
        Sanctum::actingAs($staff);
        $url='/api/v1/staff/applications/'.$application->id;
        $this->postJson($url.'/verify-documents', ['documents' => [['id' => $document->id, 'verification_status' => 'requires_correction', 'verification_notes' => 'Replace the blurred copy.']]])->assertOk();
        Sanctum::actingAs($student);
        $file=UploadedFile::fake()->createWithContent('enrollment.pdf', "%PDF-1.4\nFictional test document\n%%EOF");
        $upload=$this->postJson('/api/v1/student/applications/'.$application->id.'/documents', ['scholarship_requirement_id' => $document->scholarship_requirement_id, 'document_name' => 'Corrected enrollment', 'file' => $file])->assertCreated();
        $this->assertSoftDeleted($document);
        $this->postJson('/api/v1/student/applications/'.$application->id.'/submit', ['declaration' => true])->assertOk();
        Sanctum::actingAs($staff);
        $this->postJson($url.'/verify-documents', ['documents' => [['id' => $upload->json('data.id'), 'verification_status' => 'verified']]])->assertOk();
        $this->postJson('/api/v1/applications/'.$application->id.'/status', ['to_status' => 'under_review'])->assertOk();
        $this->postJson($url.'/eligibility')->assertOk();
        $this->postJson('/api/v1/applications/'.$application->id.'/status', ['to_status' => 'for_evaluation'])->assertOk();
        $this->postJson($url.'/recommendation', ['recommendation' => 'recommended_approval', 'score' => 90, 'evaluation_notes' => 'Verified and reviewed.'])->assertCreated();
        $this->postJson('/api/v1/admin/applications/'.$application->id.'/decision', ['to_status' => 'approved'])->assertForbidden();
        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/admin/applications/'.$application->id.'/decision', ['to_status' => 'approved'])->assertOk();
        $this->assertDatabaseHas('scholarship_awards', ['scholarship_application_id' => $application->id]);
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_submission_failure_rolls_back_related_records_and_keeps_draft(): void
    {
        extract($this->fixture());
        $this->mock(EligibilityService::class, function ($mock) {
            $mock->shouldReceive('screen')->once()->andReturn(['result' => 'eligible', 'reasons' => []]);
            $mock->shouldReceive('runAndStore')->once()->andThrow(new \RuntimeException('Simulated database failure'));
        });
        try { app(ApplicationSubmissionService::class)->submit($application, $student, true); $this->fail('Expected failure'); }
        catch (\RuntimeException $e) { $this->assertSame('Simulated database failure', $e->getMessage()); }
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);
        $this->assertDatabaseCount('application_status_histories', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_renewal_draft_resumes_and_duplicate_submission_is_blocked(): void
    {
        extract($this->fixture('approved'));
        $award=ScholarshipAward::create(['award_no' => 'TEST-AWARD', 'scholarship_application_id' => $application->id, 'user_id' => $student->id, 'scholarship_program_id' => $program->id, 'recipient_status' => 'active']);
        Sanctum::actingAs($student);
        $url='/api/v1/awards/'.$award->id.'/renewals';
        $this->postJson($url, ['academic_year' => '2027-2028', 'action' => 'draft', 'statement' => 'Saved progress'])->assertCreated();
        $this->actingAs($student)->get('/awards/'.$award->id.'/renew')->assertOk()->assertSee('Saved progress');
        $this->postJson($url, ['academic_year' => '2027-2028', 'action' => 'submit', 'statement' => 'Completed'])->assertCreated();
        $this->postJson($url, ['academic_year' => '2027-2028', 'action' => 'submit'])->assertUnprocessable();
        $this->assertDatabaseCount('renewal_applications', 1);
    }

    public function test_unassigned_staff_cannot_read_student_records(): void
    {
        extract($this->fixture('submitted'));
        $other=User::factory()->create(['role' => 'staff']);
        $this->actingAs($other)->get('/student-records/'.$student->id)->assertForbidden();
        $this->get('/student-records')->assertOk()->assertDontSee($student->studentProfile->student_id_no);
        $this->get('/reports')->assertOk()->assertDontSee($application->application_no);
    }

    public function test_saved_draft_retains_program_and_step_even_after_deadline(): void
    {
        extract($this->fixture());
        $application->update(['last_saved_step' => 5]); $program->update(['deadline' => today()->subDay()]);
        $this->actingAs($student)->get('/my-applications/'.$application->id.'/edit')->assertOk()->assertSee('value="5"', false)->assertSee($program->name)->assertSee('reviewFinancial');
    }

    public function test_profile_is_locked_during_review(): void
    {
        extract($this->fixture('submitted'));
        Sanctum::actingAs($student);
        $this->putJson('/api/v1/student/profile', ['student_id_no' => 'TEST-'.$student->id, 'first_name' => 'Changed', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2', 'enrollment_status' => 'enrolled'])->assertUnprocessable()->assertJsonValidationErrors('profile');
    }

    public function test_profile_requires_personal_details_and_saves_normalized_phone(): void
    {
        extract($this->fixture());
        Sanctum::actingAs($student);
        $data=['student_id_no' => 'TEST-'.$student->id, 'first_name' => 'Fictional', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'enrollment_status' => 'enrolled'];
        $this->putJson('/api/v1/student/profile', $data)->assertUnprocessable()->assertJsonValidationErrors(['date_of_birth', 'address', 'contact_number']);
        $this->putJson('/api/v1/student/profile', $data + ['date_of_birth' => '2004-01-01', 'address' => 'Fictional test address', 'contact_number' => '0917 123 4567'])->assertOk();
        $this->assertDatabaseHas('student_profiles', ['user_id' => $student->id, 'contact_number' => '+639171234567']);
    }

    public function test_student_announcements_reach_students_and_reminders_are_deduplicated(): void
    {
        extract($this->fixture('approved'));
        $this->actingAs($admin)->post('/announcements', ['title' => 'Fictional announcement', 'content' => 'Test notice', 'audience' => 'students'])->assertRedirect();
        $this->assertDatabaseHas('notifications', ['user_id' => $student->id, 'title' => 'Announcement: Fictional announcement']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $staff->id, 'title' => 'Announcement: Fictional announcement']);
        ScholarshipAward::create(['award_no' => 'REMINDER-TEST', 'scholarship_application_id' => $application->id, 'user_id' => $student->id, 'scholarship_program_id' => $program->id, 'recipient_status' => 'active', 'end_date' => today()->addDays(10)]);
        $this->artisan('app:renewal-reminders')->assertSuccessful();
        $this->artisan('app:renewal-reminders')->assertSuccessful();
        $this->assertSame(1, AppNotification::where('title', 'Award renewal reminder')->count());
    }

    public function test_public_programs_hide_drafts_even_with_an_admin_token(): void
    {
        extract($this->fixture());
        $program->update(['status' => 'draft']);
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/scholarships/'.$program->id)->assertNotFound();
        $this->getJson('/api/v1/scholarships')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_reports_filter_actual_submission_dates_and_reject_invalid_dates(): void
    {
        extract($this->fixture('submitted'));
        $application->update(['created_at' => today()->subMonth(), 'submitted_at' => today()]);
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/reports?from='.today()->toDateString().'&to='.today()->toDateString())->assertOk()->assertJsonPath('data.total_applications', 1);
        $this->getJson('/api/v1/admin/reports?from=invalid')->assertUnprocessable();
        $this->actingAs($admin)->get('/reports?from='.today()->toDateString().'&to='.today()->toDateString())->assertOk()->assertSee($application->application_no);
    }
}
