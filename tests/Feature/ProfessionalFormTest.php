<?php

namespace Tests\Feature;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Models\User;
use App\Services\AdfcApplicationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AdfcFormFixture;
use Tests\TestCase;

class ProfessionalFormTest extends TestCase
{
    use RefreshDatabase, AdfcFormFixture;

    private function application(array $overrides = []): ScholarshipApplication
    {
        return ScholarshipApplication::factory()->create(array_replace([
            'form_version' => AdfcApplicationForm::VERSION, 'adfc_form' => $this->adfcAnswers(),
        ], $overrides));
    }

    public function test_new_drafts_use_the_professional_form_even_without_a_client_version(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $program = ScholarshipProgram::factory()->create();
        $this->actingAs($user)->post('/my-applications', ['scholarship_program_id' => $program->id, 'action' => 'draft'])->assertRedirect();
        $application = ScholarshipApplication::firstOrFail();
        $this->assertSame(AdfcApplicationForm::VERSION, $application->form_version);
        $this->post(route('my-applications.submit',$application), ['declaration'=>true])
            ->assertSessionHasErrors(['adfc_form.place_of_birth','adfc_form.school_sector','documents']);
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_answers_are_saved_as_a_snapshot_and_unknown_office_fields_are_ignored(): void
    {
        $application = $this->application();
        $user = $application->student;
        $user->studentProfile()->create(['first_name'=>'Different', 'last_name'=>'Profile', 'date_of_birth'=>'2002-01-01']);
        $this->actingAs($user)->put(route('my-applications.update',$application), [
            'scholarship_program_id'=>$application->scholarship_program_id, 'action'=>'draft',
            'adfc_form'=>$this->adfcAnswers(['first_name'=>'Saved Applicant','reviewer_id'=>123,'other_assistance'=>'no','other_assistance_details'=>'Stale answer']),
            'form_review'=>$this->adfcReview(),
        ])->assertSessionHasNoErrors();
        $saved = $application->fresh();
        $this->assertSame('Saved Applicant', $saved->adfc_form['first_name']);
        $this->assertNull($saved->adfc_form['other_assistance_details']);
        $this->assertArrayNotHasKey('reviewer_id',$saved->adfc_form);
        $this->assertNull($saved->form_review);
        $this->assertSame('Different',$user->studentProfile->first_name);
        $this->get(route('my-applications.show',$saved))->assertOk()->assertSee('Saved Applicant');
        $this->assertStringContainsString('Saved Applicant', view('applications.professional-pdf',['application'=>$saved->load('student.studentProfile','program','documents')])->render());
        $this->get(route('my-applications.download',$saved))->assertOk()->assertHeader('Content-Type','application/pdf');
    }

    public function test_assistance_details_dates_and_office_fields_are_validated_on_submission(): void
    {
        $application = $this->application(['adfc_form'=>$this->adfcAnswers(['other_assistance'=>'yes','other_assistance_details'=>'','date_accomplished'=>today()->addDay()->toDateString()])]);
        $this->attachAdfcDocuments($application);
        $this->actingAs($application->student)->post(route('my-applications.submit',$application),['declaration'=>true])
            ->assertSessionHasErrors(['adfc_form.other_assistance_details','adfc_form.date_accomplished']);
        $this->assertSame('draft',$application->fresh()->status);
    }

    public function test_alternative_income_document_requires_the_unavailability_declaration(): void
    {
        $application = $this->application();
        $this->attachAdfcDocuments($application,'tax_exemption');
        $this->actingAs($application->student)->post(route('my-applications.submit',$application),['declaration'=>true])->assertSessionHasErrors('documents');
        $application->update(['adfc_form'=>$this->adfcAnswers(['indigency_unavailable'=>true])]);
        $this->post(route('my-applications.submit',$application),['declaration'=>true])->assertRedirect();
        $this->assertSame('submitted',$application->fresh()->status);
        $this->assertNotNull($application->fresh()->declaration_accepted_at);
    }

    public function test_complete_application_moves_through_assigned_review_and_admin_decision(): void
    {
        $staff = User::factory()->create(['role'=>'staff']);
        $admin = User::factory()->create(['role'=>'admin']);
        $application = $this->application(['assigned_to'=>$staff->id]);
        $this->attachAdfcDocuments($application);
        $this->actingAs($application->student)->post(route('my-applications.submit',$application),['declaration'=>true])->assertSessionHasNoErrors();
        $this->actingAs($staff)->post(route('applications.status',$application),['to_status'=>'under_review'])->assertSessionHasNoErrors();
        $this->post(route('applications.status',$application),['to_status'=>'for_evaluation'])->assertSessionHasErrors('to_status');
        foreach ($application->documents as $document) $this->post(route('documents.verify',[$application,$document]),['verification_status'=>'verified'])->assertRedirect();
        $this->post(route('applications.form-review',$application), $this->adfcReview()+['reviewer_name'=>'Spoofed actor'])->assertRedirect();
        $this->assertSame($staff->name,$application->fresh()->form_review['reviewer_name']);
        $this->post(route('applications.status',$application),['to_status'=>'for_evaluation'])->assertRedirect();
        $this->assertSame('for_evaluation',$application->fresh()->status);
        $this->post(route('applications.evaluate',$application),['recommendation'=>'recommended_approval','evaluation_notes'=>'The complete ADFC form and evidence support this recommendation.'])->assertRedirect();
        $this->assertSame('recommended_approval',$application->fresh()->status);
        $this->actingAs($admin)->post(route('applications.status',$application),['to_status'=>'approved','remarks'=>'Approved after office review.'])->assertRedirect();
        $this->assertSame('approved',$application->fresh()->status);
        $this->assertNotNull($application->fresh()->award);
    }

    public function test_students_and_unassigned_staff_cannot_record_office_review_or_download_others_forms(): void
    {
        $staff = User::factory()->create(['role'=>'staff']);
        $outsider = User::factory()->create(['role'=>'staff']);
        $application = $this->application(['assigned_to'=>$staff->id,'status'=>'under_review']);
        $this->actingAs($application->student)->post(route('applications.form-review',$application),$this->adfcReview())->assertForbidden();
        $this->actingAs($outsider)->post(route('applications.form-review',$application),$this->adfcReview())->assertForbidden();
        $this->get(route('applications.download',$application))->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertDontSee($application->application_no);
        $this->actingAs($staff)->get('/dashboard')->assertOk()->assertSee($application->application_no);
        $this->get(route('applications.download',$application))->assertOk();
    }

    public function test_replaced_documents_invalidate_review_and_photo_upload_rejects_pdf(): void
    {
        Storage::fake('local');
        $application = $this->application(['form_review'=>$this->adfcReview()]);
        $this->actingAs($application->student)->post(route('documents.store',$application),[
            'document_category'=>'id_photo','document_name'=>'ID photo', 'file'=>UploadedFile::fake()->create('photo.pdf',10,'application/pdf'),
        ])->assertSessionHasErrors('file');
        $this->post(route('documents.store',$application),[
            'document_category'=>'id_photo','document_name'=>'ID photo', 'file'=>UploadedFile::fake()->createWithContent('photo.jpg',file_get_contents(public_path('images/adfc-logo.jpg'))),
        ])->assertRedirect();
        $this->assertNull($application->fresh()->form_review);
        $this->assertSame('id_photo',$application->documents()->firstOrFail()->document_category);
    }

    public function test_admin_approval_cannot_bypass_missing_form_review_or_standard_documents(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $application = $this->application(['status'=>'recommended_approval']);
        $this->actingAs($admin)->post(route('applications.status',$application),['to_status'=>'approved'])->assertSessionHasErrors('documents');
        $this->assertSame('recommended_approval',$application->fresh()->status);
        $this->assertNull($application->fresh()->award);
    }
}
