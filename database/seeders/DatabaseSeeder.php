<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\EligibilityRule;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRequirement;
use App\Models\Setting;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Fictional demo seeding is disabled outside local/testing. Use app:create-admin.');
        }
        // ── Core accounts (dev-safe; change passwords in production) ──
        $admin = User::firstOrCreate(['email' => 'admin@adfc.edu.ph'], [
            'name' => '[DEMO] ADFC Administrator', 'password' => Hash::make('Admin123!'),
            'role' => 'admin', 'status' => 'active', 'email_verified_at' => now(),
        ]);
        $staff = User::firstOrCreate(['email' => 'staff@adfc.edu.ph'], [
            'name' => 'Demo Scholarship Evaluator', 'password' => Hash::make('Staff123!'),
            'role' => 'staff', 'status' => 'active', 'email_verified_at' => now(),
        ]);
        $student = User::firstOrCreate(['email' => 'student@adfc.edu.ph'], [
            'name' => 'Fictional Student Example', 'password' => Hash::make('Student123!'),
            'role' => 'student', 'status' => 'active', 'email_verified_at' => now(),
        ]);

        StudentProfile::firstOrCreate(['user_id' => $student->id], [
            'student_id_no' => 'ADFC-2024-0001',
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'contact_number' => '09170000001', 'address' => 'Tacloban City, Leyte',
            'course' => 'BSIT', 'year_level' => '2nd', 'enrollment_status' => 'enrolled',
            'profile_completed' => true,
        ]);

        // ── Scholarship programs ──
        $programs = [
            [
                'code' => 'ADFC-MERIT-2026', 'name' => '[DEMO] ADFC Academic Merit Scholarship',
                'description' => 'For students with strong academic performance and good moral character.',
                'category' => 'Merit', 'benefits' => 'Full tuition discount + ₱5,000 book allowance per semester.',
                'eligibility_criteria' => 'Enrolled; no failing grades.',
                'opening_date' => now()->subDays(10)->toDateString(), 'deadline' => now()->addDays(60)->toDateString(),
                'slots' => 50, 'status' => 'published',
                'application_template' => [
                    ['key' => 'academic_achievements', 'label' => 'Academic achievements and honors', 'type' => 'textarea', 'required' => true, 'help' => 'List academic awards, honors, or achievements from the last two academic years.'],
                    ['key' => 'leadership_experience', 'label' => 'Leadership and organization experience', 'type' => 'textarea', 'required' => false, 'help' => 'Include organizations, positions, and service activities.'],
                    ['key' => 'good_moral_declaration', 'label' => 'Good moral declaration available', 'type' => 'select', 'required' => true, 'options' => ['Yes', 'No']],
                ],
                'requirements' => ['Report Card / TOR', 'Certificate of Enrollment', 'Certificate of Good Moral Character'],
                'rules' => [
                    ['enrollment_status', 'Must be enrolled/continuing', '=', 'enrolled,continuing'],
                    ['course', 'Open to all courses', '=', 'all'],
                    ['year_level', 'Open to all year levels', '=', 'all'],
                ],
            ],
            [
                'code' => 'ADFC-NEED-2026', 'name' => '[DEMO] ADFC Financial Assistance Grant',
                'description' => 'For students with demonstrated financial need.',
                'category' => 'Need-Based', 'benefits' => '50% tuition discount per semester.',
                'eligibility_criteria' => 'Enrolled; family income requirements; complete documents.',
                'opening_date' => now()->subDays(5)->toDateString(), 'deadline' => now()->addDays(45)->toDateString(),
                'slots' => 100, 'status' => 'published',
                'application_template' => [
                    ['key' => 'financial_need_statement', 'label' => 'Statement of financial need', 'type' => 'textarea', 'required' => true, 'help' => 'Explain the financial circumstances affecting your ability to continue your studies.'],
                    ['key' => 'income_source', 'label' => 'Primary household income source', 'type' => 'text', 'required' => true],
                    ['key' => 'assistance_requested', 'label' => 'Estimated assistance requested (PHP)', 'type' => 'number', 'required' => false],
                ],
                'requirements' => ['Report Card / TOR', 'Certificate of Enrollment', 'Certificate of Indigency'],
                'rules' => [
                    ['enrollment_status', 'Must be enrolled', '=', 'enrolled,continuing,new,freshman'],
                    ['course', 'Open to all courses', '=', 'all'],
                ],
            ],
            [
                'code' => 'ADFC-ATH-2026', 'name' => '[DEMO] ADFC Athletic Scholarship',
                'description' => 'For varsity athletes representing the college.',
                'category' => 'Athletic', 'benefits' => '75% tuition discount + training allowance.',
                'eligibility_criteria' => 'Varsity member; enrolled; coach endorsement.',
                'opening_date' => now()->subDays(2)->toDateString(), 'deadline' => now()->addDays(30)->toDateString(),
                'slots' => 20, 'status' => 'published',
                'application_template' => [
                    ['key' => 'sport_discipline', 'label' => 'Sport or athletic discipline', 'type' => 'text', 'required' => true],
                    ['key' => 'team_or_event', 'label' => 'Team, league, or event represented', 'type' => 'text', 'required' => true],
                    ['key' => 'athletic_achievements', 'label' => 'Athletic achievements', 'type' => 'textarea', 'required' => true, 'help' => 'List championships, competitions, awards, and dates.'],
                    ['key' => 'coach_name', 'label' => 'Coach or athletic director', 'type' => 'text', 'required' => true],
                ],
                'requirements' => ['Certificate of Enrollment', 'Coach Endorsement Letter'],
                'rules' => [
                    ['enrollment_status', 'Must be enrolled', '=', 'enrolled,continuing'],
                ],
            ],
            [
                'code' => 'ADFC-DRAFT-2026', 'name' => '[DEMO] ADFC Future Leaders (Draft)',
                'description' => 'Draft program — not yet open.',
                'category' => 'Leadership', 'benefits' => 'TBD',
                'eligibility_criteria' => 'TBD',
                'opening_date' => null, 'deadline' => null, 'slots' => 10, 'status' => 'draft',
                'application_template' => [
                    ['key' => 'leadership_experience', 'label' => 'Leadership experience', 'type' => 'textarea', 'required' => false],
                ],
                'requirements' => [], 'rules' => [],
            ],
        ];

        foreach ($programs as $p) {
            $reqs = $p['requirements']; $rules = $p['rules'];
            unset($p['requirements'], $p['rules']);
            $p['created_by'] = $admin->id;
            $program = ScholarshipProgram::firstOrCreate(['code' => $p['code']], $p);
            if (empty($program->application_template) && ! empty($p['application_template'])) {
                $program->update(['application_template' => $p['application_template']]);
            }
            foreach ($reqs as $r) {
                ScholarshipRequirement::firstOrCreate(
                    ['scholarship_program_id' => $program->id, 'name' => $r],
                    ['is_required' => true, 'allowed_types' => 'pdf,jpg,jpeg,png', 'max_size_kb' => 5120]
                );
            }
            foreach ($rules as [$key, $label, $op, $val]) {
                EligibilityRule::firstOrCreate(
                    ['scholarship_program_id' => $program->id, 'rule_key' => $key],
                    ['rule_label' => $label, 'operator' => $op, 'rule_value' => $val, 'is_active' => true]
                );
            }
        }

        Announcement::firstOrCreate(['title' => 'Welcome to the ADFC Scholarship Portal'], [
            'content' => 'Students can now register, complete their profile, and apply for open scholarships online. Watch your notifications for status updates.',
            'audience' => 'all', 'is_published' => true, 'published_at' => now(), 'created_by' => $admin->id,
        ]);

        foreach (['school_name' => 'Asian Development Foundation College', 'academic_year' => '2026-2027'] as $k => $v) {
            Setting::firstOrCreate(['key' => $k], ['value' => $v]);
        }
    }
}
