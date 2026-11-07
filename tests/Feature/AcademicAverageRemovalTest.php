<?php

namespace Tests\Feature;

use App\Models\RenewalApplication;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ApplicationTemplateService;
use App\Services\EligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicAverageRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_grades_are_hidden_from_pages_downloads_and_api_responses(): void
    {
        $application = ScholarshipApplication::factory()->create([
            'form_data' => ['gpa' => 2.25, 'current_gwa' => 2.1, 'academic_average' => 2.2, 'service_hours' => 40],
        ]);
        $profile = StudentProfile::factory()->create(['user_id' => $application->user_id]);
        DB::table('student_profiles')->where('id', $profile->id)->update(['gpa' => 2.25]);
        DB::table('scholarship_applications')->where('id', $application->id)->update(['gpa' => 2.25]);
        $application->refresh();
        $this->assertArrayNotHasKey('gpa', $profile->fresh()->toArray());
        $this->assertArrayNotHasKey('gpa', $application->toArray());
        $this->assertSame(['service_hours' => 40], $application->form_data);
        $this->assertArrayHasKey('gpa', json_decode($application->getRawOriginal('form_data'), true));

        $this->actingAs($application->student);
        $this->getJson('/api/v1/student/profile')->assertOk()->assertJsonMissingPath('data.gpa');
        $this->getJson('/api/v1/student/applications/'.$application->id)->assertOk()
            ->assertJsonMissingPath('data.gpa')->assertJsonPath('data.form_data', ['service_hours' => 40]);
        foreach (['/profile', '/profile/edit', '/my-applications/'.$application->id, '/my-applications/'.$application->id.'/edit', '/my-renewals'] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertDoesNotMatchRegularExpression('/\b(?:GWA|GPA)\b/i', $response->getContent());
        }
        $html = view('applications.professional-pdf', ['application' => $application->load('student.studentProfile', 'program', 'documents')])->render();
        $this->assertDoesNotMatchRegularExpression('/\b(?:GWA|GPA|Academic average)\b/i', $html);
        $this->assertStringContainsString('Service hours', $html);

        $renewal = new RenewalApplication;
        $renewal->setRawAttributes(['gpa' => 2.25, 'form_data' => '{"gpa":2.25,"gwa":2.1,"service_hours":40}']);
        $this->assertArrayNotHasKey('gpa', $renewal->toArray());
        $this->assertSame(['service_hours' => 40], $renewal->toArray()['form_data']);
    }

    public function test_profile_and_application_requests_ignore_removed_grade_fields(): void
    {
        $profile = StudentProfile::factory()->create();
        $student = $profile->user;
        $program = ScholarshipProgram::factory()->create();
        $this->actingAs($student)->putJson('/api/v1/student/profile', array_merge($profile->toArray(), [
            'date_of_birth' => '2004-01-01', 'gpa' => 2.25, 'gwa' => 2.1,
        ]))->assertOk()->assertJsonMissingPath('data.gpa');
        $this->assertNull($profile->fresh()->getRawOriginal('gpa'));
        $response = $this->postJson('/api/v1/student/applications', [
            'scholarship_program_id' => $program->id, 'action' => 'draft',
            'gpa' => 2.25, 'gwa' => 2.1, 'form_data' => ['gpa' => 2.25],
        ])->assertCreated()->assertJsonMissingPath('data.gpa');
        $saved = ScholarshipApplication::findOrFail($response->json('data.id'));
        $this->assertNull($saved->getRawOriginal('gpa'));
        $this->assertSame([], $saved->form_data);
    }

    public function test_old_grade_rules_do_not_affect_eligibility_but_enrollment_still_does(): void
    {
        $application = ScholarshipApplication::factory()->create();
        $program = $application->program;
        $program->eligibilityRules()->create([
            'rule_key' => 'min_gpa', 'rule_label' => 'Minimum GPA', 'rule_type' => 'number',
            'operator' => '>=', 'rule_value' => '3', 'failure_mode' => 'mandatory', 'is_active' => true,
        ]);
        $program->eligibilityRules()->create([
            'rule_key' => 'enrollment_status', 'rule_label' => 'Current enrollment', 'rule_type' => 'text',
            'operator' => '=', 'rule_value' => 'enrolled', 'failure_mode' => 'mandatory', 'is_active' => true,
        ]);
        $service = app(EligibilityService::class);
        $result = $service->screen($application);
        $this->assertSame('eligible', $result['result']);
        $this->assertStringNotContainsString('GPA', implode(' ', $result['reasons']));
        $application->enrollment_status = 'returning';
        $this->assertSame('not_eligible', $service->screen($application)['result']);
    }

    public function test_application_and_renewal_templates_no_longer_require_grade_answers(): void
    {
        $fields = [
            ['key' => 'gpa', 'label' => 'GPA', 'type' => 'number', 'required' => true],
            ['key' => 'average', 'label' => 'General Weighted Average', 'type' => 'number', 'required' => true],
            ['key' => 'service_hours', 'label' => 'Service hours', 'type' => 'number', 'required' => true],
        ];
        $program = ScholarshipProgram::factory()->create(['application_template' => $fields, 'renewal_template' => $fields]);
        $templates = app(ApplicationTemplateService::class);
        foreach (['application_template', 'renewal_template'] as $column) {
            $this->assertSame(['service_hours'], array_column($templates->fields($program, $column), 'key'));
            $this->assertSame(['service_hours' => 40], $templates->validate($program, ['service_hours' => 40, 'gpa' => 2.25], true, $column));
        }
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('scholarships.rules.store', $program), [
            'rule_key' => 'min_gpa', 'rule_label' => 'Minimum GPA', 'operator' => '>=', 'rule_value' => '3',
        ])->assertSessionHasErrors(['rule_key', 'rule_label']);
    }

    public function test_upgrade_removes_grade_configuration_and_preserves_other_rules(): void
    {
        $program = ScholarshipProgram::factory()->create([
            'eligibility_criteria' => 'GPA of 2.0 or better; enrolled; no failing grades.',
            'application_template' => [['key' => 'gwa', 'label' => 'GWA'], ['key' => 'service_hours', 'label' => 'Service hours']],
            'renewal_template' => [['key' => 'average', 'label' => 'Grade Point Average']],
        ]);
        foreach (['min_gpa', 'enrollment_status'] as $key) {
            $program->eligibilityRules()->create(['rule_key' => $key, 'rule_label' => $key, 'operator' => '=', 'rule_value' => '1', 'is_active' => true]);
        }
        DB::table('settings')->insert([['key' => 'min_gpa_default', 'value' => '2.5'], ['key' => 'school_name', 'value' => 'ADFC']]);
        $migration = require database_path('migrations/2026_11_05_000002_retire_academic_average_fields.php');
        $migration->up();
        $program->refresh();
        $this->assertSame(['service_hours'], array_column($program->application_template, 'key'));
        $this->assertSame([], $program->renewal_template);
        $this->assertSame('Enrolled; no failing grades.', $program->eligibility_criteria);
        $this->assertDatabaseMissing('eligibility_rules', ['rule_key' => 'min_gpa']);
        $this->assertDatabaseHas('eligibility_rules', ['rule_key' => 'enrollment_status']);
        $this->assertDatabaseMissing('settings', ['key' => 'min_gpa_default']);
        $this->assertDatabaseHas('settings', ['key' => 'school_name', 'value' => 'ADFC']);
    }
}
