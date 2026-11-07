<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! $app->environment('local', 'testing')) throw new RuntimeException('Local QA only.');
// All fixtures stay in the dedicated QA database, never in runtime student data.
config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'scholarlink_qa_20261027_test',
    'database.connections.mysql.url' => null, 'scholarlink.email_notifications' => false]);
\Illuminate\Support\Facades\DB::purge('mysql');
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$tag = bin2hex(random_bytes(5));
$password = 'AppealTest123!';
$admin = \App\Models\User::factory()->create(['role' => 'admin', 'name' => 'Fictional Appeal Reviewer', 'email' => "appeal-admin-{$tag}@example.test", 'password' => $password]);
$student = \App\Models\User::factory()->create(['role' => 'student', 'name' => 'Fictional Appeal Student', 'email' => "appeal-student-{$tag}@example.test", 'password' => $password]);
$schoolId = '26-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT);
\App\Models\StudentProfile::factory()->for($student)->create(['student_id_no' => $schoolId]);
$program = \App\Models\ScholarshipProgram::factory()->create(['code' => 'APPEAL-QA-'.$tag, 'name' => '[TEST] Student Support Scholarship', 'status' => 'published', 'slots' => 5, 'created_by' => $admin->id]);
$application = \App\Models\ScholarshipApplication::create(['application_no' => 'APP-QA-'.$tag, 'user_id' => $student->id, 'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027', 'status' => 'approved']);
$award = \App\Models\ScholarshipAward::create(['award_no' => 'AWD-QA-'.$tag, 'scholarship_application_id' => $application->id, 'user_id' => $student->id, 'scholarship_program_id' => $program->id, 'recipient_status' => 'active', 'benefits' => 'Tuition assistance']);
$dir = storage_path('app/appeal-browser-qa');
if (! is_dir($dir)) mkdir($dir, 0700, true);
file_put_contents($dir.'/fixture.json', json_encode(['admin_email' => $admin->email, 'student_email' => $student->email, 'student_school_id' => $schoolId, 'password' => $password, 'award_id' => $award->id]));
echo "Appeal fixtures created in the isolated QA database.\n";
