<?php

namespace Tests\Feature;

use App\Models\{ScholarshipProgram, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Password};
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_requirements_are_accessible_without_a_token_but_drafts_are_private(): void
    {
        $program = ScholarshipProgram::factory()->create(['status' => 'published']);
        $program->requirements()->create(['name' => 'Fictional test requirement', 'allowed_types' => 'pdf', 'max_size_kb' => 1024, 'is_required' => true]);
        $url = '/api/v1/scholarships/'.$program->id.'/requirements';
        $this->getJson($url)->assertOk()->assertJsonPath('data.0.name', 'Fictional test requirement');
        $program->update(['status' => 'draft']);
        $this->getJson($url)->assertNotFound();
        Sanctum::actingAs(User::factory()->create(['role' => 'student']));
        $this->getJson('/api/v1/admin/scholarships/'.$program->id.'/requirements')->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson($url)->assertNotFound();
        $this->getJson('/api/v1/admin/scholarships/'.$program->id.'/requirements')->assertOk();
    }

    public function test_api_auth_errors_are_json_even_without_accept_header(): void
    {
        $this->get('/api/v1/student/profile')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->post('/api/v1/register', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'contact_number']);
    }

    public function test_registration_enforces_password_policy_and_hashes_a_valid_password(): void
    {
        $payload = ['name' => 'Fictional Test', 'email' => 'test@example.test', 'contact_number' => '09171234567', 'terms_accepted' => true];
        foreach (['short', 'onlylowercase', 'NoNumber!', 'NoSymbol123', 'NOLOWER123!'] as $password) {
            $this->postJson('/api/v1/register', $payload + ['password' => $password, 'password_confirmation' => $password])
                ->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->assertDatabaseCount('users', 0);
        $this->postJson('/api/v1/register', $payload + ['password' => 'ValidPassword123!', 'password_confirmation' => 'ValidPassword123!'])
            ->assertCreated()->assertJsonMissingPath('user.password');
        $this->assertTrue(Hash::check('ValidPassword123!', User::first()->password));
    }

    public function test_api_login_normalizes_email_like_registration(): void
    {
        User::factory()->create(['email' => 'student@example.test']);
        $this->postJson('/api/v1/login', ['email' => ' STUDENT@EXAMPLE.TEST ', 'password' => 'password', 'device_name' => 'Test device'])
            ->assertOk()->assertJsonPath('user.email', 'student@example.test');
    }

    public function test_failed_browser_login_never_flashes_password(): void
    {
        $this->from('/login')->post('/login', ['email' => 'nobody@example.test', 'password' => 'SensitivePassword123!'])
            ->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password')
            ->assertSessionHas('_old_input.email', 'nobody@example.test');
    }

    public function test_password_reset_validates_token_and_revokes_existing_access(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.test']);
        $user->createToken('old-device');
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $payload = ['email' => $user->email, 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'];
        $this->postJson('/api/v1/reset-password', $payload + ['token' => 'invalid'])->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $token = Password::createToken($user);
        $this->postJson('/api/v1/reset-password', $payload + ['token' => $token])->assertOk();
        $this->assertTrue(Hash::check('ChangedPassword123!', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->postJson('/api/v1/reset-password', $payload + ['token' => $token])->assertUnprocessable();
    }

    public function test_browser_mutations_require_csrf_tokens(): void
    {
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
        $this->post('/register', [])->assertStatus(419);
        $this->withSession(['_token' => 'test-session-token'])->post('/register', ['_token' => 'test-session-token'])
            ->assertSessionHasErrors('email');
    }

    public function test_demo_passwords_are_not_in_login_html_by_default(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Admin123!')->assertDontSee('Student123!');
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_duplicate_registration_at_database_layer_returns_field_error_without_orphan_account(): void
    {
        $service = app(\App\Services\RegistrationService::class);
        $payload = ['name' => 'Fictional Student', 'email' => 'first@example.test', 'password' => 'Password123!', 'student_id_no' => 'UNIQUE-TEST'];
        $service->register($payload);
        try {
            $service->register(array_replace($payload, ['email' => 'second@example.test']));
            $this->fail('Expected duplicate student number validation.');
        } catch (\Illuminate\Validation\ValidationException $error) {
            $this->assertArrayHasKey('student_id_no', $error->errors());
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('student_profiles', 1);
    }

    public function test_public_directory_renders_when_email_verification_is_enabled(): void
    {
        config(['scholarlink.verify_email' => true]);
        $this->get('/scholarships')->assertOk();
    }

    public function test_wizard_configuration_decodes_once_in_html_attributes(): void
    {
        $template = [['key' => 'goal', 'label' => 'Study "goal" & plans', 'type' => 'text', 'required' => true]];
        $program = ScholarshipProgram::factory()->create(['status' => 'published', 'opening_date' => today()->subDay(), 'deadline' => today()->addDay(), 'application_template' => $template]);
        $program->requirements()->create(['name' => 'Enrollment & grades', 'allowed_types' => 'pdf', 'max_size_kb' => 1024, 'is_required' => true]);
        $response = $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get('/my-applications/create?scholarship_id='.$program->id)->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $option = (new \DOMXPath($dom))->query('//select[@id="programSelect"]/option[@value="'.$program->id.'"]')->item(0);
        $this->assertSame($template, json_decode($option->getAttribute('data-template'), true, flags: JSON_THROW_ON_ERROR));
        $requirements = json_decode($option->getAttribute('data-requirements'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Enrollment & grades', $requirements[0]['name']);
        $this->assertTrue($option->hasAttribute('selected'));
    }

    public function test_browser_draft_autosave_returns_json_before_uploading(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $program = ScholarshipProgram::factory()->create(['status' => 'published', 'opening_date' => today()->subDay(), 'deadline' => today()->addDay()]);
        $application = \App\Models\ScholarshipApplication::factory()->create(['user_id' => $student->id, 'scholarship_program_id' => $program->id]);
        $this->actingAs($student)->postJson('/my-applications/'.$application->id, [
            '_method' => 'PUT', 'scholarship_program_id' => $program->id,
            'action' => 'draft', 'last_saved_step' => 5, 'statement' => 'Saved before upload',
        ])->assertOk()->assertJsonPath('data.id', $application->id);
        $this->assertSame(5, $application->fresh()->last_saved_step);
        $this->assertSame('Saved before upload', $application->fresh()->statement);
    }
}
