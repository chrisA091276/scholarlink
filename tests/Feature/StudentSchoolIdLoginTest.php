<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StudentSchoolIdLoginTest extends TestCase
{
    use RefreshDatabase;

    private function student(?string $schoolId = '00-000042'): User
    {
        return StudentProfile::factory()->create(['student_id_no' => $schoolId])->user()->firstOrFail();
    }

    private function start(User $student, string $url = '/login/student')
    {
        return $this->post($url, ['email' => strtoupper($student->email), 'password' => 'password', 'remember' => 'on']);
    }

    public function test_student_login_requires_credentials_then_matching_school_id(): void
    {
        $student = $this->student();
        $student->forceFill(['remember_token' => null])->save();
        $originalSession = session()->getId();
        $this->start($student)->assertRedirect('/login/student/verify')
            ->assertSessionHas('student_login.user_id', $student->id)->assertSessionMissing('_old_input.password');
        $this->assertGuest();
        $this->assertNotSame($originalSession, session()->getId());
        $this->assertNull($student->fresh()->remember_token);
        $this->assertArrayNotHasKey('password', session('student_login'));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'login']);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login/student/verify')->assertOk()->assertSee('Step 2 of 2')
            ->assertSee($student->email)->assertDontSee('00-000042')->assertDontSee('name="password"', false);
        $beforeVerification = session()->getId();
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])
            ->assertRedirect('/dashboard')->assertSessionMissing('student_login');
        $this->assertAuthenticatedAs($student);
        $this->assertNotSame($beforeVerification, session()->getId());
        $this->assertNotNull($student->fresh()->remember_token);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $student->id, 'action' => 'login']);
    }

    public function test_default_login_alias_and_intended_destination_use_both_steps(): void
    {
        $student = $this->student();
        $this->withSession(['url.intended' => url('/my-applications')]);
        $this->start($student, '/login')->assertRedirect('/login/student/verify');
        $this->assertGuest();
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])->assertRedirect('/my-applications');
        $this->assertAuthenticatedAs($student);
    }

    public function test_steps_cannot_be_skipped_or_account_swapped(): void
    {
        $student = $this->student();
        $other = $this->student('26-000001');
        $this->post('/login/student/verify', [
            'email' => $student->email, 'password' => 'password', 'student_id_no' => '00-000042',
        ])->assertRedirect('/login/student');
        $this->assertGuest();
        $this->start($student);
        $this->post('/login/student/verify', [
            'email' => $other->email, 'user_id' => $other->id, 'student_id_no' => '26-000001',
        ])->assertRedirect('/login/student/verify')->assertSessionHasErrors('student_id_no');
        $this->assertGuest();
        $this->post('/login/student/verify', [
            'email' => $other->email, 'user_id' => $other->id, 'student_id_no' => '00-000042',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($student);
    }

    public function test_wrong_credentials_never_reach_verification_or_reveal_accounts(): void
    {
        $student = $this->student();
        $messages = [];
        foreach ([$student->email, 'unknown@example.test'] as $email) {
            $this->post('/login/student', ['email' => $email, 'password' => 'WrongPassword!'])
                ->assertRedirect('/login/student')->assertSessionHasErrors('email')
                ->assertSessionHas('_old_input.email', $email)->assertSessionMissing('_old_input.password')
                ->assertSessionMissing('student_login');
            $messages[] = session('errors')->first('email');
            $this->assertGuest();
        }
        $this->assertSame($messages[0], $messages[1]);
        $this->get('/login/student/verify')->assertRedirect('/login/student');
    }

    public function test_school_id_requires_the_requested_format_and_never_flashes_passwords(): void
    {
        $student = $this->student();
        $this->start($student);
        foreach (['', '1-123456', '12-12345', 'AA-123456', '12345678'] as $id) {
            $this->post('/login/student/verify', ['student_id_no' => $id, 'password' => 'DoNotStoreThis'])
                ->assertRedirect('/login/student/verify')->assertSessionHasErrors('student_id_no')
                ->assertSessionMissing('_old_input.password');
            $this->assertGuest();
        }
        $this->assertDatabaseMissing('audit_logs', ['action' => 'login']);
    }

    public function test_verification_expires_and_cannot_be_replayed(): void
    {
        $student = $this->student();
        $this->start($student);
        $this->travel(5)->minutes();
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])
            ->assertRedirect('/login/student')->assertSessionMissing('student_login');
        $this->assertGuest();
        $this->travelBack();
        $this->start($student);
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])->assertRedirect('/dashboard');
        $this->post('/logout');
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])->assertRedirect('/login/student');
        $this->assertGuest();
    }

    public function test_back_to_credentials_clears_pending_verification_and_keeps_email(): void
    {
        $student = $this->student();
        $this->start($student);
        $this->get('/login/student')->assertOk()->assertSee($student->email)
            ->assertSee('Step 1 of 2')->assertSessionMissing('student_login');
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])->assertRedirect('/login/student');
        $this->assertGuest();
    }

    public function test_account_changes_invalidate_pending_credentials(): void
    {
        foreach ([['status' => 'inactive'], ['role' => 'staff'], ['password' => 'NewPassword123!'], ['email' => 'changed@example.test']] as $change) {
            $student = $this->student('26-00000'.User::count());
            $this->start($student);
            $student->update($change);
            $this->post('/login/student/verify', ['student_id_no' => $student->studentProfile->student_id_no])
                ->assertRedirect('/login/student')->assertSessionMissing('student_login');
            $this->assertGuest();
        }
    }

    public function test_verification_is_rate_limited_even_after_restarting_step_one(): void
    {
        $student = $this->student();
        $this->start($student);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login/student/verify', ['student_id_no' => '00-999999'])->assertSessionHasErrors('student_id_no');
        }
        $this->get('/login/student');
        $this->start($student);
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])
            ->assertRedirect('/login/student/verify')->assertSessionHasErrors('student_id_no');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('student_id_no'));
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($student);
        $this->assertSame(0, RateLimiter::attempts('student-school-id:'.$student->id.':127.0.0.1'));
    }

    public function test_account_without_school_id_gets_help_instead_of_bypassing_verification(): void
    {
        $student = $this->student(null);
        $this->start($student)->assertRedirect('/login/student/verify');
        $this->get('/login/student/verify')->assertOk()->assertSee('Contact the scholarship office')
            ->assertDontSee('id="studentSchoolIdForm"', false);
        $this->post('/login/student/verify', ['student_id_no' => '00-000042'])->assertSessionHasErrors('student_id_no');
        $this->assertGuest();
        $this->assertNull($student->studentProfile->fresh()->student_id_no);
    }

    public function test_step_one_cannot_be_changed_to_the_old_school_id_login(): void
    {
        $this->student();
        foreach (['/login/student', '/login'] as $url) {
            $this->post($url, ['login_method' => 'school_id', 'student_id_no' => '00-000042', 'password' => 'password'])
                ->assertSessionHasErrors(['email', 'login_method'])->assertSessionMissing('student_login');
            $this->assertGuest();
        }
    }

    public function test_student_always_starts_with_email_and_staff_logins_stay_single_step(): void
    {
        foreach (['/login/student', '/login', '/login/student?method=school_id', '/login/student?method=email'] as $url) {
            $this->get($url)->assertOk()->assertSee('Step 1 of 2')->assertSee('id="loginEmail"', false)
                ->assertDontSee('id="loginSchoolId"', false);
        }
        foreach (['staff', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->get('/login/'.$role)->assertOk()->assertDontSee('Student login progress');
            $this->post('/login/'.$role, ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_api_login_continues_to_require_email(): void
    {
        $student = $this->student();
        $this->postJson('/api/v1/login', ['login_method' => 'school_id', 'student_id_no' => '00-000042', 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/login', ['email' => $student->email, 'password' => 'password'])->assertOk();
    }
}
