<?php

namespace Tests\Feature;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_versioned_api_register_always_creates_student(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Attempted Administrator',
            'email' => 'attempted-admin@test.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
            'contact_number' => '09171234567', 'terms_accepted' => true,
        ]);

        $response->assertCreated()->assertJsonPath('user.role', 'student');
        $this->assertDatabaseHas('users', ['email' => 'attempted-admin@test.ph', 'role' => 'student']);
    }

    public function test_versioned_api_requires_configured_documents_before_submission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = ScholarshipProgram::create([
            'code' => 'REQ-1', 'name' => 'Required Docs Grant', 'category' => 'General', 'status' => 'published',
            'opening_date' => now()->subDay()->toDateString(), 'deadline' => now()->addDay()->toDateString(),
            'created_by' => $admin->id,
        ]);
        ScholarshipRequirement::create([
            'scholarship_program_id' => $program->id, 'name' => 'Certificate of Enrollment',
            'is_required' => true, 'allowed_types' => 'pdf', 'max_size_kb' => 5120,
        ]);

        Sanctum::actingAs($student);
        $created = $this->postJson('/api/v1/student/applications', [
            'scholarship_program_id' => $program->id,
            'academic_year' => '2026-2027', 'enrollment_status' => 'enrolled',
            'action' => 'draft',
        ])->assertCreated();

        $id = $created->json('data.id');
        $this->postJson("/api/v1/student/applications/{$id}/submit")
            ->assertStatus(422)
            ->assertJsonValidationErrors('documents');
    }

    public function test_unassigned_staff_cannot_view_another_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $student = User::factory()->create(['role' => 'student']);
        $program = ScholarshipProgram::create([
            'code' => 'ASSIGN-1', 'name' => 'Assigned Grant', 'category' => 'General', 'status' => 'published',
            'opening_date' => now()->subDay()->toDateString(), 'deadline' => now()->addDay()->toDateString(),
            'created_by' => $admin->id,
        ]);
        $application = ScholarshipApplication::create([
            'application_no' => 'APP-ASSIGN-1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027', 'status' => 'submitted',
        ]);

        Sanctum::actingAs($staff);
        $this->getJson("/api/v1/staff/applications/{$application->id}")->assertForbidden();
    }

    public function test_scholarship_template_fields_are_saved_and_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = ScholarshipProgram::create([
            'code' => 'TPL-1', 'name' => 'Template Grant', 'category' => 'General', 'status' => 'published',
            'opening_date' => now()->subDay()->toDateString(), 'deadline' => now()->addDay()->toDateString(),
            'application_template' => [
                ['key' => 'community_service', 'label' => 'Community service experience', 'type' => 'textarea', 'required' => true],
                ['key' => 'preferred_campus', 'label' => 'Preferred campus', 'type' => 'select', 'required' => true, 'options' => ['Tacloban', 'Ormoc']],
                ['key' => 'study_modes', 'label' => 'Study modes', 'type' => 'multi_select', 'required' => true, 'options' => ['Online', 'In person']],
                ['key' => 'truthful', 'label' => 'Truthful information', 'type' => 'checkbox', 'required' => true],
            ],
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($student);
        $created = $this->postJson('/api/v1/student/applications', [
            'scholarship_program_id' => $program->id,
            'academic_year' => '2026-2027', 'enrollment_status' => 'enrolled', 'action' => 'draft',
            'form_data' => [
                'community_service' => 'Peer tutoring', 'preferred_campus' => 'Tacloban',
                'study_modes' => ['Online', 'In person'], 'truthful' => true, 'unconfigured_field' => 'discard me',
            ],
        ])->assertCreated();

        $application = ScholarshipApplication::findOrFail($created->json('data.id'));
        $this->assertSame('Peer tutoring', $application->form_data['community_service']);
        $this->assertSame('Tacloban', $application->form_data['preferred_campus']);
        $this->assertSame(['Online', 'In person'], $application->form_data['study_modes']);
        $this->assertTrue($application->form_data['truthful']);
        $this->assertArrayNotHasKey('unconfigured_field', $application->form_data);

        $this->postJson('/api/v1/student/applications', [
            'scholarship_program_id' => $program->id,
            'academic_year' => '2027-2028', 'enrollment_status' => 'enrolled', 'action' => 'submit',
            'form_data' => ['preferred_campus' => 'Tacloban'],
        ])->assertStatus(422)->assertJsonValidationErrors(['form_data.community_service']);
    }

    public function test_renewal_template_fields_are_saved_and_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = ScholarshipProgram::create([
            'code' => 'RNW-TPL-1', 'name' => 'Flexible Renewal Grant', 'category' => 'General', 'status' => 'published',
            'renewal_template' => [
                ['key' => 'progress_update', 'label' => 'Progress update', 'type' => 'textarea', 'required' => true],
                ['key' => 'renewal_consent', 'label' => 'Renewal consent', 'type' => 'checkbox', 'required' => true],
            ],
            'created_by' => $admin->id,
        ]);
        $application = ScholarshipApplication::create([
            'application_no' => 'APP-RNW-TPL-1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'status' => 'approved',
        ]);
        $award = \App\Models\ScholarshipAward::create([
            'award_no' => 'AWD-RNW-TPL-1', 'scholarship_application_id' => $application->id,
            'user_id' => $student->id, 'scholarship_program_id' => $program->id,
            'benefits' => 'Tuition assistance', 'recipient_status' => 'active',
        ]);

        Sanctum::actingAs($student);
        $this->postJson("/api/v1/awards/{$award->id}/renewals", [
            'academic_year' => '2027-2028', 'action' => 'submit',
            'form_data' => ['progress_update' => 'Passed all subjects', 'renewal_consent' => true],
        ])->assertCreated();

        $this->assertDatabaseHas('renewal_applications', [
            'scholarship_award_id' => $award->id,
            'status' => 'submitted',
        ]);
    }
}
