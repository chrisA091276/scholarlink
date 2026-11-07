<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationRequest;
use App\Models\ApplicationStatusHistory;
use App\Models\AuditLog;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipAward;
use App\Models\ScholarshipProgram;
use App\Services\EligibilityService;
use App\Services\ApplicationSubmissionService;
use App\Services\NotifyService;
use App\Services\ApplicationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    use \App\Concerns\ActsOnAssignedApplications;
    // ── Student: list own ──
    public function myIndex(Request $request)
    {
        $applications = ScholarshipApplication::with('program')
            ->where('user_id', Auth::id())
            ->when($request->get('status') === 'draft', fn ($q) => $q->where('status', 'draft'))
            ->when($request->get('stage') === 'review', fn ($q) => $q->whereIn('status', ['submitted', 'under_review', 'for_evaluation', ...ScholarshipApplication::DECISION_STATUSES]))
            ->latest()->paginate(10)->withQueryString();
        return view('applications.my-index', compact('applications'));
    }

    public function create(Request $request)
    {
        $programId = $request->get('scholarship_id');
        $programs = ScholarshipProgram::where('status', 'published')->with('requirements')->orderBy('deadline')->get()->filter(fn ($program) => $program->isOpenForApplication());
        $selected = $programId ? $programs->firstWhere('id', (int) $programId) : null;
        return view('applications.create', ['programs' => $programs, 'selected' => $selected, 'application' => null]);
    }

    public function edit(ScholarshipApplication $application)
    {
        abort_unless($application->user_id === Auth::id(), 403);
        abort_unless(in_array($application->status, ['draft', 'incomplete_requirements'], true), 403);
        $application->load(['program.requirements']);
        $programs = ScholarshipProgram::where('status', 'published')->with('requirements')->orderBy('deadline')->get()->filter(fn ($program) => $program->isOpenForApplication());
        $programs = collect([$application->program]);
        return view('applications.create', ['programs' => $programs, 'selected' => $application->program, 'application' => $application]);
    }

    public function store(ApplicationRequest $request)
    {
        $application = app(\App\Services\ApplicationDraftService::class)->save($request->user(), $request->validated());
        if ($request->action === 'submit') {
            try {
                $application = app(ApplicationSubmissionService::class)->submit($application, $request->user(), $request->boolean('declaration'));
            } catch (\Illuminate\Validation\ValidationException $error) {
                return redirect()->route('my-applications.edit', $application)->withErrors($error->errors())->with('error', 'Draft saved. Complete the requirements before submitting.');
            }
        }
        return redirect()->route($application->status === 'submitted' ? 'my-applications.show' : 'my-applications.edit', $application)->with('success', $application->status === 'submitted' ? 'Application submitted! Reference: '.$application->application_no : 'Draft saved.');
    }

    public function update(ApplicationRequest $request, ScholarshipApplication $application)
    {
        $application = app(\App\Services\ApplicationDraftService::class)->save($request->user(), $request->validated(), $application);
        if ($request->action === 'submit') $application = app(ApplicationSubmissionService::class)->submit($application, $request->user(), $request->boolean('declaration'));
        if ($request->expectsJson()) {
            return response()->json(['message' => $application->status === 'submitted' ? 'Application submitted.' : 'Draft updated.',
                'data' => new \App\Http\Resources\ScholarshipApplicationResource($application)]);
        }
        return redirect()->route($application->status === 'submitted' ? 'my-applications.show' : 'my-applications.edit', $application)->with('success', $application->status === 'submitted' ? 'Application submitted! Reference: '.$application->application_no : 'Draft updated.');
    }

    public function myShow(ScholarshipApplication $application)
    {
        abort_unless($application->user_id === Auth::id(), 403);
        $application->load(['program.requirements', 'documents.requirement', 'eligibilityResults', 'evaluations', 'statusHistories.changer', 'award']);
        return view('applications.my-show', compact('application'));
    }

    public function downloadSummary(ScholarshipApplication $application)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $application);

        $application->load([
            'student.studentProfile',
            'program.requirements',
            'documents.requirement',
            'evaluations.evaluator',
        ]);

        return app(\App\Services\ApplicationPdfService::class)->render($application)
            ->download('adfc-scholarship-application-' . $application->application_no . '.pdf');
    }

    public function submitDraft(Request $request, ScholarshipApplication $application, ApplicationSubmissionService $submission)
    {
        $application = $submission->submit($application, $request->user(), $request->boolean('declaration'));
        return redirect()->route('my-applications.show', $application)->with('success', 'Application submitted. Reference: '.$application->application_no);
    }

    public function withdraw(ScholarshipApplication $application)
    {
        abort_unless($application->user_id === Auth::id(), 403);
        if (! in_array($application->status, ['draft', 'submitted', 'incomplete_requirements'], true)) {
            return back()->with('error', 'Only draft/submitted applications can be withdrawn.');
        }
        $this->changeStatus($application, 'withdrawn', Auth::user(), 'Withdrawn by student');
        return back()->with('success', 'Application withdrawn.');
    }

    // ── Staff/Admin: all applications ──
    public function index(Request $request)
    {
        $query = ScholarshipApplication::with(['student', 'program', 'assignee'])->latest();
        if (Auth::user()->isStaff()) $query->where('assigned_to', Auth::id());
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('application_no', 'like', "%{$s}%")
                ->orWhereHas('student', fn ($qq) => $qq->where('name', 'like', "%{$s}%")));
        }
        if ($st = $request->get('status')) $query->where('status', $st);
        if ($request->get('stage') === 'decision') $query->whereIn('status', ScholarshipApplication::DECISION_STATUSES);
        if ($request->get('stage') === 'active') $query->whereIn('status', ScholarshipApplication::ACTIVE_STATUSES);
        if ($request->get('stage') === 'review') $query->whereIn('status', ScholarshipApplication::REVIEW_STATUSES);
        if ($request->get('assistance') === 'yes') $query->where('adfc_form->other_assistance', 'yes')->whereNotIn('status',['draft','withdrawn']);
        if ($pid = $request->get('scholarship_id')) $query->where('scholarship_program_id', $pid);
        if ($request->get('mine') && Auth::user()->isStaff()) $query->where('assigned_to', Auth::id());
        if ($assignee = $request->get('assigned_to')) {
            $assignee === 'unassigned' ? $query->whereNull('assigned_to') : $query->where('assigned_to', $assignee);
        }
        $applications = $query->paginate(15)->withQueryString();
        $programs = ScholarshipProgram::orderBy('name')->get();
        $staff = \App\Models\User::where('role', 'staff')->where('status', 'active')->orderBy('name')->get();

        return view('applications.index', compact('applications', 'programs', 'staff'));
    }

    public function show(ScholarshipApplication $application, EligibilityService $eligibility)
    {
        $this->ensureCanActOn($application);
        $application->load(['student.studentProfile', 'assignee', 'program.requirements', 'program.eligibilityRules', 'documents.verifier', 'eligibilityResults.checker', 'evaluations.evaluator', 'statusHistories.changer']);
        return view('applications.show', compact('application'));
    }

    public function reviewForm(Request $request, ScholarshipApplication $application)
    {
        $this->ensureCanActOn($application);
        $data = $request->validate([
            'applicant' => ['required', 'boolean'], 'school' => ['required', 'boolean'],
            'family' => ['required', 'boolean'], 'assistance' => ['required', 'boolean'], 'consent' => ['required', 'boolean'],
            'qualification' => ['required', 'in:meets,needs_clarification,not_qualified'],
            'notes' => ['required', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($application, $data, $request) {
            $locked = ScholarshipApplication::lockForUpdate()->findOrFail($application->id);
            $this->ensureCanActOn($locked);
            abort_unless($locked->form_version === \App\Services\AdfcApplicationForm::VERSION, 422);
            abort_unless(in_array($locked->status, ['submitted', 'under_review', 'incomplete_requirements', 'for_evaluation']), 422, 'Office review is locked at this stage.');
            $review = $data + ['reviewer_id' => $request->user()->id, 'reviewer_name' => $request->user()->name, 'reviewed_at' => now()->toIso8601String()];
            $locked->update(['form_review' => $review]);
            AuditLog::record($request->user(), 'application_form_review', $locked, 'ADFC form sections reviewed; assessment: '.$data['qualification']);
        });
        return back()->with('success', 'ADFC office review saved.');
    }

    public function updateStatus(Request $request, ScholarshipApplication $application)
    {
        $needsReason = in_array($request->input('to_status'), ['rejected', 'incomplete_requirements', 'recommended_rejection'], true);
        $data = $request->validate([
            'to_status' => 'required|in:submitted,under_review,incomplete_requirements,for_evaluation,recommended_approval,recommended_rejection,approved,rejected,withdrawn,draft',
            'remarks' => [$needsReason ? 'required' : 'nullable', 'string', 'max:2000'],
        ], ['remarks.required' => 'A recorded reason is required for this decision.']);

        $user = Auth::user();

        // Only admins may record final decisions
        if (in_array($data['to_status'], ['approved', 'rejected'], true) && ! $user->isAdmin()) {
            return back()->with('error', 'Only administrators can record final decisions.');
        }

        $this->ensureCanActOn($application);

        if (! $application->canTransitionTo($data['to_status'])) {
            return back()->with('error', "Invalid transition from {$application->status} to {$data['to_status']}.");
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
                    AuditLog::record($user, 'award_create', $award, "Award {$award->award_no} created");
                }
            }

            $msg = match ($data['to_status']) {
                'under_review' => 'Your application is now under review.',
                'incomplete_requirements' => 'Action needed: ' . ($data['remarks'] ?? 'please complete missing requirements.'),
                'for_evaluation' => 'Your application moved to evaluation.',
                'recommended_approval' => 'Good news: your application was recommended for approval.',
                'recommended_rejection' => 'Update: your application was recommended for rejection. Awaiting final decision.',
                'approved' => 'Congratulations! Your scholarship was APPROVED. ' . ($data['remarks'] ?? ''),
                'rejected' => 'Update: your application was not approved. ' . ($data['remarks'] ?? ''),
                default => "Application status changed to {$data['to_status']}.",
            };
            NotifyService::send($application->user_id, 'Application status: ' . $data['to_status'], $msg, route('my-applications.show', $application));
        });

        AuditLog::record($user, 'application_status', $application, "Status → {$data['to_status']}");

        return back()->with('success', 'Status updated to ' . $data['to_status'] . '.');
    }

    public function assign(Request $request, ScholarshipApplication $application)
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        $data = $request->validate(['assigned_to' => 'nullable|exists:users,id']);
        if ($data['assigned_to']) {
            $staff = \App\Models\User::findOrFail($data['assigned_to']);
            abort_unless($staff->isStaff() || $staff->isAdmin(), 422, 'Only staff members can be assigned.');
        }
        $application->update(['assigned_to' => $data['assigned_to'] ?? null]);
        AuditLog::record(Auth::user(), 'application_assign', $application, 'Assigned to ' . ($application->assignee?->name ?? 'nobody'));
        if ($application->assigned_to) {
            NotifyService::send($application->assigned_to, 'Application assigned', "Application {$application->application_no} was assigned to you for review.", route('applications.show', $application));
        }
        return back()->with('success', 'Assignee updated.');
    }

    public function recommendations(Request $request)
    {
        $query = \App\Models\ApplicationEvaluation::with(['application.student', 'application.program', 'evaluator'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('application', fn ($q) => $q->where('assigned_to', auth()->id()));
        if ($request->get('mine')) $query->where('evaluator_id', Auth::id());
        if ($rec = $request->get('recommendation')) $query->where('recommendation', $rec);
        $evaluations = $query->paginate(15)->withQueryString();
        return view('evaluations.recommendations', compact('evaluations'));
    }

    public function evaluationsIndex(Request $request)
    {
        $query = \App\Models\ApplicationEvaluation::with(['application.student', 'application.program', 'evaluator'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('application', fn ($q) => $q->where('assigned_to', auth()->id()));
        if ($rec = $request->get('recommendation')) $query->where('recommendation', $rec);
        $evaluations = $query->paginate(15)->withQueryString();
        $pending = ScholarshipApplication::with(['student', 'program'])
            ->whereIn('status', ['recommended_approval', 'recommended_rejection'])->latest()->paginate(10, ['*'], 'pending_page');
        return view('evaluations.index', compact('evaluations', 'pending'));
    }

    public function storeEvaluation(Request $request, ScholarshipApplication $application)
    {
        $this->ensureCanActOn($application);
        $data = $request->validate([
            'score' => 'nullable|integer|min:0|max:100',
            'recommendation' => 'required|in:pending,recommended_approval,recommended_rejection',
            'evaluation_notes' => 'required|string|max:5000',
        ]);

        $evaluation = app(\App\Services\ApplicationEvaluationService::class)->record($application, $request->user(), $data);

        return back()->with('success', 'Evaluation saved.');
    }

    public static function changeStatusStatic(ScholarshipApplication $application, string $to, $user, ?string $remarks = null): void
    {
        (new static)->changeStatus($application, $to, $user, $remarks);
    }

    private function changeStatus(ScholarshipApplication $application, string $to, $user, ?string $remarks = null): void
    {
        app(\App\Services\ApplicationTransitionService::class)->transition($application, $to, $user, $remarks);
    }
}
