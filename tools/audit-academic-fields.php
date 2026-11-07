<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$matches = fn ($text) => preg_match('/gpa|gwa|grade.point.average|weighted.average|academic.average/i', (string) $text);
$programs = Illuminate\Support\Facades\DB::table('scholarship_programs')->get(['id', 'code', 'eligibility_criteria', 'application_template', 'renewal_template']);
$rules = Illuminate\Support\Facades\DB::table('eligibility_rules')->get(['id', 'scholarship_program_id', 'rule_key', 'rule_label', 'is_active']);
echo json_encode([
    'programs' => $programs->filter(fn ($program) => $matches(json_encode($program)))->values(),
    'rules' => $rules->filter(fn ($rule) => $matches(json_encode($rule)))->values(),
    'settings' => Illuminate\Support\Facades\DB::table('settings')->pluck('value', 'key')->filter(fn ($value, $key) => $matches($key)),
    'historical_screenings' => Illuminate\Support\Facades\DB::table('eligibility_results')->where(fn ($q) => $q->where('reasons', 'like', '%GPA%')->orWhere('reasons', 'like', '%GWA%'))->count(),
], JSON_PRETTY_PRINT).PHP_EOL;
