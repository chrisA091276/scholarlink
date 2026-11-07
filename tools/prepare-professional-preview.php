<?php

// Isolated browser fixture: never connects to the configured runtime database.
$directory = dirname(__DIR__).'/storage/app/professional-preview';
if (! is_dir($directory)) mkdir($directory, 0700, true);
$database = $directory.'/preview.sqlite';
if (! is_file($database)) touch($database);
foreach (['APP_ENV'=>'local','APP_URL'=>'http://127.0.0.1:8017','DB_CONNECTION'=>'sqlite','DB_DATABASE'=>$database,
    'DB_URL'=>'','SESSION_DRIVER'=>'file','CACHE_STORE'=>'array','MAIL_MAILER'=>'log'] as $key=>$value) {
    putenv($key.'='.$value); $_ENV[$key] = $_SERVER[$key] = $value;
}
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../tests/Support/AdfcFormFixture.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $database) throw new RuntimeException('Unsafe preview database configuration.');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force'=>true]);
$fixture = new class {
    use Tests\Support\AdfcFormFixture;
    public function answers(): array { return $this->adfcAnswers(['other_assistance'=>'yes','other_assistance_details'=>'Fictional local study allowance']); }
};
$users = [];
foreach (['admin'=>'Maria Santos','staff'=>'Daniel Reyes','student'=>'Isabella Cruz'] as $role=>$name) {
    $users[$role] = App\Models\User::updateOrCreate(['email'=>$role.'@professional-preview.example.test'],[
        'name'=>$name,'role'=>$role,'status'=>'active','password'=>Illuminate\Support\Facades\Hash::make('PreviewOnly123!'),'email_verified_at'=>now(),
    ]);
}
$student = $users['student'];
$student->studentProfile()->updateOrCreate(['user_id'=>$student->id], ['student_id_no'=>'26-000001','first_name'=>'Isabella','last_name'=>'Cruz','date_of_birth'=>'2004-02-15','contact_number'=>'09171234567','address'=>'Fictional Barangay, Tacloban City','course'=>'BS Information Technology','year_level'=>'2nd','profile_completed'=>true]);
$programs = [];
foreach (['TDP'=>'Tulong Dunong Program','TES'=>'Tertiary Education Subsidy','MERIT'=>'ADFC Academic Scholarship'] as $code=>$name) {
    $programs[] = App\Models\ScholarshipProgram::updateOrCreate(['code'=>'PREVIEW-'.$code],['name'=>$name,'category'=>'Student financial assistance','description'=>'Fictional preview program.','benefits'=>'Educational financial assistance','status'=>'published','opening_date'=>today()->subDays(10),'deadline'=>today()->addDays(20),'created_by'=>$users['admin']->id]);
}
$draft = App\Models\ScholarshipApplication::updateOrCreate(['application_no'=>'PREVIEW-DRAFT-001'],[
    'user_id'=>$student->id,'scholarship_program_id'=>$programs[0]->id,'academic_year'=>'2026-2027','status'=>'draft','enrollment_status'=>'enrolled',
    'form_version'=>App\Services\AdfcApplicationForm::VERSION,'adfc_form'=>array_replace($fixture->answers(),['first_name'=>'Isabella','last_name'=>'Cruz','printed_name'=>'Isabella Cruz']), 'last_saved_step'=>1,
]);
foreach (['under_review','recommended_approval','submitted'] as $index=>$status) {
    $person = App\Models\User::updateOrCreate(['email'=>'applicant'.$index.'@professional-preview.example.test'], ['name'=>['Alex Rivera','Sofia Mendoza','Miguel Flores'][$index],'password'=>Illuminate\Support\Facades\Hash::make('PreviewOnly123!'),'role'=>'student','status'=>'active']);
    $application = App\Models\ScholarshipApplication::updateOrCreate(['application_no'=>'PREVIEW-REVIEW-00'.($index+1)],[
        'user_id'=>$person->id,'scholarship_program_id'=>$programs[$index]->id,'academic_year'=>'2026-2027','status'=>$status,'assigned_to'=>$index < 2 ? $users['staff']->id : null,
        'form_version'=>App\Services\AdfcApplicationForm::VERSION,'adfc_form'=>$fixture->answers(),'submitted_at'=>now()->subDays(3-$index),'enrollment_status'=>'enrolled',
        'form_review'=>$status==='recommended_approval' ? ['applicant'=>true,'school'=>true,'family'=>true,'assistance'=>true,'consent'=>true,'qualification'=>'meets','reviewer_id'=>$users['staff']->id,'reviewer_name'=>$users['staff']->name,'notes'=>'Fictional preview assessment.','reviewed_at'=>now()->toIso8601String()] : null,
    ]);
    foreach (['id_photo','enrollment','indigency'] as $category) $application->documents()->updateOrCreate(['document_category'=>$category],['document_name'=>config('adfc_form.documents.'.$category),'storage_disk'=>'local','file_path'=>'preview/absent-fixture.pdf','file_type'=>'pdf','file_size'=>100,'verification_status'=>$status==='recommended_approval' ? 'verified' : 'pending']);
}
file_put_contents($directory.'/fixture.json',json_encode(['draft_id'=>$draft->id,'review_id'=>App\Models\ScholarshipApplication::where('application_no','PREVIEW-REVIEW-001')->value('id')]));
echo 'Prepared isolated preview database and fictional role accounts.'.PHP_EOL;
