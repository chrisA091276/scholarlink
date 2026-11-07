<?php

namespace Tests\Feature;

use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Services\AdfcApplicationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdfcFormFixture;
use Tests\TestCase;

class FormDashboardTest extends TestCase
{
    use RefreshDatabase, AdfcFormFixture;

    private function form(string $status, array $attributes = []): ScholarshipApplication
    {
        return ScholarshipApplication::factory()->create($attributes + [
            'status' => $status, 'form_version' => AdfcApplicationForm::VERSION,
            'adfc_form' => $this->adfcAnswers(['other_assistance' => 'yes', 'other_assistance_details' => 'Local study grant']),
        ]);
    }

    public function test_admin_counts_and_linked_queues_include_corrections_and_exclude_closed_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['submitted', 'incomplete_requirements', 'recommended_approval', 'approved', 'draft'] as $status) {
            $this->attachAdfcDocuments($this->form($status));
        }

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertViewHas('stats', fn ($stats) =>
            $stats['unassigned'] === 2 && $stats['docs_pending'] === 6 && $stats['decisions'] === 1 && $stats['other_assistance'] === 3);
        $this->get(route('applications.index', ['assigned_to' => 'unassigned', 'stage' => 'review']))
            ->assertOk()->assertViewHas('applications', fn ($applications) => $applications->total() === 2);
        $this->get(route('verifications.index'))->assertOk()->assertViewHas('documents', fn ($documents) => $documents->total() === 6);
        $this->getJson('/api/v1/admin/dashboard')->assertOk()
            ->assertJsonPath('data.documents_pending', 6)->assertJsonPath('data.awaiting_assignment', 2)->assertJsonPath('data.awaiting_decision', 1);
    }

    public function test_staff_workload_and_document_checklists_only_cover_assigned_reviewable_forms(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        foreach (['submitted', 'incomplete_requirements', 'recommended_approval', 'draft', 'approved'] as $status) {
            $this->attachAdfcDocuments($this->form($status, ['assigned_to' => $staff->id]));
        }
        $outsider = $this->form('under_review');
        $this->attachAdfcDocuments($outsider);

        $this->actingAs($staff)->get('/dashboard')->assertOk()->assertDontSee($outsider->application_no)
            ->assertViewHas('stats', fn ($stats) => $stats['assigned'] === 2 && $stats['docs_pending'] === 6 && $stats['other_assistance'] === 2)
            ->assertSee('COR / COE')->assertSee('Certificate of Indigency')->assertSee('Fictional Example');
        $this->get(route('applications.index', ['stage' => 'review']))->assertOk()
            ->assertViewHas('applications', fn ($applications) => $applications->total() === 2);
        $this->get(route('verifications.index'))->assertOk()->assertViewHas('documents', fn ($documents) => $documents->total() === 6);
    }

    public function test_student_dashboard_prioritizes_corrections_and_links_to_the_affected_application(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $correction = $this->form('incomplete_requirements', ['user_id' => $student->id, 'updated_at' => now()->subDays(4)]);
        $this->attachAdfcDocuments($correction);
        $correction->documents()->where('document_category', 'enrollment')->update([
            'verification_status' => 'requires_correction', 'verification_notes' => 'Please provide the current semester COR.',
        ]);
        $draft = $this->form('draft', ['user_id' => $student->id]);
        $otherStudent = $this->form('incomplete_requirements');
        $this->attachAdfcDocuments($otherStudent, 'indigency', 'requires_correction');

        $this->actingAs($student)->get('/dashboard')->assertOk()
            ->assertViewHas('activeApplication', fn ($application) => $application->id === $correction->id)
            ->assertSee(route('my-applications.edit', $correction).'#step-5', false)
            ->assertSee('Please provide the current semester COR.')->assertSee('Needs replacement')
            ->assertDontSee($otherStudent->application_no);
        $this->get(route('my-applications.index', ['status' => 'draft']))->assertOk()
            ->assertViewHas('applications', fn ($applications) => $applications->total() === 1 && $applications->first()->id === $draft->id);
    }

    public function test_evidence_checklist_requires_declared_alternatives_and_configured_requirements(): void
    {
        $application = $this->form('draft');
        $this->attachAdfcDocuments($application, 'tax_exemption', 'verified');
        $application->program->requirements()->create(['name' => 'Official grades', 'is_required' => true, 'allowed_types' => 'pdf', 'max_size_kb' => 2048]);
        $service = app(AdfcApplicationForm::class);
        $checklist = $service->documentChecklist($application);
        $this->assertSame(['verified', 'verified', 'missing', 'missing'], array_column($checklist, 'status'));

        $application->update(['adfc_form' => $this->adfcAnswers(['indigency_unavailable' => true])]);
        $checklist = $service->documentChecklist($application->fresh());
        $this->assertSame(['verified', 'verified', 'verified', 'missing'], array_column($checklist, 'status'));
        $this->assertSame('Official grades', $checklist[3]['label']);
    }

    public function test_document_queue_preserves_the_selected_correction_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->form('incomplete_requirements');
        $this->attachAdfcDocuments($application);
        $application->documents()->where('document_category', 'enrollment')->update(['verification_status' => 'requires_correction']);
        $this->actingAs($admin)->get(route('verifications.index'))->assertOk()
            ->assertViewHas('documents', fn ($documents) => $documents->total() === 2);
        $this->get(route('verifications.index', ['status' => 'requires_correction']))->assertOk()
            ->assertViewHas('documents', fn ($documents) => $documents->total() === 1 && $documents->first()->document_category === 'enrollment');
    }
}
