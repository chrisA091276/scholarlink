<?php

namespace Tests\Feature;

use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Services\AdfcApplicationForm;
use App\Services\ApplicationPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\Support\AdfcFormFixture;
use Tests\TestCase;

class ApplicationPdfTest extends TestCase
{
    use RefreshDatabase, AdfcFormFixture;

    private function application(array $attributes = []): ScholarshipApplication
    {
        return ScholarshipApplication::factory()->create($attributes + [
            'form_version' => AdfcApplicationForm::VERSION, 'adfc_form' => $this->adfcAnswers(),
            'statement' => null, 'form_data' => [], 'form_review' => null,
            'guardian_name' => null, 'guardian_occupation' => null, 'household_size' => null, 'monthly_household_income' => null,
        ])->load('student.studentProfile', 'program.requirements', 'documents');
    }

    public function test_standard_reference_form_fits_two_a4_pages_with_saved_answers(): void
    {
        $application = $this->application();
        $pdf = app(ApplicationPdfService::class)->render($application);
        $this->assertSame(2, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $this->assertEqualsWithDelta(595.28, $pdf->getDomPDF()->getCanvas()->get_width(), 0.1);
        $this->assertEqualsWithDelta(841.89, $pdf->getDomPDF()->getCanvas()->get_height(), 0.1);
        $this->assertStringStartsWith('%PDF', $pdf->output());
        $html = view('applications.professional-pdf', compact('application'))->render();
        $this->assertStringContainsString('15/02/2004', $html);
        $this->assertStringContainsString('SIGNATURE OVER PRINTED NAME OF APPLICANT', $html);
        $this->assertStringContainsString('I hereby certify', $html);
    }

    public function test_older_downloads_use_the_reference_layout_without_inventing_or_saving_answers(): void
    {
        $application = $this->application(['form_version' => null, 'adfc_form' => null]);
        $application->student->studentProfile()->create(['first_name' => 'Earlier', 'last_name' => 'Applicant', 'address' => 'Recorded unsplit address']);
        $application->load('student.studentProfile');
        $before = $application->fresh()->getRawOriginal();
        $answers = app(AdfcApplicationForm::class)->pdfAnswers($application);
        $this->assertSame('Earlier', $answers['first_name']);
        $this->assertArrayNotHasKey('father_status', $answers);
        $this->assertArrayNotHasKey('street_barangay', $answers);
        $rendered = false;
        View::composer('applications.professional-pdf', function () use (&$rendered) { $rendered = true; });
        $this->actingAs($application->student)->get(route('my-applications.download', $application))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertTrue($rendered);
        $this->assertSame($before, $application->fresh()->getRawOriginal());
        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get(route('my-applications.download', $application))->assertForbidden();
    }

    public function test_supplement_preserves_custom_answers_and_escapes_untrusted_content(): void
    {
        $application = $this->application(['form_data' => ['retired_question' => '<img src="https://example.test/private"> Saved old answer'], 'statement' => 'My scholarship statement.']);
        $html = view('applications.professional-pdf', compact('application'))->render();
        $this->assertStringContainsString('Retired question', $html);
        $this->assertStringContainsString('&lt;img src=', $html);
        $this->assertStringNotContainsString('<img src="https://example.test/private">', $html);
        $this->assertStringContainsString('My scholarship statement.', $html);
        $this->assertSame(3, app(ApplicationPdfService::class)->render($application)->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_office_checkboxes_distinguish_accepted_uploads_from_returned_documents(): void
    {
        $application = $this->application();
        $this->attachAdfcDocuments($application, 'indigency', 'verified');
        $application->documents()->where('document_category', 'indigency')->update(['verification_status' => 'requires_correction']);
        $application->load('documents');
        $html = view('applications.professional-pdf', compact('application'))->render();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $checks = (new \DOMXPath($document))->query('//div[@class="office-list"]/div/span[@class="check-box"]');
        $this->assertCount(3, $checks);
        $this->assertSame('✓', $checks->item(0)->textContent);
        $this->assertSame('', $checks->item(1)->textContent);
        $this->assertSame('', $checks->item(2)->textContent);
    }
}
