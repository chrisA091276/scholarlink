<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'scholarlink_qa_20261027_test') {
    throw new RuntimeException('Browser fixtures may only use the isolated QA database.');
}
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$admin = \App\Models\User::factory()->create(['role' => 'admin']);
$program = \App\Models\ScholarshipProgram::factory()->create([
    'code' => 'BROWSER-'.bin2hex(random_bytes(4)), 'name' => '[TEST] Browser acceptance grant',
    'status' => 'published', 'opening_date' => today()->subDay(), 'deadline' => today()->addDays(5),
    'application_template' => [['key' => 'study_goal', 'label' => 'Study goal', 'type' => 'text', 'required' => true]], 'created_by' => $admin->id,
]);
$program->eligibilityRules()->create(['rule_key' => 'monthly_household_income', 'rule_label' => 'Fictional income ceiling', 'operator' => '<=', 'rule_value' => '25000', 'rule_type' => 'number', 'is_active' => true, 'failure_mode' => 'mandatory']);
$requirement = $program->requirements()->create([
    'name' => '[TEST] Enrollment record', 'description' => 'Fictional browser test document only.',
    'allowed_types' => 'pdf', 'max_size_kb' => 1024, 'is_required' => true,
]);
$dir = storage_path('app/browser-qa');
if (! is_dir($dir)) mkdir($dir, 0700, true);
file_put_contents($dir.'/fixture.json', json_encode(['program_id' => $program->id, 'requirement_id' => $requirement->id]));
file_put_contents($dir.'/fictional-enrollment.pdf', "%PDF-1.4\nFictional browser QA document\n%%EOF");
echo "Fictional browser fixtures ready in isolated QA database.\n";
