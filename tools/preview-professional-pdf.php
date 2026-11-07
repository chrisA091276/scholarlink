<?php

// Builds fictional PDF examples in memory; never reads or updates application records.
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../tests/Support/AdfcFormFixture.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fixture = new class {
    use Tests\Support\AdfcFormFixture;
    public function answers(): array
    {
        return $this->adfcAnswers(['first_name' => 'Isabella', 'last_name' => 'Cruz', 'middle_name' => 'Reyes',
            'printed_name' => 'Isabella Reyes Cruz', 'course' => 'BS Information Technology',
            'other_assistance' => 'yes', 'other_assistance_details' => 'Fictional local study allowance']);
    }
};
$student = new App\Models\User(['name' => 'Isabella Reyes Cruz', 'email' => 'isabella@example.test']);
$student->setRelation('studentProfile', new App\Models\StudentProfile(['first_name' => 'Isabella', 'last_name' => 'Cruz']));
$program = new App\Models\ScholarshipProgram(['name' => 'Tulong Dunong Program', 'application_template' => []]);
$program->setRelation('requirements', collect());
$application = new App\Models\ScholarshipApplication([
    'application_no' => 'PREVIEW-ADFC-FORM', 'form_version' => App\Services\AdfcApplicationForm::VERSION,
    'adfc_form' => $fixture->answers(), 'academic_year' => '2026-2027', 'status' => 'draft', 'enrollment_status' => 'enrolled',
]);
$application->setRelation('student', $student)->setRelation('program', $program)->setRelation('documents', collect());
$directory = storage_path('app/professional-pdf-preview');
if (! is_dir($directory)) mkdir($directory, 0700, true);
$results = [];
foreach (['standard', 'reviewed', 'long-answers'] as $example) {
    $record = clone $application;
    if ($example === 'reviewed') {
        $record->form_review = ['reviewer_name' => 'Daniel Reyes', 'reviewed_at' => now()->toIso8601String(),
            'qualification' => 'meets', 'notes' => 'Fictional example: checked the saved form and supporting documents.'];
        $record->setRelation('documents', collect([
            new App\Models\ApplicationDocument(['document_name' => 'Enrollment certificate', 'document_category' => 'enrollment', 'verification_status' => 'verified']),
            new App\Models\ApplicationDocument(['document_name' => 'Certificate of Indigency', 'document_category' => 'indigency', 'verification_status' => 'pending']),
        ]));
    }
    if ($example === 'long-answers') {
        $answers = $record->adfc_form;
        $answers['street_barangay'] = str_repeat('Long fictional street address ', 12);
        $answers['father_address'] = str_repeat('Another fictional address ', 16);
        $record->adfc_form = $answers;
        $record->statement = str_repeat('This fictional statement verifies that long text continues onto subsequent pages without clipping. ', 45);
        $record->form_data = ['custom_answer' => 'Saved answer from a previous scholarship template.'];
    }
    $html = view('applications.professional-pdf', ['application' => $record])->render();
    $pdf = app(App\Services\ApplicationPdfService::class)->render($record);
    $bytes = $pdf->output();
    file_put_contents($directory.'/'.$example.'.pdf', $bytes);
    file_put_contents($directory.'/'.$example.'.html', $html);
    $results[$example] = ['pages' => $pdf->getDomPDF()->getCanvas()->get_page_count(), 'bytes' => strlen($bytes)];
}
file_put_contents($directory.'/results.json', json_encode($results, JSON_PRETTY_PRINT));
echo json_encode($results, JSON_PRETTY_PRINT).PHP_EOL;
