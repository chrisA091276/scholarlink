<?php

namespace Tests\Feature;

use App\Models\ScholarshipAppeal;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScholarshipAppealTest extends TestCase
{
    use RefreshDatabase;

    private function award(string $status = 'active'): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $program = ScholarshipProgram::create(['code' => 'APPEAL-'.bin2hex(random_bytes(4)), 'name' => 'Student Support Scholarship',
            'category' => 'General', 'status' => 'published', 'slots' => 5, 'created_by' => $admin->id]);
        $application = ScholarshipApplication::create(['application_no' => 'APP-'.bin2hex(random_bytes(6)), 'user_id' => $student->id,
            'scholarship_program_id' => $program->id, 'academic_year' => '2026-2027', 'status' => 'approved']);
        $award = ScholarshipAward::create(['award_no' => 'AWD-'.bin2hex(random_bytes(6)), 'scholarship_application_id' => $application->id,
            'user_id' => $student->id, 'scholarship_program_id' => $program->id, 'recipient_status' => $status,
            'status_reason' => in_array($status, ['removed', 'suspended']) ? 'Repeated unexcused absences.' : null]);
        return [$admin, $student, $award];
    }

    private function submit(User $student, ScholarshipAward $award): ScholarshipAppeal
    {
        $this->actingAs($student)->post(route('my-appeals.store', $award), [
            'statement' => 'My absences were due to illness. I have recovered and can attend regularly.',
        ])->assertRedirect(route('my-awards'))->assertSessionHasNoErrors();
        return $award->appeals()->firstOrFail();
    }

    public function test_student_sees_removal_reason_and_can_be_reinstated_through_web_review(): void
    {
        [$admin, $student, $award] = $this->award();
        $this->actingAs($admin)->post(route('awards.status', $award), [
            'recipient_status' => 'removed', 'status_reason' => 'Repeated unexcused absences.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('active', $student->fresh()->status, 'Scholarship removal must not disable student login.');
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('You are no longer included')->assertSee('Repeated unexcused absences.');
        $this->get(route('my-awards'))->assertOk()->assertSee('Appeal for reinstatement');
        $this->get(route('my-appeals.create', $award))->assertOk()->assertSee('Why should your scholarship be reinstated?');
        $appeal = $this->submit($student, $award);
        $this->assertSame('removed', $award->fresh()->recipient_status);
        $this->get(route('my-awards'))->assertSee('Your appeal is submitted')->assertSee($appeal->statement);
        $this->actingAs($admin)->get(route('appeals.index'))->assertOk()->assertSee($appeal->appeal_no);
        $this->get(route('appeals.show', $appeal))->assertOk()->assertSee($appeal->statement)->assertSee('Approve and reinstate');
        $this->get(route('awards.show', $award))->assertOk()->assertSee('Reason for removal or suspension')->assertSee($appeal->appeal_no);
        $this->post(route('appeals.decide', $appeal), ['status' => 'under_review'])->assertSessionHasNoErrors();
        $this->assertSame('removed', $award->fresh()->recipient_status);
        $this->post(route('appeals.decide', $appeal), ['status' => 'approved', 'decision_reason' => 'The absence explanation was accepted.'])->assertSessionHasNoErrors();
        $this->assertSame('active', $award->fresh()->recipient_status);
        $this->assertNull($award->fresh()->status_reason);
        $this->assertDatabaseCount('scholarship_awards', 1);
        $this->assertDatabaseHas('scholarship_appeals', ['id' => $appeal->id, 'status' => 'approved', 'pending_slot' => null, 'decided_by' => $admin->id]);
        $this->actingAs($student)->get(route('my-awards'))->assertOk()->assertSee('You are currently included')->assertSee('The absence explanation was accepted.');
        $this->assertDatabaseHas('notifications', ['user_id' => $student->id, 'title' => 'Scholarship appeal approved']);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'title' => 'Scholarship appeal awaiting review']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appeal_decided', 'auditable_id' => $appeal->id]);
    }

    public function test_removal_and_suspension_require_a_reason_on_web_and_api(): void
    {
        [$admin, , $award] = $this->award();
        $this->actingAs($admin)->post(route('awards.status', $award), ['recipient_status' => 'removed', 'status_reason' => '   '])->assertSessionHasErrors('status_reason');
        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/awards/{$award->id}/status", ['recipient_status' => 'suspended'])->assertUnprocessable()->assertJsonValidationErrors('status_reason');
        $this->assertSame('active', $award->fresh()->recipient_status);
    }

    public function test_only_removed_or_suspended_awards_accept_appeals(): void
    {
        foreach (['active', 'completed', 'expired'] as $status) {
            [, $student, $award] = $this->award($status);
            $this->actingAs($student)->post(route('my-appeals.store', $award), ['statement' => str_repeat('An explanation. ', 3)])->assertSessionHasErrors('statement');
        }
        $this->assertDatabaseCount('scholarship_appeals', 0);
        [, $student, $award] = $this->award('suspended');
        $this->submit($student, $award);
        $this->assertDatabaseCount('scholarship_appeals', 1);
    }

    public function test_another_student_cannot_read_or_appeal_someone_elses_award(): void
    {
        [, $student, $award] = $this->award('removed');
        $other = User::factory()->create(['role' => 'student']);
        $appeal = $this->submit($student, $award);
        $this->actingAs($other)->get(route('my-appeals.create', $award))->assertForbidden();
        $this->post(route('my-appeals.store', $award), ['statement' => str_repeat('Not my award. ', 3)])->assertForbidden();
        $this->get(route('appeals.show', $appeal))->assertForbidden();
        $this->get(route('my-awards'))->assertDontSee($award->award_no);
        Sanctum::actingAs($other);
        $this->postJson("/api/v1/awards/{$award->id}/appeals", ['statement' => str_repeat('Not my award. ', 3)])->assertForbidden();
        $this->getJson('/api/v1/my-appeals')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_duplicate_pending_appeals_are_blocked_across_web_and_api(): void
    {
        [, $student, $award] = $this->award('removed');
        $this->submit($student, $award);
        $this->post(route('my-appeals.store', $award), ['statement' => str_repeat('Second explanation. ', 3)])->assertSessionHasErrors('statement');
        Sanctum::actingAs($student);
        $this->postJson("/api/v1/awards/{$award->id}/appeals", ['statement' => str_repeat('Second explanation. ', 3)])->assertUnprocessable()->assertJsonValidationErrors('statement');
        $this->getJson('/api/v1/my-awards')->assertOk()->assertJsonPath('data.0.can_appeal', false)->assertJsonPath('data.0.appeals.0.status', 'submitted');
        $this->assertDatabaseCount('scholarship_appeals', 1);
    }

    public function test_staff_and_students_cannot_change_membership_or_decide_appeals(): void
    {
        [, $student, $award] = $this->award('removed');
        $appeal = $this->submit($student, $award);
        foreach ([$student, User::factory()->create(['role' => 'staff'])] as $user) {
            $this->actingAs($user)->post(route('awards.status', $award), ['recipient_status' => 'active'])->assertForbidden();
            $this->post(route('appeals.decide', $appeal), ['status' => 'approved', 'decision_reason' => 'Not authorized.'])->assertForbidden();
            $this->get(route('appeals.index'))->assertForbidden();
            Sanctum::actingAs($user);
            $this->postJson("/api/v1/appeals/{$appeal->id}/decide", ['status' => 'approved', 'decision_reason' => 'Not authorized.'])->assertForbidden();
        }
        $this->assertSame('removed', $award->fresh()->recipient_status);
    }

    public function test_rejection_requires_explanation_keeps_removal_and_allows_a_new_appeal(): void
    {
        [$admin, $student, $award] = $this->award('removed');
        $appeal = $this->submit($student, $award);
        $this->actingAs($admin)->post(route('appeals.decide', $appeal), ['status' => 'rejected'])->assertSessionHasErrors('decision_reason');
        $this->post(route('appeals.decide', $appeal), ['status' => 'rejected', 'decision_reason' => 'The attendance concern remains unresolved.'])->assertSessionHasNoErrors();
        $this->assertSame('removed', $award->fresh()->recipient_status);
        $this->actingAs($student)->get(route('my-awards'))->assertSee('The attendance concern remains unresolved.')->assertSee('Appeal for reinstatement');
        $new = $this->submit($student, $award);
        $this->assertNotSame($appeal->id, $new->id);
        $this->assertDatabaseCount('scholarship_appeals', 2);
    }

    public function test_changing_award_status_closes_pending_appeal_and_blocks_stale_reinstatement(): void
    {
        [$admin, $student, $award] = $this->award('removed');
        $appeal = $this->submit($student, $award);
        $this->actingAs($admin)->post(route('awards.status', $award), ['recipient_status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('closed', $appeal->fresh()->status);
        $this->post(route('appeals.decide', $appeal), ['status' => 'approved', 'decision_reason' => 'Old appeal.'])->assertSessionHasErrors('status');
        $this->assertSame('completed', $award->fresh()->recipient_status);
    }

    public function test_final_appeal_cannot_be_decided_again_and_new_removal_can_be_appealed(): void
    {
        [$admin, $student, $award] = $this->award('removed');
        $appeal = $this->submit($student, $award);
        $this->actingAs($admin)->post(route('appeals.decide', $appeal), ['status' => 'approved', 'decision_reason' => 'Approved on review.'])->assertSessionHasNoErrors();
        $this->post(route('awards.status', $award), ['recipient_status' => 'removed', 'status_reason' => 'A new attendance incident.'])->assertSessionHasNoErrors();
        $this->post(route('appeals.decide', $appeal), ['status' => 'approved', 'decision_reason' => 'Replay old decision.'])->assertSessionHasErrors('status');
        $new = $this->submit($student, $award);
        $this->assertGreaterThan($appeal->award_status_version, $new->award_status_version);
        $this->assertSame('removed', $award->fresh()->recipient_status);
    }

    public function test_api_submission_review_and_reinstatement_use_the_same_workflow(): void
    {
        [$admin, $student, $award] = $this->award('suspended');
        Sanctum::actingAs($student);
        $this->postJson("/api/v1/awards/{$award->id}/appeals", ['statement' => 'short'])->assertUnprocessable();
        $response = $this->postJson("/api/v1/awards/{$award->id}/appeals", ['statement' => 'I have resolved the attendance issues and request reinstatement.', 'status' => 'approved', 'user_id' => $admin->id])->assertCreated()->assertJsonPath('data.status', 'submitted');
        $appealId = $response->json('data.id');
        $this->assertDatabaseHas('scholarship_appeals', ['id' => $appealId, 'user_id' => $student->id]);
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/appeals?status=submitted')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/appeals/{$appealId}/decide", ['status' => 'approved', 'decision_reason' => 'Attendance concern addressed.'])->assertOk()->assertJsonPath('data.award.recipient_status', 'active');
        Sanctum::actingAs($student);
        $this->getJson('/api/v1/my-awards')->assertOk()->assertJsonPath('data.0.recipient_status', 'active')->assertJsonPath('data.0.can_appeal', false);
    }

    public function test_reinstatement_capacity_failure_rolls_back_the_appeal_decision(): void
    {
        [$admin, $student, $award] = $this->award('removed');
        $appeal = $this->submit($student, $award);
        $award->program->update(['slots' => 0]);
        $this->actingAs($admin)->post(route('appeals.decide', $appeal), ['status' => 'approved', 'decision_reason' => 'Ready to return.'])->assertSessionHasErrors('recipient_status');
        $this->assertSame('removed', $award->fresh()->recipient_status);
        $this->assertSame('submitted', $appeal->fresh()->status);
        $this->assertNull($appeal->fresh()->decided_at);
    }

    public function test_effective_award_dates_are_validated_when_only_end_date_is_updated(): void
    {
        [$admin, , $award] = $this->award();
        $award->update(['start_date' => '2026-10-01']);
        $this->actingAs($admin)->post(route('awards.status', $award), ['recipient_status' => 'active', 'end_date' => '2026-09-01'])->assertSessionHasErrors('end_date');
    }
}
