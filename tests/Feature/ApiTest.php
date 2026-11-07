<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\AdfcFormFixture;

    private function makeProgram(User $admin, array $overrides = []): ScholarshipProgram
    {
        return ScholarshipProgram::create(array_merge([
            'code' => 'API-' . fake()->unique()->numberBetween(1000, 9999),
            'name' => 'API Test Grant',
            'category' => 'Merit',
            'status' => 'published',
            'opening_date' => now()->subDay()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
            'slots' => 10,
            'created_by' => $admin->id,
        ], $overrides));
    }

    private function makeApplication(User $student, ScholarshipProgram $program, array $overrides = []): ScholarshipApplication
    {
        return ScholarshipApplication::create(array_merge([
            'application_no' => 'APP-API-' . fake()->unique()->numberBetween(1000, 9999),
            'user_id' => $student->id,
            'scholarship_program_id' => $program->id,
            'academic_year' => '2026-2027',
            'status' => 'draft',
            'enrollment_status' => 'enrolled',
        ], $overrides));
    }

    public function test_register_returns_token_and_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'API Student',
            'email' => 'api-student@test.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'student',
            'contact_number' => '09171234567', 'terms_accepted' => true,
            'device_name' => 'phpunit',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'api-student@test.ph')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email', 'role', 'status'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'api-student@test.ph', 'role' => 'student']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_returns_token_and_enforces_role_and_status(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        $inactive = User::factory()->create(['role' => 'student', 'status' => 'inactive']);

        $this->postJson('/api/login', [
            'email' => $student->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/login', [
            'email' => $student->email,
            'password' => 'password',
            'login_role' => 'admin',
        ])->assertStatus(422);

        $this->postJson('/api/login', [
            'email' => $inactive->email,
            'password' => 'password',
        ])->assertForbidden();
    }

    public function test_api_requires_authentication_and_blocks_wrong_roles(): void
    {
        $this->getJson('/api/my-applications')->assertUnauthorized();

        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        Sanctum::actingAs($student);

        $this->getJson('/api/applications')->assertForbidden();
        $this->postJson('/api/scholarships', [])->assertForbidden();
    }

    public function test_student_can_apply_and_duplicate_is_prevented(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        $program = $this->makeProgram($admin);

        Sanctum::actingAs($student);

        $payload = [
            'scholarship_program_id' => $program->id,
            'academic_year' => '2026-2027',
            'enrollment_status' => 'enrolled',
            'statement' => 'I need this scholarship.',
            'action' => 'draft', 'adfc_form' => $this->adfcAnswers(),
        ];

        $created = $this->postJson('/api/my-applications', $payload)->assertCreated()->assertJsonPath('data.status', 'draft');
        $application = ScholarshipApplication::findOrFail($created->json('data.id'));
        $this->attachAdfcDocuments($application);
        $this->postJson('/api/my-applications/'.$application->id.'/submit', ['declaration'=>true])->assertOk()->assertJsonPath('data.status','submitted');

        $this->assertDatabaseHas('scholarship_applications', [
            'user_id' => $student->id,
            'scholarship_program_id' => $program->id,
            'status' => 'submitted',
        ]);

        $this->postJson('/api/my-applications', $payload)->assertStatus(422);
    }

    public function test_student_can_submit_and_withdraw_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        $program = $this->makeProgram($admin);
        $application = $this->makeApplication($student, $program);

        Sanctum::actingAs($student);

        $this->postJson("/api/my-applications/{$application->id}/submit", ['declaration' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->postJson("/api/my-applications/{$application->id}/withdraw")
            ->assertOk()
            ->assertJsonPath('data.status', 'withdrawn');
    }

    public function test_student_can_upload_document_and_staff_can_verify(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        $program = $this->makeProgram($admin);
        $application = $this->makeApplication($student, $program);

        Sanctum::actingAs($student);

        $response = $this->post("/api/applications/{$application->id}/documents", [
            'document_name' => 'Transcript of Records',
            'file' => UploadedFile::fake()->create('transcript.pdf', 120, 'application/pdf'),
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()->assertJsonPath('data.verification_status', 'pending');
        $documentId = $response->json('data.id');

        $this->assertDatabaseHas('application_documents', [
            'id' => $documentId,
            'scholarship_application_id' => $application->id,
            'verification_status' => 'pending',
        ]);

        $application->update(['status' => 'submitted']);
        Sanctum::actingAs($admin);

        $this->postJson("/api/applications/{$application->id}/documents/{$documentId}/verify", [
            'verification_status' => 'verified',
            'verification_notes' => 'Looks good.',
        ])->assertOk()->assertJsonPath('data.verification_status', 'verified');
    }

    public function test_admin_can_crud_scholarship_program(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $created = $this->postJson('/api/scholarships', [
            'code' => 'API-CRUD-1',
            'name' => 'API CRUD Grant',
            'category' => 'Merit',
            'status' => 'draft',
            'opening_date' => now()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
            'slots' => 5,
        ])->assertCreated()->assertJsonPath('data.code', 'API-CRUD-1');

        $id = $created->json('data.id');

        $this->putJson("/api/scholarships/{$id}", [
            'code' => 'API-CRUD-1',
            'name' => 'API CRUD Grant Updated',
            'category' => 'Merit',
            'status' => 'published',
            'opening_date' => now()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
            'slots' => 8,
        ])->assertOk()->assertJsonPath('data.name', 'API CRUD Grant Updated');

        $this->deleteJson("/api/scholarships/{$id}")->assertOk();
        $this->assertDatabaseMissing('scholarship_programs', ['id' => $id]);
    }

    public function test_admin_approval_creates_award(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        $program = $this->makeProgram($admin);
        $application = $this->makeApplication($student, $program, ['status' => 'recommended_approval']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/applications/{$application->id}/status", [
            'to_status' => 'approved',
            'remarks' => 'Approved by API test.',
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('scholarship_awards', [
            'scholarship_application_id' => $application->id,
            'user_id' => $student->id,
            'recipient_status' => 'active',
        ]);
    }

    public function test_staff_cannot_record_final_decision_via_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        $program = $this->makeProgram($admin);
        $application = $this->makeApplication($student, $program, [
            'status' => 'recommended_approval',
            'assigned_to' => $staff->id,
        ]);

        Sanctum::actingAs($staff);

        $this->postJson("/api/applications/{$application->id}/status", [
            'to_status' => 'approved',
        ])->assertForbidden();

        $this->assertDatabaseCount('scholarship_awards', 0);
    }

    public function test_notifications_and_logout(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $student->studentProfile()->create(['date_of_birth' => '2004-01-01', 'contact_number' => '+639171234567', 'address' => 'Fictional test address', 'student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);
        $notification = AppNotification::create([
            'user_id' => $student->id,
            'title' => 'Welcome',
            'message' => 'Hello there',
        ]);

        $token = $student->createToken('phpunit')->plainTextToken;

        $this->withToken($token)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id);

        $this->withToken($token)->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
