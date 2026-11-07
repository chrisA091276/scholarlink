<?php

namespace Tests\Feature;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtendedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function program(User $admin): ScholarshipProgram
    {
        return ScholarshipProgram::create([
            'code' => 'PX-' . uniqid(), 'name' => 'PX', 'category' => 'Merit',
            'status' => 'published', 'opening_date' => now()->subDay()->toDateString(),
            'deadline' => now()->addDays(10)->toDateString(), 'created_by' => $admin->id,
        ]);
    }

    private function app(User $student, ScholarshipProgram $program, string $status = 'submitted'): ScholarshipApplication
    {
        return ScholarshipApplication::create([
            'application_no' => 'APP-' . uniqid(), 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027', 'status' => $status,
        ]);
    }

    public function test_admin_can_assign_and_staff_filters_mine(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $student = User::factory()->create(['role' => 'student']);
        $a1 = $this->app($student, $this->program($admin));
        $a2 = $this->app($student, $this->program($admin));

        $this->actingAs($admin)->post("/applications/{$a1->id}/assign", ['assigned_to' => $staff->id])->assertRedirect();
        $this->assertEquals($staff->id, $a1->fresh()->assigned_to);

        $res = $this->actingAs($staff)->get('/applications?mine=1');
        $res->assertOk()->assertSee($a1->application_no)->assertDontSee($a2->application_no);
    }

    public function test_rejection_requires_recorded_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $app = $this->app($student, $this->program($admin), 'recommended_rejection');

        $this->actingAs($admin)->post("/applications/{$app->id}/status", ['to_status' => 'rejected'])
            ->assertSessionHasErrors('remarks');
        $this->assertEquals('recommended_rejection', $app->fresh()->status);

        $this->actingAs($admin)->post("/applications/{$app->id}/status", ['to_status' => 'rejected', 'remarks' => 'Incomplete grades'])
            ->assertRedirect();
        $this->assertEquals('rejected', $app->fresh()->status);
    }

    public function test_student_cannot_access_other_students_documents(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $s1 = User::factory()->create(['role' => 'student']);
        $s2 = User::factory()->create(['role' => 'student']);
        $app = $this->app($s1, $this->program($admin), 'draft');
        $doc = $app->documents()->create([
            'document_name' => 'Card', 'file_path' => 'documents/x.pdf',
            'file_type' => 'pdf', 'file_size' => 10,
        ]);
        $this->actingAs($s2)->get("/applications/{$app->id}/documents/{$doc->id}/download")->assertForbidden();
        $this->actingAs($s2)->get('/my-applications/' . $app->id)->assertForbidden();
    }

    public function test_account_update_and_password_change(): void
    {
        $u = User::factory()->create(['role' => 'staff']);
        $this->actingAs($u)->get('/account')->assertOk();
        $this->actingAs($u)->put('/account', ['name' => 'New Name', 'email' => $u->email])->assertRedirect();
        $this->assertEquals('New Name', $u->fresh()->name);

        $this->actingAs($u)->put('/account/password', [
            'current_password' => 'wrong', 'password' => 'NewPassword1!', 'password_confirmation' => 'NewPassword1!',
        ])->assertSessionHasErrors('current_password');
        $this->actingAs($u)->put('/account/password', [
            'current_password' => 'password', 'password' => 'NewPassword1!', 'password_confirmation' => 'NewPassword1!',
        ])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_pdf_export_for_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $this->app($student, $this->program($admin));

        $res = $this->actingAs($admin)->get('/reports?export=pdf');
        $res->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($student)->get('/reports?export=pdf')->assertForbidden();
    }

    public function test_new_module_pages_render_with_correct_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $student = User::factory()->create(['role' => 'student']);

        foreach (['/student-records', '/verifications', '/screening', '/recommendations'] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }
        foreach (['/evaluations', '/requirements-manage', '/eligibility-criteria'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
            $this->actingAs($staff)->get($url)->assertForbidden();
        }

        foreach (['/my-documents', '/eligibility-checklist', '/my-renewals', '/account'] as $url) {
            $this->actingAs($student)->get($url)->assertOk();
        }
        $this->actingAs($student)->get('/student-records')->assertForbidden();
        $this->actingAs($student)->get('/evaluations')->assertForbidden();
        $this->actingAs($staff)->get('/evaluations')->assertForbidden();
    }
}
