<?php

namespace Tests\Feature;

use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_with_role_choice(): void
    {
        $res = $this->post('/register', [
            'name' => 'Test Student', 'email' => 's@test.ph',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'role' => 'student', 'contact_number' => '09171234567', 'terms_accepted' => '1',
            'student_id_no' => '00-000042',
        ]);
        $res->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 's@test.ph', 'role' => 'student']);
    }

    public function test_public_registration_cannot_grant_staff_or_admin_roles(): void
    {
        foreach (['staff', 'admin'] as $role) {
            $this->post('/logout');
            auth()->logout();
            $res = $this->post('/register', [
                'name' => ucfirst($role), 'email' => "{$role}@test.ph",
                'password' => 'Password123!', 'password_confirmation' => 'Password123!',
                'role' => $role, 'contact_number' => '09171234567', 'terms_accepted' => '1',
                'student_id_no' => $role === 'staff' ? '26-000001' : '26-000002',
            ]);
            $res->assertRedirect('/dashboard');
            $this->assertDatabaseHas('users', ['email' => "{$role}@test.ph", 'role' => 'student']);
            auth()->logout();
        }
    }

    public function test_browser_signup_requires_a_school_id_compatible_with_student_login(): void
    {
        $payload = [
            'first_name' => 'Fictional', 'last_name' => 'Student', 'email' => 'signup@example.test',
            'contact_number' => '09171234567', 'password' => 'Password123!',
            'password_confirmation' => 'Password123!', 'terms_accepted' => '1',
        ];
        foreach (['', '12345678', 'ADFC-2024-0001', '12-12345'] as $id) {
            $this->from('/register')->post('/register', $payload + ['student_id_no' => $id])
                ->assertRedirect('/register')->assertSessionHasErrors('student_id_no')
                ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
            $this->assertGuest();
        }
        $this->assertDatabaseCount('users', 0);
        $this->post('/register', $payload + ['student_id_no' => '00-000042'])->assertRedirect('/dashboard');
        $student = User::where('email', $payload['email'])->firstOrFail();
        $this->assertSame('00-000042', $student->studentProfile->student_id_no);
        $this->post('/logout');
        $this->post('/login/student', ['email' => $payload['email'], 'password' => $payload['password']])
            ->assertRedirect('/login/student/verify');
        $this->assertGuest();
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($student);
    }

    public function test_signup_errors_preserve_details_and_consent_but_never_passwords(): void
    {
        \App\Models\StudentProfile::factory()->create(['student_id_no' => '00-000042']);
        $payload = [
            'first_name' => 'Fictional', 'last_name' => 'Applicant', 'email' => 'new-signup@example.test',
            'contact_number' => '09171234567', 'student_id_no' => '00-000042',
            'password' => 'Password123!', 'password_confirmation' => 'Mismatch123!', 'terms_accepted' => '1',
        ];
        $this->from('/register')->post('/register', $payload)->assertRedirect('/register')
            ->assertSessionHasErrors(['student_id_no', 'password'])
            ->assertSessionHas('_old_input.first_name', 'Fictional')->assertSessionHas('_old_input.terms_accepted', '1')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->get('/register')->assertOk()->assertSee('studentNumber-error')
            ->assertSee('studentNumber-help')->assertSee('regPw-error')->assertSee('regPw-help')
            ->assertDontSee('value="Password123!"', false)->assertDontSee('value="Mismatch123!"', false);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_users_redirect_to_role_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('Student Dashboard');
    }

    public function test_staff_dashboard_renders_with_staff_safe_links(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Scholarship Staff Dashboard')
            ->assertSee('Open recommendations')
            ->assertSee(route('scholarships.public'), false)
            ->assertDontSee(route('evaluations.index'), false);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $u = User::factory()->create(['role' => 'student', 'status' => 'inactive']);
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_student_cannot_access_admin_pages(): void
    {
        $s = User::factory()->create(['role' => 'student']);
        $this->actingAs($s)->get('/users')->assertForbidden();
        $this->actingAs($s)->get('/reports')->assertForbidden();
        $this->actingAs($s)->get('/audit-logs')->assertForbidden();
    }

    public function test_staff_cannot_record_final_decision(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $program = ScholarshipProgram::create([
            'code' => 'T1', 'name' => 'T', 'category' => 'General', 'status' => 'published',
            'opening_date' => now()->subDay()->toDateString(), 'deadline' => now()->addDay()->toDateString(),
            'created_by' => $admin->id,
        ]);
        $student = User::factory()->create(['role' => 'student']);
        $app = \App\Models\ScholarshipApplication::create([
            'application_no' => 'APP-X1', 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027',
            'status' => 'recommended_approval',
        ]);
        $this->actingAs($staff)->post("/applications/{$app->id}/status", ['to_status' => 'approved'])
            ->assertSessionHas('error');
        $this->assertEquals('recommended_approval', $app->fresh()->status);
    }

    public function test_each_role_has_a_separate_login_page_and_switcher(): void
    {
        foreach (['student' => 'Student', 'staff' => 'Staff', 'admin' => 'Administrator'] as $role => $label) {
            $response = $this->get('/login/'.$role)->assertOk()
                ->assertSee('<h1>'.$label.' login</h1>', false)
                ->assertSee('action="'.route('login.role.submit', ['role' => $role]).'"', false)
                ->assertSee('Switch role')->assertDontSee('login-role')->assertDontSee('panelSignup');

            foreach (User::ROLES as $destination) {
                $response->assertSee(route('login.role', ['role' => $destination]), false);
            }

            if ($role === 'student') {
                $response->assertSee('Create an account');
            } else {
                $response->assertDontSee('Create an account');
            }
        }

        $this->get('/login')->assertOk()->assertSee('<h1>Student login</h1>', false);
        $this->get('/register')->assertOk()->assertSee('panelSignup')->assertDontSee('panelLogin');
        $this->get('/login/unknown')->assertNotFound();
        $this->post('/login/unknown', [])->assertNotFound();
    }

    public function test_each_role_can_login_through_its_own_page(): void
    {
        foreach (User::ROLES as $role) {
            $user = User::factory()->create(['role' => $role]);
            if ($role === 'student') {
                \App\Models\StudentProfile::factory()->for($user)->create(['student_id_no' => '26-000001']);
            }
            $this->post('/login/'.$role, ['email' => $user->email, 'password' => 'password', 'remember' => 'on'])
                ->assertRedirect($role === 'student' ? '/login/student/verify' : '/dashboard');
            if ($role === 'student') {
                $this->assertGuest();
                $this->post('/login/student/verify', ['student_id_no' => '26-000001'])->assertRedirect('/dashboard');
            }
            $this->assertAuthenticatedAs($user);
            $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'login']);
            auth()->logout();
        }
    }

    public function test_wrong_role_is_rejected_even_when_submitted_role_is_tampered_with(): void
    {
        foreach (User::ROLES as $accountRole) {
            $user = User::factory()->create(['role' => $accountRole, 'remember_token' => null]);
            foreach (array_diff(User::ROLES, [$accountRole]) as $portalRole) {
                $this->post('/login/'.$portalRole, [
                    'email' => $user->email, 'password' => 'password',
                    'login_role' => $accountRole, 'remember' => 'on',
                ])->assertRedirect('/login/'.$portalRole)->assertSessionHasErrors('email')
                    ->assertSessionHas('_old_input.email', $user->email)
                    ->assertSessionMissing('_old_input.password');
                $this->assertGuest();
                $this->assertNull($user->fresh()->remember_token);
            }
        }
        $this->assertDatabaseMissing('audit_logs', ['action' => 'login']);
    }

    public function test_default_login_only_accepts_student_accounts(): void
    {
        foreach (['staff', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->post('/login', ['email' => $user->email, 'password' => 'password', 'login_role' => $role])
                ->assertRedirect('/login/student')->assertSessionHasErrors('email');
            $this->assertGuest();
        }

        $student = User::factory()->create(['role' => 'student']);
        \App\Models\StudentProfile::factory()->for($student)->create(['student_id_no' => '26-000001']);
        $this->post('/login', ['email' => $student->email, 'password' => 'password'])->assertRedirect('/login/student/verify');
        $this->assertGuest();
        $this->post('/login/student/verify', ['student_id_no' => '26-000001'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($student);
    }

    public function test_failed_login_stays_on_its_portal_and_inactive_accounts_are_rejected(): void
    {
        foreach (User::ROLES as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'inactive']);
            $this->post('/login/'.$role, ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect('/login/'.$role)->assertSessionHasErrors('email');
            $this->assertGuest();

            $this->from('/login/'.$role)->post('/login/'.$role, ['email' => 'invalid', 'password' => 'password'])
                ->assertRedirect('/login/'.$role)->assertSessionHasErrors('email');
        }
    }
}
