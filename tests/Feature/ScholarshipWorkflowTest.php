<?php

namespace Tests\Feature;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScholarshipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeProgram(User $admin, array $over = []): ScholarshipProgram
    {
        return ScholarshipProgram::create(array_merge([
            'code' => 'SCH-' . uniqid(), 'name' => 'Test Scholarship', 'category' => 'Merit',
            'status' => 'published', 'opening_date' => now()->subDay()->toDateString(),
            'deadline' => now()->addDays(10)->toDateString(), 'created_by' => $admin->id,
        ], $over));
    }

    public function test_admin_can_crud_scholarship(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/manage-scholarships', [
            'code' => 'CRUD-1', 'name' => 'Crud Test', 'category' => 'Merit', 'status' => 'draft',
        ])->assertRedirect();
        $this->assertDatabaseHas('scholarship_programs', ['code' => 'CRUD-1']);
    }

    public function test_admin_can_create_a_custom_eligibility_rule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram($admin);

        $this->actingAs($admin)->post(route('scholarships.rules.store', $program), [
            'rule_key' => 'form_data.community_service_hours',
            'rule_label' => 'Community service hours',
            'rule_type' => 'number',
            'operator' => '>=',
            'rule_value' => '40',
            'failure_mode' => 'mandatory',
        ])->assertRedirect();

        $this->assertDatabaseHas('eligibility_rules', [
            'scholarship_program_id' => $program->id,
            'rule_key' => 'form_data.community_service_hours',
            'rule_type' => 'number',
            'failure_mode' => 'mandatory',
        ]);
    }

    public function test_closed_program_rejects_applications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin, ['status' => 'closed']);
        $this->actingAs($student)->post('/my-applications', [
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'enrollment_status' => 'enrolled', 'action' => 'submit',
        ])->assertSessionHasErrors('scholarship_program_id');
        $this->assertDatabaseMissing('scholarship_applications', ['scholarship_program_id' => $program->id]);
    }

    public function test_duplicate_application_prevented(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin);
        $payload = [
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'enrollment_status' => 'enrolled', 'action' => 'submit',
        ];
        $this->actingAs($student)->post('/my-applications', $payload)->assertRedirect();
        $this->actingAs($student)->post('/my-applications', $payload)->assertSessionHasErrors('scholarship_program_id');
        $this->assertEquals(1, ScholarshipApplication::count());
    }

    public function test_document_upload_validates_type(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin);
        $app = ScholarshipApplication::create([
            'application_no' => 'APP-D1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027', 'status' => 'draft',
        ]);
        // Invalid: .exe
        $this->actingAs($student)->post("/applications/{$app->id}/documents", [
            'document_name' => 'Bad', 'file' => UploadedFile::fake()->create('virus.exe', 100),
        ])->assertSessionHasErrors('file');
        // Valid: pdf
        $this->actingAs($student)->post("/applications/{$app->id}/documents", [
            'document_name' => 'Report Card', 'file' => UploadedFile::fake()->create('card.pdf', 200, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('application_documents', ['document_name' => 'Report Card']);
    }

    public function test_document_download_sanitizes_filename(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin);
        $application = ScholarshipApplication::create([
            'application_no' => 'APP-SAFE-1',
            'user_id' => $student->id, 'scholarship_program_id' => $program->id,
            'academic_year' => '2026-2027', 'status' => 'draft',
        ]);
        Storage::disk('local')->put('documents/test-safe.pdf', 'test document');
        $document = \App\Models\ApplicationDocument::create([
            'scholarship_application_id' => $application->id,
            'document_name' => '//ll', 'file_path' => 'documents/test-safe.pdf',
            'file_type' => 'pdf', 'file_size' => 13, 'verification_status' => 'pending',
        ]);

        $this->actingAs($student)
            ->get(route('documents.download', [$application, $document]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=ll.pdf');
    }

    public function test_student_can_download_own_application_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin, [
            'application_template' => [
                ['key' => 'achievement', 'label' => 'Achievement', 'type' => 'text', 'required' => true],
            ],
        ]);
        $application = ScholarshipApplication::create([
            'application_no' => 'APP-PDF-1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'status' => 'draft', 'enrollment_status' => 'enrolled',
            'form_data' => ['achievement' => 'Dean\'s list'],
        ]);

        $this->actingAs($student)
            ->get(route('my-applications.download', $application))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($otherStudent)
            ->get(route('my-applications.download', $application))
            ->assertForbidden();
    }

    public function test_eligibility_screening_runs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        \App\Models\StudentProfile::create([
            'user_id' => $student->id, 'student_id_no' => 'S-1', 'first_name' => 'A',
            'last_name' => 'B', 'course' => 'BSIT', 'year_level' => '2nd',
            'enrollment_status' => 'enrolled',
        ]);
        $program = $this->makeProgram($admin);
        $program->eligibilityRules()->create([
            'rule_key' => 'course', 'rule_label' => 'Allowed course', 'operator' => '=',
            'rule_value' => 'BSIT', 'is_active' => true,
        ]);
        $app = ScholarshipApplication::create([
            'application_no' => 'APP-E1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'status' => 'submitted',
        ]);
        $this->actingAs($admin)->post("/applications/{$app->id}/screen")->assertRedirect();
        $this->assertDatabaseHas('eligibility_results', ['scholarship_application_id' => $app->id]);
    }

    public function test_flexible_eligibility_rules_cover_profile_and_custom_application_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        \App\Models\StudentProfile::create([
            'user_id' => $student->id,
            'student_id_no' => 'S-FLEX-1',
            'first_name' => 'Flexible',
            'last_name' => 'Applicant',
            'course' => 'BSIT',
            'year_level' => '2nd',
            'enrollment_status' => 'enrolled',
            'monthly_household_income' => 20000,
        ]);
        $program = $this->makeProgram($admin);
        $program->eligibilityRules()->createMany([
            [
                'rule_key' => 'monthly_household_income', 'rule_label' => 'Income ceiling',
                'rule_type' => 'number', 'operator' => '<=', 'rule_value' => '25000',
                'failure_mode' => 'mandatory', 'is_active' => true,
            ],
            [
                'rule_key' => 'form_data.community_service_hours', 'rule_label' => 'Community service',
                'rule_type' => 'number', 'operator' => '>=', 'rule_value' => '40',
                'failure_mode' => 'mandatory', 'is_active' => true,
            ],
            [
                'rule_key' => 'form_data.residency', 'rule_label' => 'Residency',
                'rule_type' => 'text', 'operator' => 'in', 'rule_value' => 'Manila,Quezon City',
                'failure_mode' => 'mandatory', 'is_active' => true,
            ],
        ]);
        $app = ScholarshipApplication::create([
            'application_no' => 'APP-FLEX-1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'status' => 'submitted', 'form_data' => [
                'community_service_hours' => 40,
                'residency' => 'Manila',
            ],
        ]);

        $result = app(\App\Services\EligibilityService::class)->screen($app);
        $this->assertSame('eligible', $result['result']);

        $app->update(['form_data' => ['community_service_hours' => 12, 'residency' => 'Manila']]);
        $result = app(\App\Services\EligibilityService::class)->screen($app->fresh());
        $this->assertSame('not_eligible', $result['result']);
    }

    public function test_flexible_rule_can_send_an_application_to_manual_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin);
        $program->eligibilityRules()->create([
            'rule_key' => 'form_data.portfolio_url', 'rule_label' => 'Portfolio',
            'rule_type' => 'text', 'operator' => 'exists', 'rule_value' => '',
            'failure_mode' => 'manual_review', 'is_active' => true,
        ]);
        $app = ScholarshipApplication::create([
            'application_no' => 'APP-FLEX-2', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'status' => 'submitted', 'form_data' => [],
        ]);

        $result = app(\App\Services\EligibilityService::class)->screen($app);
        $this->assertSame('manual_review', $result['result']);
    }

    public function test_admin_approval_creates_award_in_transaction(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin);
        $app = ScholarshipApplication::create([
            'application_no' => 'APP-A1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'status' => 'recommended_approval',
        ]);
        $this->actingAs($admin)->post("/applications/{$app->id}/status", [
            'to_status' => 'approved', 'remarks' => 'Congrats',
        ])->assertRedirect();
        $this->assertEquals('approved', $app->fresh()->status);
        $this->assertDatabaseHas('scholarship_awards', ['scholarship_application_id' => $app->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $student->id]);
    }

    public function test_invalid_status_transition_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = $this->makeProgram($admin);
        $app = ScholarshipApplication::create([
            'application_no' => 'APP-I1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027', 'status' => 'draft',
        ]);
        $this->actingAs($admin)->post("/applications/{$app->id}/status", ['to_status' => 'approved'])
            ->assertSessionHas('error');
    }
}
