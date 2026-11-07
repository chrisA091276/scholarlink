<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAwardStatusRequest;
use App\Models\RenewalApplication;
use App\Models\ScholarshipAward;
use App\Services\AwardStatusService;
use App\Services\ApplicationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AwardController extends Controller
{
    public function index(Request $request)
    {
        $query = ScholarshipAward::with(['student', 'program'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('application', fn ($q) => $q->where('assigned_to', auth()->id()));
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('award_no', 'like', "%{$s}%")
                ->orWhereHas('student', fn ($qq) => $qq->where('name', 'like', "%{$s}%")));
        }
        if ($st = $request->get('status')) $query->where('recipient_status', $st);
        $awards = $query->paginate(15)->withQueryString();

        return view('awards.index', compact('awards'));
    }

    public function show(ScholarshipAward $award)
    {
        if (auth()->user()->isStaff()) abort_unless((int) $award->application->assigned_to === auth()->id(), 403);
        $award->load(['student.studentProfile', 'program', 'application', 'renewals', 'appeals']);
        return view('awards.show', compact('award'));
    }

    public function updateStatus(UpdateAwardStatusRequest $request, ScholarshipAward $award, AwardStatusService $service)
    {
        $service->update($award, $request->user(), $request->validated());

        return back()->with('success', 'Award updated.');
    }

    // ── Student: own awards ──
    public function myAwards()
    {
        $awards = ScholarshipAward::with(['program', 'renewals', 'appeals'])
            ->where('user_id', Auth::id())->latest()->paginate(10);
        return view('awards.my-index', compact('awards'));
    }

    // ── Student: own renewals ──
    public function myRenewals()
    {
        $renewals = RenewalApplication::with(['award.program'])
            ->where('user_id', Auth::id())->latest()->paginate(10);
        return view('awards.my-renewals', compact('renewals'));
    }

    // ── Renewals ──
    public function createRenewal(ScholarshipAward $award, ApplicationTemplateService $templates)
    {
        abort_unless($award->user_id === Auth::id(), 403);
        abort_unless($award->recipient_status === 'active', 403, 'Only active awards can be renewed.');
        $award->load('program');
        $renewalFields = $templates->fields($award->program, 'renewal_template');
        $renewal = $award->renewals()->where('status', 'draft')->latest()->first();
        return view('awards.renew-create', compact('award', 'renewalFields', 'renewal'));
    }

    public function storeRenewal(Request $request, ScholarshipAward $award, ApplicationTemplateService $templates)
    {
        abort_unless($award->user_id === Auth::id(), 403);
        $award->loadMissing('program');
        $data = $request->validate([
            'academic_year' => 'required|string|max:20',
            'statement' => 'nullable|string|max:5000',
            'form_data' => 'nullable|array',
            'action' => 'required|in:draft,submit',
        ]);
        $renewal = app(\App\Services\RenewalService::class)->save($award, $request->user(), $data);

        return redirect()->route('my-awards')->with('success', 'Renewal application saved.');
    }

    public function renewals(Request $request)
    {
        $query = RenewalApplication::with(['award.program', 'student'])->latest();
        if (auth()->user()->isStaff()) $query->whereHas('award.application', fn ($q) => $q->where('assigned_to', auth()->id()));
        if ($st = $request->get('status')) $query->where('status', $st);
        $renewals = $query->paginate(15)->withQueryString();
        return view('awards.renewals', compact('renewals'));
    }

    public function decideRenewal(Request $request, RenewalApplication $renewal)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected,under_review',
            'remarks' => 'nullable|string|max:2000',
        ]);
        app(\App\Services\RenewalService::class)->decide($renewal, $request->user(), $data);

        return back()->with('success', 'Renewal decision recorded.');
    }
}
