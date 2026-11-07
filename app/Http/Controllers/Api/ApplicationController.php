<?php

namespace App\Http\Controllers\Api;

use App\Concerns\ActsOnAssignedApplications;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use App\Http\Resources\ApplicationEvaluationResource;
use App\Http\Resources\ScholarshipApplicationResource;
use App\Models\ApplicationStatusHistory;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\ScholarshipProgram;
use App\Services\NotifyService;
use App\Services\ApplicationSubmissionService;
use App\Services\ApplicationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    use ActsOnAssignedApplications;

    public function myIndex(Request $request)
    {
        $applications = $request->user()->applications()
            ->with('program')
            ->latest()
            ->paginate(15);

        return ScholarshipApplicationResource::collection($applications);
    }

    public function store(ApplicationRequest $request)
    {
        $application = app(\App\Services\ApplicationDraftService::class)->save($request->user(), $request->validated());
        if ($request->action === 'submit') {
            try {
                $application = app(ApplicationSubmissionService::class)->submit($application, $request->user(), $request->boolean('declaration'));
            } catch (\Illuminate\Validation\ValidationException $error) {
                return response()->json(['message' => 'Draft saved. Complete the requirements before submitting.', 'errors' => $error->errors(), 'data' => new ScholarshipApplicationResource($application->load('program'))], 422);
            }
        }
        return response()->json(['message' => $application->status === 'submitted' ? 'Application submitted.' : 'Draft saved.', 'data' => new ScholarshipApplicationResource($application->load('program'))], 201);
    }

    public function update(ApplicationRequest $request, ScholarshipApplication $application)
    {
        $application = app(\App\Services\ApplicationDraftService::class)->save($request->user(), $request->validated(), $application);
        if ($request->action === 'submit') $application = app(ApplicationSubmissionService::class)->submit($application, $request->user(), $request->boolean('declaration'));
        return response()->json(['message' => $application->status === 'submitted' ? 'Application submitted.' : 'Draft updated.', 'data' => new ScholarshipApplicationResource($application->load('program'))]);
    }

    public function myShow(Request $request, ScholarshipApplication $application)
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $application->load([
            'program.requirements',
            'documents.requirement',
            'eligibilityResults.checker',
            'evaluations.evaluator',
            'award',
        ]);

        return new ScholarshipApplicationResource($application);
    }

    public function submit(Request $request, ScholarshipApplication $application, ApplicationSubmissionService $submission)
    {
        $application = $submission->submit($application, $request->user(), $request->boolean('declaration'));
        return response()->json(['message' => 'Application submitted.', 'data' => new ScholarshipApplicationResource($application->load('program'))]);
    }

    public function history(Request $request, ScholarshipApplication $application)
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        return response()->json([
            'data' => $application->statusHistories()->with('changer:id,name,role')->get(),
        ]);
    }

    public function withdraw(Request $request, ScholarshipApplication $application)
    {
        $user = $request->user();
        abort_unless($application->user_id === $user->id, 403);

        if (! in_array($application->status, ['draft', 'submitted', 'incomplete_requirements'], true)) {
            return response()->json(['message' => 'Only draft/submitted applications can be withdrawn.'], 422);
        }

        $this->changeStatus($application, 'withdrawn', $user, 'Withdrawn by student via API');

        return response()->json([
            'message' => 'Application withdrawn.',
            'data' => new ScholarshipApplicationResource($application->fresh()->load('program')),
        ]);
    }

    public function index(Request $request)
    {
        $query = ScholarshipApplication::with(['student', 'program', 'assignee'])->latest();
        if ($request->user()->isStaff()) $query->where('assigned_to', $request->user()->id);

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('application_no', 'like', "%{$search}%")
                ->orWhereHas('student', fn ($qq) => $qq->where('name', 'like', "%{$search}%")));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($programId = $request->get('scholarship_id')) {
            $query->where('scholarship_program_id', $programId);
        }
        if ($request->boolean('mine') && $request->user()->isStaff()) {
            $query->where('assigned_to', $request->user()->id);
        }
        if ($assignee = $request->get('assigned_to')) {
            $assignee === 'unassigned'
                ? $query->whereNull('assigned_to')
                : $query->where('assigned_to', $assignee);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 50);

        return ScholarshipApplicationResource::collection($query->paginate($perPage));
    }

    public function show(ScholarshipApplication $application)
    {
        $this->ensureCanActOn($application);
        $application->load([
            'student.studentProfile',
            'assignee',
            'program.requirements',
            'program.eligibilityRules',
            'documents.verifier',
            'eligibilityResults.checker',
            'evaluations.evaluator',
            'statusHistories.changer',
        ]);

        return new ScholarshipApplicationResource($application);
    }

    public function updateStatus(Request $request, ScholarshipApplication $application)
    {
        $needsReason = in_array($request->input('to_status'), ['rejected', 'incomplete_requirements', 'recommended_rejection'], true);

        $data = $request->validate([
            'to_status' => ['required', 'in:submitted,under_review,incomplete_requirements,for_evaluation,recommended_approval,recommended_rejection,approved,rejected,withdrawn,draft'],
            'remarks' => [$needsReason ? 'required' : 'nullable', 'string', 'max:2000'],
        ], ['remarks.required' => 'A recorded reason is required for this decision.']);

        $user = $request->user();

        if (in_array($data['to_status'], ['approved', 'rejected'], true) && ! $user->isAdmin()) {
            return response()->json(['message' => 'Only administrators can record final decisions.'], 403);
        }

        $this->ensureCanActOn($application);

        if (! $application->canTransitionTo($data['to_status'])) {
            return response()->json([
                'message' => "Invalid transition from {$application->status} to {$data['to_status']}.",
            ], 422);
        }

        DB::transaction(function () use ($application, $data, $user) {
            $this->changeStatus($application, $data['to_status'], $user, $data['remarks'] ?? null);

            if (in_array($data['to_status'], ['approved', 'rejected'], true)) {
                $application->update([
                    'decided_at' => now(),
                    'decided_by' => $user->id,
                    'decision_remarks' => $data['remarks'] ?? null,
                ]);

                if ($data['to_status'] === 'approved' && ! $application->award) {
                    $award = ScholarshipAward::create([
                        'award_no' => 'AWD-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(8))),
                        'scholarship_application_id' => $application->id,
                        'user_id' => $application->user_id,
                        'scholarship_program_id' => $application->scholarship_program_id,
                        'benefits' => $application->program->benefits,
                        'start_date' => now()->toDateString(),
                        'recipient_status' => 'active',
                        'awarded_by' => $user->id,
                    ]);
                    AuditLog::record($user, 'award_create', $award, "Award {$award->award_no} created via API");
                }
            }

            $message = match ($data['to_status']) {
                'under_review' => 'Your application is now under review.',
                'incomplete_requirements' => 'Action needed: ' . ($data['remarks'] ?? 'please complete missing requirements.'),
                'for_evaluation' => 'Your application moved to evaluation.',
                'recommended_approval' => 'Good news: your application was recommended for approval.',
                'recommended_rejection' => 'Update: your application was recommended for rejection. Awaiting final decision.',
                'approved' => 'Congratulations! Your scholarship was APPROVED. ' . ($data['remarks'] ?? ''),
                'rejected' => 'Update: your application was not approved. ' . ($data['remarks'] ?? ''),
                default => "Application status changed to {$data['to_status']}.",
            };

            NotifyService::send(
                $application->user_id,
                'Application status: ' . $data['to_status'],
                $message,
                route('my-applications.show', $application)
            );
        });

        AuditLog::record($user, 'application_status', $application, "Status → {$data['to_status']} via API");

        return response()->json([
            'message' => 'Status updated to ' . $data['to_status'] . '.',
            'data' => new ScholarshipApplicationResource(
                $application->fresh()->load(['program', 'student', 'award'])
            ),
        ]);
    }

    public function assign(Request $request, ScholarshipApplication $application)
    {
        $data = $request->validate(['assigned_to' => ['nullable', 'exists:users,id']]);

        if (! empty($data['assigned_to'])) {
            $staff = \App\Models\User::findOrFail($data['assigned_to']);
            abort_unless($staff->isStaff() || $staff->isAdmin(), 422, 'Only staff members can be assigned.');
        }

        $application->update(['assigned_to' => $data['assigned_to'] ?? null]);
        AuditLog::record(
            $request->user(),
            'application_assign',
            $application,
            'Assigned to ' . ($application->assignee?->name ?? 'nobody') . ' via API'
        );

        if ($application->assigned_to) {
            NotifyService::send(
                $application->assigned_to,
                'Application assigned',
                "Application {$application->application_no} was assigned to you for review.",
                route('applications.show', $application)
            );
        }

        return response()->json([
            'message' => 'Assignee updated.',
            'data' => new ScholarshipApplicationResource($application->fresh()->load(['assignee', 'program', 'student'])),
        ]);
    }

    public function evaluate(Request $request, ScholarshipApplication $application)
    {
        $this->ensureCanActOn($application);

        $data = $request->validate([
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recommendation' => ['required', 'in:pending,recommended_approval,recommended_rejection'],
            'evaluation_notes' => ['required', 'string', 'max:5000'],
        ]);

        $evaluation = app(\App\Services\ApplicationEvaluationService::class)->record($application, $request->user(), $data);

        return response()->json([
            'message' => 'Evaluation saved.',
            'data' => new ApplicationEvaluationResource($evaluation->load('evaluator')),
        ], 201);
    }

    public function recommendation(Request $request, ScholarshipApplication $application)
    {
        return $this->evaluate($request, $application);
    }

    public function requestCorrection(Request $request, ScholarshipApplication $application)
    {
        $this->ensureCanActOn($application);
        $data = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        abort_unless($application->canTransitionTo('incomplete_requirements'), 422, 'Application cannot be returned for correction at this stage.');
        $this->changeStatus($application, 'incomplete_requirements', $request->user(), $data['remarks']);
        NotifyService::send($application->user_id, 'Correction requested', $data['remarks'], route('my-applications.show', $application));
        return response()->json(['message' => 'Correction request sent.', 'data' => new ScholarshipApplicationResource($application->fresh()->load('program'))]);
    }

    public function verifyDocuments(Request $request, ScholarshipApplication $application)
    {
        $this->ensureCanActOn($application);
        $data = $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.id' => ['required', 'integer'],
            'documents.*.verification_status' => ['required', 'in:pending,verified,rejected,requires_correction'],
            'documents.*.verification_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        app(\App\Services\DocumentReviewService::class)->review($application, $request->user(), $data['documents']);
        return response()->json(['message' => 'Document verification updated.', 'data' => \App\Http\Resources\ApplicationDocumentResource::collection($application->documents()->get())]);
    }

    public function recommendations(Request $request)
    {
        $query = \App\Models\ApplicationEvaluation::with(['application.student', 'application.program', 'evaluator'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('application', fn ($q) => $q->where('assigned_to', auth()->id()));

        if ($request->boolean('mine')) {
            $query->where('evaluator_id', $request->user()->id);
        }
        if ($recommendation = $request->get('recommendation')) {
            $query->where('recommendation', $recommendation);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 50);

        return ApplicationEvaluationResource::collection($query->paginate($perPage));
    }

    public function evaluations(Request $request)
    {
        $query = \App\Models\ApplicationEvaluation::with(['application.student', 'application.program', 'evaluator'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('application', fn ($q) => $q->where('assigned_to', auth()->id()));

        if ($recommendation = $request->get('recommendation')) {
            $query->where('recommendation', $recommendation);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 50);

        return ApplicationEvaluationResource::collection($query->paginate($perPage));
    }

    private function changeStatus(ScholarshipApplication $application, string $to, $user, ?string $remarks = null): void
    {
        app(\App\Services\ApplicationTransitionService::class)->transition($application, $to, $user, $remarks);
    }

    public static function changeStatusStatic(ScholarshipApplication $application, string $to, $user, ?string $remarks = null): void
    {
        app(\App\Services\ApplicationTransitionService::class)->transition($application, $to, $user, $remarks);
    }
}
